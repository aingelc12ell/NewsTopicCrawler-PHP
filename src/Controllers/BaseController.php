<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

abstract class BaseController
{
    protected Twig $view;

    public function __construct()
    {
        // Twig will be injected by middleware
    }

    protected function render(Response $response, string $template, array $data = []): Response
    {
        $view = Twig::fromRequest(request: $this->getRequest($response));
        return $view->render($response, $template, $data);
    }

    private function getRequest(Response $response): Request
    {
        // This is a workaround - in real implementation, inject request
        global $_SERVER;
        return new \Slim\Psr7\Request(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            []
        );
    }
}