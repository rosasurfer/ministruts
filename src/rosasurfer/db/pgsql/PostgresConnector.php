<?php
namespace rosasurfer\db\pgsql;

use rosasurfer\db\Connector;
use rosasurfer\exception\UnimplementedFeatureException;
use rosasurfer\exception\InfrastructureException;
use rosasurfer\exception\DatabaseException;


/**
 * PostgresConnector
 */
class PostgresConnector extends Connector {


   /** @var resource - connection handle */
   protected $connection;

   /** @var string[] */
   protected $config = [];

   /** @var string[] */
   protected $options = [];

   /** @var string - the resulting connection string as passed to pg_connect() */
   private $connectionStr;

   /** @var int - transaction nesting level */
   protected $transactions = 0;


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
         throw new InfrastructureException('Cannot connect to PostgreSQL server with connection string: "'.$connStr.'"', null, $ex);
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
    * Execute a SQL statement and return the result.
    *
    * @param  string $sql - SQL statement
    *
    * @return array['set' ] - a result resource (for SELECT statements only)
    *              ['rows'] - number of affected or modified rows (for SELECT/INSERT/UPDATE statements only)
    */
   public function executeSql($sql) {
      $result['set' ] = $this->executeRaw($sql);
      $result['rows'] = -1;                                    // supported but not yet implemented
      return $result;
   }


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  string $sql - SQL statement
    *
    * @return resource - a result resource
    */
   public function executeRaw($sql) {
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

      !$this->isConnected() && $this->connect();
      try {

         $result = pg_query($this->connection, $sql);          // automatically wraps multiple statements in a transaction

         if (!$result) {
            $message  = pg_last_error($this->connection);
            $message .= NL.' SQL: "'.$sql.'"';
            throw new DatabaseException($message, null, $ex);
         }
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
    * Start a new transaction.
    *
    * @return self
    */
   public function begin() {
      throw new UnimplementedFeatureException();
   }


   /**
    * Commit a pending transaction.
    *
    * @return self
    */
   public function commit() {
      throw new UnimplementedFeatureException();
   }


   /**
    * Roll back a pending transaction.
    *
    * @return self
    */
   public function rollback() {
      throw new UnimplementedFeatureException();
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
