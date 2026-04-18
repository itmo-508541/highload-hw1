<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = true;

new Dotenv('test')->loadEnv(__DIR__.'/../.env', 'test', 'test');
