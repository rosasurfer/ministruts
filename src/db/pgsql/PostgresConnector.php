<?php
namespace rosasurfer\db\pgsql;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\RosasurferExceptionInterface as RosasurferException;
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
   public function connect() {
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
      - options='--client_encoding=UTF8'                                      // set client encoding

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
   public function disconnect() {
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
   public function isConnected() {
      return ($this->connection != null);
   }


   /**
    * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return PostgresResult
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

      $result = null;
      try {
         // execute statement
         $result = pg_query($this->connection, $sql);             // wraps multi-statement queries in a transaction
      }
      catch (RosasurferException $ex) {
         throw $ex->addMessage('SQL: "'.$sql.'"');
      }

      if (!$result) {
         $message  = pg_last_error($this->connection);
         $message .= NL.' SQL: "'.$sql.'"';
         throw new DatabaseException($message, null, $ex);
      }
      //
      // TODO: All queries must be sent via pg_send_query()/pg_get_result(). All errors must be analyzed per result
      //       via pg_result_error(). This way we get access to SQLSTATE codes and to custom exception handling.
      //
      //       PDO and missing support for asynchronous queries:
      // @see  http://grokbase.com/t/php/php-pdo/09b2hywmak/asynchronous-requests
      // @see  http://stackoverflow.com/questions/865017/pg-send-query-cannot-set-connection-to-blocking-mode
      // @see  https://bugs.php.net/bug.php?id=65015
      //

      /*
      pg_send_query($this->connection, $sql);
      $result = pg_get_result($this->connection);     // get one result per statement from a multi-statement query

      echoPre(pg_result_error($result));              // analyze errors

      echoPre('PGSQL_DIAG_SEVERITY           = '.pg_result_error_field($result, PGSQL_DIAG_SEVERITY          ));
      echoPre('PGSQL_DIAG_SQLSTATE           = '.pg_result_error_field($result, PGSQL_DIAG_SQLSTATE          ));
      echoPre('PGSQL_DIAG_MESSAGE_PRIMARY    = '.pg_result_error_field($result, PGSQL_DIAG_MESSAGE_PRIMARY   ));
      echoPre('PGSQL_DIAG_MESSAGE_DETAIL     = '.pg_result_error_field($result, PGSQL_DIAG_MESSAGE_DETAIL    ));
      echoPre('PGSQL_DIAG_MESSAGE_HINT       = '.pg_result_error_field($result, PGSQL_DIAG_MESSAGE_HINT      ));
      echoPre('PGSQL_DIAG_STATEMENT_POSITION = '.pg_result_error_field($result, PGSQL_DIAG_STATEMENT_POSITION));
      echoPre('PGSQL_DIAG_INTERNAL_POSITION  = '.pg_result_error_field($result, PGSQL_DIAG_INTERNAL_POSITION ));
      echoPre('PGSQL_DIAG_INTERNAL_QUERY     = '.pg_result_error_field($result, PGSQL_DIAG_INTERNAL_QUERY    ));
      echoPre('PGSQL_DIAG_CONTEXT            = '.pg_result_error_field($result, PGSQL_DIAG_CONTEXT           ));
      echoPre('PGSQL_DIAG_SOURCE_FILE        = '.pg_result_error_field($result, PGSQL_DIAG_SOURCE_FILE       ));
      echoPre('PGSQL_DIAG_SOURCE_LINE        = '.pg_result_error_field($result, PGSQL_DIAG_SOURCE_LINE       ));
      echoPre('PGSQL_DIAG_SOURCE_FUNCTION    = '.pg_result_error_field($result, PGSQL_DIAG_SOURCE_FUNCTION   ));
      // ----------------------------------------------------------------------------------------------------------

      $>  select lastval()
      ERROR:  lastval is not yet defined in this session
      PGSQL_DIAG_SEVERITY           = ERROR
      PGSQL_DIAG_SQLSTATE           = 55000
      PGSQL_DIAG_MESSAGE_PRIMARY    = lastval is not yet defined in this session
      PGSQL_DIAG_MESSAGE_DETAIL     =
      PGSQL_DIAG_MESSAGE_HINT       =
      PGSQL_DIAG_STATEMENT_POSITION =
      PGSQL_DIAG_INTERNAL_POSITION  =
      PGSQL_DIAG_INTERNAL_QUERY     =
      PGSQL_DIAG_CONTEXT            =
      PGSQL_DIAG_SOURCE_FILE        = sequence.c
      PGSQL_DIAG_SOURCE_LINE        = 794
      PGSQL_DIAG_SOURCE_FUNCTION    = lastval
      // ----------------------------------------------------------------------------------------------------------

      $>  insert into t_doesnotexist (name) values ('a')
      ERROR:  relation "t_doesnotexist" does not exist
      LINE 1: insert into t_doesnotexist (name) values ('a'), ('b'), ('c')
                          ^
      PGSQL_DIAG_SEVERITY           = ERROR
      PGSQL_DIAG_SQLSTATE           = 42P01
      PGSQL_DIAG_MESSAGE_PRIMARY    = relation "t_doesnotexist" does not exist
      PGSQL_DIAG_MESSAGE_DETAIL     =
      PGSQL_DIAG_MESSAGE_HINT       =
      PGSQL_DIAG_STATEMENT_POSITION = 13
      PGSQL_DIAG_INTERNAL_POSITION  =
      PGSQL_DIAG_INTERNAL_QUERY     =
      PGSQL_DIAG_CONTEXT            =
      PGSQL_DIAG_SOURCE_FILE        = parse_relation.c
      PGSQL_DIAG_SOURCE_LINE        = 866
      PGSQL_DIAG_SOURCE_FUNCTION    = parserOpenTable
      */


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

      return $result;
   }


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (usually an INSERT).
    *
    * This function returnes the most recently generated ID. It's value is not reset between queries.
    *
    * @return int - generated ID or 0 (zero) if no previous statement yet generated an ID;
    *               -1 if the PostgreSQL server version doesn't support this functionality
    */
   public function lastInsertId() {
      return (int) $this->query("select lastval()")->fetchField();
      /*
      PHP Warning: pg_query(): Query failed: ERROR:  lastval is not yet defined in this session
       SQL: "select lastval()"

      @see  http://stackoverflow.com/questions/6485778/php-postgres-get-last-insert-id/6488840
      @see  http://stackoverflow.com/questions/22530585/how-to-turn-off-multiple-statements-in-postgres
      @see  http://php.net/manual/en/function.pg-query-params.php
      @see  http://stackoverflow.com/questions/24182521/how-to-find-out-if-a-sequence-was-initialized-in-this-session
      @see  http://stackoverflow.com/questions/32991564/how-to-check-in-postgres-that-lastval-is-defined
      @see  http://stackoverflow.com/questions/55956/mysql-insert-id-alternative-for-postgresql
      */
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
