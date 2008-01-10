<?
/**
 * DB
 *
 * Abstrakte Superklasse für verschiedene Datenbank-Connectoren.
 */
abstract class DB extends Object {


   /**
    * die interne, originale Connection
    */
   protected /*resource*/ $link;


   // credentials
   protected $host;           // string
   protected $port;           // string
   protected $username;       // string
   protected $password;       // string
   protected $database;       // string


   /**
    * Transaktionszähler (0 = keine aktive Transaktion)
    */
   private $transaction = 0;  // int


   /**
    * Schützt den Konstruktor von außen, Instanzen können nur über DB::spawn() erzeugt werden.
    */
   protected function __construct() { /**/ }


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung des Objekts dafür, daß eine evt. noch offene Transaktion zurückgerollt
    * und die Verbindung zur Datenbank geschlossen wird.
    */
   public function __destruct() {
      if ($this->isConnected()) {

         if ($this->transaction)
            $this->rollback();

         $this->disconnect();
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
    * @param string $sql - SQL-Anweisung
    *
    * @return mixed - je nach Connector
    */
   abstract public function queryRaw($sql);


   /**
    * Führt eine SQL-Anweisung aus. Gibt das Ergebnis als Array zurück.
    *
    * @param string $sql - SQL-Anweisung
    *
    * @return array['set' ] - das zurückgegebene Resultset (bei SELECT)
    *              ['rows'] - Anzahl der betroffenen Datensätze (bei SELECT/INSERT/UPDATE)
    */
   abstract public function executeSql($sql);


   /**
    * Erzeugt einen neuen Connector und initialisiert ihn.
    *
    * @param  string $class    - Klassenname des konkreten Connectors
    * @param  string $host     - Hostname(:Port) des Datenbankservers
    * @param  string $username - Benutzername
    * @param  string $password - Passwort
    * @param  string $database - vorzuselektierende Datenbank
    *
    * @return DB - Connector
    */
   public static function spawn($class, $host, $username, $password, $database = null) {
      if (!is_subclass_of($class, __CLASS__))
         throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

      $connector = new $class();
      $connector->setHost($host)
                ->setUsername($username)
                ->setPassword($password)
                ->setDataBase($database);

      return $connector;
   }


   /**
    * Setzt Namen und Port des Datenbankservers.
    *
    * @param string $host
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
    * @param string $name
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
    * @param string $password
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
    * @param string $name - Datenbankname
    *
    * @return DB
    */
   protected function setDatabase($name) {
      $this->database = $name;
      return $this;
   }


   /**
    * Ob eine Connection zur Datenbank besteht.
    *
    * @return boolean
    */
   protected function isConnected() {
      return ($this->link && is_resource($this->link));
   }


   /**
    * Befindet sich der Connector nicht in einer Transaktion, wird eine neue Transaktion gestartet.
    * Befindet er sich bereits in einer Transaktion, wird statt dessen nur der Transaktionszähler
    * um eins erhöht.
    *
    * @return DB
    */
   public function begin() {
      if (!$this->transaction)
         $this->queryRaw('start transaction');

      ++$this->transaction;
      return $this;
   }


   /**
    * Befindet sich der Connector in GENAU einer Transaktion, wird diese Transaktion abgeschlossen.
    * Befindet er sich in einer verschachtelten Transaktion, wird nur der Transaktionszähler um eins
    * heruntergezählt.
    *
    * @return DB
    */
   public function commit() {
      if ($this->transaction < 1) {
         Logger ::log('No database transaction to commit', L_WARN, __CLASS__);
      }
      else {
         if ($this->transaction == 1)
            $this->queryRaw('commit');

         --$this->transaction;
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
      if ($this->transaction < 1) {
         Logger ::log('No database transaction to roll back', L_WARN, __CLASS__);
      }
      else {
         if ($this->transaction == 1)
            $this->queryRaw('rollback');

         --$this->transaction;
      }
      return $this;
   }


   /**
    * Ob der Connector sich im Moment in einer Transaktion befindet.
    *
    * @return boolean
    */
   public function inTransaction() {
      return ($this->transaction > 0);
   }
}
?>
