
ORM mapping reference
=====================


Entity mapping:
---------------
An entity mapping describes a single model class and the database table where its class properties are stored.

```php
/**
 * @phpstan-var ORM_ENTITY $mapping 
 * @see \rosasurfer\ministruts\db\orm\ORM
 */
$mapping = [
    'class'      => '{entity-class-name}',      // model class, extended from PersistableObject (required)
    'connection' => '{database-id}',            // database identifier as configured in the application configuration (required)
    'table'      => '{table-name}',             // name of the database table where class properties are stored (required)
    'properties' => [                           // one or more property definitions (required)
        ORM_PROPERTY, 
        ORM_PROPERTY, 
        ..., 
    ],
    'relations' => [                            // zero or more relation definitions between entities/database tables (optional)
        ORM_RELATION,
        ORM_RELATION,
        ..., 
    ]
];
```


Property mapping:
-----------------
A property mapping describes how one specific class member is mapped to one specific database column.

```php
/**
 * @phpstan-var ORM_PROPERTY $property
 * @see \rosasurfer\ministruts\db\orm\ORM
 */
$property = [
    'name'   => '{property-name}',
    'type'   => '',
    'column' => '{column-name}',
];
```


Relation mapping:
-----------------
```php
/**
 * @phpstan-var ORM_RELATION $relation
 * @see \rosasurfer\ministruts\db\orm\ORM
 */
$relation = [
    'name'  => '{property-name}',                                   // local property of the relation (required)
    'type'  => '{entity-class-name}',                               // related model class, extended from PersistableObject (required)
    'assoc' => 'one-to-one|one-to-many|many-to-one|many-to-many',   // relation type (required)
    ...,                                                            // optional fields depending on relation type (see below)
];
```


Relation type "one-to-one" with local foreign-key column
--------------------------------------------------------
```php
$relation = [
    ...,                                                            // required fields (see above)
    'assoc'      => 'one-to-one',
    'column'     => '{column-name}',                                // local column referencing a foreign key
    'ref-column' => '{column-name}',                                // referenced foreign column (optional, default: primary key)
];
```


Relation type "one-to-one" without local foreign-key column
-----------------------------------------------------------
```php
$relation = [
    ...,                                                            // required fields (see above)
    'assoc'         => 'one-to-one',
    'key'           => '{property-name}',                           // local key property (optional, default: primary key)
    'ref-column'    => '{column-name}',                             // foreign column referencing the local key (required)
    'join-table'    => '{table-name}',                              // join table (optional, default: table of the related entity)
    'fk-ref-column' => '{column-name}',                             // join table column referencing the foreign key of the relation (required with join-table)
];
```


Relation type "one-to-many" (no local foreign-key column)
---------------------------------------------------------
```php
$relation = [
    ...,                                        // required fields (see above)
    'assoc'      => 'one-to-many'
    'key'        => 'propertyName'              // optional: local key property (default: identity)
    'ref-column' => 'column_name'               // foreign column referencing the local key
];
```


Relation type "many-to-one" (local foreign-key column)
------------------------------------------------------
```php
$relation = [
    ...,                                        // required fields (see above)
    'assoc'      => 'many-to-one'
    'column'     => 'column_name'               // local column referencing a foreign key
    'ref-column' => 'column_name'               // optional: referenced foreign column (default: identity)
];
```


Relation type "many-to-many" (no local foreign-key column)
----------------------------------------------------------
```php
$relation = [
    ...,                                        // required fields (see above)
    'assoc'         => 'many-to-many'
    'key'           => 'propertyName'           // optional: local key property (default: identity)
    'ref-column'    => 'column_name'            // join table column referencing the local key
    'join-table'    => 'table_name'             // join table
    'fk-ref-column' => 'column_name'            // join table column referencing the foreign key
    'foreign-key'   => 'propertyName'           // optional: foreign key property (default: identity)
];
```


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
