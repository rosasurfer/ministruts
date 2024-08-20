
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
 *
 * TODO:
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


Relation type "one-to-one" (local foreign-key column, no join table)
--------------------------------------------------------------------
```php
$relation = [
    ...,                                                            // common fields (see above)
    'assoc'      => 'one-to-one',
    'column'     => '{column-name}',                                // local column referencing a foreign key (required)
    'ref-column' => '{column-name}',                                // referenced foreign column (optional, default: primary key)
];
```


Relation type "one-to-one" (no local foreign-key column, optional join table)
-----------------------------------------------------------------------------
```php
$relation = [
    ...,                                                            // common fields (see above)
    'assoc'      => 'one-to-one',
    'key'        => '{property-name}',                              // local key property (optional, default: primary key)
    'ref-column' => '{column-name}',                                // foreign column referencing the local key (required)
];
```


Relation type "one-to-many" (no local foreign-key column, optional join table)
------------------------------------------------------------------------------
```php
$relation = [
    ...,                                                            // common fields (see above)
    'assoc'      => 'one-to-many'
    'key'        => '{property-name}'                               // local key property (optional, default: identity)
    'ref-column' => '{column-name}'                                 // foreign column referencing the local key
];
```


Relation type "many-to-one" (local foreign-key column, no join table)
---------------------------------------------------------------------
```php
$relation = [
    ...,                                                            // common fields (see above)
    'assoc'      => 'many-to-one'
    'column'     => '{column-name}'                                 // local column referencing a foreign key (required)
    'ref-column' => '{column-name}'                                 // referenced foreign column (optional, default: identity)
];
```


Relation type "many-to-many" (no local foreign-key column, mandatory join table)
--------------------------------------------------------------------------------
```php
$relation = [
    ...,                                        // required fields (see above)
    'assoc'         => 'many-to-many'
    'key'           => 'propertyName'           // local key property (optional, default: identity)
    'join-table'    => 'table_name'             // join table (required)
    'ref-column'    => 'column_name'            // join table column referencing the local key (required)
    'fk-ref-column' => 'column_name'            // join table column referencing the foreign key (required)
    'foreign-key'   => 'propertyName'           // foreign key property (optional, default: identity)
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
