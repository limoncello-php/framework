<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Routing\Controller;

class HomeController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response|string
     *
     * @throws ValidationException
     */
    public function create(Request $request)
    {
        $inputDateFormat    = 'Y-m-d';
        $databaseDateFormat = 'Y-m-d H:i:s';

        ['title' => $title, 'text' => $text, 'created-at' => $createdAt] = $this->validate($request, [
            'title'      => 'required|string|max:255',
            'text'       => 'required|string',
            'created-at' => "required|string|date_format:$inputDateFormat",
        ]);

        $title     = filter_var($title, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $text      = filter_var($text, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $createdAt = DateTime::createFromFormat($inputDateFormat, $createdAt)->format($databaseDateFormat);

        $response = "values($title,$text,$createdAt)";

        return $response;
    }
}
