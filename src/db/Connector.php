<?php
namespace rosasurfer\db;

use rosasurfer\core\Object;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\InvalidArgumentException;
use \Exception;


/**
 * Connector
 *
 * Abstract super class for concrete storage mechanism adapters.
 */
abstract class Connector extends Object implements ConnectorInterface {


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
        catch (Exception $ex) {
            throw ErrorHandler::handleDestructorException($ex);
        }
    }


    /**
     * Create a new connector with the specified configuration options.
     *
     * @param  string   $class   - name of class implementing IConnector
     * @param  string[] $options - configuration options
     *
     * @return IConnector
     */
    public static function create($class, array $options) {
        if (!is_subclass_of($class, IConnector::class)) throw new InvalidArgumentException('Not a '.IConnector::class.' implementing class: '.$class);
        return new $class($options);
    }
}
