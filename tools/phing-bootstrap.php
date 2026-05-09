<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Phing создаёт Project, прокидывает -D в его свойства и только потом начинает читать build.xml

/** @var Phing\Task\System\AutoloaderTask $this */
$env = $this->project->getProperty('app.env');
/** @var string|null $env */
if (null !== $env) {
    $_SERVER['APP_ENV'] = $env;
}
if (!isset($_SERVER['APP_ENV'])) {
    throw new LogicException('APP_ENV is missing.');
}

$debug = $this->project->getProperty('app.debug');
/** @var string|null $debug */
if (null !== $debug) {
    $_SERVER['APP_DEBUG'] = filter_var($debug, \FILTER_VALIDATE_BOOLEAN);
}
if (!isset($_SERVER['APP_DEBUG'])) {
    $_SERVER['APP_DEBUG'] = 'prod' !== $_SERVER['APP_ENV'];
}

// overrideExistingVars: true — то же правило, что composer.json → extra.runtime.dotenv_overload:
// значения из .env перезаписывают переменные, уже заданные в окружении контейнера. Так Runtime
// ведёт себя при запуске через bin/console и public/index.php; здесь Dotenv вызываем явно.
new Dotenv()->bootEnv(dirname(__DIR__).'/.env', overrideExistingVars: true);

// bootEnv не добавляет в SYMFONY_DOTENV_VARS ключи, которые уже были в $_ENV/$_SERVER до её вызова
// (см. Dotenv::populate). Поэтому APP_ENV и APP_DEBUG, выставленные выше, могут не попасть в список;
// без этого symfonyenv не создаст в Phing свойства ${dotenv.APP_ENV} и ${dotenv.APP_DEBUG}.
$loaded = isset($_SERVER['SYMFONY_DOTENV_VARS']) && '' !== $_SERVER['SYMFONY_DOTENV_VARS']
    ? explode(',', $_SERVER['SYMFONY_DOTENV_VARS'])
    : [];
foreach (['APP_ENV', 'APP_DEBUG'] as $framework) {
    if (isset($_SERVER[$framework]) && !in_array($framework, $loaded, true)) {
        $loaded[] = $framework;
    }
}
$_SERVER['SYMFONY_DOTENV_VARS'] = implode(',', $loaded);
