<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

class HomeController
{
    public function index(Request $request, Response $response)
    {
        $response->getBody()->write('Hi! My name is Slim!');

        return $response;
    }
}

$config = [
    'settings' => [
        'routerCacheFile' => __DIR__ . '/../routes.cache.php',
    ],
];

$app = new App($config);

$app->get('/', HomeController::class . ':index');

$app->run();
