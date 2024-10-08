<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db;

use Throwable;
use UnexpectedValueException;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\InvalidTypeException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\UnimplementedFeatureException;

use function rosasurfer\ministruts\strIsNumeric;
use function rosasurfer\ministruts\strToBool;

use const rosasurfer\ministruts\ARRAY_BOTH;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
abstract class Result extends CObject implements ResultInterface {


    /** @var ConnectorInterface - the used database connector */
    protected ConnectorInterface $connector;

    /** @var int - index of the row fetched by the next unqualified fetch* method call or -1 when hit the end */
    protected int $nextRowIndex = 0;


    /**
     * Destructor
     *
     * Release the result's internal resoruces.
     */
    public function __destruct() {
        try {
            $this->release();
        }
        catch (Throwable $ex) {
            throw ErrorHandler::handleDestructorException($ex);
        }
    }


    /**
     * {@inheritdoc}
     *
     * @param  string|int $column       [optional]
     * @param  ?int       $row          [optional]
     * @param  mixed      $onNull       [optional]
     * @param  mixed      $onNoMoreRows [optional]
     *
     * @return mixed
     */
    public function fetchColumn($column=0, ?int $row=null, $onNull=null, $onNoMoreRows=null) {
        // @phpstan-ignore booleanAnd.alwaysFalse (type comes from PHPDoc)
        if (!is_int($column) && !is_string($column)) throw new InvalidTypeException('Illegal type of parameter $column: '.gettype($column));
        if (isset($row))                             throw new UnimplementedFeatureException('$row='.$row.' (!= NULL)');

        // Generic default implementation:
        // A connector-specific implementation will be faster and more efficient.

        $row = $this->fetchRow(ARRAY_BOTH);             // field types depend on the DBMS/driver

        if (!$row) {
            if (func_num_args() < 4) throw new NoMoreRecordsException();
            return $onNoMoreRows;
        }

        if (!\key_exists($column, $row)) {
            if (is_int($column)) throw new InvalidValueException('Invalid parameter $column: '.$column.' (no such column)');

            $row = \array_change_key_case($row, CASE_LOWER);
            $column = strtolower($column);
            if (!\key_exists($column, $row)) throw new InvalidValueException('Invalid parameter $column: "'.$column.'" (no such column)');
        }
        $value = $row[$column];
        return $value ?? $onNull;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string|int $column       [optional]
     * @param  ?int       $row          [optional]
     * @param  ?string    $onNull       [optional]
     * @param  ?string    $onNoMoreRows [optional]
     *
     * @return ?string
     */
    public function fetchString($column=0, ?int $row=null, ?string $onNull=null, ?string $onNoMoreRows=null): ?string {
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
     *
     * @param  string|int $column       [optional]
     * @param  ?int       $row          [optional]
     * @param  ?bool      $onNull       [optional]
     * @param  ?bool      $onNoMoreRows [optional]
     *
     * @return ?bool
     */
    public function fetchBool($column=0, ?int $row=null, ?bool $onNull=null, ?bool $onNoMoreRows=null): ?bool {
        if (func_num_args() < 4) $value = $this->fetchColumn($column, $row, null);
        else                     $value = $this->fetchColumn($column, $row, null, $onNoMoreRows);

        if (is_bool($value)) return $value;
        if (!isset($value))  return $onNull;

        $value = (string) $value;
        $bValue = strToBool($value, true);

        if (!isset($bValue)) {
            if (!is_numeric($value)) throw new UnexpectedValueException("unexpected value for a boolean: \"$value\"");
            $bValue = (bool)(float) $value;
        }
        return $bValue;
    }


    /**
     * {@inheritdoc}
     *
     * @param  string|int $column       [optional]
     * @param  ?int       $row          [optional]
     * @param  ?int       $onNull       [optional]
     * @param  ?int       $onNoMoreRows [optional]
     *
     * @return ?int
     */
    public function fetchInt($column=0, ?int $row=null, ?int $onNull=null, ?int $onNoMoreRows=null): ?int {
        if (func_num_args() < 4) $value = $this->fetchColumn($column, $row, null);
        else                     $value = $this->fetchColumn($column, $row, null, $onNoMoreRows);

        if (is_int($value)) return $value;
        if (!isset($value)) return $onNull;

        if (is_float($value)) {
            $iValue = (int) $value;
            if ($iValue == $value)
                return $iValue;
            throw new UnexpectedValueException('unexpected float value: "'.$value.'" (not an integer)');
        }

        if (strIsNumeric($value)) {
            $fValue = (float) $value;              // skip leading zeros of numeric strings
            $iValue = (int) $fValue;
            if ($iValue == $fValue)
                return $iValue;
        }
        throw new UnexpectedValueException('unexpected string value: "'.$value.'" (not an integer)');
    }


    /**
     * {@inheritdoc}
     *
     * @param  string|int $column       [optional]
     * @param  ?int       $row          [optional]
     * @param  ?float     $onNull       [optional]
     * @param  ?float     $onNoMoreRows [optional]
     *
     * @return ?float
     */
    public function fetchFloat($column=0, ?int $row=null, ?float $onNull=null, ?float $onNoMoreRows=null): ?float {
        if (func_num_args() < 4) $value = $this->fetchColumn($column, $row, null);
        else                     $value = $this->fetchColumn($column, $row, null, $onNoMoreRows);

        if (is_float($value)) return $value;
        if (!isset($value))   return $onNull;

        if (!strIsNumeric($value)) throw new UnexpectedValueException('unexpected string value: "'.$value.'" (not a float)');
        return (float) $value;                 // skip leading zeros of numeric strings
    }


    /**
     * Return the index of the row beeing fetched by the next unqualified fetch* method call.
     *
     * @return int - row index (starting at 0) or -1 after reaching the end
     */
    public function nextRowIndex(): int {
        return (int) $this->nextRowIndex;
    }


    /**
     * Return the type of the DBMS the result is generated from.
     *
     * @return string
     */
    public function getType(): string {
        return $this->connector->getType();
    }


    /**
     * Return the version of the DBMS the result is generated from as a string.
     *
     * @return string
     */
    public function getVersionString(): string {
        return $this->connector->getVersionString();
    }


    /**
     * Return the version ID of the DBMS the result is generated from as an integer.
     *
     * @return int
     */
    public function getVersionNumber(): int {
        return $this->connector->getVersionNumber();
    }
}
