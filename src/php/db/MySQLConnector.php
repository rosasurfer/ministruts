<?
/**
 *
 *
 * MySQLConnector
 */
final class MySQLConnector extends DB {


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice,
                  /*int*/  $maxQueryTime = 3; // benötigt eine Query länger als hier angegeben, wird sie im Logelevel DEBUG geloggt


   /**
    * Erzeugt eine neue MySQLConnector-Instanz.
    */
   public function __construct() {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      parent ::__construct();
   }


   /**
    * Stellt die Verbindung zur Datenbank her.
    *
    * @return MySQLConnector
    */
   protected function connect() {
      $host = $this->host;
      if ($this->port)
         $host .= ':'.$this->port;

      $this->link = mysql_connect($host,
                                  $this->username,
                                  $this->password,
                                  true,
                                  2             // 2 = CLIENT_FOUND_ROWS
                                  );

      if (!$this->link)
         throw new InfrastructureException('Can not connect to MySQL server: '.mysql_error($this->link));

      if ($this->database && !mysql_select_db($this->database, $this->link))
         throw new InfrastructureException('Can not select database '.$this->database.': '.mysql_error($this->link));

      return $this;
   }


   /**
    * Trennt die Verbindung des Connectors zur Datenbank.
    *
    * @return MySQLConnector
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
    * @param string $sql - SQL-Anweisung
    *
    * @return mixed - je nach Statement ein ResultSet oder ein Boolean
    */
   public function queryRaw($sql) {
      if ($sql!==(string)$sql) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

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
         $error = ($errno = mysql_errno()) ? "SQL-Error $errno: ".mysql_error() : 'Can not connect to MySQL server';
         if (self::$logDebug)
            $error .= ' (taken time: '.round($end - $start, 4).' seconds)';

         $message = $error."\nSQL: ".$sql;

         if ($errno == 1213) {      // 1213: Deadlock found when trying to get lock
            $deadlockStatus = $this->printDeadlockStatus(true);
            $message .= "\n\n".$deadlockStatus;
         }

         throw new DatabaseException($message);
      }


      // Zu lang dauernde Statements loggen
      if (self::$logDebug) {
         $neededTime = round($end - $start, 4);
         if ($neededTime > self::$maxQueryTime) {
            Logger ::log('SQL statement took more than '.self::$maxQueryTime." seconds: $neededTime\n$sql", L_DEBUG, __CLASS__);
         }
      }

      return $result;
   }


   /**
    * Führt eine SQL-Anweisung aus. Gibt das Ergebnis als Array zurück.
    *
    * @param string $sql - SQL-Anweisung
    *
    * @return array['set' ] - das zurückgegebene Resultset (bei SELECT)
    *              ['rows'] - Anzahl der betroffenen Datensätze (bei SELECT/INSERT/UPDATE)
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
    * @param bool $return - Ob die Ausgabe auf STDOUT (FALSE) oder als Rückgabewert der Funktion (TRUE) erfolgen soll.
    *                       (default: FALSE)
    *
    * @return string - Rückgabewert, wenn $return TRUE ist, NULL andererseits
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

      foreach ($list as &$row) {
         $row['Info'] = trim(String ::stripDoubleSpaces($row['Info']));

         $lengthId      = max($lengthId     , strLen($row['Id'     ]));
         $lengthUser    = max($lengthUser   , strLen($row['User'   ]));
         $lengthHost    = max($lengthHost   , strLen($row['Host'   ]));
         $lengthDb      = max($lengthDb     , strLen($row['db'     ]));
         $lengthCommand = max($lengthCommand, strLen($row['Command']));
         $lengthTime    = max($lengthTime   , strLen($row['Time'   ]));
         $lengthState   = max($lengthState  , strLen($row['State'  ]));
         $lengthInfo    = max($lengthInfo   , strLen($row['Info'   ]));
      }

      // top separator line
      $string = '+-'.str_repeat('-', $lengthId     )
              .'-+-'.str_repeat('-', $lengthUser   )
              .'-+-'.str_repeat('-', $lengthHost   )
              .'-+-'.str_repeat('-', $lengthDb     )
              .'-+-'.str_repeat('-', $lengthCommand)
              .'-+-'.str_repeat('-', $lengthTime   )
              .'-+-'.str_repeat('-', $lengthState  )
              .'-+-'.str_repeat('-', $lengthInfo   )."-+\n";

      // header line
      $string .= '| '.str_pad('Id'     , $lengthId     , ' ', STR_PAD_RIGHT)
               .' | '.str_pad('User'   , $lengthUser   , ' ', STR_PAD_RIGHT)
               .' | '.str_pad('Host'   , $lengthHost   , ' ', STR_PAD_RIGHT)
               .' | '.str_pad('db'     , $lengthDb     , ' ', STR_PAD_RIGHT)
               .' | '.str_pad('Command', $lengthCommand, ' ', STR_PAD_RIGHT)
               .' | '.str_pad('Time'   , $lengthTime   , ' ', STR_PAD_RIGHT)
               .' | '.str_pad('State'  , $lengthState  , ' ', STR_PAD_RIGHT)
               .' | '.str_pad('Info'   , $lengthInfo   , ' ', STR_PAD_RIGHT)." |\n";

      // separator line
      $string .= '+-'.str_repeat('-', $lengthId     )
               .'-+-'.str_repeat('-', $lengthUser   )
               .'-+-'.str_repeat('-', $lengthHost   )
               .'-+-'.str_repeat('-', $lengthDb     )
               .'-+-'.str_repeat('-', $lengthCommand)
               .'-+-'.str_repeat('-', $lengthTime   )
               .'-+-'.str_repeat('-', $lengthState  )
               .'-+-'.str_repeat('-', $lengthInfo   )."-+\n";

      // data lines
      foreach ($list as $key => &$row) {
         $string .= '| '.str_pad($row['Id'     ], $lengthId     , ' ', STR_PAD_LEFT )
                  .' | '.str_pad($row['User'   ], $lengthUser   , ' ', STR_PAD_RIGHT)
                  .' | '.str_pad($row['Host'   ], $lengthHost   , ' ', STR_PAD_RIGHT)
                  .' | '.str_pad($row['db'     ], $lengthDb     , ' ', STR_PAD_RIGHT)
                  .' | '.str_pad($row['Command'], $lengthCommand, ' ', STR_PAD_RIGHT)
                  .' | '.str_pad($row['Time'   ], $lengthTime   , ' ', STR_PAD_LEFT )
                  .' | '.str_pad($row['State'  ], $lengthState  , ' ', STR_PAD_RIGHT)
                  .' | '.str_pad($row['Info'   ], $lengthInfo   , ' ', STR_PAD_RIGHT)." |\n";
      }

      // bottom separator line
      $string .= '+-'.str_repeat('-', $lengthId     )
               .'-+-'.str_repeat('-', $lengthUser   )
               .'-+-'.str_repeat('-', $lengthHost   )
               .'-+-'.str_repeat('-', $lengthDb     )
               .'-+-'.str_repeat('-', $lengthCommand)
               .'-+-'.str_repeat('-', $lengthTime   )
               .'-+-'.str_repeat('-', $lengthState  )
               .'-+-'.str_repeat('-', $lengthInfo   )."-+\n";

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
    * @param bool $return - Ob die Ausgabe auf STDOUT (FALSE) oder als Rückgabewert der Funktion (TRUE) erfolgen soll.
    *                       (default: FALSE)
    *
    * @return string - Rückgabewert, wenn $return TRUE ist, NULL andererseits
    */
   private function printDeadlockStatus($return = false) {
      $status = $this->getInnoDbStatus();

      // Datenformat: siehe Ende der Methode
      if (!preg_match('/\nLATEST DETECTED DEADLOCK\n-+\n(.+)\n-+\n/sU', $status, $match))
         return null;
      $status = $match[1];

      // Blöcke trennen
      $blocks = explode("\n*** ", $status);
      if (!$blocks) {
         self::$logNotice && Logger ::log("Error parsing deadlock status\n\n".$status, L_NOTICE, __CLASS__);
         return null;
      }
      array_shift($blocks); // führende Timestring-Zeile entfernen

      $transactions = array();


      // Blöcke parsen
      foreach ($blocks as $block) {
         $block = trim($block);

         // Roll back block
         if (String ::startsWith($block, 'WE ROLL BACK TRANSACTION ', true)) {
            if (!preg_match('/^WE ROLL BACK TRANSACTION \((\d+)\)$/i', $block, $match)) {
               self::$logNotice && Logger ::log("Error parsing deadlock status roll back block\n\n".$block, L_NOTICE, __CLASS__);
               return null;
            }
            foreach ($transactions as &$transaction) {
               $transaction['victim'] = ($transaction['no']==(int) $match[1]) ? 'Yes':'No';
            }
         }
         else {
            $lines = explode("\n", $block);
            if (sizeOf($lines) < 2) {
               self::$logNotice && Logger ::log("Error parsing deadlock status block\n\n".$block, L_NOTICE, __CLASS__);
               return null;
            }
            // Transaction block
            if (String ::startsWith($lines[1], 'TRANSACTION ', true)) {
               if (!preg_match('/\s*\((\d+)\).*\nTRANSACTION \d+ (\d+), ACTIVE (\d+) sec.+\n(LOCK WAIT )?(\d+) lock struct.+, undo log entries (\d+).*\nMySQL thread id (\d+), query id \d+ (\S+) \S+ (\S+).+?\n(.+)$/is', $block, $match)) {
                  self::$logNotice && Logger ::log("Error parsing deadlock status transaction block\n\n".$block, L_NOTICE, __CLASS__);
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
                                    'query'       => trim(String ::stripDoubleSpaces(String ::stripLineBreaks($match[10]))),
                                    'locks'       => array(),
                                    );
               $transactions[$match[2]] = $transaction;
            }
            // Lock block
            elseif (String ::startsWith($lines[1], 'RECORD LOCKS ', true)) {
               if (!preg_match('/\s*\((\d+)\).*\nRECORD LOCKS space id \d+ page no \d+ n bits \d+ index `(\S+)` of table `([^\/]+)\/([^`]+)` trx id \d+ (\d+) lock(_| )mode (S|X) locks\s(.+(waiting)?)/i', $block, $match)) {
                  self::$logNotice && Logger ::log("Error parsing deadlock status lock block\n\n".$block, L_NOTICE, __CLASS__);
                  return null;
               }
               $lock = array('no'          => (int) $match[1],
                             'index'       =>       $match[2],
                             'db'          =>       $match[3],
                             'table'       =>       $match[4],
                             'transaction' => (int) $match[5],
                             'mode'        => strToUpper($match[7]),
                             'special'     => str_replace(' waiting', '', $match[8]),
                             'waiting'     => (int) String ::endsWith($match[8], ' waiting', true),
                             );
               $transactions[$match[5]]['locks'][] = $lock;
            }
            else {
               self::$logNotice && Logger ::log("Error parsing deadlock status block\n\n".$block, L_NOTICE, __CLASS__);
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

      foreach ($transactions as &$t) {
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

      foreach ($transactions as &$t) {
         foreach ($t['locks'] as &$l) {
            $lengthDb      = max($lengthDb     , strLen($l['db'     ]));
            $lengthTable   = max($lengthTable  , strLen($l['table'  ]));
            $lengthIndex   = max($lengthIndex  , strLen($l['index'  ]));
            $lengthSpecial = max($lengthSpecial, strLen($l['special']));
         }
      }


      // Transaktionsanzeige generieren
      // top separator line
      $lengthL = $lengthId+2+$lengthWaiting+2+$lengthMode+2+$lengthDb+2+$lengthTable+2+$lengthIndex+2+$lengthSpecial;
      $lengthT = $lengthId+2+$lengthUser+2+$lengthHost+2+$lengthVictim+2+$lengthTime+2+$lengthUndo+2+$lengthQuery;
      if ($lengthT > 180) {
         $lengthQuery -= ($lengthT - 180);
         $lengthT = 180;
      }
      $lPre   = $lPost = ($lengthL-strLen(' Deadlock Transactions '))/2;
      $lPost += $lengthT - $lengthL;
      $string = str_repeat('_', floor($lPre)).' Deadlock Transactions '.str_repeat('_', ceil($lPost))."\n";

      // header line
      $string .=    str_pad('ID'    , $lengthId    , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('User'  , $lengthUser  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Host'  , $lengthHost  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Victim', $lengthVictim, ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Time'  , $lengthTime  , ' ', STR_PAD_LEFT )
              .'  '.str_pad('Undo'  , $lengthUndo  , ' ', STR_PAD_LEFT )
              .'  '.        'Query'."\n";

      // data lines
      foreach ($transactions as &$t) {
         $string .=    str_pad($t['connection'], $lengthId    , ' ', STR_PAD_LEFT )
                 .'  '.str_pad($t['user'      ], $lengthUser  , ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($t['host'      ], $lengthHost  , ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($t['victim'    ], $lengthVictim, ' ', STR_PAD_RIGHT)
                 .'  '.str_pad($t['time'      ], $lengthTime  , ' ', STR_PAD_LEFT )
                 .'  '.str_pad($t['undo'      ], $lengthUndo  , ' ', STR_PAD_LEFT )
                 .'  '.subStr ($t['query'     ], 0, $lengthQuery)."\n";
      }

      // bottom separator line
      $string .= str_repeat('_', $lengthT)."\n";


      // Lockanzeige generieren
      // top separator line
      $lPre    = $lPost = ($lengthL-strLen(' Deadlock Locks '))/2;
      $string .= "\n\n\n".str_repeat('_', floor($lPre)).' Deadlock Locks '.str_repeat('_', ceil($lPost))."\n";

      // header line
      $string .=    str_pad('ID'     , $lengthId     , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Waiting', $lengthWaiting, ' ', STR_PAD_LEFT )
              .'  '.str_pad('Mode'   , $lengthMode   , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('DB'     , $lengthDb     , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Table'  , $lengthTable  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Index'  , $lengthIndex  , ' ', STR_PAD_RIGHT)
              .'  '.str_pad('Special', $lengthSpecial, ' ', STR_PAD_RIGHT)."\n";

      // data lines
      foreach ($transactions as &$t) {
         foreach ($t['locks'] as &$l) {
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

      /*
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
      RECORD LOCKS space id 0 page no 450 n bits 408 index `PRIMARY` of table `database/v_view` trx id 0 56471970 lock mode S locks rec but not gap
      Record lock, heap no 341 PHYSICAL RECORD: n_fields 5; compact format; info bits 0
       0: len 3; hex 8fb24d; asc   M;; 1: len 6; hex 0000035db1a0; asc    ]  ;; 2: len 7; hex 00000001c910a9; asc        ;; 3: len 4; hex 000010af; asc     ;; 4: len 4; hex 00000808; asc     ;;

      *** (2) WAITING FOR THIS LOCK TO BE GRANTED:
      RECORD LOCKS space id 0 page no 450 n bits 408 index `PRIMARY` of table `database/v_view` trx id 0 56471970 lock_mode X locks rec but not gap waiting
      Record lock, heap no 341 PHYSICAL RECORD: n_fields 5; compact format; info bits 0
       0: len 3; hex 8fb24d; asc   M;; 1: len 6; hex 0000035db1a0; asc    ]  ;; 2: len 7; hex 00000001c910a9; asc        ;; 3: len 4; hex 000010af; asc     ;; 4: len 4; hex 00000808; asc     ;;

      *** WE ROLL BACK TRANSACTION (2)
      ------------
      TRANSACTIONS
      ------------
      */
   }
}
?>
