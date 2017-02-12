<?php
namespace rosasurfer\db;

use rosasurfer\core\Object;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\InvalidArgumentException;


/**
 * Connector
 *
 * Abstract super class for concrete storage mechanism adapters.
 */
abstract class Connector extends Object implements ConnectorInterface {


   /**
    * Default constructor.
    *
    * To create an instance use self::create().
    */
   protected function __construct() {
   }


   /**
    * Destructor
    *
    * Make sure that on destruction of the instance a pending transaction is rolled back and the connection is closed.
    */
   public function __destruct() {
      try {
         if ($this->isConnected()) {
            if ($this->isInTransaction())
               $this->rollback();
            $this->disconnect();
         }
      }
      catch (\Exception $ex) {
         throw ErrorHandler::handleDestructorException($ex);
      }
   }


   /**
    * Create a new connector and initialize it with connection specific configuration values.
    *
    * @param  string   $class   - IConnector class name
    * @param  string[] $config  - connection configuration
    * @param  string[] $options - additional connection options (default: none)
    *
    * @return IConnector
    */
   public static function create($class, array $config, array $options=[]) {
      if (!is_subclass_of($class, IConnector::class)) throw new InvalidArgumentException('Not a '.IConnector::class.': '.$class);
      return new $class($config, $options);
   }


   /**
    * Return the type of the database system the connector is used for.
    *
    * @return string
    */
   public function getType() {
      return $this->type;
   }
}
