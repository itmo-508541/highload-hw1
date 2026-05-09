<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = true;

// overrideExistingVars: true — то же правило, что composer.json → extra.runtime.dotenv_overload:
// значения из .env перезаписывают переменные, уже заданные в окружении контейнера. Так Runtime
// ведёт себя при запуске через bin/console и public/index.php; в тестовом bootstrap Dotenv вызываем явно.
new Dotenv()->bootEnv(dirname(__DIR__).'/.env', overrideExistingVars: true);
