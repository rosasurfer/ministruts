<?php
namespace rosasurfer\db;

use const rosasurfer\ARRAY_BOTH;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
interface ResultInterface {


   /**
    * Fetch the next row from the result set.
    *
    * @param  int $mode - Controls how the returned array is indexed. Can take one of the following values:
    *                     ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
    *
    * @return array - array of columns or NULL if no more rows are available
    */
   public function fetchNext($mode=ARRAY_BOTH);


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
   public function fetchField($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


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
   public function fetchAsBool($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


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
   public function fetchAsInt($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


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
   public function fetchAsFloat($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (connector specific, see the db README).
    *
    * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session;
    *               -1 if the DBMS doesn't support this functionality
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastInsertId();


   /**
    * Return the last number of rows affected by a row modifying statement (connector specific, see the db README).
    *
    * @return int - last number of affected rows or 0 (zero) if no rows were modified yet in the current session;
    *               -1 if the DBMS doesn't support this functionality
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastAffectedRows();


   /**
    * Return the number of rows in the result set.
    *
    * @return int
    */
   public function numRows();


   /**
    * Return the Result's internal result object.
    *
    * @return resource|object - result handle or instance
    */
   public function getInternalResult();
}
