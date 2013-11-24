<?php

namespace Marmelab\SonataElasticaBundle\Builder;

use Marmelab\SonataElasticaBundle\Datagrid\ElasticaPager;
use Marmelab\SonataElasticaBundle\Repository\ElasticaProxyRepository;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Builder\DatagridBuilder as BaseDatagridBuilder;
use Symfony\Component\Form\FormFactory;

class ElasticaDatagridBuilder extends BaseDatagridBuilder
{
    /** @var ElasticaProxyRepository */
    protected $repository;

    /**
     * @param FormFactory             $formFactory
     * @param FilterFactoryInterface  $filterFactory
     * @param TypeGuesserInterface    $guesser
     * @param ElasticaProxyRepository $repository
     */
    public function __construct(FormFactory $formFactory, FilterFactoryInterface $filterFactory, TypeGuesserInterface $guesser, ElasticaProxyRepository $repository)
    {
        parent::__construct($formFactory, $filterFactory, $guesser);

        $this->repository = $repository;
    }

    /**
     * @param AdminInterface $admin
     * @param array          $values
     *
     * @return \Sonata\AdminBundle\Datagrid\DatagridInterface
     */
    public function getBaseDatagrid(AdminInterface $admin, array $values = array())
    {
        $modelClass = $admin->getClass();
        $this->repository->setModelIdentifier(current($admin->getModelManager()->getIdentifierFieldNames($modelClass)));

        $pager = new ElasticaPager();
        $pager->setCountColumn($admin->getModelManager()->getIdentifierFieldNames($modelClass));

        $formBuilder = $this->formFactory->createNamedBuilder('filter', 'form', array(), array('csrf_protection' => false));

        return new Datagrid($admin->createQuery(), $admin->getList(), $pager, $formBuilder, $values);
    }
}
