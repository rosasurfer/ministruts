<?php
namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\Logger;

use const rosasurfer\ministruts\DAYS;
use const rosasurfer\ministruts\L_WARN;


/**
 * Date
 */
class Date extends StaticClass {


    /**
     * Berechnet die Anzahl von Tagen zwischen zwei Zeitpunkten.
     *
     * @param  string $start - erster Zeitpunkt  (Format: yyyy-mm-dd)
     * @param  string $end   - zweiter Zeitpunkt (Format: yyyy-mm-dd)
     *
     * @return int - Tage
     */
    public static function diffDays($start, $end) {
        Assert::string($start, '$start');
        Assert::string($end,   '$end');
        if (Validator::isDateTime($start) === false) throw new InvalidValueException('Invalid parameter $start: "'.$start.'"');
        if (Validator::isDateTime($end) === false)   throw new InvalidValueException('Invalid parameter $end: "'.$end.'"');

        $ts1 = strtotime($start.' GMT'); // ohne Angabe einer Zeitzone wird die lokale DST einkalkuliert
        $ts2 = strtotime($end.' GMT');

        $diff = $ts2 - $ts1;

        if ($diff % DAYS)
            Logger::log('('.$ts2.'-'.$ts1.') % DAYS != 0: '.($diff%DAYS), L_WARN);

        return (int)($diff / DAYS);
    }


    /**
     * Gibt eine Anzahl von Datumswerten zurueck.
     *
     * @param  string $startDate - Startzeitpunkt (Format: yyyy-mm-dd)
     * @param  int    $days      - Anzahl der zurueckzugebenden Werte
     *
     * @return array
     */
    public static function getDateRange($startDate, $days) {
        Assert::string($startDate, '$startDate');
        Assert::int   ($days,      '$days');
        if (Validator::isDateTime($startDate) === false) throw new InvalidValueException('Invalid parameter $startDate: "'.$startDate.'"');
        if ($days < 0)                                   throw new InvalidValueException('Invalid parameter $days: '.$days);

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
     * @return string - reslting date
     */
    public static function addDays($date, $days) {
        if (Validator::isDateTime($date) === false) throw new InvalidValueException('Invalid parameter $date: '.$date);
        Assert::int($days, '$days');

        $parts = explode('-', $date);
        $year  = (int) $parts[0];
        $month = (int) $parts[1];
        $day   = (int) $parts[2];

        return date('Y-m-d', mktime(0, 0, 0, $month, $day+$days, $year));
    }
}
