<?php

$autoloadGlobalPath = __DIR__ . '/../../../autoload.php';
$autoloadLocalPath = __DIR__ . '/../vendor/autoload.php';
file_exists($autoloadLocalPath) ? require_once $autoloadLocalPath : require_once $autoloadGlobalPath;

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Hexlet\Code\UrlRepository;
use Hexlet\Code\UrlCheckRepository;
use GuzzleHttp\Client;
use DiDom\Document;

const DATABASE_ENV_NAME = 'DATABASE_URL';
const DATABASE_PORT = 5432;

session_start();

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../templates', [
        'auto_reload' => true
    ]);
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$container->set(\PDO::class, function () {
    $databaseUrl = parse_url(getenv(DATABASE_ENV_NAME));
    $user = $databaseUrl['user'];
    $pass = $databaseUrl['pass'];
    $host = $databaseUrl['host'];
    $port = $databaseUrl['port'] ?? 5432;
    $dbname = ltrim($databaseUrl['path'], '/');

    $conn = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

$app = AppFactory::createFromContainer($container);

$router = $app->getRouteCollector()->getRouteParser();

$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
$app->addErrorMiddleware(true, true, true);


$app->get('/', function ($request, $response) {
    return $this->get(Twig::class)->render($response, 'index_template.twig');
})->setName('index');


$app->get('/urls', function ($request, $response) {
    $urlRepository = $this->get(UrlRepository::class);
    $checkRepository = $this->get(UrlCheckRepository::class);
    $urls = $urlRepository->readAll();

    $urlsDTO = [];
    foreach ($urls as $url) {
        $id = $url->getId();
        $checks = $checkRepository->findByUrlId($id);
        $lastCheck = $checks->first();
        $lastCheckDate = $lastCheck ? $lastCheck->getCreatedAt() : '-';
        $lastStatusCode = $lastCheck ? $lastCheck->getStatusCode() : '-';

        $urlsDTO[] = [
            'id' => $id,
            'name' => $url->getName(),
            'lastCheckDate' => $lastCheckDate,
            'lastStatusCode' => $lastStatusCode,
        ];
    }

    $params = [
        'urls' => $urlsDTO,
        'alerts' => $this->get("flash")->getMessages()
    ];

    return $this->get(Twig::class)->render($response, 'urls_template.twig', $params);
})->setName('urls');


$app->get('/urls/{id}', function ($request, $response, $args) {
    $urlRepository = $this->get(UrlRepository::class);
    $checkRepository = $this->get(UrlCheckRepository::class);
    $id = $args['id'];
    $url = $urlRepository->findById($id);

    $urlDTO = [
        'id' => $url->getId(),
        'name' => $url->getName(),
        'createdAt' => $url->getCreatedAt()
    ];

    $checks = $checkRepository->findByUrlId($id);
    $checksDTO = [];
    foreach ($checks as $check) {
        $checksDTO[] = [
            'id' => $check->getId(),
            'statusCode' => $check->getStatusCode(),
            'h1' => $check->getH1(),
            'title' => $check->getTitle(),
            'description' => $check->getDescription(),
            'createdAt' => $check->getCreatedAt()
        ];
    }

    $params = [
        'url' => $urlDTO,
        'checks' => $checksDTO,
        'alerts' => $this->get("flash")->getMessages()
    ];

    return $this->get(Twig::class)->render($response, 'url_template.twig', $params);
})->setName('url');


$app->post('/urls', function ($request, $response) use ($router) {
    $body = $request->getParsedBody();
    $urlName = $body['url']['name'];

    $urlRepository = $this->get(UrlRepository::class);

    $validator = new Valitron\Validator(['urlName' => $urlName]);
    $validator->rule('url', 'urlName');
    $validator->rule('required', 'urlName');
    $validator->rule('lengthMax', 'urlName', 255);
    if ($validator->validate() === false) {
        $errors = ['url_name' => "Некорректный URL"];
        $params = ['errors' => $errors];
        return $this->get(Twig::class)->render($response, 'index_template.twig', $params);
    }

    $parsedUrl = parse_url($urlName);
    $baseUrl = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];

    $existedUrl = $urlRepository->findByName($baseUrl);
    if ($existedUrl !== null) {
        $existedId = $existedUrl->getId();
        $this->get('flash')->addMessage('success', "Страница уже существует");
        return $response->withRedirect($router->urlFor ("url", ["id" => $existedId]));
    }

    $newId = $urlRepository->save($baseUrl);
    if ($newId === false) {
        $this->get('flash')->addMessage('error', "Что то пошло не так");
        return $response->withRedirect($router->urlFor ("index"));
    }

    $this->get('flash')->addMessage('success', "Страница успешно добавлена");
    return $response->withRedirect($router->urlFor ("url", ["id" => $newId]));
})->setName('add_url');


$app->post('/urls/{id}/checks', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);

    try {
        $url = $urlRepository->findById($id)->getName();
        $client = new GuzzleHttp\Client();
        $res = $client->request('GET', $url);
        $statusCode = $res->getStatusCode();

        $document = new DiDom\Document($url, true);
        $h1 = $document->first('h1');
        $h1Text = $h1 ? $h1->text() : "-";
        $title = $document->first('title');
        $titleText = $title ? $title->text() : "-";
        $meta = $document->first('meta[name=description]');
        $description = $meta ? $meta->getAttribute('content') : '-';

        if ($urlCheckRepository->save($id, $statusCode, $h1Text, $titleText, $description)) {

            $statusClass = (int)($statusCode / 100);
            if ($statusClass === 1) {
                $this->get('flash')->addMessage('success', "Проверка была выполнена успешно, получено информационное сообщение");
            } elseif ($statusClass === 2) {
                $this->get('flash')->addMessage('success', "Страница успешно проверена");
            } elseif ($statusClass === 3) {
                $this->get('flash')->addMessage('success', "Проверка была выполнена успешно, но сервер ответил с перенаправлением");
            } elseif ($statusClass === 4 || $statusClass === 5) {
                $this->get('flash')->addMessage('success', "Проверка была выполнена успешно, но сервер ответил с ошибкой");
            }
        }
    } catch (\Throwable $exception) {
        $this->get('flash')->addMessage('error', "Произошла ошибка при проверке, не удалось подключиться");
    }
    
    return $response->withRedirect($router->urlFor ("url", ["id" => $id]));
})->setName('url_check');


$app->run();
