<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return new Response('<h1>Welcome to Symfony 7.4 with MariaDB!</h1><p>Your application is running successfully.</p>');
    }
}