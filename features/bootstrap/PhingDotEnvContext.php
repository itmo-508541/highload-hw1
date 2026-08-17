<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\Process\Process;

/**
 * Запускает Phing по фикстурам в fixtures/tools/<scenario>/ (относительно этого контекста)
 * и проверяет stdout/stderr.
 *
 * Каждая фикстура — отдельный подкаталог с build.xml; Phing вызывается без -f, то есть
 * читает build.xml из текущей рабочей директории.
 */
final class PhingDotEnvContext implements Context
{
    private ?string $lastOutput = null;

    private ?int $lastExitCode = null;

    #[When('я запускаю phing из каталога :dir')]
    public function iRunPhingFrom(string $dir): void
    {
        $this->runPhing($dir, []);
    }

    #[When('я запускаю phing из каталога :dir со свойством :name равным :value')]
    public function iRunPhingFromWithProperty(string $dir, string $name, string $value): void
    {
        $this->runPhing($dir, ['-D'.$name.'='.$value]);
    }

    #[Then('команда phing должна завершиться успешно')]
    public function thePhingCommandShouldSucceed(): void
    {
        if (0 !== $this->lastExitCode) {
            throw new \RuntimeException(
                \sprintf("Ожидался код выхода 0, получен %d.\n--- вывод ---\n%s", $this->lastExitCode ?? -1, $this->lastOutput ?? '')
            );
        }
    }

    #[Then('команда phing должна завершиться с ошибкой')]
    public function thePhingCommandShouldFail(): void
    {
        if (0 === $this->lastExitCode) {
            throw new \RuntimeException(
                \sprintf("Ожидался ненулевой код выхода, получен 0.\n--- вывод ---\n%s", $this->lastOutput ?? '')
            );
        }
    }

    #[Then('вывод должен содержать :fragment')]
    public function theOutputShouldContain(string $fragment): void
    {
        if (null === $this->lastOutput) {
            throw new \RuntimeException('Нет вывода Phing: сначала выполните шаг «Когда …».');
        }

        if (!str_contains($this->lastOutput, $fragment)) {
            throw new \RuntimeException(
                \sprintf("В выводе нет подстроки %s.\n--- вывод ---\n%s", $fragment, $this->lastOutput)
            );
        }
    }

    /**
     * @param list<string> $phingArgs аргументы после исполняемого файла phing (например `-Dapp.env=test`)
     */
    private function runPhing(string $relativeProjectDir, array $phingArgs): void
    {
        $cwd = __DIR__.'/'.trim($relativeProjectDir, '/');

        $process = new Process(array_merge(['phing'], $phingArgs), $cwd);
        $process->run();

        $this->lastExitCode = $process->getExitCode();
        $this->lastOutput = $process->getOutput();
        if ('' !== $process->getErrorOutput()) {
            $this->lastOutput .= "\n--- STDERR ---\n".$process->getErrorOutput();
        }
    }
}
