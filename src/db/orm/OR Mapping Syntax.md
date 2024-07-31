
O/R Mapping Reference
=====================


Entity mapping:
---------------
```php
$mapping = [
    'class'      => 'EntityClassName',                  // (1) class name
    'table'      => '{table-name}',                     // (2) table name
    'connection' => '{connection-id}',                  // (3) connection identifier, e.g. 'application-db'
    'properties' => [$propertyA, ..., $propertyN],
    'relations'  => [$relationA, ..., $relationN]
];
```


Property mapping:
-----------------
```php
$property = [
    'name'   => '{property-name}',
    'type'   => 'RelatedClassName',
    'column' => '{column-name}'
];
```


Relation mapping:
-----------------
```php
$relation = [
    'name'   => '{property-name}',
    'type'   => 'RelatedClassName',
    'column' => '{column-name}'
];
```


One-To-One: without local foreign-key column
-----------
    'assoc'      => 'one-to-one'
    'name'       => 'propertyName'
    'type'       => 'RelatedClassName'
    'key'        => 'propertyName'          // optional: local key property (default: identity)
    'ref-column' => 'column_name'           // foreign column referencing the local key


One-To-One: with local foreign-key column
-----------
    'assoc'      => 'one-to-one'
    'name'       => 'propertyName'
    'type'       => 'RelatedClassName'
    'column'     => 'column_name'           // local column referencing a foreign key
    'ref-column' => 'column_name'           // optional: referenced foreign column (default: identity)


One-To-Many: no local foreign-key column
------------
    'assoc'      => 'one-to-many'
    'name'       => 'propertyName'
    'type'       => 'RelatedClassName'
    'key'        => 'propertyName'          // optional: local key property (default: identity)
    'ref-column' => 'column_name'           // foreign column referencing the local key


Many-To-One: with local foreign-key column
------------
    'assoc'      => 'many-to-one'
    'name'       => 'propertyName'
    'type'       => 'RelatedClassName'
    'column'     => 'column_name'           // local column referencing a foreign key
    'ref-column' => 'column_name'           // optional: referenced foreign column (default: identity)


Many-To-Many: no local foreign-key column
------------
    'assoc'         => 'many-to-many'
    'name'          => 'propertyName'
    'type'          => 'RelatedClassName'
    'key'           => 'propertyName'       // optional: local key property (default: identity)
    'ref-column'    => 'column_name'        // join table column referencing the local key
    'join-table'    => 'table_name'         // join table
    'foreign-key'   => 'propertyName'       // optional: foreign key property (default: identity)
    'fk-ref-column' => 'column_name'        // join table column referencing the foreign key




Many-To-One:
```php
$relation = [
    'name'   => 'propertyName',           // (1)
    'assoc'  => 'many-to-one',            // (2)
    'type'   => 'RelatedClassName',       // (3)
    'column' => 'organization_id'
];
```
(1) Name of the PHP property to access the related objects.  
(2) Association type of the relation. One of ```one-to-one```, ```one-to-many```, ```many-to-many``` or ```many-to-one```.  
(3) Class name of the related objects.  
