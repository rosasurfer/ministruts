<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;
use rosasurfer\db\Result;

use rosasurfer\exception\RosasurferExceptionInterface as RosasurferException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\log\Logger;

use const rosasurfer\L_WARN;
use const rosasurfer\NL;


/**
 * SqliteConnector
 */
class SqliteConnector extends Connector {


   /** @var string - database system type */
   protected $type = 'sqlite';

   /** @var string - database file to connect to */
   protected $file;

   /** @var string[] */
   protected $options = [];

   /** @var \SQLite3 - internal database handler instance */
   protected $handler;

   /** @var int - last value of \SQLite3::changes() */
   protected $lastChanges = 0;

   /** @var int - transaction nesting level */
   protected $transactionLevel = 0;

   /** @var bool - whether or not a query to execute can skip results */
   private $skipResults = false;


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
   public function connect() {
      try {                                                          // available flags:
         $flags = SQLITE3_OPEN_READWRITE ;   // SQLITE3_OPEN_READONLY| SQLITE3_OPEN_CREATE
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
         throw new RuntimeException('Cannot '.$what.' SQLite database file "'.$file.'"'.$where, null, $ex);
      }
      $this->handler = $handler;
   }


   /**
    * Disconnect the adapter from the database.
    *
    * @return self
    */
   public function disconnect() {
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
   public function isConnected() {
      return ($this->handler != null);
   }


   /**
    * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return Result
    *
    * @throws DatabaseException in case of failure
    */
   public function query($sql) {
      try {
         $lastExecMode = $this->skipResults;
         $this->skipResults = false;

         $affectedRows = 0;
         $response = $this->executeRaw($sql, $affectedRows);
         if ($response === true)
            $response = null;
         return new SqliteResult($this, $sql, $response, $affectedRows);
      }
      finally {
         $this->skipResults = $lastExecMode;
      }
   }


   /**
    * Execute a SQL statement and skip result set processing. This method should be used for SQL statements
    * not returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return int - Number of rows affected by the statement. Unreliable for specific UPDATE statements (matched but
    *               unmodified rows are reported as changed) and for multiple statement queries.
    *
    * @throws DatabaseException in case of failure
    */
   public function execute($sql) {
      try {
         $lastExecMode = $this->skipResults;
         $this->skipResults = true;

         $affectedRows = 0;
         $this->executeRaw($sql, $affectedRows);
         return $affectedRows;
      }
      finally {
         $this->skipResults = $lastExecMode;
      }
   }


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  _IN_  string $sql          - SQL statement
    * @param  _OUT_ int   &$affectedRows - A variable receiving the number of affected rows. Unreliable for specific UPDATE
    *                                      statements (matched but unmodified rows are reported as changed) and for multiple
    *                                      statement queries.
    *
    * @return \SQLite3Result|bool - SQLite3Result or TRUE (depending on the statement)
    *
    * @throws DatabaseException in case of failure
    */
   public function executeRaw($sql, &$affectedRows=0) {
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      $affectedRows = 0;

      if (!$this->isConnected())
         $this->connect();

      try {
         if ($this->skipResults) $result = $this->handler->exec($sql);  // TRUE on success, FALSE on error
         else                    $result = $this->handler->query($sql); // SQLite3Result or bool for result-less statements

         if (!$result) {
            $message  = 'Error '.$this->handler->lastErrorCode().', '.$this->handler->lastErrorMsg();
            $message .= NL.'SQL: "'.$sql.'"';
            throw new DatabaseException($message, null, $ex);
         }

         // Calculate number of rows affected by an INSERT/UPDATE/DELETE statement.
         //
         // - SQLite3::changes() is not updated for every statement.
         // - SQLite3::changes() returns the matched, not the modified rows of a result.
         // - SQLite3::query('DELETE...') returns an empty SQLite3Result, thus a result set is no valid criterion.
         // - SQLite3 supports multiple statements per query.
         //
         // The following logic assumes a single statement query with matched = modified rows:
         //
         $changes = $this->handler->changes();
         if ($changes && $changes==$this->lastChanges) {
            $str = strToLower(subStr(trim($sql), 0, 6));
            if ($str!='insert' && $str!='update' && $str!='delete')
               $changes = 0;
         }
         $affectedRows      = $changes;
         $this->lastChanges = $this->handler->changes();
      }
      catch (RosasurferException $ex) {
         $frame = $ex->getBetterTrace()[0];
         $class = isSet($frame['class']) ? $frame['class'] : '';
         if ($this->handler instanceof $class)
            $ex->addMessage('SQL: "'.$sql.'"');
         throw $ex;
      }
      return $result;
   }


   /**
    * Return the ID generated by the last SQL statement.
    *
    * @return int - Generated ID or 0 (zero) if the previous statement did not generate an ID.
    */
   public function lastInsertId() {
      return $this->handler->lastInsertRowID();
   }


   /**
    * Start a new transaction. If there is already an active transaction only the transaction nesting level is increased.
    *
    * @return self
    */
   public function begin() {
      if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

      if (!$this->transactionLevel)
         $this->executeRaw('begin');

      $this->transactionLevel++;
      return $this;
   }


   /**
    * Commit a pending transaction.
    *
    * @return self
    */
   public function commit() {
      if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

      if (!$this->transactionLevel) {
         Logger::log('No database transaction to commit', L_WARN);
      }
      else {
         if ($this->transactionLevel == 1)
            $this->executeRaw('commit');

         $this->transactionLevel--;
      }
      return $this;
   }


   /**
    * Roll back an active transaction. If a nested transaction is active only the transaction nesting level is decreased.
    * If only one (the outer most) transaction is active the transaction is rolled back.
    *
    * @return self
    */
   public function rollback() {
      if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

      if (!$this->transactionLevel) {
         Logger::log('No database transaction to roll back', L_WARN);
      }
      else {
         if ($this->transactionLevel == 1)
            $this->executeRaw('rollback');

         $this->transactionLevel--;
      }
      return $this;
   }


   /**
    * Whether or not the connection currently is in a transaction.
    *
    * @return bool
    */
   public function isInTransaction() {
      return ($this->transactionLevel > 0);
   }


   /**
    * Return the connector's internal connection object.
    *
    * @return \SQLite3 - the internal connection handler
    */
   public function getInternalHandler() {
      return $this->handler;
   }
}
