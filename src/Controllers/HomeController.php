<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    public function index(Request $request, Response $response): Response
    {
        // Redirect to articles list
        return $response->withHeader('Location', '/articles')->withStatus(302);
    }
}