<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\User;
use App\Traits\UserPasswordHasherTrait;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

abstract class UserProviderAdmin extends AbstractAdmin
{
    use UserPasswordHasherTrait;

    protected array $securityRoleHierarchy = [];

    public function setSecurityRoleHierarchy(array $securityRoleHierarchy): void
    {
        $this->securityRoleHierarchy = $securityRoleHierarchy;
    }

    protected function preUpdate($object): void
    {
        /** @var User $object */
        $plainPassword = $object->getPlainPassword();
        if (!empty($plainPassword)) {
            $password = $this->userPasswordHasher->hashPassword($object, $plainPassword);

            $object->setPassword($password);
        }
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
    }

    protected function getRoleHierarchyChoices(): array
    {
        $choices = [];
        foreach ($this->securityRoleHierarchy as $key => $value) {
            $choices[$key] = $key;
            foreach ($value as $name) {
                $choices[$name] = $name;
            }
        }

        asort($choices);

        return $choices;
    }
}
