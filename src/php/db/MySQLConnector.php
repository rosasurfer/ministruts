<?
/**
 * MySQL Connector
 */
final class MySQLConnector extends DB {


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
      if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

      if (!$this->isConnected())
         $this->connect();

      // Statement abschicken
      if (!$result = mysql_query($sql, $this->link)) {
         $error = ($errno=mysql_errno()) ? "SQL-Error $errno: ".mysql_error() : 'Can not connect to MySQL server';
         throw new DatabaseException($error."\nSQL: ".str_replace(array("\r\n","\r","\n"), array("\n","\n"," "), $sql));
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

      $set = $this->queryRaw($sql);

      if (is_resource($set)) {
         $result['set']  = $set;
         $result['rows'] = mysql_num_rows($set);                  // Anzahl der erhaltenen Zeilen
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
