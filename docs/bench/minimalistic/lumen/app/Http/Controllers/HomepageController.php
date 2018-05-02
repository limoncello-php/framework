<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;

class HomepageController extends Controller
{
    /**
     * @return string
     */
    public function index()
    {
        return 'Hi! My name is Lumen!';
    }
}
