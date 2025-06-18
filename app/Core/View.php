<?php

namespace App\Core;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;

class View
{
    private $twig;

    public function __construct()
    {
        $this->twig = Twig::create(__DIR__ . '/../Views', ['cache' => false]);
    }

    public function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        return $this->twig->render($response, $template, $data);
    }
} 