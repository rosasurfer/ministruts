
ORM mapping reference
=====================


## Entity mapping:
An entity mapping describes an entity class and the database table where its class properties are stored.

```php
/**
 * @phpstan-var ORM_ENTITY $mapping
 * @see \rosasurfer\ministruts\db\orm\ORM
 */
$mapping = [
    "class"      => PersistableObject::class,   // model class, must extend PersistableObject (required)
    "connection" => "{database-id}",            // database identifier as configured in the application configuration (required)
    "table"      => "{table-name}",             // name of the database table where class properties are stored (required)
    "properties" => [                           // one or more property definitions (required)
        ORM_PROPERTY,
        ORM_PROPERTY,
        ...,
    ],
    "relations" => [                            // zero or more relation definitions between entity classes/db tables (optional)
        ORM_RELATION,
        ORM_RELATION,
        ...,
    ]
];
```


## Property mapping:
A property mapping describes how a member variable of an entity class is mapped to a specific database column.

```php
/**
 * @phpstan-var ORM_PROPERTY $property
 * @see \rosasurfer\ministruts\db\orm\ORM
 */
$property = [
    "name"        => "{property-name}",         // name of the PHP variable, accessor of the mapped database field (required)
    "type"        => "{type}",                  // scalar PHP type of the variable (required)
    "column"      => "{column-name}",           // database column (optional, default: same as "name")
    "column-type" => "{column-type}",           // database column type (optional, default: same as "type")
    "primary-key" => true,                      // marks the property as identity field of the entity (optional)
    "version"     => true,                      // marks the property as version field of the entity (optional)
    "soft-delete" => true,                      // defines the property as marker for "soft-deleted" entities (optional)
];
```


## Relation mapping:
A relation mapping describes the object-oriented relation between two entity clases (resp. ER model of two database tables).

```php
/**
 * @phpstan-var ORM_RELATION $relation
 * @see \rosasurfer\ministruts\db\orm\ORM
 */
$relation = [
    "name"  => "{property-name}",               // PHP property of the relation, accessor of the related object/s (required)
    "type"  => "{type}",                        // one of "one-to-one|one-to-many|many-to-one|many-to-many" (required)
    "class" => PersistableObject::class,        // model class of the related object/s, must extend PersistableObject (required)
    ...,                                        // optional fields (see below)
];
```


#### Relation type "one-to-one" (local foreign-key column, no join table)
```php
$relation = [
    ...,                                        // common fields (see above)
    "assoc"      => "one-to-one",
    "column"     => "{column-name}",            // local column referencing a foreign key (required)
    "ref-column" => "{column-name}",            // referenced foreign column (optional, default: primary key)
];
```


#### Relation type "one-to-one" (no local foreign-key column, optional join table)
```php
$relation = [
    ...,                                        // common fields (see above)
    "assoc"      => "one-to-one",
    "key"        => "{property-name}",          // local key property (optional, default: primary key)
    "ref-column" => "{column-name}",            // foreign column referencing the local key (required)
];
```


#### Relation type "one-to-many" (no local foreign-key column, optional join table)
```php
$relation = [
    ...,                                        // common fields (see above)
    "assoc"      => "one-to-many"
    "key"        => "{property-name}"           // local key property (optional, default: identity)
    "ref-column" => "{column-name}"             // foreign column referencing the local key (required)
];
```


#### Relation type "many-to-one" (local foreign-key column, no join table)
```php
$relation = [
    ...,                                        // common fields (see above)
    "assoc"      => "many-to-one"
    "column"     => "{column-name}"             // local column referencing a foreign key (required)
    "ref-column" => "{column-name}"             // referenced foreign column (optional, default: identity)
];
```


#### Relation type "many-to-many" (no local foreign-key column, mandatory join table)
```php
$relation = [
    ...,                                        // common fields (see above)
    "assoc"         => "many-to-many"
    "key"           => "{property-name}"        // local key property (optional, default: identity)
    "join-table"    => "{table-name}"           // join table (required)
    "ref-column"    => "{column-name}"          // join table column referencing the local key (required)
    "fk-ref-column" => "{column-name}"          // join table column referencing the foreign key (required)
    "foreign-key"   => "{property-name}"        // foreign key property (optional, default: identity)
];
```
