<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Traits\EntityManagerTrait;
use App\Traits\UserPasswordHasherTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserCreateCommand extends Command
{
    use EntityManagerTrait;
    use UserCommandTrait;
    use UserPasswordHasherTrait;

    protected static $defaultName = 'app:user:create';

    protected function configure()
    {
        $this->setDescription('Create new user')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactWith($input, $output, ['username', 'password']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = new User();

        $username = $input->getArgument('username');
        $password = $this->userPassworsHasher->hashPassword($user, $input->getArgument('password'));

        $user->setUsername($username);
        $user->setPassword($password);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('Created user <comment>%s</comment>', $username));

        return 0;
    }
}
