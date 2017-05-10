<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

class HomeController
{
    public function index(Request $request, Response $response)
    {
        $response->getBody()->write('Hello slim!');

        return $response;
    }
}

($app = new App())
    ->get('/', HomeController::class . ':index');

$app->run();
