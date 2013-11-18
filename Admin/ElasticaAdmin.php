<?php

namespace Marmelab\SonataElasticaAdapterBundle\Admin;

use FOS\ElasticaBundle\Finder\FinderInterface;
use Sonata\AdminBundle\Admin\Admin;

class ElasticaAdmin extends Admin
{
    /** @var FinderInterface */
    private $finder;

    /**
     * @param string          $code
     * @param string          $class
     * @param string          $baseControllerName
     * @param FinderInterface $finder
     */
    public function __construct($code, $class, $baseControllerName, FinderInterface $finder)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->finder = $finder;
    }

    /**
     * @return FinderInterface
     */
    public function getFinder()
    {
        return $this->finder;
    }
}
