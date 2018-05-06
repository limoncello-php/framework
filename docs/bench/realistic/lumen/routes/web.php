<?php

use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

for($i = 0; $i < 10 + 10 * 5; ++$i) {
    $router->post("posts$i/create", HomeController::class . '@create');
}
