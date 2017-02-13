<?php
namespace rosasurfer\db;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\UnimplementedFeatureException;

use const rosasurfer\ARRAY_BOTH;
use function rosasurfer\strToBool;
use function rosasurfer\strIsNumeric;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
abstract class Result extends Object implements ResultInterface {


   /**
    * Destructor
    */
   abstract public function __destruct(
      // enforce a destructor to release a result set
   );


   /**
    * Fetch a single field from the result set as a string.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNull       - value to return if the cell value is NULL (default: NULL)
    * @param  mixed      $onNoMoreRows - value to return if no more rows are available
    *
    * @return string - string value of a single cell or $onNull if the cell value is NULL
    *
    * @throws NoMoreRowsException if no more rows are available and parameter $onNoMoreRows was not set.
    */
   public function fetchField($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
      if (!is_null($row)) throw new UnimplementedFeatureException('$row='.$row.' (!= NULL)');

      // Generic default implementation: A connector-specific implementation will be faster and more efficient.

      $row = $this->fetchNext(ARRAY_BOTH);            // returned field types depend on the DBMS

      if (!$row) {
         if (func_num_args() < 4) throw new NoMoreRowsException();
         return $onNoMoreRows;
      }

      if ($column===0 || is_null($column)) {
         $value = $row[0];
      }
      else {
         if (!array_key_exists($column, $row)) {
            if (is_int($column))     throw new InvalidArgumentException('Invalid $column: '.$column.' (no such column)');
            if (!is_string($column)) throw new IllegalTypeException('Illegal type of parameter $column: '.getType($column));

            $row    = array_change_key_case($row, CASE_LOWER);
            $column = strToLower($column);
            if (!array_key_exists($column, $row))
               throw new InvalidArgumentException('Invalid $column: "'.$column.'" (no such column)');
         }
         $value = $row[$column];
      }

      if (is_null($value))
         return $onNull;
      return $value;
   }


   /**
    * Fetch a single field from the result set as a boolean.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNull       - value to return if the cell value is NULL (default: NULL)
    * @param  mixed      $onNoMoreRows - value to return if no more rows are available
    *
    * @return bool - boolean value of a single cell or $onNull if the cell value is NULL
    *
    * @throws NoMoreRowsException       if no more rows are available and parameter $onNoMoreRows was not set.
    * @throws \UnexpectedValueException if the cell value is not NULL and does not represent a boolean. Accepted string
    *                                   representations are "true" and "false", "on" and "off", "yes" and "no', and
    *                                   numerical representations.
    */
   public function fetchAsBool($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
      if (func_num_args() < 4) $sValue = $this->fetchField($column, $row, null);
      else                     $sValue = $this->fetchField($column, $row, null, $onNoMoreRows);

      if (is_null($sValue))
         return $onNull;

      $bValue = strToBool($sValue);

      if (is_null($bValue)) {
         if (!strIsNumeric($sValue)) throw new \UnexpectedValueException('unexpected numerical value for a boolean: "'.$sValue.'"');
         $bValue = (bool)(float) $sValue;       // skip leading zeros of numeric strings
      }
      return $bValue;
   }


   /**
    * Fetch a single field from the result set as an integer.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNull       - value to return if the cell value is NULL (default: NULL)
    * @param  mixed      $onNoMoreRows - value to return if no more rows are available
    *
    * @return int - integer value of a single cell or $onNull if the cell value is NULL
    *
    * @throws NoMoreRowsException       if no more rows are available and parameter $onNoMoreRows was not set.
    * @throws \UnexpectedValueException if the cell value is not NULL and does not represent an integer. The accepted
    *                                   floating point values must have a fractional part equal to 0 (zero).
    *                                   Use "self::fetchAsFloat()" to interpret more floating point values as integer.
    */
   public function fetchAsInt($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
      if (func_num_args() < 4) $value = $this->fetchField($column, $row, null);
      else                     $value = $this->fetchField($column, $row, null, $onNoMoreRows);

      if (is_null($value))
         return $onNull;

      if (is_int($value))
         return $value;

      if (is_float($value)) {
         $iValue = (int) $value;
         if ($iValue == $value)
            return $iValue;
         throw new \UnexpectedValueException('unexpected float value: "'.$value.'" (not an integer)');
      }

      if (strIsNumeric($value)) {
         $fValue = (float) $value;              // skip leading zeros of numeric strings
         $iValue = (int) $fValue;

         if ($iValue == $fValue)
            return $iValue;
      }
      throw new \UnexpectedValueException('unexpected string value: "'.$value.'" (not an integer)');
   }


   /**
    * Fetch a single field from the result set as a floating point value.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNull       - value to return if the cell value is NULL (default: NULL)
    * @param  mixed      $onNoMoreRows - value to return if no more rows are available
    *
    * @return float - floating point value of a single cell or $onNull if the cell value is NULL
    *
    * @throws NoMoreRowsException       if no more rows are available and parameter $onNoMoreRows was not set.
    * @throws \UnexpectedValueException if the cell value is not NULL and does not represent a floating point value.
    */
   public function fetchAsFloat($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
      if (func_num_args() < 4) $sValue = $this->fetchField($column, $row, null);
      else                     $sValue = $this->fetchField($column, $row, null, $onNoMoreRows);

      if (is_null($sValue))
         return $onNull;

      if (!strIsNumeric($sValue))
         throw new \UnexpectedValueException('unexpected string value: "'.$sValue.'" (not a float)');

      return (float) $sValue;                // skip leading zeros of numeric strings
   }


   /**
    * Gibt den Wert des internen Ergebniszaehlers zurueck. Kann bei seitenweiser Ergebnisanzeige
    * statt einer zweiten Datenbankabfrage benutzt werden.
    *
    * @return int - Gesamtanzahl von Ergebnissen der letzten Abfrage (ohne Beruecksichtigung einer LIMIT-Klausel)
    *
   public function countFoundItems() {
      return $this->foundItemsCounter;

      //$result = $this->query('select found_rows()');
      //$this->foundItemsCounter = $result->fetchAsInt();
   }
   */

   /*
   $rawResult = $this->executeRaw($sql);
   if (is_resource($rawResult)) {
      $resultSet     = $rawResult;
      $resultNumRows = mysql_num_rows($rawResult);       // number of returned rows
   }
   */
}
