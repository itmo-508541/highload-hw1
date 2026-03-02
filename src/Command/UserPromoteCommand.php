<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Traits\EntityManagerTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:user:promote', description: 'Create new user')]
class UserPromoteCommand extends Command
{
    use EntityManagerTrait;
    use UserCommandTrait;

    protected function configure()
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'The username')
            ->addArgument('role', InputArgument::OPTIONAL, 'Role');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactWith($input, $output, ['username', 'role']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->em->getRepository(User::class);

        $username = $input->getArgument('username');
        $user = $repository->findOneBy([
            'username' => $username,
        ]);
        /* @var User $user */
        $role = $input->getArgument('role');

        $roles = $user->getRoles();
        if (!\in_array($role, $roles, true)) {
            $roles[] = $role;
            $user->setRoles($roles);

            $this->em->persist($user);
            $this->em->flush();

            $output->writeln(sprintf('Role "%s" has been added to user "%s". This change will not apply until the user logs out and back in again.', $role, $username));
        } else {
            $output->writeln(sprintf('User "%s" did already have "%s" role.', $username, $role));
        }

        return 0;
    }
}
