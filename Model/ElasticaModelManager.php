<?php

namespace Marmelab\SonataElasticaBundle\Model;

use Marmelab\SonataElasticaBundle\Datagrid\ElasticaProxyQuery;
use Marmelab\SonataElasticaBundle\Repository\ElasticaProxyRepository;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;

class ElasticaModelManager implements ModelManagerInterface
{
    /** @var ModelManagerInterface */
    protected $baseModelManager;

    /** @var ElasticaProxyRepository */
    protected $repository;

    /**
     * @param ModelManagerInterface   $baseModelManager
     * @param ElasticaProxyRepository $repository
     */
    public function __construct(ModelManagerInterface $baseModelManager, ElasticaProxyRepository $repository)
    {
        $this->baseModelManager = $baseModelManager;
        $this->repository = $repository;
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array(($this->baseModelManager), $name), $args);
    }

    /**
     * Returns a new FieldDescription
     *
     * @param string $class
     * @param string $name
     * @param array  $options
     *
     * @return FieldDescriptionInterface
     */
    public function getNewFieldDescriptionInstance($class, $name, array $options = array())
    {
        return $this->baseModelManager->getNewFieldDescriptionInstance($class, $name, $options);
    }

    /**
     * @param mixed $object
     *
     * @return void
     */
    public function create($object)
    {
        return $this->baseModelManager->create($object);
    }

    /**
     * @param mixed $object
     *
     * @return void
     */
    public function update($object)
    {
        return $this->baseModelManager->update($object);
    }

    /**
     * @param object $object
     *
     * @return void
     */
    public function delete($object)
    {
        return $this->baseModelManager->delete($object);
    }

    /**
     * @param string $class
     * @param array  $criteria
     *
     * @return array all objects matching the criteria
     */
    public function findBy($class, array $criteria = array())
    {
        return $this->baseModelManager->findBy($class, $criteria);
    }

    /**
     * @param string $class
     * @param array  $criteria
     *
     * @return object an object matching the criteria or null if none match
     */
    public function findOneBy($class, array $criteria = array())
    {
        return $this->baseModelManager->findOneBy($class, $criteria);
    }

    /**
     * @param string $class
     * @param mixed  $id
     *
     * @return object the object with id or null if not found
     */
    public function find($class, $id)
    {
        return $this->baseModelManager->find($class, $id);
    }

    /**
     * @param string              $class
     * @param ProxyQueryInterface $queryProxy
     *
     * @return void
     */
    public function batchDelete($class, ProxyQueryInterface $queryProxy)
    {
        return $this->baseModelManager->batchDelete($class, $queryProxy);
    }

    /**
     * @param array  $parentAssociationMapping
     * @param string $class
     *
     * @return void
     */
    public function getParentFieldDescription($parentAssociationMapping, $class)
    {
        return $this->baseModelManager->getParentFieldDescription($parentAssociationMapping, $class);
    }

    /**
     * @param string $class
     * @param string $alias
     *
     * @return ProxyQueryInterface
     */
    public function createQuery($class, $alias = 'o', $formData = array())
    {
        return new ElasticaProxyQuery($this->repository, $this->baseModelManager->createQuery($class, $alias, $formData));
    }

    /**
     * Get the identifier for the model type of this class.
     *
     * @param string $class fully qualified class name
     *
     * @return string
     */
    public function getModelIdentifier($class)
    {
        return $this->baseModelManager->getModelIdentifier($class);
    }

    /**
     * Get the identifiers of this model class.
     *
     * This returns an array to handle cases like a primary key that is
     * composed of multiple columns. If you need a string representation,
     * use getNormalizedIdentifier resp. getUrlsafeIdentifier
     *
     * @param object $model
     *
     * @return array list of all identifiers of this model
     */
    public function getIdentifierValues($model)
    {
        return $this->baseModelManager->getIdentifierValues($model);
    }

    /**
     * Get a list of the field names models of the specified class use to store
     * the identifier.
     *
     * @param string $class fully qualified class name
     *
     * @return array
     */
    public function getIdentifierFieldNames($class)
    {
        return $this->baseModelManager->getIdentifierFieldNames($class);
    }

    /**
     * Get the identifiers for this model class as a string.
     *
     * @param object $model
     *
     * @return string a string representation of the identifiers for this
     *      instance
     */
    public function getNormalizedIdentifier($model)
    {
        $identifier = $this->baseModelManager->getNormalizedIdentifier($model);

        if ($identifier === null) {
            $identifierName = current($this->getModelIdentifier(get_class($model)));

            return $model->{'get'.ucfirst($identifierName)}();
        }
    }

    /**
     * Get the identifiers as a string that is save to use in an url.
     *
     * This is similar to getNormalizedIdentifier but guarantees an id that can
     * be used in an URL.
     *
     * @param object $model
     *
     * @return string string representation of the id that is save to use in an url
     */
    public function getUrlsafeIdentifier($model)
    {
        return $model->getId();
    }

    /**
     * Create a new instance of the model of the specified class.
     *
     * @param string $class
     *
     * @return mixed
     */
    public function getModelInstance($class)
    {
        return $this->baseModelManager->getModelInstance($class);
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getModelCollectionInstance($class)
    {
        return $this->baseModelManager->getModelCollectionInstance($class);
    }

    /**
     * Removes an element from the collection
     *
     * @param mixed $collection
     * @param mixed $element
     *
     * @return void
     */
    public function collectionRemoveElement(&$collection, &$element)
    {
        return $this->baseModelManager->collectionRemoveElement($collection, $element);
    }

    /**
     * Add an element from the collection
     *
     * @param mixed $collection
     * @param mixed $element
     *
     * @return mixed
     */
    public function collectionAddElement(&$collection, &$element)
    {
        return $this->baseModelManager->collectionAddElement($collection, $element);
    }

    /**
     * Check if the element exists in the collection
     *
     * @param mixed $collection
     * @param mixed $element
     *
     * @return boolean
     */
    public function collectionHasElement(&$collection, &$element)
    {
        return $this->baseModelManager->collectionHasElement($collection, $element);
    }

    /**
     * Clear the collection
     *
     * @param mixed $collection
     *
     * @return mixed
     */
    public function collectionClear(&$collection)
    {
        return $this->baseModelManager->collectionClear($collection);
    }

    /**
     * Returns the parameters used in the columns header
     *
     * @param FieldDescriptionInterface $fieldDescription
     * @param DatagridInterface         $datagrid
     *
     * @return array
     */
    public function getSortParameters(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid)
    {
        return $this->baseModelManager->getSortParameters($fieldDescription, $datagrid);
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getDefaultSortValues($class)
    {
        return $this->baseModelManager->getDefaultSortValues($class);
    }

    /**
     * @param string $class
     * @param array  $array
     */
    public function modelReverseTransform($class, array $array = array())
    {
        return $this->baseModelManager->modelReverseTransform($class, $array);
    }

    /**
     * @param string $class
     * @param object $instance
     *
     * @return void
     */
    public function modelTransform($class, $instance)
    {
        return $this->baseModelManager->modelTransform($class, $instance);
    }

    /**
     * @param mixed $query
     */
    public function executeQuery($query)
    {
        return $this->baseModelManager->executeQuery($query);
    }

    /**
     * @param DatagridInterface $datagrid
     * @param array             $fields
     * @param null              $firstResult
     * @param null              $maxResult
     *
     * @return \Exporter\Source\SourceIteratorInterface
     */
    public function getDataSourceIterator(DatagridInterface $datagrid, array $fields, $firstResult = null, $maxResult = null)
    {
        return $this->baseModelManager->getDataSourceIterator($datagrid, $fields, $firstResult, $maxResult);
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getExportFields($class)
    {
        return $this->baseModelManager->getExportFields($class);
    }

    /**
     * @param DatagridInterface $datagrid
     * @param int               $page
     *
     * @return mixed
     */
    public function getPaginationParameters(DatagridInterface $datagrid, $page)
    {
        return $this->baseModelManager->getPaginationParameters($datagrid, $page);
    }

    /**
     * @param string              $class
     * @param ProxyQueryInterface $query
     * @param array               $idx
     */
    public function addIdentifiersToQuery($class, ProxyQueryInterface $query, array $idx)
    {
        return $this->baseModelManager->addIdentifiersToQuery($class, $query, $idx);
    }
}
