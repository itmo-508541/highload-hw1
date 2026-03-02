<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ConfigAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('value', null, [
                'editable' => true,
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('id');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('id', TextType::class)
            ->add('value', TextareaType::class);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('create')
            ->remove('edit')
            ->remove('delete');
    }
}
