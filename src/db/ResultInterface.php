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
    * Fetch a single field of a row from the result set.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNoMoreRows - alternative value to return if no more rows are available
    *
    * @return mixed - content of a single cell (can be NULL)
    *
    * @throws NoMoreRowsException if no more rows are available and parameter $onNoMoreRows was not set
    */
   public function fetchField($column=0, $row=null, $onNoMoreRows=null);


   /**
    * Return the number of rows affected if the SQL was an INSERT/UPDATE/DELETE statement.
    *
    * @return int
    */
   public function affectedRows();


   /**
    * Return the number of rows in the result set.
    *
    * @return int
    */
   public function numRows();


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (usually an INSERT). The value may or
    * may not be reset between queries (see the specific connector implementation).
    *
    * @return int - generated ID or 0 (zero) if no ID was generated;
    *               -1 if the database system doesn't support this functionality.
    */
   public function lastInsertId();


   /**
    * Get the underlying driver's original result object.
    *
    * @return mixed
    */
   public function getInternalResult();
}
