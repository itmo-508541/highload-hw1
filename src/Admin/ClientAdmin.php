<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Client;
use App\Traits\JWTManagerTrait;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;

class ClientAdmin extends AbstractAdmin
{
    use JWTManagerTrait;

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->addIdentifier('username')
            ->add('firstname')
            ->add('lastname')
            ->add(
                '_action',
                'actions',
                [
                    'actions' => [
                        'show' => [],
                    ],
                ]
            );
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $object = $showMapper->getAdmin()->getSubject();
        /* @var Client $object */
        $showMapper
            ->add('id')
            ->add('username')
            ->add('firstname')
            ->add('lastname')
            ->add('createdAt')
            ->add('confirmationToken')
            ->add('confirmedAt')
            ->add('token', null, [
                'template' => 'CRUD/client_show_token.html.twig',
                'value' => $this->jwtManager->create($object),
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filterMapper): void
    {
        $filterMapper
            ->add('username');
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('create')
            ->remove('edit');
    }
}
