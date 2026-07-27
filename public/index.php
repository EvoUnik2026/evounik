<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

// Load environment variables
if (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env');
}

// Enable debug mode in dev environment
if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
    Debug::enable();
}

// Create and handle the request
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? false));
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);