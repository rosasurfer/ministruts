<?php
namespace rosasurfer\db;

use rosasurfer\core\Object;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
abstract class Result extends Object {


   /**
    * Fetch the next row from the result set (if any).
    *
    * @return mixed[] - associative array of columns or NULL if no more rows are available
    */
   abstract public function fetchNext();


   /**
    * Gibt den Wert des internen Ergebniszählers zurück. Kann bei seitenweiser Ergebnisanzeige
    * statt einer zweiten Datenbankabfrage benutzt werden.
    * (siehe found_rows():  http://dev.mysql.com/doc/refman/5.1/en/information-functions.html)
    *
    * @return int - Gesamtanzahl von Ergebnissen der letzten Abfrage (ohne Berücksichtigung einer LIMIT-Klausel)
    *
   public function countFoundItems() {
      return $this->foundItemsCounter;

      /*
      if ($count) {
         $result2 = $this->executeSql('select found_rows()');
         $this->foundItemsCounter = (int) mysql_result($result2['set'], 0);
      }
      else {
         $this->foundItemsCounter = $result['rows'];
      }
      return $result;
      *\/
   }
   */
}
