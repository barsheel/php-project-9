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

const DATABASE_ENV_NAME = 'DATABASE_URL';
const DATABASE_PORT = 5432;

session_start();

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../templates', [
        'cache' => false, // Полностью отключаем кеш
        'auto_reload' => true, // Автоматически проверяем изменения
        'debug' => true // либо Включаем режим отладки
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
    $port = DATABASE_PORT;
    $dbname = ltrim($databaseUrl['path'], '/');

    $conn = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

$app = AppFactory::createFromContainer($container);



$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
$app->addErrorMiddleware(true, true, true);


$app->get('/', function ($request, $response) {
    $twig = $this->get(Twig::class);
    return $twig->render($response, 'index_template.twig');
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

    $twig = $this->get(Twig::class);
    return $twig->render($response, 'urls_template.twig', $params);
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

    $twig = $this->get(Twig::class);
    return $twig->render($response, 'url_template.twig', $params);
})->setName('url');


$app->post('/urls', function ($request, $response) {
    $twig = $this->get(Twig::class);
    $body = $request->getParsedBody();
    $urlName = $body['url']['name'];

    $urlRepository = $this->get(UrlRepository::class);
    $flash = $this->get('flash');

    $validator = new Valitron\Validator(['urlName' => $urlName]);
    $validator->rule('url', 'urlName');
    $validator->rule('required', 'urlName');
    $validator->rule('lengthMax', '255');
    if ($validator->validate() === false) {
        $errors = ['url_name' => "Некорректный URL"];
        $params = ['errors' => $errors];
        return $twig->render($response, 'index_template.twig', $params);
    }

    $parsedUrl = parse_url($urlName);
    $baseUrl = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];

    $existedUrl = $urlRepository->findByName($baseUrl);
    if ($existedUrl !== null) {
        $existedId = $existedUrl->getId();
        $flash->addMessage('success', "Страница уже существует");
        return $response->withRedirect("/urls/{$existedId}");
    }

    $newId = $urlRepository->save($baseUrl);
    if ($newId === false) {
        $flash->addMessage('error', "Что то пошло не так");
        return $response->withRedirect('/');
    }

    $flash->addMessage('success', "Страница успешно добавлена");
    return $response->withRedirect("/urls/{$newId}");
})->setName('add_url');


$app->post('/urls/{id}/checks', function ($request, $response, $args) {
    $flash = $this->get('flash');
    $id = $args['id'];
    $urlCheckRepository = $this->get(UrlCheckRepository::class);

    $statusCode = 0;
    $h1 = "test_h1";
    $title = "test_title";
    $description = "test_description";

    if ($urlCheckRepository->save($id, $statusCode, $h1, $title, $description)) {
        $flash->addMessage('success', "Страница успешно проверена");
        return $response->withRedirect("/urls/{$id}");
    }
    $params = [];
    $twig = $this->get(Twig::class);
    return $twig->render($response, 'urls_template.twig', $params);
})->setName('url_check');


$app->run();
