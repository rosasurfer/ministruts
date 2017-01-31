<?php
namespace rosasurfer\db;

use rosasurfer\core\Object;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\InvalidArgumentException;


/**
 * Connector
 *
 * Abstrakte Superklasse für verschiedene Datenbank-Connectoren.
 */
abstract class Connector extends Object {


   /**
    * Geschützter Default-Constructor, Instanzen können nur über self::create() erzeugt werden.
    */
   protected function __construct() {}


   /**
    * Destructor
    *
    * Sorgt bei Zerstörung des Objekts dafür, daß eine evt. noch offene Transaktion zurückgerollt
    * und die Verbindung zur Datenbank geschlossen wird.
    */
   public function __destruct() {
      try {
         if ($this->isConnected()) {
            if ($this->isInTransaction())
               $this->rollback();
            $this->disconnect();
         }
      }
      catch (\Exception $ex) {
         // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
         // @see  http://php.net/manual/en/language.oop5.decon.php
         ErrorHandler::handleDestructorException($ex);
         throw $ex;
      }
   }


   /**
    * Erzeugt einen neuen Connector und initialisiert ihn.
    *
    * @param  string   $class   - Klassenname des Connectors
    * @param  string[] $config  - Connector-Konfiguration
    * @param  string[] $options - weitere Connector-Optionen (default: keine)
    *
    * @return self
    */
   public static function create($class, array $config, array $options=[]) {
      if (!is_subclass_of($class, __CLASS__)) throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);
      return new $class($config, $options);
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
    * Ob eine Connection zur Datenbank besteht.
    *
    * @return bool
    */
   abstract protected function isConnected();


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
    * Führt eine SQL-Anweisung aus.
    *
    * @param  string $sql - SQL-Anweisung
    *
    * @return mixed - je nach Connector
    */
   abstract public function queryRaw($sql);


   /**
    * Beginnt eine neue Transaktion.
    *
    * @return self
    */
   abstract public function begin();


   /**
    * Schließt eine Transaktion ab.
    *
    * @return self
    */
   abstract public function commit();


   /**
    * Rollt eine Transaktion zurück.
    *
    * @return self
    */
   abstract public function rollback();


   /**
    * Ob der Connector sich in einer Transaktion befindet.
    *
    * @return bool
    */
   abstract public function isInTransaction();
}
