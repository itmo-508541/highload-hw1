<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

(new Dotenv($_SERVER['APP_ENV'] ?? 'prod'))->loadEnv(__DIR__.'/../.env');

return static fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
