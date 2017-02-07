<?php
namespace rosasurfer\db;

use rosasurfer\exception\RuntimeException;
use rosasurfer\log\Logger;

use function rosasurfer\strContains;

use const rosasurfer\L_WARN;


/**
 * PostgresConnector
 */
class PostgresConnector extends Connector {


   /** @var string - database system type */
   protected $type = 'pgsql';

   /** @var string[] */
   protected $config = [];

   /** @var string[] */
   protected $options = [];

   /** @var string - the resulting connection string as passed to pg_connect() */
   protected $connectionStr;

   /** @var resource - internal connection handle */
   protected $connection;

   /** @var int - transaction nesting level */
   protected $transactionLevel = 0;


   /**
    * Constructor
    *
    * Create a new PostgresConnector instance.
    *
    * @param  string[] $config  - connection configuration (default: none)
    * @param  string[] $options - additional PostgreSQL typical options (default: none)
    */
   protected function __construct(array $config=[], array $options=[]) {
      $this->setConfig($config);
      $this->setOptions($options);
      parent::__construct();
   }


   /**
    * Set the connection configuration.
    *
    * @param  string[] $config
    *
    * @return self
    */
   protected function setConfig(array $config) {
      $this->config = $config;
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
      $connStr = '';
      foreach ($this->config as $key => $value) {
         if (!strLen($value)) {
            $value = "''";
         }
         else {
            $value = str_replace(['\\', "'"], ['\\\\', "\'"], $value);
            if (strContains($value, ' '))
               $value = "'".$value."'";
         }
         $connStr .= $key.'='.$value.' ';
      }
      $this->connectionStr = $connStr = trim($connStr);

      try {
         $this->connection = pg_connect($connStr, PGSQL_CONNECT_FORCE_NEW);
      }
      catch (\Exception $ex) {
         $this->connection    = null;
         $this->connectionStr = null;
         throw new RuntimeException('Cannot connect to PostgreSQL server with connection string: "'.$connStr.'"', null, $ex);
      }
      return $this;

      /*
      The connection string can be empty to use all default parameters, or it can contain one or more parameter settings
      separated by whitespace. Each parameter setting is in the form `keyword=value`. Spaces around the equal sign are
      optional. To write an empty value or a value containing spaces, surround it with single quotes, e.g.,
      `keyword='a value'`. Single quotes and backslashes within the value must be escaped with a backslash, i.e., \' and \\.

      The currently recognized parameter keywords are: 'host', 'hostaddr', 'port', 'dbname' (defaults to value of 'user'),
      'user', 'password', 'connect_timeout', 'options', 'tty' (ignored), 'sslmode', 'requiressl' (deprecated in favor of
      'sslmode'), and 'service'. Which of these arguments exist depends on your PostgreSQL version.

      Keywords:  https://www.postgresql.org/docs/9.6/static/libpq-connect.html#LIBPQ-PARAMKEYWORDS

      The 'options' parameter can be used to set command line parameters to be invoked by the server.

      @see  http://php.net/manual/en/function.pg-connect.php
      @see  https://www.postgresql.org/docs/7.4/static/pgtcl-pgconnect.html
      @see  https://www.postgresql.org/docs/9.6/static/libpq-connect.html#LIBPQ-CONNSTRING

      -----------------------------------------------------------------------------------------------------------------------

      - host=/tmp                                                             // connect to socket
      - options='--application_name=$appName'                                 // send $appName to backend (pgAdmin, logs)
      - options='--client_encoding=UTF8'                                      // mset client encoding

      - putEnv('PGSERVICEFILE=/path/to/your/service/file/pg_service.conf');   // external connection configuration
        pg_connect("service=testdb");

        @see  https://www.postgresql.org/docs/9.6/static/libpq-pgservice.html
      */
   }


   /**
    * Disconnect the adapter from the database.
    *
    * @return self
    */
   protected function disconnect() {
      if ($this->isConnected()) {
         $tmp = $this->connection;
         $this->connection = null;
         pg_close($tmp);
      }
      return $this;

      // TODO: If there are open large object resources on the connection, do not close the connection before closing all
      //       large object resources.
      // @see  http://php.net/manual/en/function.pg-close.php
   }


   /**
    * Whether or not the adapter currently is connected to the database.
    *
    * @return bool
    */
   protected function isConnected() {
      return ($this->connection != null);
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
      $affectedRows = 0;
      $response = $this->executeRaw($sql, $affectedRows);                  // pass on $affectedRows to avoid
      return new PostgresResult($this, $sql, $response, $affectedRows);    // multiple calculations
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
      $affectedRows = 0;
      $result = $this->executeRaw($sql, $affectedRows);
      if (is_resource($result))
         pg_free_result($result);
      return $affectedRows;
   }


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  _IN_  string $sql          - SQL statement
    * @param  _OUT_ int   &$affectedRows - A variable receiving the number of affected rows. Unreliable for specific UPDATE
    *                                      statements (matched but unmodified rows are reported as changed) and for multiple
    *                                      statement queries.
    *
    * @return resource - a result resource
    *
    * @throws DatabaseException in case of failure
    */
   public function executeRaw($sql, &$affectedRows=0) {
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      $affectedRows = 0;

      if (!$this->isConnected())
         $this->connect();

      try {
         $result = pg_query($this->connection, $sql);          // automatically wraps multiple statements in a transaction

         if (!$result) {
            $message  = pg_last_error($this->connection);
            $message .= NL.' SQL: "'.$sql.'"';
            throw new DatabaseException($message, null, $ex);
         }

         // Calculate number of rows affected by an INSERT/UPDATE/DELETE statement.
         //
         // - pg_affected_rows($result) returns the matched, not the modified rows of a result.
         // - PostgreSQL supports multi-statement queries.
         //
         // The following logic assumes a single statement query with matched = modified rows:
         //
         $rows = pg_affected_rows($result);
         if ($rows) {
            $str = strToLower(subStr(trim($sql), 0, 6));
            if ($str!='insert' && $str!='update' && $str!='delete')
               $rows = 0;
         }
         $affectedRows = $rows;
      }
      catch (\Exception $ex) {
         if (!$ex instanceof DatabaseException) {
            $message  = pg_last_error($this->connection);
            $message .= NL.' SQL: "'.$sql.'"';
            $ex = new DatabaseException($message, null, $ex);
         }
         throw $ex;
      }
      return $result;
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
    * @return resource - the internal connection handle
    */
   public function getInternalHandler() {
      return $this->connection;
   }
}
