<?php
namespace rosasurfer\db;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InfrastructureException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\log\Logger;
use rosasurfer\util\Date;

use function rosasurfer\echoPre;
use function rosasurfer\strCompareI;
use function rosasurfer\strContains;
use function rosasurfer\strEndsWithI;
use function rosasurfer\strStartsWithI;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;
use const rosasurfer\NL;
use const rosasurfer\SECONDS;


/**
 * MysqlConnector
 */
class MysqlConnector extends Connector {


   /** @var bool */                             // logging
   private static $logDebug  = false;

   /** @var bool */
   private static $logInfo   = false;

   /** @var bool */
   private static $logNotice = false;

   /** @var int - Queries spending more time for completion than specified are logged with level L_DEBUG. */
   private static $maxQueryTime = 3 * SECONDS;

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

   /** @var resource - connection handle */
   protected $connection;

   /** @var int - last number of affected rows */
   protected $affectedRows = 0;

   /** @var int - transaction nesting level */
   protected $transactionLevel = 0;


   /**
    * Constructor
    *
    * Create a new MysqlConnector instance.
    *
    * @param  string[] $config  - connection configuration
    * @param  string[] $options - additional MySQL typical options (default: none)
    */
   protected function __construct(array $config, array $options=[]) {
      $loglevel        = Logger::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

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
   protected function connect() {
      $host = $this->host;
      if ($this->port)
         $host .= ':'.$this->port;
      try {
         $this->connection = mysql_connect($host,
                                           $this->username,
                                           $this->password,
                                           $createNewLink=true,
                                           $flags=2               // CLIENT_FOUND_ROWS
                                           );
      }
      catch (\Exception $ex) {
         throw new InfrastructureException('Can not connect to MySQL server on "'.$host.'"', null, $ex);
      }

      try {
         foreach ($this->options as $option => $value) {
            if (strLen($value)) {
               if (strCompareI($option, 'charset')) {
                  if (!mysql_set_charset($value, $this->connection)) throw new InfrastructureException(mysql_error($this->connection));
                  // synonymous with the sql statement "set character set {$value}"
               }
               else {
                  if (!is_numeric($value))
                     $value = "'$value'";
                  $sql = "set $option = $value";
                  if (!$this->executeRaw($sql)) throw new InfrastructureException(mysql_error($this->connection));
               }
            }
         }
      }
      catch (\Exception $ex) {
         if (!$ex instanceof InfrastructureException)
            $ex = new InfrastructureException('Can not set system variable "'.$sql.'"', null, $ex);
         throw $ex;
      }

      try {
         if ($this->database && !mysql_select_db($this->database, $this->connection))
            throw new InfrastructureException(mysql_error($this->connection));
      }
      catch (\Exception $ex) {
         if (!$ex instanceof InfrastructureException)
            $ex = new InfrastructureException('Can not select database "'.$this->database.'"', null, $ex);
         throw $ex;
      }

      return $this;
      /*                         #define CLIENT_LONG_PASSWORD          1             // new more secure passwords
                                 #define CLIENT_FOUND_ROWS             2             // found instead of affected rows
                                 #define CLIENT_LONG_FLAG              4             // get all column flags
                                 #define CLIENT_CONNECT_WITH_DB        8             // one can specify db on connect
                                 #define CLIENT_NO_SCHEMA             16             // don't allow database.table.column
      MYSQL_CLIENT_COMPRESS      #define CLIENT_COMPRESS              32             // can use compression protocol
                                 #define CLIENT_ODBC                  64             // ODBC client
                                 #define CLIENT_LOCAL_FILES          128             // can use LOAD DATA LOCAL
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
   protected function disconnect() {
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
   protected function isConnected() {
      return ($this->connection != null);
   }


   /**
    * Execute a SQL statement and return the result. This method should be used if the SQL statement returns rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return Result
    */
   public function query($sql) {
      $response = $this->executeRaw($sql);
      if ($response === true)
         $response = null;
      return new MysqlResult($this, $sql, $response);
   }


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  string $sql - SQL statement
    *
    * @return resource|bool - a result resource or TRUE (depending on the statement type)
    */
   public function executeRaw($sql) {
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

      if (!$this->isConnected())
         $this->connect();
      self::$logDebug && $startTime=microTime(true);

      // execute statement (seems it never triggers an error on invalid SQL but instead only returns FALSE)
      $result = mysql_query($sql, $this->connection);

      if (!$result) {
         $message = ($errno=mysql_errno()) ? 'SQL-Error '.$errno.': '.mysql_error() : 'Can not connect to MySQL server';
                             $message .= NL.' SQL: "'.$sql.'"';
         if ($errno == 1205) $message .= NL.NL.$this->printProcessList   ($return=true); // Lock wait timeout exceeded
         if ($errno == 1213) $message .= NL.NL.$this->printDeadlockStatus($return=true); // Deadlock found when trying to get lock
         throw new DatabaseException($message);
      }

      // Get number of rows affected by the last INSERT/UPDATE/DELETE/REPLACE statement. The PHP mysqlnd driver updates this
      // value for all SQL statements, special care has to be taken to not lose the original correct value.
      $s = strToLower(subStr(trim($sql), 0, 7));
      if (strPos($s, 'insert')===0 || strPos($s, 'update')===0 || strPos($s, 'delete')===0 || strPos($s, 'replace')===0) {
         $this->affectedRows = mysql_affected_rows($this->connection);
      }

      // L_DEBUG: log statements exceeding $maxQueryTime
      if (self::$logDebug) {
         $endTime   = microTime(true);
         $spentTime = round($endTime-$startTime, 4);
         if ($spentTime > self::$maxQueryTime)
            Logger::log('SQL statement took more than '.self::$maxQueryTime.' seconds: '.$spentTime.NL.$sql, L_DEBUG);
           //Logger::log($this->printDeadlockStatus(true), L_DEBUG);
      }
      return $result;
   }


   /**
    * Return the number of rows affected by the last INSERT/UPDATE/DELETE/REPLACE statement. REPLACE is a MySQL extension
    * standing for a DELETE followed by an INSERT.
    *
    * @return int
    */
   public function affectedRows() {
      return (int) $this->affectedRows;
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
    * Read the currently running and visible processes.
    *
    * @return array[] - process data
    */
   private function getProcessList() {
      ($oldLogDebug=self::$logDebug) && self::$logDebug = false;

      $result = $this->executeRaw('show full processlist');

      self::$logDebug = $oldLogDebug;

      while ($data[] = mysql_fetch_assoc($result)) {
      }
      array_pop($data);

      return $data;
   }


   /**
    * Helper: Format a list of currently running processes.
    *
    * @param  bool $return - Whether to return the list as a regular return value (TRUE) or to print the list to STDOUT
    *                        (FALSE).
    *
    * @return string - string or NULL if parameter $return is FALSE
    */
   private function printProcessList($return) {
      $list = $this->getProcessList();

      $lengthId      = strLen('Id'     );
      $lengthUser    = strLen('User'   );
      $lengthHost    = strLen('Host'   );
      $lengthDb      = strLen('db'     );
      $lengthCommand = strLen('Command');
      $lengthTime    = strLen('Time'   );
      $lengthState   = strLen('State'  );
      $lengthInfo    = strLen('Info'   );

      foreach ($list as &$p) {
         if (($i=striPos($p['Host'], '.localdomain:')) || ($i=striPos($p['Host'], ':')))
            $p['Host'] = subStr($p['Host'], 0, $i);
         $p['Info'] = trim($this->collapseSpaces($p['Info']));

         $lengthId      = max($lengthId     , strLen($p['Id'     ]));
         $lengthUser    = max($lengthUser   , strLen($p['User'   ]));
         $lengthHost    = max($lengthHost   , strLen($p['Host'   ]));
         $lengthDb      = max($lengthDb     , strLen($p['db'     ]));
         $lengthCommand = max($lengthCommand, strLen($p['Command']));
         $lengthTime    = max($lengthTime   , strLen($p['Time'   ]));
         $lengthState   = max($lengthState  , strLen($p['State'  ]));
         $lengthInfo    = max($lengthInfo   , strLen($p['Info'   ]));
      }

      // title line
      $length = $lengthId+2+$lengthUser+2+$lengthHost+2+$lengthDb+2+$lengthCommand+2+$lengthTime+2+$lengthState+2+$lengthInfo;
      if ($length > 160) {
         $lengthInfo -= ($length - 160);
         $length = 160;
      }
      $lPre   = $lPost = ($length-strLen(' Process List '))/2;
      $string = str_repeat('_', (int)floor($lPre)).' Process List '.str_repeat('_', (int)ceil($lPost))."\n";

      // header line
      $string .=    str_pad('Id'     , $lengthId     , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('User'   , $lengthUser   , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Host'   , $lengthHost   , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Db'     , $lengthDb     , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Command', $lengthCommand, ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Time'   , $lengthTime   , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('State'  , $lengthState  , ' ', STR_PAD_RIGHT)
              .'  '.        'Info'."\n";

      // data lines
      foreach ($list as &$p) {
         $string .=    str_pad($p['Id'     ], $lengthId     , ' ', STR_PAD_LEFT )
                 .'  '.str_pad($p['User'   ], $lengthUser   , ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($p['Host'   ], $lengthHost   , ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($p['db'     ], $lengthDb     , ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($p['Command'], $lengthCommand, ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($p['Time'   ], $lengthTime   , ' ', STR_PAD_LEFT )
                 .'  '.str_pad($p['State'  ], $lengthState  , ' ', STR_PAD_RIGHT)
                 .'  '.subStr ($p['Info'], 0, $lengthInfo)."\n";
      }

      // bottom separator line
      $string .= str_repeat('_', $length)."\n";

      if ($return)
         return $string;

      echoPre($list);
      return null;
   }


   /**
    * Read the current MySQL InnoDB status.
    *
    * @return string - status report
    */
   private function getInnoDbStatus() {
      ($oldLogDebug=self::$logDebug) && self::$logDebug = false;

      // TODO: special attention: 1227: Access denied; you need the SUPER privilege for this operation
      $result = $this->executeRaw('show engine innodb status');

      self::$logDebug = $oldLogDebug;

      return trim(mysql_result($result, 0)).NL;
   }


   /**
    * Return a formatted version of the current MySQL InnoDB deadlock status of the database.
    *
    * @param  bool $return - Whether to return the result as a regular return value (TRUE) or to print the result to STDOUT
    *                        (FALSE).
    *
    * @return string - string or NULL if parameter $return is FALSE
    */
   private function printDeadlockStatus($return) {
      $status = $this->getInnoDbStatus();

      // source data format: @see end of method
      if (!preg_match('/\nLATEST DETECTED DEADLOCK\n-+\n(.+)\n-+\n/sU', $status, $match)) {
         if (strContains($status, "\nLATEST DETECTED DEADLOCK\n")) $message = "Error parsing InnoDB status:";
         else                                                      $message = "No deadlock infos found in InnoDB status:";
         Logger::log($message."\n\n".$status, L_ERROR);
         return null;
      }
      $status = $match[1];


      // separate blocks
      $blocks = explode("\n*** ", $status);
      if (!$blocks) {
         Logger::log("Error parsing deadlock status\n\n".$status, L_ERROR);
         return null;
      }
      array_shift($blocks);                     // skip leading timestamp row

      $transactions = array();


      // parse blocks
      foreach ($blocks as $block) {
         $block = trim($block);

         // "roll back" block
         if (strStartsWithI($block, 'WE ROLL BACK TRANSACTION ')) {
            if (!preg_match('/^WE ROLL BACK TRANSACTION \((\d+)\)$/i', $block, $match)) {
               Logger::log("Error parsing deadlock status roll back block\n\n".$block, L_ERROR);
               return null;
            }
            foreach ($transactions as &$transaction) {
               $transaction['victim'] = ($transaction['no']==(int) $match[1]) ? 'Yes':'No';
            }
         }
         else {
            $lines = explode("\n", $block);
            if (sizeOf($lines) < 2) {
               Logger::log("Error parsing deadlock status block\n\n".$block, L_ERROR);
               return null;
            }
            // "transaction" block
            if (strStartsWithI($lines[1], 'TRANSACTION ')) {
               if (!preg_match('/\s*\((\d+)\).*\nTRANSACTION \d+ (\d+), ACTIVE (\d+) sec.+\n(LOCK WAIT )?(\d+) lock struct\(s\), heap size \d+(?:, undo log entries (\d+))?\nMySQL thread id (\d+), query id \d+ (\S+) \S+ (\S+).+?\n(.+)$/is', $block, $match)) {
                  Logger::log("Error parsing deadlock status transaction block\n\n".$block, L_ERROR);
                  return null;
               }

               $transaction = array('no'          => (int) $match[1],
                                    'transaction' => (int) $match[2],
                                    'time'        => (int) $match[3],
                                    'structs'     => (int) $match[5],
                                    'undo'        => (int) $match[6],
                                    'connection'  => (int) $match[7],
                                    'host'        =>       $match[8],
                                    'user'        =>       $match[9],
                                    'query'       => trim($this->collapseSpaces($this->joinLines($match[10]))),
                                    'locks'       => array(),
                                    );
               $transactions[$match[2]] = $transaction;
            }
            // "lock" block
            elseif (strStartsWithI($lines[1], 'RECORD LOCKS ')) {
               if (!preg_match('/\s*\((\d+)\).*\nRECORD LOCKS space id \d+ page no \d+ n bits \d+ index `(\S+)` of table `([^\/]+)\/([^`]+)` trx id \d+ (\d+) lock(_| )mode (S|X)( locks (.+))?( waiting)?/i', $block, $match)) {
                  Logger::log("Error parsing deadlock status lock block\n\n".$block, L_ERROR);
                  return null;
               }
               $lock = array('no'          => (int) $match[1],
                             'index'       =>       $match[2],
                             'db'          =>       $match[3],
                             'table'       =>       $match[4],
                             'transaction' => (int) $match[5],
                             'mode'        => strToUpper($match[7]),
                             'special'     => ($special = isSet($match[9]) ? $match[9] : ''),
                             'waiting'     => (string)(int) (strEndsWithI($special, ' waiting') || (isSet($match[10]) && $match[10]==' waiting')),
                             );
               if (strEndsWithI($special, ' waiting'))
                  $lock['special'] = subStr($special, 0, strLen($special)-8);
               $transactions[$match[5]]['locks'][] = $lock;
            }
            else {
               Logger::log("Error parsing deadlock status block\n\n".$block, L_ERROR);
               return null;
            }
         }
      }


      // sort transactions by transaction number
      kSort($transactions);


      // resolve length of lines to display
      $lengthId     = strLen('Id'    );
      $lengthUser   = strLen('User'  );
      $lengthHost   = strLen('Host'  );
      $lengthVictim = strLen('Victim');
      $lengthTime   = strLen('Time'  );
      $lengthUndo   = strLen('Undo'  );
      $lengthQuery  = strLen('Query' );

      foreach ($transactions as $t) {
         $lengthId    = max($lengthId   , strLen($t['connection']));
         $lengthUser  = max($lengthUser , strLen($t['user'      ]));
         $lengthHost  = max($lengthHost , strLen($t['host'      ]));
         $lengthTime  = max($lengthTime , strLen($t['time'      ]));
         $lengthUndo  = max($lengthUndo , strLen($t['undo'      ]));
         $lengthQuery = max($lengthQuery, strLen($t['query'     ]));
      }


      // resolve lengths of "lock" display
      $lengthWaiting = strLen('Waiting');
      $lengthMode    = strLen('Mode'   );
      $lengthDb      = strLen('DB'     );
      $lengthTable   = strLen('Table'  );
      $lengthIndex   = strLen('Index'  );
      $lengthSpecial = strLen('Special');

      foreach ($transactions as $t) {
         foreach ($t['locks'] as $l) {
            $lengthDb      = max($lengthDb     , strLen($l['db'     ]));
            $lengthTable   = max($lengthTable  , strLen($l['table'  ]));
            $lengthIndex   = max($lengthIndex  , strLen($l['index'  ]));
            $lengthSpecial = max($lengthSpecial, strLen($l['special']));
         }
      }


      // generate "transaction" display
      // top separator line
      $lengthT = $lengthId+2+$lengthUser+2+$lengthHost+2+$lengthVictim+2+$lengthTime+2+$lengthUndo+2+$lengthQuery;
      $lengthL = $lengthId+2+$lengthWaiting+2+$lengthMode+2+$lengthDb+2+$lengthTable+2+$lengthIndex+2+$lengthSpecial;
      if ($lengthT > 180) {
         $lengthQuery -= ($lengthT - 180);
         $lengthT = 180;
      }
      $lPre   = $lPost = ($lengthL-strLen(' Deadlock Transactions '))/2;
      $lPost += $lengthT - $lengthL;
      $string = str_repeat('_', (int)floor($lPre)).' Deadlock Transactions '.str_repeat('_', (int)ceil($lPost))."\n";

      // header line
      $string .=    str_pad('ID'    , $lengthId    , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('User'  , $lengthUser  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Host'  , $lengthHost  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Victim', $lengthVictim, ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Time'  , $lengthTime  , ' ', STR_PAD_LEFT )
              .'  '.str_pad('Undo'  , $lengthUndo  , ' ', STR_PAD_LEFT )
              .'  '.        'Query'."\n";

      // data lines
      foreach ($transactions as $t) {
         $string .=    str_pad($t['connection'], $lengthId    , ' ', STR_PAD_LEFT )
                 .'  '.str_pad($t['user'      ], $lengthUser  , ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($t['host'      ], $lengthHost  , ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($t['victim'    ], $lengthVictim, ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($t['time'      ], $lengthTime  , ' ', STR_PAD_LEFT )
                 .'  '.str_pad($t['undo'      ], $lengthUndo  , ' ', STR_PAD_LEFT )
                 .'  '.subStr ($t['query'], 0, $lengthQuery)."\n";
      }

      // bottom separator line
      $string .= str_repeat('_', $lengthT)."\n";


      // generate "lock" display
      // top separator line
      $lPre    = $lPost = ($lengthL-strLen(' Deadlock Locks '))/2;
      $string .= "\n\n\n".str_repeat('_', (int)floor($lPre)).' Deadlock Locks '.str_repeat('_', (int)ceil($lPost))."\n";

      // header line
      $string .=    str_pad('ID'     , $lengthId     , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Waiting', $lengthWaiting, ' ', STR_PAD_LEFT )
              .'  '.str_pad('Mode'   , $lengthMode   , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('DB'     , $lengthDb     , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Table'  , $lengthTable  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Index'  , $lengthIndex  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Special', $lengthSpecial, ' ', STR_PAD_RIGHT)."\n";

      // data lines
      foreach ($transactions as $t) {
         foreach ($t['locks'] as $l) {
            $string .=    str_pad($t['connection'], $lengthId     , ' ', STR_PAD_LEFT )
                    .'  '.str_pad($l['waiting'   ], $lengthWaiting, ' ', STR_PAD_LEFT )
                    .'  '.str_pad($l['mode'      ], $lengthMode   , ' ', STR_PAD_RIGHT)
                    .'  '.str_pad($l['db'        ], $lengthDb     , ' ', STR_PAD_RIGHT)
                    .'  '.str_pad($l['table'     ], $lengthTable  , ' ', STR_PAD_RIGHT)
                    .'  '.str_pad($l['index'     ], $lengthIndex  , ' ', STR_PAD_RIGHT)
                    .'  '.str_pad($l['special'   ], $lengthSpecial, ' ', STR_PAD_RIGHT)."\n";
         }
      }

      // bottom separator line
      $string .= str_repeat('_', $lengthL)."\n";

      if ($return)
         return $string;

      echoPre($string);
      return null;
   }
/*
$status = "
------------------------
LATEST DETECTED DEADLOCK
------------------------
090213 20:12:02
*** (1) TRANSACTION:
TRANSACTION 0 56471972, ACTIVE 1 sec, process no 25931, OS thread id 81980336 starting index read
mysql tables in use 2, locked 2
LOCK WAIT 4 lock struct(s), heap size 320, undo log entries 1
MySQL thread id 279372, query id 1830074 server.localdomain 0.0.0.0 database Updating
update v_view
      set registrations = registrations + if(@delete  , -1, if(@undelete  , +1, 0)),
          activations   = activations   + if(@activate, +1, if(@deactivate, -1, 0))
      where date = date(old.created)
*** (1) WAITING FOR THIS LOCK TO BE GRANTED:
RECORD LOCKS space id 0 page no 450 n bits 408 index `PRIMARY` of table `database/v_view` trx id 0 56471972 lock_mode X locks rec but not gap waiting
Record lock, heap no 341 PHYSICAL RECORD: n_fields 5; compact format; info bits 0
 0: len 3; hex 8fb24d; asc   M;; 1: len 6; hex 0000035db1a0; asc    ]  ;; 2: len 7; hex 00000001c910a9; asc        ;; 3: len 4; hex 000010af; asc     ;; 4: len 4; hex 00000808; asc     ;;

*** (2) TRANSACTION:
TRANSACTION 0 56471970, ACTIVE 2 sec, process no 25931, OS thread id 120036272 starting index read, thread declared inside InnoDB 0
mysql tables in use 2, locked 2
11 lock struct(s), heap size 1024, undo log entries 1
MySQL thread id 279368, query id 1830052 server.localdomain 0.0.0.0 database executing
insert into v_view (date, registrations, activations)
      select date(new.created)              as 'date',
             1                              as 'registrations',
             new.orderactivated is not null as 'activations'
         from dual
         where new.deleted is null
      on duplicate key update registrations = registrations + 1,
                              activations   = activations + (new.orderactivated is not null)
*** (2) HOLDS THE LOCK(S):
RECORD LOCKS space id 0 page no 450 n bits 408 index `PRIMARY` of table `database/v_view` trx id 0 56471970 lock mode S
Record lock, heap no 341 PHYSICAL RECORD: n_fields 5; compact format; info bits 0
 0: len 3; hex 8fb24d; asc   M;; 1: len 6; hex 0000035db1a0; asc    ]  ;; 2: len 7; hex 00000001c910a9; asc        ;; 3: len 4; hex 000010af; asc     ;; 4: len 4; hex 00000808; asc     ;;

*** (2) WAITING FOR THIS LOCK TO BE GRANTED:
RECORD LOCKS space id 0 page no 450 n bits 408 index `PRIMARY` of table `database/v_view` trx id 0 56471970 lock_mode X locks rec but not gap waiting
Record lock, heap no 341 PHYSICAL RECORD: n_fields 5; compact format; info bits 0
 0: len 3; hex 8fb24d; asc   M;; 1: len 6; hex 0000035db1a0; asc    ]  ;; 2: len 7; hex 00000001c910a9; asc        ;; 3: len 4; hex 000010af; asc     ;; 4: len 4; hex 00000808; asc     ;;

*** WE ROLL BACK TRANSACTION (2)
------------
TRANSACTIONS
------------";
*/

   /**
    * Replace line breaks with spaces.
    *
    * @param  string $string
    *
    * @return string
    */
   private function joinLines($string) {
      if (!strLen($string))
         return $string;
      return str_replace(["\r\n", "\r", "\n"], ' ', $string);
   }


   /**
    * Replace multiple spaces with a single one.
    *
    * @param  string $string
    *
    * @return string
    */
   private function collapseSpaces($string) {
      if (!strLen($string))
         return $string;
      return preg_replace('/\s{2,}/', ' ', $string);
   }
}
