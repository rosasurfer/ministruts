<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;


/**
 * SQLiteConnector
 *
 * Connector for SQLite/SQLite3 databases.
 *
 * Known bugs in php_sqlite3 v0.7-dev (PHP 5.6):
 * ---------------------------------------------
 * SQLite3Result::fetchArray()  In contrast to the documentation SQLite3::query() always returns a SQLite3Result instance,
 *                              not only for queries returning rows (SELECT, EXPLAIN). Each time SQLite3Result::fetchArray()
 *                              is called on a result from a result-less query internally the query is executed again, thus
 *                              breaking the application.
 *
 * Workaround: Check with SQLite3Result::numColumns() for an empty result before calling SQLite3Result::fetchArray().
 */
class SQLiteConnector extends Connector {


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
    * Create a new SQLiteConnector instance.
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
         !$handler && trigger_error(@$php_errormsg, E_USER_ERROR);
      }
      catch (IRosasurferException $ex) {
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
         throw $ex->addMessage('Cannot '.$what.' SQLite database file "'.$file.'"'.$where);
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
      return is_object($this->handler);
   }


   /**
    * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return SQLiteResult
    *
    * @throws DatabaseException in case of failure
    */
   public function query($sql) {
      try {
         $lastExecMode = $this->skipResults;
         $this->skipResults = false;

         $affectedRows = 0;
         $result = $this->executeRaw($sql, $affectedRows);
         return new SQLiteResult($this, $sql, $result, $affectedRows, $this->lastInsertId());
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
         $this->executeRaw($sql, $affectedRows)->finalize();         // destroy the result
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
    * @return \SQLite3Result
    *
    * @throws DatabaseException in case of failure
    */
   public function executeRaw($sql, &$affectedRows=0) {
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!$this->isConnected())
         $this->connect();

      $result       = null;
      $affectedRows = 0;

      // execute statement
      try {
         if ($this->skipResults) $result = $this->handler->exec($sql);     // TRUE on success, FALSE on error
         else                    $result = $this->handler->query($sql);    // bug: always SQLite3Result, never boolean
         $result || trigger_error('Error '.$this->handler->lastErrorCode().', '.$this->handler->lastErrorMsg(), E_USER_ERROR);
      }
      catch (IRosasurferException $ex) {
         throw $ex->addMessage('SQL: "'.$sql.'"');
      }

      // Calculate number of rows affected by an INSERT/UPDATE/DELETE statement.
      //
      // - SQLite3::changes() is matchedRows().
      // - SQLite3::changes() is not reset between queries.
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

      return $result;
   }
   /*
   $db->query("drop table if exists t_test");
   $db->query("create table t_test (id integer primary key, name text not null)");
   $db->query("insert into t_test (name) values ('a'), ('b'), ('c'), ('123')");
   $db->query("explain select count(*) from t_test");
   $db->query("update t_test set name='c' where name in ('c')");
   $db->query("select * from t_test where name in ('a','b')");
   $db->query("select * from t_test where name in ('a','b') limit 1");
   $db->query("update t_test set name='aa' where name in ('c')");
   $db->query("select * from t_test where name = 'no-one'");
   $db->query("select count(*) from t_test");
   $db->query("delete from t_test where name = 'a' or name = 'b'");
   $db->query("select count(*) from t_test");
   $db->query("insert into t_test (name) values ('y'), ('z')");
   $db->query("insert into t_test (name) values ('x')");
   $db->query("explain select count(*) from t_test");
   $db->query("select * from t_test");
   $db->query("select * from t_test where name = 'no-one'");

   echoPre(str_pad(explode(' ', $sql, 2)[0].':', 9).'  lastInsertRowID='.$handler->lastInsertRowID().'  changes='.$handler->changes().'  result='.(is_object($result) ? 'object':'      ').'  num_rows='.$this->sqlite3_num_rows($result));

   drop:     lastInsertRowID=0  changes=0  result=object  num_rows=0
   create:   lastInsertRowID=0  changes=0  result=object  num_rows=0
   insert:   lastInsertRowID=4  changes=4  result=object  num_rows=0
   explain:  lastInsertRowID=4  changes=4  result=object  num_rows=10
   update:   lastInsertRowID=4  changes=1  result=object  num_rows=0
   select:   lastInsertRowID=4  changes=1  result=object  num_rows=2
   select:   lastInsertRowID=4  changes=1  result=object  num_rows=1
   update:   lastInsertRowID=4  changes=1  result=object  num_rows=0
   select:   lastInsertRowID=4  changes=1  result=object  num_rows=0
   select:   lastInsertRowID=4  changes=1  result=object  num_rows=1
   delete:   lastInsertRowID=4  changes=2  result=object  num_rows=0
   select:   lastInsertRowID=4  changes=2  result=object  num_rows=1
   insert:   lastInsertRowID=6  changes=2  result=object  num_rows=0
   insert:   lastInsertRowID=7  changes=1  result=object  num_rows=0
   explain:  lastInsertRowID=7  changes=1  result=object  num_rows=10
   select:   lastInsertRowID=7  changes=1  result=object  num_rows=5
   select:   lastInsertRowID=7  changes=1  result=object  num_rows=0
   */


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement. The value is not reset between queries.
    * (see the db README)
    *
    * @return int - generated ID or 0 (zero) if no new ID was yet generated in the current session
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastInsertId() {
      if (!$this->isConnected())
         return 0;
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
         $this->execute('begin');

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

      if      (!$this->isConnected())    trigger_error('Not connected', E_USER_WARNING);
      else if (!$this->transactionLevel) trigger_error('No database transaction to commit', E_USER_WARNING);
      else {
         if ($this->transactionLevel == 1)
            $this->execute('commit');
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

      if      (!$this->isConnected())    trigger_error('Not connected', E_USER_WARNING);
      else if (!$this->transactionLevel) trigger_error('No database transaction to roll back', E_USER_WARNING);
      else {
         if ($this->transactionLevel == 1)
            $this->execute('rollback');
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
      if ($this->isConnected())
         return ($this->transactionLevel > 0);
      return false;
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
