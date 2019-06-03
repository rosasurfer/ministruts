<?php
namespace rosasurfer\db;

use rosasurfer\core\CObject;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\core\exception\IllegalTypeException;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\UnimplementedFeatureException;

use function rosasurfer\strIsNumeric;
use function rosasurfer\strToBool;

use const rosasurfer\ARRAY_BOTH;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
abstract class Result extends CObject implements ResultInterface {


    /** @var ConnectorInterface - used database connector */
    protected $connector;

    /** @var int - index of the row fetched by the next unqualified fetch* method call or -1 when hit the end */
    protected $nextRowIndex = 0;


    /**
     * Destructor
     *
     * Release the Result's internal resoruces.
     */
    public function __destruct() {
        try {
            $this->release();
        }
        catch (\Throwable $ex) { throw ErrorHandler::handleDestructorException($ex); }
        catch (\Exception $ex) { throw ErrorHandler::handleDestructorException($ex); }
    }


    /**
     * {@inheritdoc}
     */
    public function fetchColumn($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
        if (!is_int($column) && !is_string($column))
            throw new IllegalTypeException('Illegal type of parameter $column: '.gettype($column));
        if (isset($row)) throw new UnimplementedFeatureException('$row='.$row.' (!= NULL)');

        // Generic default implementation:
        // A connector-specific implementation will be faster and more efficient.

        $row = $this->fetchRow(ARRAY_BOTH);             // field types depend on the DBMS/driver

        if (!$row) {
            if (func_num_args() < 4) throw new NoMoreRecordsException();
            return $onNoMoreRows;
        }

        if (!\key_exists($column, $row)) {
            if (is_int($column)) throw new InvalidArgumentException('Invalid parameter $column: '.$column.' (no such column)');

            $row    = \array_change_key_case($row, CASE_LOWER);
            $column = strtolower($column);
            if (!\key_exists($column, $row)) throw new InvalidArgumentException('Invalid parameter $column: "'.$column.'" (no such column)');
        }
        $value = $row[$column];

        return isset($value) ? $value : $onNull;
    }


    /**
     * {@inheritdoc}
     */
    public function fetchString($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
        if (func_num_args() < 4) $value = $this->fetchColumn($column, $row, null);
        else                     $value = $this->fetchColumn($column, $row, null, $onNoMoreRows);

        if (is_string($value)) return $value;
        if (!isset($value))    return $onNull;

        if (is_bool($value))
            $value = (int) $value;
        return (string) $value;
    }


    /**
     * {@inheritdoc}
     */
    public function fetchBool($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
        if (func_num_args() < 4) $value = $this->fetchColumn($column, $row, null);
        else                     $value = $this->fetchColumn($column, $row, null, $onNoMoreRows);

        if (is_bool($value)) return $value;
        if (!isset($value))  return $onNull;

        $bValue = strToBool($value);

        if (!isset($bValue)) {
            if (!strIsNumeric($value)) throw new \UnexpectedValueException('unexpected numerical value for a boolean: "'.$value.'"');
            $bValue = (bool)(float) $value;        // skip leading zeros of numeric strings
        }
        return $bValue;
    }


    /**
     * {@inheritdoc}
     */
    public function fetchInt($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
        if (func_num_args() < 4) $value = $this->fetchColumn($column, $row, null);
        else                     $value = $this->fetchColumn($column, $row, null, $onNoMoreRows);

        if (is_int($value)) return $value;
        if (!isset($value)) return $onNull;

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
     * {@inheritdoc}
     */
    public function fetchFloat($column=0, $row=null, $onNull=null, $onNoMoreRows=null) {
        if (func_num_args() < 4) $value = $this->fetchColumn($column, $row, null);
        else                     $value = $this->fetchColumn($column, $row, null, $onNoMoreRows);

        if (is_float($value)) return $value;
        if (!isset($value))   return $onNull;

        if (!strIsNumeric($value)) throw new \UnexpectedValueException('unexpected string value: "'.$value.'" (not a float)');
        return (float) $value;                 // skip leading zeros of numeric strings
    }


    /**
     * Return the index of the row beeing fetched by the next unqualified fetch* method call.
     *
     * @return int - row index (starting at 0) or -1 after reaching the end
     */
    public function nextRowIndex() {
        return (int) $this->nextRowIndex;
    }


    /**
     * Return the type of the DBMS the result is generated from.
     *
     * @return string
     */
    public function getType() {
        return $this->connector->getType();
    }


    /**
     * Return the version of the DBMS the result is generated from as a string.
     *
     * @return string
     */
    public function getVersionString() {
        return $this->connector->getVersionString();
    }


    /**
     * Return the version ID of the DBMS the result is generated from as an integer.
     *
     * @return int
     */
    public function getVersionNumber() {
        return $this->connector->getVersionNumber();
    }
}
