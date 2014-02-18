UPGRADE FROM 1.0-dev to 2.0-dev
=======================

## Configuration of Elastica Index

### In 1.0-dev version :

- To sort on a field, it needs to be `not_analyzed`.
- To filter on a field, it needs to be `analyzed`.

So to make both works, in the 1.0-dev version, all the fields (except `id`) must be set to `multi_field` with a `raw` sub-field: the `raw` sub-field is `not_analyzed` and is used to sort.

For more information about this, see [ElasticSearch documentation](http://www.elasticsearch.org/guide/en/elasticsearch/reference/0.90/mapping-multi-field-type.html#mapping-multi-field-type)
(or [this](http://www.elasticsearch.org/guide/en/elasticsearch/reference/master/_multi_fields.html) one, as the 1.0 version of ElasticSearch was released recently).

So the bundle won't work if one of your field does not have a `raw` sub-field.
Which is not a good thing, especially as you don't need an `analyzed` field to do an exact match:


**Example** 

If I have a `status` field that can be either `"Not Started"`, `"Started"`, `"Finished"`.
- To sort it, it need to be `not_analyzed`.
- To filter it, it does not need to be analyzed if the filter is on the exact word (like when we sort from a select list).


### In 2.0-dev version :

Configure fields as `multi_field` is not required anymore, so the sort is not done on the `$fieldName .'.raw'` anymore.
If you upgrade the bundle, you will need to make some change in your index configuration.


For the fields that just need to be `not_analyzed` to perform "exact search" & filter, no need to use the `multi_field`.

For string fields, if you want to be able to sort and to search on the field, you may want to declare them as `multi_field`, with two "sub-fields"
* The first one named after the field, required for search, must use `index: analyzed`
* The second one named `raw`, required for sorting, must use `index: not_analyzed`


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


Then in your Admin class, configure the field to use the `not_analyzed` sub-field to sort:

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
