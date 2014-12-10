<?php

namespace Marmelab\SonataElasticaBundle\Transformer;

use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use FOS\ElasticaBundle\HybridResult;

class ElasticaToModelTransformer implements ElasticaToModelTransformerInterface
{
    protected $options = array(
        'hydrate'        => false,
        'identifier'     => '_id',
        'ignore_missing' => false,
    );

    /**
     *
     * @param type $objectClass
     * @return \Marmelab\SonataElasticaBundle\Transformer\ElasticaToModelTransformer
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }


    /**
     * {@inheritdoc}
     */
    public function transform(array $elasticaObjects)
    {
        $results = array();

        foreach ($elasticaObjects as $elasticaObject) {
            $elasticaObject = $elasticaObject->getHit();

            $obj = new $this->objectClass();
            $obj->setId($elasticaObject['_id']);

            foreach ($elasticaObject['_source'] as $attributeName => $attributeValue) {
                if (property_exists($this->objectClass, $attributeName)) {
                    $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $attributeName)));
                    $obj->{$method}($attributeValue);
                }
            }

            $results[$obj->getId()] = $obj;
        }

        return $results;
    }


    /**
     * {@inheritdoc}
     */
    public function hybridTransform(array $elasticaObjects)
    {
        $objects = $this->transform($elasticaObjects);

        $result = array();
        for ($i = 0; $i < count($elasticaObjects); $i++) {
            $result[] = new HybridResult($elasticaObjects[$i], $objects[$i]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierField()
    {
        return $this->options['identifier'];
    }

    /**
     * Fetch objects for theses identifier values
     *
     * @param  array   $identifierValues ids values
     * @param  Boolean $hydrate          whether or not to hydrate the objects, false returns arrays
     * @return array   of objects or arrays
     */
    protected function findByIdentifiers(array $identifierValues, $hydrate)
    {
        return null;
    }
}
