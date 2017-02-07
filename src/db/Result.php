<?php
namespace rosasurfer\db;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\UnimplementedFeatureException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
abstract class Result extends Object {


   /**
    * Fetch the next row from the result set.
    *
    * @param  int $mode - Controls how the returned array is indexed. Can take one of the following values:
    *                     ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
    *
    * @return array - array of columns or NULL if no (more) rows are available
    */
   abstract public function fetchNext($mode=ARRAY_BOTH);


   /**
    * Fetch a single field of a row from the result set.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNoMoreRows - alternative value to return if no (more) rows are available
    *
    * @return mixed - content of a single cell (can be NULL)
    *
    * @throws NoMoreRowsException if no (more) rows are available and parameter $alt was not specified
    */
   public function fetchField($column=0, $row=null, $onNoMoreRows=null) {
      if (!is_null($row)) throw new UnimplementedFeatureException('$row='.$row.' (!= NULL)');

      // TODO: This cross-platform implementation might not be as efficient as a driver-specific Result could be.

      $row = $this->fetchNext(ARRAY_BOTH);

      if (!$row) {
         if (func_num_args() < 3) throw new NoMoreRowsException();
         return $onNoMoreRows;
      }

      if ($column===0 || is_null($column))
         return $row[0];

      if (!array_key_exists($column, $row)) {
         if (is_int($column))     throw new InvalidArgumentException('Invalid $column: '.$column.' (no such column)');
         if (!is_string($column)) throw new IllegalTypeException('Illegal type of parameter $column: '.getType($column));

         $row    = array_change_key_case($row, CASE_LOWER);
         $column = strToLower($column);
         if (!array_key_exists($column, $row))
            throw new InvalidArgumentException('Invalid $column: "'.$column.'" (no such column)');
      }
      return $row[$column];
   }


   /**
    * Return the number of rows affected if the SQL was an INSERT/UPDATE/DELETE statement.
    *
    * This value is provided for userland code only and must not be used in the framework. The implementation may be
    * unreliable (see the specific result implementation).
    *
    * @return int
    */
   abstract public function affectedRows();


   /**
    * Return the number of rows in the result set.
    *
    * @return int
    */
   abstract public function numRows();


   /**
    * Get the underlying driver's original result object.
    *
    * @return mixed
    */
   abstract public function getInternalResult();


   /**
    * Gibt den Wert des internen Ergebniszaehlers zurück. Kann bei seitenweiser Ergebnisanzeige
    * statt einer zweiten Datenbankabfrage benutzt werden.
    *
    * @return int - Gesamtanzahl von Ergebnissen der letzten Abfrage (ohne Beruecksichtigung einer LIMIT-Klausel)
    *
   public function countFoundItems() {
      return $this->foundItemsCounter;
      /*
      if ($count) {
         $result = $this->query('select found_rows()');
         $this->foundItemsCounter = (int) $result->fetchField();
      }
      *\/
   }
   */


   /*
   $rawResult = $this->executeRaw($sql);

   if (is_resource($rawResult)) {
      $resultSet     = $rawResult;
      $resultNumRows = mysql_num_rows($rawResult);                   // number of returned rows
   }
   */
}
