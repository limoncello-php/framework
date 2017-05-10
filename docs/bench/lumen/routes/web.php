<?php

use App\Http\Controllers\HomeController;

$app->get('/', HomeController::class . '@index');
