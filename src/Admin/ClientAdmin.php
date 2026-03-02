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

    protected function configureListFields(ListMapper $list): void
    {
        $list
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

    protected function configureShowFields(ShowMapper $show): void
    {
        $object = $show->getAdmin()->getSubject();
        /* @var Client $object */
        $show
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

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('username');
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('create')
            ->remove('edit');
    }
}
