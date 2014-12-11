<?php

namespace Marmelab\SonataElasticaBundle\Repository;

use Elastica\Query as Elastica_Query;
use FOS\ElasticaBundle\Finder\FinderInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

class ElasticaProxyRepository
{
    const MINIMUM_SEARCH_TERM_LENGTH = 2;

    /** @var  FinderInterface */
    protected $finder;

    /** @var  string */
    protected $modelIdentifier;

    /** @var  array */
    protected $fieldsMapping;

    /** @var AdminInterface */
    protected $admin;

    /**
     * @param FinderInterface $finder
     * param  array           $fieldsMapping
     *
     * @return $this
     */
    public function __construct(FinderInterface $finder, array $fieldsMapping)
    {
        $this->finder = $finder;
        $this->fieldsMapping = $fieldsMapping;
    }

    /**
     * @param AdminInterface $admin
     */
    public function setAdmin(AdminInterface $admin)
    {
        $this->admin = $admin;
    }

    /**
     * @param string $modelIdentifier
     *
     * @return $this
     */
    public function setModelIdentifier($modelIdentifier)
    {
        $this->modelIdentifier = $modelIdentifier;

        return $this;
    }

    /**
     *
     * @param type $options
     * @return type
     */
    public function getResultsAndTotalHits($options)
    {
        $query = $options['params'] ? $this->createFilterQuery($options['params']) : new Elastica_Query();

        // Custom filter for admin
        if (method_exists($this->admin, 'getExtraFilter')) {
            $query->setFilter($this->admin->getExtraFilter());
        }

        $query->setSort($this->getSort($options['sortBy'], $options['sortOrder']));

        $paginatorAdapter = $this->finder->createPaginatorAdapter($query);

        return array(
            'results' => $paginatorAdapter->getResults($options['start'], $options['limit']),
            'totalHits' => $paginatorAdapter->getTotalHits()
        );
    }

    /**
     * Useful for debugging with elastic head plugin
     *
     * @param \Elastica\Query $query
     *
     * @return string
     */
    protected function getQueryString($query)
    {
        $wrapper = ($query instanceof Elastica_Query) ? array('query' => $query->toArray()) : $query->toArray();

        return json_encode($wrapper);
    }

    /**
     * @param array $params
     *
     * @return Query
     */
    protected function createFilterQuery(array $params)
    {
        $mainQuery = new Elastica_Query\Bool();
        foreach ($params as $name => $value) {
            $fieldName = str_replace('_elastica', '', $name);
            if (strlen($value) < self::MINIMUM_SEARCH_TERM_LENGTH) {
                continue;
            }

            $fieldQuery = new Elastica_Query\Match();
            $fieldQuery->setFieldQuery($fieldName, $value);
            $mainQuery->addMust($fieldQuery);
        }

        return new Elastica_Query($mainQuery);
    }

    /**
     *
     * @param type $sortBy
     * @param type $sortOrder
     * @return type
     */
    protected function getSort($sortBy, $sortOrder = 'ASC')
    {
        $fieldName = isset($sortBy['fieldName']) ? $sortBy['fieldName'] : null;

        if ((null === $fieldName) || ($fieldName === $this->modelIdentifier)) {
            return array(
                $this->modelIdentifier => array('order' => strtolower($sortOrder))
            );
        }

        if (isset($this->fieldsMapping[$fieldName])) {
            $fieldName = $this->fieldsMapping[$fieldName];
        }

        return array(
            $fieldName => array('order' => strtolower($sortOrder))
        );
    }
}
