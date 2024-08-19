
ORM mapping reference
=====================


Entity mapping:
---------------
An entity mapping describes a single model class and the database table where its class properties are stored.

```php
/**
 * @phpstan-var ORM_ENTITY $mapping 
 * @see rosasurfer\ministruts\db\orm\ORM
 */
$mapping = [
    'class'      => '{entity-class-name}',      // model class name, must be extended from PersistableObject (required)
    'connection' => '{database-id}',            // the database identifier as configured in the application configuration (required)
    'table'      => '{table-name}',             // name of the database table where class properties are stored (required)
    'properties' => [                           // one or more property definitions (required)
        ORM_PROPERTY, 
        ORM_PROPERTY, 
        ..., 
    ],
    'relations' => [                            // zero or more relation definitions between entities resp. database tables (optional)
        ORM_RELATION,
        ORM_RELATION,
        ..., 
    ]
];
```


Property mapping:
-----------------
A property mapping describes how a specific class member is mapped to a sepcific database column.

```php
ORM_PROPERTY = [
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
    'key'        => 'propertyName'          // local key property (optional, default: identity)
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
