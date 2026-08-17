<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../vendor/autoload.php';
require_once __DIR__.'/../../../../tools/dotEnvPropertyTask.php';

use Symfony\Component\Dotenv\Dotenv;

// Сбрасываем SYMFONY_DOTENV_VARS, которая могла быть унаследована от родительского Behat-процесса
// (его bootstrap уже вызывал bootEnv), чтобы каждая фикстура работала на «своём» наборе переменных.
unset($_SERVER['SYMFONY_DOTENV_VARS'], $_ENV['SYMFONY_DOTENV_VARS']);

/** @var Phing\Task\System\AutoloaderTask $this */
/** @phpstan-ignore class.notFound */
$env = $this->project->getProperty('app.env');
$_SERVER['APP_ENV'] = $env ?? 'dev';

/** @phpstan-ignore class.notFound */
$basedir = $this->project->getBasedir()->getAbsolutePath();
new Dotenv()->bootEnv($basedir.'/.env');

/** @phpstan-ignore nullCoalesce.offset */
$dotEnvVars = $_SERVER['SYMFONY_DOTENV_VARS'] ?? '';
/** @phpstan-ignore notIdentical.alwaysFalse */
$loaded = '' !== $dotEnvVars ? explode(',', $dotEnvVars) : [];
foreach (['APP_ENV', 'APP_DEBUG'] as $framework) {
    if (isset($_SERVER[$framework]) && !in_array($framework, $loaded, true)) {
        $loaded[] = $framework;
    }
}

$_SERVER['SYMFONY_DOTENV_VARS'] = implode(',', $loaded);
