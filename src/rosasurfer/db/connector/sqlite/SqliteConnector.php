<?php
namespace rosasurfer\db\connector\sqlite;

use rosasurfer\db\connector\Connector;

use rosasurfer\exception\DatabaseException;
use rosasurfer\exception\InfrastructureException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\log\Logger;

use const rosasurfer\L_WARN;
use const rosasurfer\NL;


/**
 * SqliteConnector
 */
class SqliteConnector extends Connector {


   /** @var \SQLite3 - the handler instance */
   protected $handler;

   /** @var string - the database file to connect to */
   protected $file;

   /** @var string[] */
   protected $options = [];

   /** @var int - transaction nesting level */
   protected $transactions = 0;


   /**
    * Constructor
    *
    * Create a new SqliteConnector instance.
    *
    * @param  string[] $config  - connection configuration
    * @param  string[] $options - additional SQLite typical options (default: none)
    */
   protected function __construct(array $config, array $options=[]) {
      $this->setFile($config['file']);
      $this->setOptions($options);
      parent::__construct();
   }


   /**
    * Set the file name of the database to connect to. May be ":memory:" to use an in-memory database.
    *
    * @param  string $fileName
    *
    * @return self
    */
   protected function setFile($fileName) {
      if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of parameter $fileName: '.getType($fileName));
      if (!strLen($fileName))    throw new InvalidArgumentException('Invalid parameter $fileName: "'.$fileName.'" (empty)');

      $this->file = $fileName;
      return $this;
   }


   /**
    * Set additonal connection options.
    *
    * @param  string[] $options
    *
    * @return self
    */
   protected function setOptions(array $options) {
      $this->options = $options;
      return $this;
   }


   /**
    * Connect the adapter to the database.
    *
    * @return self
    */
   protected function connect() {
      try {                                                          // available flags:
         $flags = SQLITE3_OPEN_READWRITE ;      // SQLITE3_OPEN_READONLY| SQLITE3_OPEN_CREATE
                                                                     // SQLITE3_OPEN_READWRITE
         $handler = new \SQLite3($this->file, $flags);               // SQLITE3_OPEN_CREATE
      }
      catch (\Exception $ex) {
         $file = $this->file;
         $what = $where = null;
         if (file_exists($file)) {
            $what = 'open';
            if (is_dir($file=realPath($file)))
               $where = ' (directory)';
         }
         else {
            $what = ($flags & SQLITE3_OPEN_CREATE) ? 'create':'find';
            if      ( WINDOWS && preg_match('/^[a-z]:/i', $file)) $absolutePath = true;
            else if (!WINDOWS && $file[0]=='/')                   $absolutePath = true;
            else                                                  $absolutePath = false;
            if (!$absolutePath) $where = ' in "'.getCwd().'"';
         }
         throw new InfrastructureException('Cannot '.$what.' SQLite database file "'.$file.'"'.$where, null, $ex);
      }
      $this->handler = $handler;
   }


   /**
    * Disconnect the adapter from the database.
    *
    * @return self
    */
   protected function disconnect() {
      if ($this->isConnected()) {
         $handler = $this->handler;
         $this->handler = null;
         $handler->close();
      }
      return $this;
   }


   /**
    * Whether or not the adapter currently is connected to the database.
    *
    * @return bool
    */
   protected function isConnected() {
      return ($this->handler != null);
   }


   /**
    * Execute a SQL statement and return the result.
    *
    * @param  string $sql - SQL statement
    *
    * @return array['set' ] - \SQLite3Result (for SELECT statements only)
    *              ['rows'] - number of affected or modified rows (for SELECT/INSERT/UPDATE statements only)
    */
   public function executeSql($sql) {
      $retval = $this->executeRaw($sql);

      $result['set' ] = is_object($retval) ? $retval : null;
      $result['rows'] = -1;                                          // no SQLite row count support

      return $result;
   }


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  string $sql - SQL statement
    *
    * @return \SQLite3Result|bool - SQLite3Result or TRUE (depending on the statement)
    */
   public function executeRaw($sql) {
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

      !$this->isConnected() && $this->connect();
      try {
         $result = $this->handler->query($sql);
         //$result = $this->handler->exec($sql);

         if (!$result) {
            $message  = 'Error '.$this->handler->lastErrorCode().', '.$this->handler->lastErrorMsg();
            $message .= NL.' SQL: "'.$sql.'"';
            throw new DatabaseException($message, null, $ex);
         }
      }
      catch (\Exception $ex) {
         if (!$ex instanceof DatabaseException) {
            $message  = 'Error '.$this->handler->lastErrorCode().', '.$this->handler->lastErrorMsg();
            $message .= NL.' SQL: "'.$sql.'"';
            $ex = new DatabaseException($message, null, $ex);
         }
         throw $ex;
      }
      return $result;
   }


   /**
    * Start a new transaction. If there is already an active transaction only the transaction counter is increased.
    *
    * @return self
    */
   public function begin() {
      if ($this->transactions < 0) throw new RuntimeException('Negative transaction counter detected: '.$this->transactions);

      if (!$this->transactions)
         $this->executeRaw('begin');

      $this->transactions++;
      return $this;
   }


   /**
    * Commit a pending transaction.
    *
    * @return self
    */
   public function commit() {
      if ($this->transactions < 0) throw new RuntimeException('Negative transaction counter detected: '.$this->transactions);

      if (!$this->transactions) {
         Logger::log('No database transaction to commit', L_WARN);
      }
      else {
         if ($this->transactions == 1)
            $this->executeRaw('commit');

         $this->transactions--;
      }
      return $this;
   }


   /**
    * Roll back an active transaction. If a nested transaction is active only the transaction counter is decreased. If only
    * one (the outer most) transaction is active the transaction is rolled back.
    *
    * @return self
    */
   public function rollback() {
      if ($this->transactions < 0) throw new RuntimeException('Negative transaction counter detected: '.$this->transactions);

      if (!$this->transactions) {
         Logger::log('No database transaction to roll back', L_WARN);
      }
      else {
         if ($this->transactions == 1)
            $this->executeRaw('rollback');

         $this->transactions--;
      }
      return $this;
   }


   /**
    * Whether or not the connection currently is in a transaction.
    *
    * @return bool
    */
   public function isInTransaction() {
      return ($this->transactions > 0);
   }
}
