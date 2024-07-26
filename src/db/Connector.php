<?php
namespace rosasurfer\ministruts\db;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\InvalidTypeException;
use rosasurfer\ministruts\db\ConnectorInterface as IConnector;


/**
 * Connector
 *
 * Abstract super class for concrete storage mechanism adapters.
 */
abstract class Connector extends CObject implements ConnectorInterface {


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
        catch (\Throwable $ex) { throw ErrorHandler::handleDestructorException($ex); }
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
        if (!is_subclass_of($class, IConnector::class)) throw new InvalidTypeException('Not a '.IConnector::class.' implementing class: '.$class);
        return new $class($options);
    }


    /**
     * Execute a task in a transactional way. The transaction is automatically committed or rolled back.
     * A nested transaction is executed in the context of the nesting transaction.
     *
     * @param  \Closure $task - task to execute (an anonymous function is implicitly casted)
     *
     * @return mixed - the task's return value (if any)
     */
    public function transaction(\Closure $task) {
        try {
            $this->begin();
            $result = $task();
            $this->commit();
            return $result;
        }
        catch (\Throwable $ex) { $this->rollback(); throw $ex; }
    }
}
