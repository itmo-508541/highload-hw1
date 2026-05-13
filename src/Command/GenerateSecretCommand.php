<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

#[AsCommand(
    name: 'app:generate-secrets',
    description: 'Render a Twig template to STDOUT using env vars from SYMFONY_DOTENV_VARS (values from $_SERVER).'
)]
final class GenerateSecretCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'template',
            InputArgument::REQUIRED,
            'Path to the Twig template (relative or absolute).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $templateRelative */
        $templateRelative = $input->getArgument('template');
        if (!is_readable($templateRelative)) {
            throw new \InvalidArgumentException(\sprintf('Twig template is not readable: "%s".', $templateRelative));
        }

        $env = [];
        foreach (array_filter(explode(',', (string) $_SERVER['SYMFONY_DOTENV_VARS'])) as $name) {
            if (!\array_key_exists($name, $_SERVER)) {
                throw new \RuntimeException(\sprintf(
                    'Variable "%s" is listed in SYMFONY_DOTENV_VARS but is missing in $_SERVER.',
                    $name
                ));
            }

            $env[$name] = (string) $_SERVER[$name];
        }

        $twigReal = realpath($templateRelative);
        if (false === $twigReal) {
            throw new \InvalidArgumentException(\sprintf('Twig template path could not be resolved: "%s".', $templateRelative));
        }

        $loader = new FilesystemLoader(\dirname($twigReal));
        $twig = new Environment($loader, ['strict_variables' => true]);

        $rendered = $twig->render(basename($twigReal), ['env' => $env]);
        $output->write($rendered, false, OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}
