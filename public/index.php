<?php

$autoloadGlobalPath = __DIR__ . '/../../../autoload.php';
$autoloadLocalPath = __DIR__ . '/../vendor/autoload.php';
file_exists($autoloadLocalPath) ? require_once $autoloadLocalPath : require_once $autoloadGlobalPath;

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../templates', [
        'cache' => 'cache', // Полностью отключаем кеш
        'auto_reload' => true, // Автоматически проверяем изменения
        'debug' => true // либо Включаем режим отладки
    ]);
});

$app = AppFactory::createFromContainer($container);

$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
$app->addErrorMiddleware(true, true, true);


$app->get('/', function ($request, $response) {

    $params = ['test' => 'test'];

    $twig = $this->get(Twig::class);

    return $twig->render($response, 'index.twig', $params);
});

$app->run();
