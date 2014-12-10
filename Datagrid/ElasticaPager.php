<?php

namespace Marmelab\SonataElasticaBundle\Datagrid;

use Doctrine\ORM\Query;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;

class ElasticaPager extends BasePager
{
    /**
     *
     * @return int
     */
    public function computeNbResult()
    {
        return $this->getQuery()->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        return $this->getQuery()->execute(array(), $hydrationMode)->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->resetIterator();

        $query = $this->getQuery();
        $query->setMaxResults($this->getMaxPerPage());
        $this->setNbResults($this->computeNbResult());

        if ($parameters = $this->getParameters()) {
            $query->setParameters($parameters);
        }

        if (0 === $this->getPage() || 0 === $this->getMaxPerPage() || 0 === $this->getNbResults()) {
            $this->setLastPage(0);
            return;
        }

        $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
        $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));
        $query->setFirstResult($offset);
    }
}
