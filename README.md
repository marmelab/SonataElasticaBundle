<table>
        <tr>
            <td><img width="20" src="https://cdnjs.cloudflare.com/ajax/libs/octicons/8.5.0/svg/archive.svg" alt="archived" /></td>
            <td><strong>Archived Repository</strong><br />
            This code is no longer maintained. Feel free to fork it, but use it at your own risks.
        </td>
        </tr>
</table>

SonataElastica
=====================

Power the Sonata Admin list view and filters by an ElasticSearch index to speed up navigation.

The [Sonata Admin Bundle](http://sonata-project.org/bundles/admin/master/doc/index.html) provides a web UI to many types of persistence (RDBMS, MongoDB, PHPCR), some of which have limited query capabilities. If you already have an ElasticSearch index for a given model, this bundle allows you to use this index instead of the native repository query system. This may provide a great performance boost, depending on your data structure and indexes.

## Requirements

This bundle depends on:
* `sonata-project/admin-bundle`
* `friendsofsymfony/elastica-bundle`

It can be used with Doctrine (ORM, ODM, PHPCR-ODM), or Propel ORM:
* `sonata-project/doctrine-orm-admin-bundle` - after version `@15ed873424fb30af43569014a48f6d216fdefe78`
* `sonata-project/propel-orm-admin-bundle`

## Installation

### Step 1: Download using composer 

Require `marmelab/sonata-elastica-bundle` in your `composer.json` file:

```json
{
    "require": {
        "marmelab/sonata-elastica-bundle": "dev-master"
    }
}
```
Then run `composer.phar install` as usual.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Marmelab\SonataElasticaBundle\MarmelabSonataElasticaBundle(),
    );
}
```

## Configuration of Elastica Index

For each model that you index, the identifier field (`id`) must be specified, with the type `integer`.
Configure other fields as `multi_field` is not required anymore, see [UPGRADE-2.0-dev.md](./UPGRADE-2.0-dev.md).


For string fields, if you want to be able to sort and to search on them, you may want to declare them as `multi_field`, with two "sub-fields"
* The first one, named after the field, required for filters, must use `index: analyzed`
* The second one, named `raw`, required for sorting, must use `index: not_analyzed`

For more information about this, see [ElasticSearch documentation](http://www.elasticsearch.org/guide/en/elasticsearch/reference/0.90/mapping-multi-field-type.html#mapping-multi-field-type)
(or [this one](http://www.elasticsearch.org/guide/en/elasticsearch/reference/master/_multi_fields.html), as the 1.0 version of ElasticSearch was released recently).


**Example**

```yaml
book:
    mappings:
        id: {type: integer}
        title:
            type: multi_field
            fields:
                title: { type: string, index: analyzed }
                raw: { type: string, index: not_analyzed }
        created_at: { type: date }
        ...
```


Then, in your Admin class, configure the field to use the `not_analyzed` sub-field:

```php
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('title', 'string', array(
                'sortable' => true,
                'sort_field_mapping' => ["fieldName" => "title.raw", "type"=> "string"] // To be able to sort by title.raw which is not_analyzed
            ))
            ...
        ;
    }
```

## Configuration

To enable ElasticSearch for a given model admin, you only need to edit the `sonata.admin` tag in the `services.xml`:

* Add a fourth empty argument to the admin service definition.
* Add two attributes in the `sonata.admin` tag:
    * `searcher="elastica"`
    * `search_index=""`, set the value of your elastica index type

**Example**

For a `Book` entity:

```xml
<service id="book.admin" class="Acme\BookBundle\Admin\BookAdmin">
    <argument/>
    <argument>Acme\BookBundle\Entity\Book</argument>
    <argument>AcmeBookBundle:BookCRUD</argument>
    <argument/>
    <tag name="sonata.admin" group="Content" label="Books" manager_type="orm"
         searcher="elastica" search_index="acme.book"/>
</service>
```

The `search_index=acme.book` corresponds to the following type of configuration for elastica bundle:

```yaml
fos_elastica:
    clients:
        default: { host: %elasticsearch_server_url%, port: %elasticsearch_server_port% }
    indexes:
        acme:
            types:
                book:
                    mappings:
                        ...
                author:
                    mappings:
                        ...
```


## Optional: Bypass ORM Hydration Completely

By default, this bundle uses ElasticSearch to find the ids of the entities or documents matching the request, then queries the underlying persistence to get real entities or documents. This should always be a fast query (since it's using a primary key), but it's also a useless query. Indeed, in most cases, all the data required to hydrate the entities is already available in the ElasticSearch response.

This bundle allows to use a custom transformer service to hydrate ElasticSearch results into Entities, therefore saving one query.
To enable this transformer, add the `fastgrid` parameter to the `admin` tag in `services.xml`:

Using the "basic" transformer:

```xml
 <tag name="sonata.admin" group="Content" label="Books" manager_type="orm"
         fastGrid="true" searcher="elastica" search_index="acme.book"/>
```

Using your custom transformer:

```xml
 <tag name="sonata.admin" group="Content" label="Books" manager_type="orm"
         fastGrid="true" transformer="my.custom.transformer.service" searcher="elastica" search_index="acme.book"/>
```

The default transformer does basic hydration using setters and makes a few assumptions, like the fact that entities provide a `setId()` method.
You can of course use a custom transformer to implement a more sophisticated hydration logic, by providing your service's id. The transformer class must have a `transform` method, converting an array of elastica objects into an array of model objects,
fetched from the doctrine/propel repository. The transformer class should also have a setter for the `objectClass` attribute.


## Optional: Use mapping for fields

To match fields between Elastica index and your application, you can configure the mapping for your entity as a parameter collection:

```xml
<parameter key="book.admin.elastica.mapping" type="collection">
    <parameter key="contentType">_type</parameter>
    <parameter key="publicationDate">publication_timestamp</parameter>
    <parameter key="lastUpdateDate">last_update_timestamp</parameter>
</parameter>
```

Then specify this parameter in your tag admin

```xml
 <tag name="sonata.admin" group="Content" label="Books" manager_type="orm"
         search_index="acme.book"
         fields_mapping="book.admin.elastica.mapping" />
```

## Optional: Define a custom filter for your admin

You can specify a custom filter (using elastica filter classes) for your entity admin.
Simply add a `getExtraFilter()` method in the admin class.

For example, if in my book admin list I want to fetch only the ones that are in a PDF or epub format:

```php
// in Acme\BookBundle\Admin\BookAdmin
use Elastica\Filter\Terms;

...

public function getExtraFilter() {
    $filter = new Terms();
    $filter->setTerms('format', array('pdf', 'epub'));

    return $filter;
}
```

## Optional: Use a custom form filter type

To use a custom form filter class, specify it in the admin tag:

```xml
 <tag name="sonata.admin" group="Content" label="Books" manager_type="orm"
         search_index="acme.book"
         search_form="my.custom.filter.form_type" />
```

## License

This bundle is available under the MIT License, courtesy of [marmelab](http://marmelab.com).
