<?php

namespace Marmelab\SonataElasticaAdapterBundle\Datagrid;

use Doctrine\ORM\Query;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;

class ElasticaPager extends BasePager
{
    /**
     * {@inheritdoc}
     */
    public function computeNbResult()
    {
        return $this->getQuery()->getTotalResults();
    }

    /**
     * {@inheritdoc}
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        return $this->getQuery()->execute(array(), $hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->resetIterator();
        $this->setNbResults($this->computeNbResult());

        $this->getQuery()->setFirstResult(null);
        $this->getQuery()->setMaxResults(null);

        if (count($this->getParameters()) > 0) {
            $this->getQuery()->setParameters($this->getParameters());
        }

        if (0 == $this->getPage() || 0 == $this->getMaxPerPage() || 0 == $this->getNbResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($this->getMaxPerPage());
        }
    }
}
