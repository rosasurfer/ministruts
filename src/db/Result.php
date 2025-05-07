<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db;

use Throwable;
use UnexpectedValueException;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\UnimplementedFeatureException;
use rosasurfer\ministruts\db\ConnectorInterface as Connector;

use function rosasurfer\ministruts\strIsNumeric;
use function rosasurfer\ministruts\strToBool;

use const rosasurfer\ministruts\ARRAY_BOTH;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
abstract class Result extends CObject implements ResultInterface {


    /** @var Connector - the used database connector */
    protected Connector $connector;

    /** @var string - SQL statement the result was generated from */
    protected string $sql;

    /** @var int - index of the row fetched by the next unqualified fetch* method call or -1 when hit the end */
    protected int $nextRowIndex = 0;


    /**
     * Constructor
     *
     * @param  Connector $connector - connector managing the database connection
     * @param  string    $sql       - executed SQL statement
     */
    public function __construct(Connector $connector, string $sql) {
        $this->connector = $connector;
        $this->sql = $sql;
    }


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
     */
    public function fetchColumn($column=0, ?int $row=null, $onNull=null, $onNoMoreRows=null) {
        if (isset($row)) throw new UnimplementedFeatureException('$row='.$row.' (not NULL)');

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
            if ($iValue == $fValue) {
                return $iValue;
            }
        }
        throw new UnexpectedValueException('unexpected string value: "'.$value.'" (not an integer)');
    }


    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function nextRowIndex(): int {
        return $this->nextRowIndex;
    }


    /**
     * {@inheritdoc}
     */
    public function getType(): string {
        return $this->connector->getType();
    }


    /**
     * {@inheritdoc}
     */
    public function getVersionString(): string {
        return $this->connector->getVersionString();
    }


    /**
     * {@inheritdoc}
     */
    public function getVersionNumber(): int {
        return $this->connector->getVersionNumber();
    }
}
