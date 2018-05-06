<?php

require __DIR__ . '/../vendor/autoload.php';

use Respect\Validation\Validator as v;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

class HomeController
{
    public function index(Request $request, Response $response)
    {
        $form = $request->getParsedBody();

        $inputDateFormat    = 'Y-m-d';
        $databaseDateFormat = 'Y-m-d H:i:s';

        v::keySet(
            v::key('title', v::stringType()->length(1, 255)),
            v::key('text', v::stringType()),
            v::key('created-at', v::date($inputDateFormat))
        )->assert($form);

        ['title' => $title, 'text' => $text, 'created-at' => $createdAt] = $form;

        $title     = filter_var($title, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $text      = filter_var($text, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $createdAt = DateTime::createFromFormat($inputDateFormat, $createdAt)->format($databaseDateFormat);

        $response->getBody()->write("values($title,$text,$createdAt)");

        return $response;
    }
}

$config = [
// I really had hard time trying to make it work with POSTs. GETs - no probs but POSTs just didn't wanted to work with cache.
//    'settings' => [
//        'routerCacheFile' => __DIR__ . '/../routes.cache.php',
//    ],
];

$app = new App($config);

for ($i = 0; $i < 10 + 10 * 5; ++$i) {
    $app->post("/posts$i/create", HomeController::class . ':index');
}

$app->run();
