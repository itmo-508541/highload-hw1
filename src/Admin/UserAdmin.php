<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\User;
use App\Traits\UserPasswordHasherTrait;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserAdmin extends AbstractAdmin
{
    use UserPasswordHasherTrait;

    private array $securityRoleHierarchy = [];

    public function setSecurityRoleHierarchy(array $securityRoleHierarchy): void
    {
        $this->securityRoleHierarchy = $securityRoleHierarchy;
    }

    protected function preUpdate($object): void
    {
        /** @var User $object */
        $plainPassword = $object->getPlainPassword();
        if (!empty($plainPassword)) {
            $password = $this->userPassworsHasher->hashPassword($object, $plainPassword);

            $object->setPassword($password);
        }
    }

    #[\Override]
    protected function configureExportFields(): array
    {
        $exportFields = parent::configureExportFields();

        return array_filter(
            $exportFields,
            static fn (string $v): bool => !\in_array($v, ['password', 'salt'], true)
        );
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->addIdentifier('username')
            ->add('rolesAsString')
            ->add(
                '_action',
                'actions',
                [
                    'actions' => [
                        'edit' => [],
                    ],
                ]
            );
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('username')
            ->add('rolesAsString');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('username');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $choices = [];
        foreach ($this->securityRoleHierarchy as $key => $value) {
            $choices[$key] = $key;
            foreach ($value as $name) {
                $choices[$name] = $name;
            }
        }

        asort($choices);

        $form
            ->add('username')
            ->add('plainPassword', TextType::class, [
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $choices,
                'multiple' => true,
            ]);
    }
}
