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

Require `marmelab/sonata-elastica-bundle` in your `composer.json` file:

```json
{
    "require": {
        "marmelab/sonata-elastica-bundle": "@stable"
    }
}
```

Then run `composer.phar install` as usual.

## Configuration of Elastica Index

For each model that you index, the identifier field (`id`) must be specified, with the type `integer`.

All the other fields must be set as `multi_field`, with (at least) these two "sub-fields":
* The first one, named after the field, required for filters, must use `index: analyzed`
* The second one, named `raw`, required for sorting, must use `index: not_analyzed`

For more information about this, see [http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/mapping-multi-field-type.html#mapping-multi-field-type](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/mapping-multi-field-type.html#mapping-multi-field-type)

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
        summary:
            type: multi_field
            fields:
                summary: { type: string, index: analyzed }
                raw: { type: string, index: not_analyzed }
        ...
```

## Configuration

To enable ElasticSearch for a given model admin, you only need to edit the `sonata.admin` tag in the `services.xml` and extend `ElasticaAdmin` instead of `Admin`.

### Step 1/2: Configure admin service

* Add a fourth empty argument to the admin service definition.
* Add two attributes in the `sonata.admin` tag :
    * `searcher="elastica"`
    * `search_index=""`, set the value of your elastica index type

**Example**

For a `Book` entity :

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

### Step 2/2 : Extend `ElasticaAdmin` in Your Admin Class

```php
use Marmelab\SonataElasticaBundle\Admin\ElasticaAdmin;

class BookAdmin extends ElasticaAdmin
{
    // ...
}
```

## Optional: Bypass ORM Hydration Completely

By default, this bundle uses ElasticSearch to find the ids of the entities or documents matching the request, then queries the underlying persistence to get real entities or documents. This should always be a fast query (since it's using a primary key), but it's also a useless query. Indeed, in most cases, all the data required to hydrate the entities is already available in the ElasticSearch response.

This bundle allows to use a custom transformer service to hydrate ElasticSearch results into Entities, therefore saving one query. To enable this transformer, add a new `transformer` parameter to the `admin` tag in `services.xml`:

```xml
<service id="book.admin" class="Acme\BookBundle\Admin\BookAdmin">
    <argument/>
    <argument>Acme\BookBundle\Entity\Book</argument>
    <argument>AcmeBookBundle:BookCRUD</argument>
    <argument/>
    <tag name="sonata.admin" group="Content" label="Books" manager_type="orm"
         transformer="marmelab.book.elastica.transformer" searcher="elastica" search_index="acme.book"/>
</service>
```

The default transformer (`marmelab.book.elastica.transformer`) does basic hydration using setters and makes a few assumptions, like the fact that entities provide a `setId()` method. You can of course use a custom transformer to implement a more sophisticated hydration logic. The transformer class must have a `transform` method, converting an array of elastica objects into an array of model objects,
fetched from the doctrine/propel repository. The transformer class should also have a setter for the `objectClass` attribute.

## License

This bundle is available under the MIT License, courtesy of [marmelab](http://marmelab.com).
