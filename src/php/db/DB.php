<?php
/**
 * DB
 *
 * Abstrakte Superklasse für verschiedene Datenbank-Connectoren.
 */
abstract class DB extends Object {


   /**
    * Handle auf das interne, originale Connection-Objekt.
    */
   protected /*hResource*/ $link;


   // Verbindungs- und Zugangsdaten
   protected /*string*/   $host;
   protected /*string*/   $port;
   protected /*string*/   $username;
   protected /*string*/   $password;
   protected /*string*/   $database;
   protected /*string[]*/ $options;


   /**
    * Transaktionszähler (0 = keine aktive Transaktion)
    */
   private /*int*/ $transaction = 0;


   /**
    * Geschützter Default-Konstruktor, Instanzen können nur über DB::spawn() erzeugt werden.
    */
   protected function __construct() { /**/ }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung des Objekts dafür, daß eine evt. noch offene Transaktion zurückgerollt
    * und die Verbindung zur Datenbank geschlossen wird.
    */
   public function __destruct() {
      try {
         if ($this->isConnected()) {

            if ($this->transaction)
               $this->rollback();

            $this->disconnect();
         }
      }
      catch (Exception $ex) {
         Logger ::handleException($ex, $ignoreIfNotInShutdown=true);
         throw $ex;
      }
   }


   /**
    * Verbindet den Connector mit der Datenbank.
    */
   abstract protected function connect();


   /**
    * Trennt die Verbindung des Connectors zur Datenbank.
    */
   abstract protected function disconnect();


   /**
    * Führt eine SQL-Anweisung aus.
    *
    * @param  string $sql - SQL-Anweisung
    *
    * @return mixed - je nach Connector
    */
   abstract public function queryRaw($sql);


   /**
    * Führt eine SQL-Anweisung aus. Gibt das Ergebnis als mehrdimensionales Array zurück.
    *
    * @param  string $sql - SQL-Anweisung
    *
    * @return array['set' ] - das zurückgegebene Resultset (nur bei SELECT-Statement)
    *              ['rows'] - Anzahl der betroffenen Datensätze (nur bei SELECT/INSERT/UPDATE-Statement)
    */
   abstract public function executeSql($sql);


   /**
    * Erzeugt einen neuen Connector und initialisiert ihn.
    *
    * @param  string   $class    - Klassenname des konkreten Connectors
    * @param  string   $host     - Hostname(:Port) des Datenbankservers
    * @param  string   $username - Benutzername
    * @param  string   $password - Passwort
    * @param  string   $database - vorzuselektierende Datenbank
    * @param  string[] $options  - weitere Verbindungsoptionen
    *
    * @return DB - Connector
    */
   public static function spawn($class, $host, $username, $password, $database = null, array $options = null) {
      if (!is_subclass_of($class, __CLASS__))
         throw new plInvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

      $connector = new $class();
      $connector->setHost($host)
                ->setUsername($username)
                ->setPassword($password)
                ->setDataBase($database)
                ->setOptions($options);
      return $connector;
   }


   /**
    * Setzt Namen und Port des Datenbankservers.
    *
    * @param  string $host
    *
    * @return DB
    */
   protected function setHost($host) {
      $port = null;

      if (strPos($host, ':') !== false)
         list($host, $port) = explode(':', $host, 2);

      $this->host = $host;
      $this->port = $port;

      return $this;
   }


   /**
    * Setzt den Benutzernamen.
    *
    * @param  string $name
    *
    * @return DB
    */
   protected function setUsername($name) {
      $this->username = $name;
      return $this;
   }


   /**
    * Setzt das Passwort.
    *
    * @param  string $password
    *
    * @return DB
    */
   protected function setPassword($password) {
      $this->password = $password;
      return $this;
   }


   /**
    * Setzt den Namen des Datenbankschemas.
    *
    * @param  string $name - Datenbankname
    *
    * @return DB
    */
   protected function setDatabase($name) {
      $this->database = $name;
      return $this;
   }


   /**
    * Setzt weitere Verbindungsoptionen.
    *
    * @param  string[] $options - Optionen
    *
    * @return DB
    */
   protected function setOptions($options) {
      if (is_null($options))
         $options = array();
      $this->options = $options;
      return $this;
   }


   /**
    * Ob eine Connection zur Datenbank besteht.
    *
    * @return bool
    */
   protected function isConnected() {
      return ($this->link && is_resource($this->link));
   }


   /**
    * Befindet sich der Connector in *keiner* Transaktion, wird eine neue Transaktion gestartet und der Transaktionszähler erhöht.
    * Befindet er sich bereits in einer Transaktion, wird nur der Transaktionszähler erhöht.
    *
    * @return DB
    */
   public function begin() {
      if ($this->transaction < 0)
         throw new plRuntimeException('Negative transaction counter detected: '.$this->transaction);

      if ($this->transaction == 0)
         $this->queryRaw('start transaction');

      $this->transaction++;
      return $this;
   }


   /**
    * Befindet sich der Connector in genau *einer* Transaktion, wird diese Transaktion abgeschlossen.
    * Befindet er sich in einer verschachtelten Transaktion, wird nur der Transaktionszähler heruntergezählt.
    *
    * @return DB
    */
   public function commit() {
      if ($this->transaction < 0)
         throw new plRuntimeException('Negative transaction counter detected: '.$this->transaction);

      if ($this->transaction == 0) {
         Logger ::log('No database transaction to commit', L_WARN, __CLASS__);
      }
      else {
         if ($this->transaction == 1)
            $this->queryRaw('commit');

         $this->transaction--;
      }
      return $this;
   }


   /**
    * Befindet sich der Connector in GENAU einer Transaktion, wird diese Transaktion zurückgerollt.
    * Befindet er sich in einer verschachtelten Transaktion, wird der Aufruf ignoriert.
    *
    * @return DB
    */
   public function rollback() {
      if ($this->transaction < 0)
         throw new plRuntimeException('Negative transaction counter detected: '.$this->transaction);

      if ($this->transaction == 0) {
         Logger ::log('No database transaction to roll back', L_WARN, __CLASS__);
      }
      else {
         if ($this->transaction == 1)
            $this->queryRaw('rollback');

         $this->transaction--;
      }
      return $this;
   }


   /**
    * Ob der Connector sich im Moment in einer Transaktion befindet.
    *
    * @return bool
    */
   public function inTransaction() {
      return ($this->transaction > 0);
   }
}
?>
