<?php

namespace Marmelab\SonataElasticaBundle\Repository;

use Elastica\Query;
use Elastica\Query\Bool as QueryBool;
use Elastica\Query\Text as QueryText;
use Elastica\Query\AbstractQuery;
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
     *
     * @return $this
     */
    public function __construct(FinderInterface $finder, array $fieldsMapping)
    {
        $this->finder = $finder;
        $this->fieldsMapping = $fieldsMapping;
    }

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
     * @param int    $start
     * @param int    $limit
     * @param string $sortBy
     * @param string $sortOrder
     * @param array  $params
     *
     * @return int
     */
    public function getTotalResult($start, $limit, $sortBy, $sortOrder, $params)
    {
        $results = $this->findAll($start, $limit, $sortBy, $sortOrder, $params);

        return count($results);
    }

    /**
     * @param int    $start
     * @param int    $limit
     * @param string $sortBy
     * @param string $sortOrder
     * @param array  $params
     *
     * @return int
     */
    public function findAll($start, $limit, $sortBy, $sortOrder = 'ASC', $params)
    {
        $query = count($params) ? $this->createFilterQuery($params) : new Query();
        $query->setFrom($start);

        // Sort & order
        $fieldName = (isset($sortBy['fieldName'])) ? $sortBy['fieldName'] : null;

        if ($fieldName !== null && $fieldName !== $this->modelIdentifier) {

            if (isset($this->fieldsMapping[$fieldName])) {
                $fieldName = $this->fieldsMapping[$fieldName];
            }
            $query->setSort(array($fieldName => array('order' => strtolower($sortOrder))));
        } else {
            $query->setSort(array(
                $this->modelIdentifier => array('order' => strtolower($sortOrder)),
            ));
        }

        // Custom filter for admin
        if(method_exists($this->admin, 'getExtraFilter')) {
            $query->setFilter($this->admin->getExtraFilter());
        }

        // Limit
        if ($limit === null) {
            $limit = 10000; // set to 0 does not seem to work
        }

        return $this->finder->find($query, $limit);
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
        $wrapper = ($query instanceof AbstractQuery) ? array('query' => $query->toArray()) : $query->toArray();

        return json_encode($wrapper);
    }

    /**
     * @param array $params
     * 
     * @return Query
     */
    protected function createFilterQuery(array $params)
    {
        $mainQuery = new QueryBool();
        foreach ($params as $name => $value) {
            $fieldName = str_replace('_elastica', '', $name);
            if (strlen($value) < self::MINIMUM_SEARCH_TERM_LENGTH) {
                continue;
            }

            $fieldQuery = new QueryText();
            $fieldQuery->setFieldQuery($fieldName, $value);
            $mainQuery->addMust($fieldQuery);
        }

        return new Query($mainQuery);
    }
}
