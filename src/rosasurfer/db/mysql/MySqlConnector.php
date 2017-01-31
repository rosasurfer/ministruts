<?php
namespace rosasurfer\db;

use rosasurfer\exception\DatabaseException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InfrastructureException;

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


/**
 * MySQLConnector
 */
class MySqlConnector extends Connector {


   /** @var bool */
   private static $logDebug;

   /** @var bool */
   private static $logInfo;

   /** @var bool */
   private static $logNotice;

   /** @var int - benötigt eine Query länger als hier angegeben, wird sie im Logelevel DEBUG geloggt */
   private static $maxQueryTime = 3;


   /**
    * Erzeugt eine neue MySQLConnector-Instanz.
    */
   protected function __construct() {
      $loglevel        = Logger::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      parent::__construct();
   }


   /**
    * Stellt die Verbindung zur Datenbank her.
    *
    * @return self
    */
   protected function connect() {
      $host = $this->host;
      if ($this->port)
         $host .= ':'.$this->port;

      try {
         $this->link = mysql_connect($host,
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
                  if (!mysql_set_charset($value, $this->link)) throw new InfrastructureException(mysql_error($this->link));
                  // synonymous with the sql statement "set character set {$value}"
               }
               else {
                  if (!is_numeric($value))
                     $value = "'$value'";
                  $sql = "set $option = $value";
                  if (!$this->queryRaw($sql)) throw new InfrastructureException(mysql_error($this->link));
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
         if ($this->database && !mysql_select_db($this->database, $this->link))
            throw new InfrastructureException(mysql_error($this->link));
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
    * Trennt die Verbindung des Connectors zur Datenbank.
    *
    * @return self
    */
   protected function disconnect() {
      if ($this->isConnected()) {
         mysql_close($this->link);
         $this->link = null;
      }
      return $this;
   }


   /**
    * Führt eine SQL-Anweisung aus und gibt das Ergebnis als Resource zurück.
    *
    * @param  string $sql - SQL-Anweisung
    *
    * @return array|bool - je nach Statement ein ResultSet oder ein Boolean
    */
   public function queryRaw($sql) {
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

      if (!$this->isConnected())
         $this->connect();

      // ggf. Startzeitpunkt speichern
      if (self::$logDebug)
         $start = microTime(true);

      // Statement ausführen
      $result = mysql_query($sql, $this->link);

      // ggf. Endzeitpunkt speichern
      if (self::$logDebug)
         $end = microTime(true);

      // Ergebnis auswerten
      if (!$result) {
         $message = ($errno = mysql_errno()) ? "SQL-Error $errno: ".mysql_error() : 'Can not connect to MySQL server';

         if (self::$logDebug)
            $message .= ' (taken time: '.round($end - $start, 4).' seconds)';

         $message .= "\nSQL: ".$sql;

         if ($errno == 1205)           // 1205: Lock wait timeout exceeded
            $message .= "\n\n".$this->printProcessList(true);

         if ($errno == 1213)           // 1213: Deadlock found when trying to get lock
            $message .= "\n\n".$this->printDeadlockStatus(true);

         throw new DatabaseException($message);
      }


      // Zu lang dauernde Statements loggen
      if (self::$logDebug) {
         $neededTime = round($end - $start, 4);
         if ($neededTime > self::$maxQueryTime)
            Logger::log('SQL statement took more than '.self::$maxQueryTime." seconds: $neededTime\n$sql", L_DEBUG);
           //Logger::log($this->printDeadlockStatus(true), L_DEBUG);
      }
      return $result;
   }


   /**
    * Führt eine SQL-Anweisung aus. Gibt das Ergebnis als mehrdimensionales Array zurück.
    *
    * @param  string $sql - SQL-Anweisung
    *
    * @return array['set' ] - das zurückgegebene Resultset (nur bei SELECT-Statement)
    *              ['rows'] - Anzahl der betroffenen Datensätze (nur bei SELECT/INSERT/UPDATE-Statement)
    */
   public function executeSql($sql) {
      $result = array('set'  => null,
                      'rows' => 0);

      $rawResult = $this->queryRaw($sql);

      if (is_resource($rawResult)) {
         $result['set']  = $rawResult;
         $result['rows'] = mysql_num_rows($rawResult);            // Anzahl der erhaltenen Zeilen
      }
      else {
         $sql = strToLower($sql);
         if (subStr($sql, 0, 6)=='insert' || subStr($sql, 0, 7)=='replace' || subStr($sql, 0, 6)=='update' || subStr($sql, 0, 6)=='delete') {
            $result['rows'] = mysql_affected_rows($this->link);   // Anzahl der modifizierten Zeilen
         }
      }
      return $result;
   }


   /**
    * Ersetzt in einem String mehrfache durch einfache Leerzeichen.
    *
    * @param  string $string - der zu bearbeitende String
    *
    * @return string
    */
   private static function stripDoubleSpaces($string) {
      if (!strLen($string))
         return $string;
      return preg_replace('/\s{2,}/', ' ', $string);
   }


   /**
    * Entfernt Zeilenumbrüche aus einem String und ersetzt sie mit Leerzeichen.  Mehrere aufeinanderfolgende
    * Zeilenumbrüche werden auf ein Leerzeichen reduziert.
    *
    * @param  string $string - der zu bearbeitende String
    *
    * @return string
    */
   public static function stripLineBreaks($string) {
      if ($string===null || $string==='')
         return $string;
      return str_replace(array("\r\n", "\r", "\n"),
                         array("\n"  , "\n", " " ),
                         $string);
   }


   /**
    * Liest die aktuell laufenden und für diese Connection sichtbaren Prozesse aus.
    *
    * @return array - Report mit Processlist-Daten
    */
   private function getProcessList() {
      $oldLogDebug = self::$logDebug;
      if ($oldLogDebug)
         self::$logDebug = false;

      $result = $this->queryRaw('show full processlist');

      self::$logDebug = $oldLogDebug;

      while ($data[] = mysql_fetch_assoc($result));
      array_pop($data);

      return $data;
   }


   /**
    * Hilfsfunktion zur formatierten Ausgabe der aktuell laufenden Prozesse.
    *
    * @param  bool $return - Ob die Ausgabe auf STDOUT (FALSE) oder als Rückgabewert der Funktion (TRUE) erfolgen soll.
    *                        (default: FALSE)
    *
    * @return string - wenn $return TRUE ist, die Ausgabe, andererseits NULL
    */
   private function printProcessList($return = false) {
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
         $p['Info'] = trim(self::stripDoubleSpaces($p['Info']));

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
    * Liest den aktuellen InnoDB-Status der Datenbank aus.
    *
    * @return string - Report mit Status-Daten
    */
   private function getInnoDbStatus() {
      $oldLogDebug = self::$logDebug;
      if ($oldLogDebug)
         self::$logDebug = false;

      // TODO: Vorsicht vor 1227: Access denied; you need the SUPER privilege for this operation
      $result = $this->queryRaw('show engine innodb status');

      self::$logDebug = $oldLogDebug;

      return trim(mysql_result($result, 0))."\n";
   }


   /**
    * Gibt eine aufbereitete und formatierte Version des aktuellen InnoDB-Deadlock-Status der Datenbank aus.
    *
    * @param  bool $return - Ob die Ausgabe auf STDOUT (FALSE) oder als Rückgabewert der Funktion (TRUE) erfolgen soll.
    *                        (default: FALSE)
    *
    * @return string - Rückgabewert, wenn $return TRUE ist, NULL andererseits
    */
   private function printDeadlockStatus($return = false) {
      $status = $this->getInnoDbStatus();

      // Datenformat: siehe Ende der Methode
      if (!preg_match('/\nLATEST DETECTED DEADLOCK\n-+\n(.+)\n-+\n/sU', $status, $match)) {
         if (strContains($status, "\nLATEST DETECTED DEADLOCK\n")) $message = "Error parsing InnoDB status:";
         else                                                      $message = "No deadlock infos found in InnoDB status:";
         Logger::log($message."\n\n".$status, L_ERROR);
         return null;
      }
      $status = $match[1];


      // Blöcke trennen
      $blocks = explode("\n*** ", $status);
      if (!$blocks) {
         Logger::log("Error parsing deadlock status\n\n".$status, L_ERROR);
         return null;
      }
      array_shift($blocks); // führende Timestring-Zeile entfernen

      $transactions = array();


      // Blöcke parsen
      foreach ($blocks as $block) {
         $block = trim($block);

         // Roll back block
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
            // Transaction block
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
                                    'query'       => trim(self::stripDoubleSpaces(self::stripLineBreaks($match[10]))),
                                    'locks'       => array(),
                                    );
               $transactions[$match[2]] = $transaction;
            }
            // Lock block
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


      // Transaktionen nach Transaktions-Nr. sortieren
      kSort($transactions);


      // Längen der Transaktionsanzeige ermitteln
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


      // Längen der Lockanzeige ermitteln
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


      // Transaktionsanzeige generieren
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


      // Lockanzeige generieren
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
}
