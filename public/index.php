<?php

$autoloadGlobalPath = __DIR__ . '/../../../autoload.php';
$autoloadLocalPath = __DIR__ . '/../vendor/autoload.php';
file_exists($autoloadLocalPath) ? require_once $autoloadLocalPath : require_once $autoloadGlobalPath;

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Hexlet\Code\UrlRepository;
use Hexlet\Code\CheckRepository;

const DATABASE_ENV_NAME = 'DATABASE_URL';
const DATABASE_PORT = 5432;

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../templates', [
        'cache' => false, // Полностью отключаем кеш
        'auto_reload' => true, // Автоматически проверяем изменения
        'debug' => true // либо Включаем режим отладки
    ]);
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
    $checkRepository = $this->get(CheckRepository::class);
    $urls = $urlRepository->readAll();

    $urlsDTO = [];

    foreach ($urls as $url) {
        $id = $url->getId();
        $checks = $checkRepository->findChecksByUrlId($id);

        $lastCheck = $checks->first();

        $lastCheckDate = $lastCheck ? $lastCheck->getCreatedAt() : '-';
        $lastResponseCode = $lastCheck ? $lastCheck->getResponseCode() : '-';

        $urlsDTO[] = [
            'id' => $id,
            'name' => $url->getName(),
            'lastCheckDate' => $lastCheckDate,
            'lastResponseCode' => $lastResponseCode,
        ];
    }

    $params = [
        'urls' => $urlsDTO,
        'alerts' => []
    ];

    $twig = $this->get(Twig::class);
    return $twig->render($response, 'urls_template.twig', $params);
})->setName('urls');


$app->get('/urls/{id}', function ($request, $response, $args) {
    $urlRepository = $this->get(UrlRepository::class);
    $checkRepository = $this->get(CheckRepository::class);
    $id = $args['id'];
    $url = $urlRepository->findById($id);

    $urlDTO = [
        'id' => $url->getId(),
        'name' => $url->getName(),
        'createdAt' => $url->getCreatedAt()
    ];

    $checks = $checkRepository->findChecksByUrlId($id);
    $checksDTO = [];
    foreach ($checks as $check) {
        $checksDTO[] = [
            'id' => $check->getId(),
            'responseCode' => $check->getResponseCode(),
            'h1' => $check->getH1(),
            'title' => $check->getTitle(),
            'description' => $check->getDescription(),
            'createdAt' => $check->getCreatedAt()
        ];
    }

    $params = [
        'url' => $urlDTO,
        'checks' => $checksDTO,
        'alerts' => []
    ];

    $twig = $this->get(Twig::class);
    return $twig->render($response, 'url_template.twig', $params);
})->setName('url');





$app->run();
