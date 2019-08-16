<?php

namespace Marmelab\SonataElasticaBundle\Datagrid;

use Marmelab\SonataElasticaBundle\Repository\ElasticaProxyRepository;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

class ElasticaProxyQuery implements ProxyQueryInterface
{
    /** @var ElasticaProxyRepository */
    protected $repository;

    /** @var ProxyQueryInterface */
    protected $baseProxyQuery;

    /** @var string */
    protected $sortBy;

    /** @var string */
    protected $sortOrder;

    /** @var int */
    protected $firstResult;

    /** @var int */
    protected $limit;

    /** @var array */
    protected $params = array();

    /**
     * @param ElasticaProxyRepository $repository
     * @param ProxyQueryInterface     $baseProxyQuery
     */
    public function __construct(ElasticaProxyRepository $repository, ProxyQueryInterface $baseProxyQuery)
    {
        $this->repository = $repository;
        $this->baseProxyQuery = $baseProxyQuery;
    }

    /**
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array(($this->baseProxyQuery), $name), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params = array(), $hydrationMode = null)
    {
        return $this->repository->getResultsAndTotalHits(array(
            'params' => array_merge($this->params, $params),
            'sortBy' => $this->getSortBy(),
            'sortOrder' => $this->getSortOrder(),
            'start' => $this->getFirstResult(),
            'limit' => $this->getMaxResults(),
        ));
    }

    /**
     * @param $name
     * @param $value
     */
    public function setParameter($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortBy($parentAssociationMappings, $fieldName)
    {
        $this->sortBy = $fieldName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleScalarResult()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxResults($maxResults)
    {
        $this->limit = $maxResults;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxResults()
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function getUniqueParameterId()
    {
        return 'elastica';
    }

    /**
     * {@inheritdoc}
     */
    public function entityJoin(array $associationMappings)
    {
        return null;
    }
}
