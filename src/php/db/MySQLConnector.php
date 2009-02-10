<?
/**
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

         if ($errno==1205 || $errno==1213) {             // 1205: Lock wait timeout exceeded
            $list = $this->printProcessList(true);       // 1213: Deadlock found when trying to get lock
            $message .= "\n\nProcess list:\n".$list;
         }

         throw new DatabaseException($message);
      }


      // Zu lang dauernde Statements loggen
      if (self::$logDebug) {
         $neededTime = round($end - $start, 4);
         if ($neededTime > self::$maxQueryTime)
            Logger ::log('SQL statement took more than '.self::$maxQueryTime." seconds: $neededTime\n$sql", L_DEBUG, __CLASS__);
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
    * @param bool $return - Ob die Ausgabe auf STDOUT oder als Rückgabewert der Funktion (TRUE) erfolgen soll.
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
         $row['Info'] = trim(preg_replace('/\s+/', ' ', $row['Info']));

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
}
?>
