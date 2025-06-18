<?php

namespace App\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Slim\Psr7\Response;

class Controller
{
    protected $view;

    public function __construct()
    {
        $this->view = Twig::create(__DIR__ . '/../../app/Views', ['cache' => false]);
    }

    protected function view($template, $data = [])
    {
        $response = new Response();
        return $this->view->render($response, $template, $data);
    }

    protected function jsonResponse(ResponseInterface $response, $data, $status = 200)
    {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status);
    }
} 