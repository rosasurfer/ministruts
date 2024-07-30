<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db;

use const rosasurfer\ministruts\ARRAY_BOTH;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
interface ResultInterface {


    /**
     * Fetch the next row from the result set.
     *
     * @param  int $mode [optional] - Controls how the returned array is indexed. Can take one of the following values:
     *                                ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
     *
     * @return array<?scalar>|null - array of columns or NULL if no more rows are available
     */
    public function fetchRow($mode = ARRAY_BOTH);


    /**
     * Fetch a single column from the result set.
     *
     * @param  string|int $column       [optional] - name or offset of the column to fetch from (default: 0)
     * @param  ?int       $row          [optional] - row to fetch from, starting at 0 (default: the next row)
     * @param  mixed      $onNull       [optional] - value to return if the cell value is NULL
     * @param  mixed      $onNoMoreRows [optional] - value to return if no more rows are available
     *
     * @return mixed - value of a single cell (driver dependent type) or $onNull if the cell value is NULL
     *
     * @throws NoMoreRecordsException if no more rows are available and parameter $onNoMoreRows was not set.
     */
    public function fetchColumn($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


    /**
     * Fetch a single field from the result set as a string value.
     *
     * @param  string|int $column       [optional] - name or offset of the column to fetch from (default: 0)
     * @param  ?int       $row          [optional] - row to fetch from, starting at 0 (default: the next row)
     * @param  mixed      $onNull       [optional] - value to return if the cell value is NULL
     * @param  mixed      $onNoMoreRows [optional] - value to return if no more rows are available
     *
     * @return string - string value of a single cell or $onNull if the cell value is NULL
     *
     * @throws NoMoreRecordsException    if no more rows are available and parameter $onNoMoreRows was not set.
     */
    public function fetchString($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


    /**
     * Fetch a single field from the result set as a boolean.
     *
     * @param  string|int $column       [optional] - name or offset of the column to fetch from (default: 0)
     * @param  ?int       $row          [optional] - row to fetch from, starting at 0 (default: the next row)
     * @param  mixed      $onNull       [optional] - value to return if the cell value is NULL
     * @param  mixed      $onNoMoreRows [optional] - value to return if no more rows are available
     *
     * @return bool - boolean value of a single cell or $onNull if the cell value is NULL
     *
     * @throws NoMoreRecordsException    if no more rows are available and parameter $onNoMoreRows was not set.
     * @throws \UnexpectedValueException if the cell value is not NULL and does not represent a boolean. Accepted string
     *                                   representations are "true" and "false", "on" and "off", "yes" and "no", and
     *                                   numerical representations.
     */
    public function fetchBool($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


    /**
     * Fetch a single field from the result set as an integer.
     *
     * @param  string|int $column       [optional] - name or offset of the column to fetch from (default: 0)
     * @param  ?int       $row          [optional] - row to fetch from, starting at 0 (default: the next row)
     * @param  mixed      $onNull       [optional] - value to return if the cell value is NULL
     * @param  mixed      $onNoMoreRows [optional] - value to return if no more rows are available
     *
     * @return int - integer value of a single cell or $onNull if the cell value is NULL
     *
     * @throws NoMoreRecordsException    if no more rows are available and parameter $onNoMoreRows was not set.
     * @throws \UnexpectedValueException if the cell value is not NULL and does not represent an integer. The accepted
     *                                   floating point values must have a fractional part equal to 0 (zero).
     *                                   Use <tt>self::fetchFloat()</tt> to interpret more floating point values as integer.
     */
    public function fetchInt($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


    /**
     * Fetch a single field from the result set as a floating point value.
     *
     * @param  string|int $column       [optional] - name or offset of the column to fetch from (default: 0)
     * @param  ?int       $row          [optional] - row to fetch from, starting at 0 (default: the next row)
     * @param  mixed      $onNull       [optional] - value to return if the cell value is NULL
     * @param  mixed      $onNoMoreRows [optional] - value to return if no more rows are available
     *
     * @return float - floating point value of a single cell or $onNull if the cell value is NULL
     *
     * @throws NoMoreRecordsException    if no more rows are available and parameter $onNoMoreRows was not set.
     * @throws \UnexpectedValueException if the cell value is not NULL and does not represent a floating point value.
     */
    public function fetchFloat($column=0, $row=null, $onNull=null, $onNoMoreRows=null);


    /**
     * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (connector specific, see the db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session;
     *               -1 if the DBMS doesn't support this functionality
     *
     * @link   https://github.com/rosasurfer/ministruts/tree/master/src/db
     */
    public function lastInsertId();


    /**
     * Return the last number of rows affected by a row modifying statement (connector specific, see the db README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session;
     *               -1 if the DBMS doesn't support this functionality
     *
     * @link   https://github.com/rosasurfer/ministruts/tree/master/src/db
     */
    public function lastAffectedRows();


    /**
     * Return the number of rows returned by the query.
     *
     * @return int
     */
    public function numRows();


    /**
     * Return the index of the row beeing fetched by the next unqualified fetch* method call.
     *
     * @return int - row index (starting at 0) or -1 after reaching the end
     */
    public function nextRowIndex();


    /**
     * Release the internal resources held by the Result.
     *
     * @return void
     */
    public function release();


    /**
     * Return the result's internal result object.
     *
     * @return resource|object|null - result handle, handler or NULL for a result-less query
     */
    public function getInternalResult();


    /**
     * Return the type of the DBMS the result is generated from.
     *
     * @return string
     */
    public function getType();


    /**
     * Return the version of the DBMS the result is generated from as a string.
     *
     * @return string
     */
    public function getVersionString();


    /**
     * Return the version ID of the DBMS the result is generated from as an integer.
     *
     * @return int
     */
    public function getVersionNumber();
}
