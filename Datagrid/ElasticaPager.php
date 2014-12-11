<?php

namespace Marmelab\SonataElasticaBundle\Datagrid;

use Doctrine\ORM\Query;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;

class ElasticaPager extends BasePager
{
    /**
     * {@inheritdoc}
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        $resultsAndTotalHits = $this->getQuery()->execute(array(), $hydrationMode);

        $this->updatePagesSettings($resultsAndTotalHits['totalHits']);

        return $resultsAndTotalHits['results']->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->resetIterator();

        $query = $this->getQuery();
        $query->setMaxResults($this->getMaxPerPage());

        if ($parameters = $this->getParameters()) {
            $query->setParameters($parameters);
        }

        if ($this->getPage() && $this->getMaxPerPage()) {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
            $query->setFirstResult($offset);
        }

        $this->setLastPage(0);
    }

    /**
     *
     * @param type $totalHits
     * @return type
     */
    protected function updatePagesSettings($totalHits)
    {
        if (!$totalHits) {
            return;
        }
        
        $this->setNbResults($totalHits);
        $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));
    }
}
