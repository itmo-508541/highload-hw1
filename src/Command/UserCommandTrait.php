<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

trait UserCommandTrait
{
    protected EntityUserProvider $userProvider;

    public function setEntityUserProvider(EntityUserProvider $userProvider): void
    {
        $this->userProvider = $userProvider;
    }

    protected function interactWith(InputInterface $input, OutputInterface $output, array $arguments): void
    {
        foreach ($arguments as $argument) {
            if (!$input->getArgument($argument)) {
                $question = new Question(sprintf('Please choose a %s:', $argument));
                $question->setValidator(static function ($value) use ($argument) {
                    if (empty($value)) {
                        throw new \Exception(sprintf('%s can not be empty', ucfirst($argument)));
                    }

                    return $value;
                });

                if (\in_array($argument, ['password'], true)) {
                    $question->setHidden(true);
                }

                $answer = $this->getHelper('question')->ask($input, $output, $question);
                $input->setArgument($argument, $answer);
            }
        }
    }
}
