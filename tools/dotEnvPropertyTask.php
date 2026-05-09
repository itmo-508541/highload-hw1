<?php

declare(strict_types=1);

namespace tools;

use Phing\Exception\BuildException;
use Phing\Project;
use Phing\PropertyHelper;
use Phing\Task;

/**
 * Зеркалит переменные, загруженные Symfony\Component\Dotenv\Dotenv::bootEnv(), в свойства Phing.
 *
 * Источник имён — $_SERVER['SYMFONY_DOTENV_VARS'] (формируется bootEnv в скрипте <autoloader>);
 * значения берутся из $_SERVER, чтобы не зависеть от getenv()/putenv() и от $_ENV.
 */
final class dotEnvPropertyTask extends Task
{
    private ?string $prefix = null;

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
        if (!str_ends_with($prefix, '.')) {
            $this->prefix .= '.';
        }
    }

    public function main(): void
    {
        if (!isset($_SERVER['SYMFONY_DOTENV_VARS'])) {
            throw new BuildException(
                'SYMFONY_DOTENV_VARS не задана в $_SERVER: bootstrap-скрипт из <autoloader> должен вызывать Symfony\Component\Dotenv\Dotenv::bootEnv() до symfonyenv.'
            );
        }

        $helper = PropertyHelper::getPropertyHelper($this->getProject());
        foreach (array_filter(explode(',', $_SERVER['SYMFONY_DOTENV_VARS'])) as $name) {
            if (!\array_key_exists($name, $_SERVER)) {
                continue;
            }
            $propName = null !== $this->prefix ? $this->prefix.$name : $name;
            $helper->setNewProperty(null, $propName, (string) $_SERVER[$name]);
        }

        $this->log('Mirrored SYMFONY_DOTENV_VARS to project properties', Project::MSG_VERBOSE);
    }
}
