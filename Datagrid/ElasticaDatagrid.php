<?php

namespace Marmelab\SonataElasticaBundle\Datagrid;

use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\PagerInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\FilterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\CallbackTransformer;

class ElasticaDatagrid extends Datagrid
{

    /** @var AbstractType */
    protected $searchForm;

    /**
     * @param ProxyQueryInterface        $query
     * @param FieldDescriptionCollection $columns
     * @param PagerInterface             $pager
     * @param FormBuilder                $formBuilder
     * @param array                      $values
     */
    public function __construct(ProxyQueryInterface $query, AbstractType $searchForm, FieldDescriptionCollection $columns, PagerInterface $pager, FormBuilder $formBuilder, array $values = array())
    {
        parent::__construct($query, $columns, $pager, $formBuilder, $values);

        $this->searchForm  = $searchForm;
    }

    /**
     * {@inheritdoc}
     */
    public function buildPager()
    {
        if ($this->bound) {
            return;
        }

        if ($this->searchForm) {
            $this->formBuilder->add('admin_search_form', $this->searchForm, array('label' => false));
        } else {
            foreach ($this->getFilters() as $filter) {
                list($type, $options) = $filter->getRenderSettings();

                $this->formBuilder->add($filter->getFormName(), $type, $options);
            }
        }

        $this->formBuilder->add('_sort_by', 'hidden');
        $this->formBuilder->get('_sort_by')->addViewTransformer(new CallbackTransformer(
            function($value) { return $value; },
            function($value) { return $value instanceof FieldDescriptionInterface ? $value->getName() : $value; }
        ));

        $this->formBuilder->add('_sort_order', 'hidden');
        $this->formBuilder->add('_page', 'hidden');
        $this->formBuilder->add('_per_page', 'hidden');

        $this->form = $this->formBuilder->getForm();
        $this->form->bind($this->values);

        $data = $this->form->getData();

        if ($this->searchForm) {

            $filters = array_filter($data['admin_search_form'], function($filterValue) {
                if ($filterValue === null || $filterValue === '') {
                    return false;
                }
                if (!is_array($filterValue)) {
                    return true;
                }

                foreach ($filterValue as $value) {
                    if ($value === null || $value === '') {
                        return false;
                    }
                }

                return true;
            });

            foreach($filters as $filterName => $filterValue) {
                $this->query->setParameter($filterName, $filterValue);
            }
        }
        else {
            foreach ($this->getFilters() as $name => $filter) {
                $this->values[$name] = isset($this->values[$name]) ? $this->values[$name] : null;
                $filter->apply($this->query, $data[$filter->getFormName()]);

                // Added for phpcr filter
                if (isset($this->values[$name]['value']) && $this->values[$name]['value'] !== null && !$this->query->hasParameter($name)) {
                    $this->query->setParameter($name, $this->values[$name]['value']);
                }
            }
        }


        if (isset($this->values['_sort_by'])) {
            if (!$this->values['_sort_by'] instanceof FieldDescriptionInterface) {
                throw new UnexpectedTypeException($this->values['_sort_by'],'FieldDescriptionInterface');
            }

            if ($this->values['_sort_by']->isSortable()) {
                $this->query->setSortBy($this->values['_sort_by']->getSortParentAssociationMapping(), $this->values['_sort_by']->getSortFieldMapping());
                $this->query->setSortOrder(isset($this->values['_sort_order']) ? $this->values['_sort_order'] : null);
            }
        }


        $this->pager->setMaxPerPage(isset($this->values['_per_page']) ? $this->values['_per_page'] : 25);
        $this->pager->setPage(isset($this->values['_page']) ? $this->values['_page'] : 1);
        $this->pager->setQuery($this->query);
        $this->pager->init();

        $this->bound = true;
    }
}
