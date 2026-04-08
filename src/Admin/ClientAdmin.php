<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Client;
use App\Traits\JWTManagerTrait;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ClientAdmin extends UserProviderAdmin
{
    use JWTManagerTrait;

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('username')
            ->add('clientAccount')
            ->add('rolesAsString')
            ->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
                'actions' => [
                    'edit' => [],
                    'show' => [],
                ],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('username')
            ->add('clientAccount');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('username')
            ->add('clientAccount')
            ->add('apiKey', null, [
                'help' => 'help.form.api_key',
            ])
            ->add('plainPassword', TextType::class, [
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $this->getRoleHierarchyChoices(),
                'multiple' => true,
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        /** @var Client $object */
        $object = $show->getAdmin()->getSubject();
        $show
            ->add('username')
            ->add('apiKey')
            ->add('rolesAsString')
            ->add('temporaryAccessToken', null, [
                'template' => 'CRUD/client_show_token.html.twig',
                'value' => $this->jwtManager->create($object),
            ])
            ->add('permanentAccessToken', null, [
                'template' => 'CRUD/client_show_token.html.twig',
                'value' => $this->jwtManager->createFromPayload($object, [
                    'exp' => time() + (20 * 365 * 24 * 60 * 60),
                ]),
            ]);
    }
}
