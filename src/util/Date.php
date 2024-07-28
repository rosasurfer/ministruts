<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\log\Logger;

use const rosasurfer\DAYS;
use const rosasurfer\L_WARN;


/**
 * Date
 */
class Date extends StaticClass {


    /**
     * Calculate the number of days between two dates.
     *
     * @param  string $start - first date (format: yyyy-mm-dd)
     * @param  string $end   - second date (format: yyyy-mm-dd)
     *
     * @return int - number of days
     */
    public static function diffDays($start, $end) {
        Assert::string($start, '$start');
        Assert::string($end, '$end');
        if (Validator::isDateTime($start) === false) throw new InvalidArgumentException('Invalid argument $start: "'.$start.'"');
        if (Validator::isDateTime($end) === false)   throw new InvalidArgumentException('Invalid argument $end: "'.$end.'"');

        $ts1 = strtotime($start.' GMT');    // always GMT (w/o timezone PHP assumes local time and DST may distort the result)
        $ts2 = strtotime($end.' GMT');

        $diff = $ts2 - $ts1;

        if ($diff % DAYS)
            Logger::log('('.$ts2.'-'.$ts1.') % DAYS != 0: '.($diff%DAYS), L_WARN);

        return (int)($diff / DAYS);
    }


    /**
     * Return a range of dates.
     *
     * @param  string $startDate - start date (format: yyyy-mm-dd)
     * @param  int    $days      - number of dates to return
     *
     * @return array
     */
    public static function getDateRange($startDate, $days) {
        Assert::string($startDate, '$startDate');
        Assert::int   ($days,      '$days');
        if (Validator::isDateTime($startDate) === false) throw new InvalidArgumentException('Invalid argument $startDate: "'.$startDate.'"');
        if ($days < 0)                                   throw new InvalidArgumentException('Invalid argument $days: '.$days);

        $range = [];
        $date  = new DateTime($startDate);

        for ($i=0; $i < $days; ++$i) {
            $range[] = $date->format('Y-m-d');
            $date->modify('+1 day');
        }
        return $range;
    }


    /**
     * Add a number of days to a date.
     *
     * @param  string $date - initial date (format: yyyy-mm-dd)
     * @param  int    $days - number of days to add
     *
     * @return string - resulting date
     */
    public static function addDays($date, $days) {
        if (Validator::isDateTime($date) === false) throw new InvalidArgumentException('Invalid argument $date: '.$date);
        Assert::int($days, '$days');

        $parts = explode('-', $date);
        $year  = (int) $parts[0];
        $month = (int) $parts[1];
        $day   = (int) $parts[2];

        return date('Y-m-d', mktime(0, 0, 0, $month, $day+$days, $year));
    }
}
