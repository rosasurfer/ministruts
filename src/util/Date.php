<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\Logger;

use function rosasurfer\ministruts\strToTimestamp;

use const rosasurfer\ministruts\DAYS;
use const rosasurfer\ministruts\L_WARN;

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
    public static function diffDays(string $start, string $end): int {
        if (strToTimestamp($start) === null) throw new InvalidValueException("Invalid parameter \$start: \"$start\"");
        if (strToTimestamp($end) === null)   throw new InvalidValueException("Invalid parameter \$end: \"$end\"");

        $ts1 = strtotime($start.' GMT');    // always GMT (w/o timezone PHP assumes local time and DST may distort the result)
        $ts2 = strtotime($end.' GMT');

        $diff = $ts2 - $ts1;

        if ($diff % DAYS) {
            Logger::log("($ts2-$ts1) % DAYS != 0: ".($diff % DAYS), L_WARN);
        }
        return (int)($diff / DAYS);
    }


    /**
     * Return an array holding a number of consecutive date values.
     *
     * @param  string $startDate - start date (format: yyyy-mm-dd)
     * @param  int    $days      - number of dates to return
     *
     * @return string[]
     */
    public static function getDateRange(string $startDate, int $days): array {
        $ts = strToTimestamp($startDate, 'Y-m-d');
        if ($ts === null) throw new InvalidValueException("Invalid parameter \$startDate: \"$startDate\"");
        if ($days < 0)    throw new InvalidValueException("Invalid parameter \$days: $days");

        $range = [];

        for ($i=0; $i < $days; $i++) {
            $range[] = date('Y-m-d', $ts + $i*DAYS);
        }
        return $range;
    }


    /**
     * Add a number of days to a date.
     *
     * @param  string $date - initial date (format: yyyy-mm-dd)
     * @param  int    $days - number of days to add
     *
     * @return string - resulting date (format: yyyy-mm-dd)
     */
    public static function addDays(string $date, int $days): string {
        $ts = strToTimestamp($date, 'Y-m-d');
        if ($ts === null) throw new InvalidValueException("Invalid parameter \$date: \"$date\"");
        return date('Y-m-d', $ts + $days*DAYS);
    }
}
