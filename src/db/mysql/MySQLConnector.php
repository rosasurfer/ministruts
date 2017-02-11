<?php
namespace rosasurfer\db\mysql;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;


/**
 * MySQLConnector
 */
class MySQLConnector extends Connector {


   /** @var string - database system type */
   protected $type = 'mysql';

   /** @var string */
   protected $host;

   /** @var string */
   protected $port;

   /** @var string */
   protected $username;

   /** @var string */
   protected $password;

   /** @var string */
   protected $database;

   /** @var string[] */
   protected $options = [];

   /** @var resource - internal connection handle */
   protected $connection;

   /** @var int - transaction nesting level */
   protected $transactionLevel = 0;

   /** @var int - the last inserted row id (not reset between queries) */
   protected $lastInsertId = 0;


   /**
    * Constructor
    *
    * Create a new MySQLConnector instance.
    *
    * @param  string[] $config  - connection configuration
    * @param  string[] $options - additional MySQL typical options (default: none)
    */
   protected function __construct(array $config, array $options=[]) {
      $this->setHost    ($config['host'    ])
           ->setUsername($config['username'])
           ->setOptions ($options);
      if (isSet($config['password'])) $this->setPassword($config['password']);
      if (isSet($config['schema'  ])) $this->setDatabase($config['schema'  ]);

      parent::__construct();
   }


   /**
    * Set the database server's hostname, and port (if any).
    *
    * @param  string $hostname - format: "hostname[:port]"
    *
    * @return self
    */
   protected function setHost($hostname) {
      if (!is_string($hostname)) throw new IllegalTypeException('Illegal type of parameter $hostname: '.getType($hostname));
      if (!strLen($hostname))    throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (empty)');

      $host = $hostname;
      $port = null;

      if (strPos($host, ':') !== false) {
         list($host, $port) = explode(':', $host, 2);
         $host = trim($host);
         if (!strLen($host)) throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (empty host name)');

         $port = trim($port);
         if (!ctype_digit($port)) throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (not a port)');
         $port = (int) $port;
         if (!$port || $port > 65535) throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (illegal port)');
      }

      $this->host = $host;
      $this->port = $port;
      return $this;
   }


   /**
    * Set the username for the connection.
    *
    * @param  string $name
    *
    * @return self
    */
   protected function setUsername($name) {
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if (!strLen($name))    throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'" (empty)');

      $this->username = $name;
      return $this;
   }


   /**
    * Set the password for the connection (if any).
    *
    * @param  string $password - may be empty or NULL (no password)
    *
    * @return self
    */
   protected function setPassword($password) {
      if (is_null($password)) $password = '';
      else if (!is_string($password)) throw new IllegalTypeException('Illegal type of parameter $password: '.getType($password));

      $this->password = $password;
      return $this;
   }


   /**
    * Set the name of the default database schema to use.
    *
    * @param  string $name - schema name
    *
    * @return self
    */
   protected function setDatabase($name) {
      if (!is_null($name) && !is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if (!strLen($name))
         $name = null;

      $this->database = $name;
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
      $host = $this->host; if ($this->port) $host .= ':'.$this->port;
      $user = $this->username;
      $pass = $this->password;

      // connect
      try {                                                                   // CLIENT_FOUND_ROWS
         $this->connection  = mysql_connect($host, $user, $pass, $newLink=true/*, $flags=2 */);
         $this->connection || trigger_error(@$php_errormsg, E_USER_ERROR);
      }
      catch (IRosasurferException $ex) {
         throw $ex->addMessage('Can not connect to MySQL server on "'.$host.'"');
      }

      // set connection options
      try {
         foreach ($this->options as $option => $value) {
            if (strLen($value)) {
               if (strToLower($option) == 'charset') {
                  mysql_set_charset($value, $this->connection) || trigger_error(mysql_error($this->connection), E_USER_ERROR);
                  // synonymous with the sql statement "set character set {$value}"
               }
               else {
                  if (!is_numeric($value))
                     $value = "'$value'";
                  $sql = "set $option = $value";
                  $this->executeRaw($sql) || trigger_error(mysql_error($this->connection), E_USER_ERROR);
               }
            }
         }
      }
      catch (IRosasurferException $ex) {
         throw $ex->addMessage('Can not set system variable "'.$value.'"')->setCode(mysql_errno($this->connection));
      }

      // use specified database
      if ($this->database) {
         try {
            mysql_select_db($this->database, $this->connection) || trigger_error(mysql_error($this->connection), E_USER_ERROR);
         }
         catch (IRosasurferException $ex) {
            throw $ex->addMessage('Can not select database "'.$this->database.'"')->setCode(mysql_errno($this->connection));
         }
      }

      return $this;
      /*
      @see also: http://nl1.php.net/manual/en/mysql.constants.php#mysql.client-flags
                 http://nl1.php.net/manual/en/mysqli.real-connect.php
                 http://nl1.php.net/manual/en/mysqli.options.php

                                 #define CLIENT_LONG_PASSWORD          1             // new more secure passwords
                                 #define CLIENT_FOUND_ROWS             2             // found instead of affected rows
                                 #define CLIENT_LONG_FLAG              4             // get all column flags
                                 #define CLIENT_CONNECT_WITH_DB        8             // one can specify db on connect
                                 #define CLIENT_NO_SCHEMA             16             // don't allow database.table.column
      MYSQL_CLIENT_COMPRESS      #define CLIENT_COMPRESS              32             // can use compression protocol
                                 #define CLIENT_ODBC                  64             // ODBC client
                                 #define CLIENT_LOCAL_FILES          128             // enable LOAD DATA LOCAL
      MYSQL_CLIENT_IGNORE_SPACE  #define CLIENT_IGNORE_SPACE         256             // ignore spaces before '('
                                 #define CLIENT_CHANGE_USER          512             // support the mysql_change_user()    alt: #define CLIENT_PROTOCOL_41  512  // new 4.1 protocol
      MYSQL_CLIENT_INTERACTIVE   #define CLIENT_INTERACTIVE         1024             // this is an interactive client
      MYSQL_CLIENT_SSL           #define CLIENT_SSL                 2048             // switch to SSL after handshake
                                 #define CLIENT_IGNORE_SIGPIPE      4096             // ignore sigpipes
                                 #define CLIENT_TRANSACTIONS        8192             // client knows about transactions
                                 #define CLIENT_RESERVED           16384             // old flag for 4.1 protocol
                                 #define CLIENT_SECURE_CONNECTION  32768             // new 4.1 authentication
                                 #define CLIENT_MULTI_STATEMENTS   65536             // enable multi-stmt support
                                 #define CLIENT_MULTI_RESULTS     131072             // enable multi-results
                                 #define CLIENT_REMEMBER_OPTIONS  ((ulong) 1) << 31  // ?

      Not all of these may work or be meaningful, but CLIENT_FOUND_ROWS does, at least.
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
         mysql_close($tmp);
      }
      return $this;
   }


   /**
    * Whether or not the adapter currently is connected to the database.
    *
    * @return bool
    */
   public function isConnected() {
      return is_resource($this->connection);
   }


   /**
    * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return MySQLResult
    *
    * @throws DatabaseException in case of failure
    */
   public function query($sql) {
      $affectedRows = 0;
      $response = $this->executeRaw($sql, $affectedRows);
      if ($response === true)
         $response = null;
      return new MySQLResult($this, $sql, $response, $affectedRows, $this->lastInsertId);
   }


   /**
    * Execute a SQL statement and skip result set processing. This method should be used for SQL statements
    * not returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return int - Number of rows affected by the statement. Unreliable for specific UPDATE statements. Matched but
    *               unmodified rows are reported as changed if the connection flag CLIENT_FOUND_ROWS is set.
    *
    * @throws DatabaseException in case of failure
    */
   public function execute($sql) {
      //
      // TODO: check mysql_unbuffered_query() with mysql_free_result() for larger result sets
      //       @see  https://www.percona.com/blog/2006/06/26/handling-of-big-parts-of-data/
      //       @see  https://dev.mysql.com/doc/refman/5.7/en/mysql-use-result.html

      $affectedRows = 0;
      $response = $this->executeRaw($sql, $affectedRows);
      if (is_resource($response))
         mysql_free_result($response);
      return $affectedRows;
   }


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  _IN_  string $sql          - SQL statement
    * @param  _OUT_ int   &$affectedRows - A variable receiving the number of affected rows. Unreliable for specific UPDATE
    *                                      statements. Matched but unmodified rows are reported as changed if the connection
    *                                      flag CLIENT_FOUND_ROWS is set.
    *
    * @return resource|bool - a result resource or TRUE (depending on the statement type)
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
         $result  = mysql_query($sql, $this->connection);
         $result || trigger_error('SQL-Error '.mysql_errno($this->connection).': '.mysql_error($this->connection), E_USER_ERROR);
      }
      catch (IRosasurferException $ex) {
         throw $ex->addMessage('SQL: "'.$sql.'"')->setCode(mysql_errno($this->connection));
      }

      // determine number of rows affected by an INSERT/UPDATE/DELETE statement.
      if ($result === true)                                       // no result set
         $affectedRows = mysql_affected_rows($this->connection);

      // track last_insert_id
      if ($id = mysql_insert_id($this->connection))
         $this->lastInsertId = $id + mysql_affected_rows($this->connection) - 1;

      return $result;
   }
   /*
   drop:      insert_id=0   affected_rows=0   result_set=0   num_rows=0   info=""
   create:    insert_id=0   affected_rows=0   result_set=0   num_rows=0   info=""
   insert(4): insert_id=1   affected_rows=4   result_set=0   num_rows=0   info="Records: 4  Duplicates: 0  Warnings: 0"
   set:       insert_id=0   affected_rows=0   result_set=0   num_rows=0   info=""
   explain:   insert_id=0   affected_rows=1   result_set=1   num_rows=1   info=""
   update(0): insert_id=0   affected_rows=0   result_set=0   num_rows=0   info="Rows matched: 1  Changed: 0  Warnings: 0"
   select:    insert_id=0   affected_rows=2   result_set=1   num_rows=2   info=""
   update(1): insert_id=0   affected_rows=1   result_set=0   num_rows=0   info="Rows matched: 1  Changed: 1  Warnings: 0"
   select:    insert_id=0   affected_rows=1   result_set=1   num_rows=1   info=""
   select(0): insert_id=0   affected_rows=0   result_set=1   num_rows=0   info=""
   delete(2): insert_id=0   affected_rows=2   result_set=0   num_rows=0   info=""
   insert(2): insert_id=5   affected_rows=2   result_set=0   num_rows=0   info="Records: 2  Duplicates: 0  Warnings: 0"
   insert(1): insert_id=7   affected_rows=1   result_set=0   num_rows=0   info=""    <- single row inserts produce no info
   explain:   insert_id=0   affected_rows=1   result_set=1   num_rows=1   info=""
   */


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement. The value is not reset between queries.
    * (see the README)
    *
    * @return int - generated ID or 0 (zero) if no ID was yet generated
    */
   public function lastInsertId() {
      return (int) $this->lastInsertId;
   }


   /**
    * Start a new transaction. If there is already an active transaction only the transaction nesting level is increased.
    *
    * @return self
    */
   public function begin() {
      if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

      if (!$this->transactionLevel)
         $this->executeRaw('start transaction');

      $this->transactionLevel++;
      return $this;
   }


   /**
    * Commit an active transaction. If a nested transaction is active only the transaction nesting level is decreased.
    *
    * @return self
    */
   public function commit() {
      if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

      if (!$this->isConnected()) {
         trigger_error('Not connected', E_USER_WARNING);
      }
      else if (!$this->transactionLevel) {
         trigger_error('No database transaction to commit', E_USER_WARNING);
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

      if (!$this->isConnected()) {
         trigger_error('Not connected', E_USER_WARNING);
      }
      else if (!$this->transactionLevel) {
         trigger_error('No database transaction to roll back', E_USER_WARNING);
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
      if ($this->isConnected())
         return ($this->transactionLevel > 0);
      return false;
   }


   /**
    * Return the connector's internal connection object.
    *
    * @return resource - the internal connection handle
    */
   public function getInternalHandler() {
      if (is_resource($this->connection))
         return $this->connection;
      return null;
   }
}
