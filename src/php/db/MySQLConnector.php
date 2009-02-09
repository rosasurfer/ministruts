<?
/**
 * MySQLConnector
 */
final class MySQLConnector extends DB {


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;


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
      $start = $end = 0;
      if (self::$logDebug)
         $start = microTime(true);


      // Statement abschicken
      $result = mysql_query($sql, $this->link);


      // ggf. Endzeitpunkt speichern
      if (self::$logDebug)
         $end = microTime(true);


      // Ergebnis auswerten
      if (!$result) {
         $error   = ($errno = mysql_errno()) ? "SQL-Error $errno: ".mysql_error() : 'Can not connect to MySQL server';
         if (self::$logDebug)
            $error .= ' (taken time: '.round($end - $start, 4).' seconds)';
         $message = $error."\nSQL: ".str_replace(array("\r\n","\r","\n"), array("\n","\n"," "), $sql);
         throw new DatabaseException($message);
      }


      // Zu lange Statements (> 3 Sekunden) ggf. loggen
      if (self::$logDebug) {
         $maxTime    = 3;
         $neededTime = round($end - $start, 4);
         if ($neededTime > $maxTime) {
            $sql = str_replace(array("\r\n","\r","\n"), array("\n","\n"," "), $sql);
            Logger ::log("SQL statement took more than $maxTime seconds: $neededTime\n\n$sql", L_DEBUG, __CLASS__);
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
}
?>
