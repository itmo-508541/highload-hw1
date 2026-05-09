<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../tools/dotEnvPropertyTask.php';

// Намеренно НЕ вызываем Symfony\Dotenv::bootEnv: тест проверяет, что symfonyenv падает
// с понятной ошибкой, когда переменная SYMFONY_DOTENV_VARS отсутствует в $_SERVER.
unset($_SERVER['SYMFONY_DOTENV_VARS'], $_ENV['SYMFONY_DOTENV_VARS']);
