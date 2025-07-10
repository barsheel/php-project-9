<?php

$autoloadGlobalPath =__DIR__ . '/../../../autoload.php';
$autoloadLocalPath =__DIR__ . '/../vendor/autoload.php';
file_exists($autoloadLocalPath) ? require_once $autoloadLocalPath : require_once $autoloadGlobalPath;

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->run();