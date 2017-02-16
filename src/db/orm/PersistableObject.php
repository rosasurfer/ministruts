<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Object;
use rosasurfer\core\Singleton;
use rosasurfer\db\ConnectorInterface as IConnector;

use rosasurfer\exception\IllegalAccessException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\UnimplementedFeatureException;

use rosasurfer\log\Logger;
use rosasurfer\util\Date;

use const rosasurfer\PHP_TYPE_ARRAY;
use const rosasurfer\PHP_TYPE_BOOL;
use const rosasurfer\PHP_TYPE_FLOAT;
use const rosasurfer\PHP_TYPE_INT;
use const rosasurfer\PHP_TYPE_STRING;


/**
 * PersistableObject
 *
 * Abstract base class for stored objects.
 */
abstract class PersistableObject extends Object {


   /** @var bool     - current modification status */
   protected $modified = false;

   /** @var string[] - modified and unsaved properties */
   protected $modifications;


   // standard properties

   /** @var int - primary key */
   protected $id;

   /** @var (string)datetime - time of creation */
   protected $created;

   /** @var (string)datetime - time of last modification */
   protected $version;

   /** @var (string)datetime - time of soft deletion */
   protected $deleted;


   /**
    * Default constructor. Final and used only by the ORM. To create new instances define and use static helper methods.
    *
    * @example
    *
    *  class MyClass extends PersistableObject {
    *
    *     public static function create($properties, ...) {
    *        $instance = new static();
    *        // set properties...
    *        return $instance;
    *     }
    *  }
    *
    *  $object = MyClass::create('foo');
    *  $object->save();
    */
   protected final function __construct() {
      $this->created = $this->touch();
   }


   /**
    * Return the id (the primary key) of the instance.
    *
    * @return int - id
    */
   public function getId() {
      return $this->id;
   }


   /**
    * Return the creation time of the instance.
    *
    * @param  string $format - format as used by date($format, $timestamp)
    *
    * @return string - creation time
    */
   public function getCreated($format = 'Y-m-d H:i:s')  {
      if ($format == 'Y-m-d H:i:s')
         return $this->created;
      return Date::format($this->created, $format);
   }


   /**
    * Return the version string of the instance. The default implementation returns the last modification time.
    *
    * @return string - version
    */
   public function getVersion() {
      return $this->version;
   }


   /**
    * Update the version string of the instance and return it.
    *
    * @return string - version
    */
   protected function touch() {
      return $this->version = gmDate('Y-m-d H:i:s');
   }


   /**
    * Return the soft deletion time of the instance (if applicable).
    *
    * @param  string $format - format as used by date($format, $timestamp)
    *
    * @return string - deletion time
    */
   public function getDeleted($format = 'Y-m-d H:i:s')  {
      if ($format == 'Y-m-d H:i:s')
         return $this->deleted;
      return Date::format($this->deleted, $format);
   }


   /**
    * Whether or not the instance is marked as "soft deleted".
    *
    * @return bool
    */
   public function isDeleted() {
      return ($this->deleted !== null);
   }


   /**
    * Whether or not the instance was already saved. If the instance was saved it has a unique id assigned to it (the primary
    * key). If the instance was not yet saved the id is NULL. Overwrite this method if the name of the primary key is not "id".
    *
    * @return bool
    */
   public function isPersistent() {
      return ($this->id !== null);
   }


   /**
    * Whether or not the instance contains unsaved  modifications.
    *
    * @return bool
    */
   public function isModified() {
      return ($this->modified);
   }


   /**
    * Save the instance in the storage mechanism.
    *
    * @return self
    */
   public final function save() {
      if (!$this->isPersistent()) {
         $this->insert();
      }
      elseif ($this->modified) {
         $this->update();
      }
      else {
         //Logger::log('Nothing to save, '.get_class($this).' instance is in sync with the database.', L_NOTICE);
      }
      $this->updateLinks();
      $this->modified = false;

      return $this;
   }


   /**
    * Insert this instance into the storage mechanism. Needs to be implemented by the actual class.
    *
    * @return self
    */
   protected function insert() {
      throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to insert a '.get_class($this).'.');
   }


   /**
    * Update the instance in the storage mechanism. Needs to be implemented by the actual class.
    *
    * @return self
    */
   protected function update() {
      throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to update a '.get_class($this).'.');
   }


   /**
    * Update the relational cross-links of the instance. Needs to be implemented by the actual class.
    *
    * @return self
    */
   protected function updateLinks() {
      return $this;
   }


   /**
    * Delete the instance from the storage mechanism. Needs to be implemented by the actual class.
    *
    * @return NULL
    */
   public function delete() {
      throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to delete a '.get_class($this).'.');
   }


   /**
    * Create a new instance and populate it with the specified properties. This method is called by the ORM to transform
    * rows originating from database queries to objects of the respective model class.
    *
    * @param  string $class - class name of the model
    * @param  array  $row   - array with property values (typically a row from a database table)
    *
    * @return self
    */
   public static function createInstance($class, array $row) {
      if (static::class != __CLASS__) throw new IllegalAccessException('Cannot access method '.__METHOD__.'() from a model class.');
      $object = new $class();
      if (!$object instanceof self)   throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

      $row      = array_change_key_case($row, CASE_LOWER);
      $mappings = $object->dao()->getMapping();

      foreach ($mappings['columns'] as $phpName => $mapping) {
         $column = strToLower($mapping[0]);

         if ($row[$column] !== null) {
            $phpType = $mapping[1];

            switch ($phpType) {
               case PHP_TYPE_BOOL  : $object->$phpName =   (bool) $row[$column]; break;
               case PHP_TYPE_INT   : $object->$phpName =    (int) $row[$column]; break;
               case PHP_TYPE_FLOAT : $object->$phpName =  (float) $row[$column]; break;
               case PHP_TYPE_STRING: $object->$phpName = (string) $row[$column]; break;
               case PHP_TYPE_ARRAY : $object->$phpName =   strLen($row[$column]) ? explode(',', $row[$column]) : []; break;

               default: throw new InvalidArgumentException('Unknown PHP type "'.$phpType.'" in database mapping of '.$class.'::'.$phpName);
            }
         }
      }
      return $object;
   }


   /**
    * Return the DAO for the calling class.
    *
    * @return DAO
    */
   public static function dao() {
      if (static::class == __CLASS__) throw new IllegalAccessException('Use a model class to access method '.__METHOD__.'()');
      return Singleton::getInstance(static::class.'DAO');

      // TODO: The calling class may be a derived class with the DAO being one of its parents.
   }


   /**
    * Return the database adapter for the calling class.
    *
    * @return IConnector
    */
   public static function db() {
      if (static::class == __CLASS__) throw new IllegalAccessException('Use a model class to access method '.__METHOD__.'()');
      return self::dao()->db();
   }
}
