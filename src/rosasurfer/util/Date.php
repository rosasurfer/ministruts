<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use const rosasurfer\DAYS;
use const rosasurfer\L_WARN;


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
      if (!is_string($start))                      throw new IllegalTypeException('Illegal type of parameter $start: '.getType($start));
      if (Validator::isDateTime($start) === false) throw new InvalidArgumentException('Invalid argument $start: "'.$start.'"');
      if (!is_string($end))                        throw new IllegalTypeException('Illegal type of parameter $end: '.getType($end));
      if (Validator::isDateTime($end) === false)   throw new InvalidArgumentException('Invalid argument $end: "'.$end.'"');

      $ts1 = strToTime($start.' GMT'); // ohne Angabe einer Zeitzone wird die lokale DST einkalkuliert
      $ts2 = strToTime($end.' GMT');

      $diff = $ts2 - $ts1;

      if ($diff % DAYS)
         Logger::log("($ts2-$ts1) % DAYS != 0: ".($diff%DAYS), null, L_WARN, __CLASS__);

      return (int) ($diff / DAYS);
   }


   /**
    * Gibt eine Anzahl von Datumswerten zurück.
    *
    * @param  string $startDate - Startzeitpunkt (Format: yyyy-mm-dd)
    * @param  int    $days      - Anzahl der zurückzugebenden Werte
    *
    * @return array
    */
   public static function getDateRange($startDate, $days) {
      if (!is_string($startDate))                      throw new IllegalTypeException('Illegal type of parameter $startDate: '.getType($startDate));
      if (Validator::isDateTime($startDate) === false) throw new InvalidArgumentException('Invalid argument $startDate: "'.$startDate.'"');
      if (!is_int($days))                              throw new IllegalTypeException('Illegal type of parameter $days: '.getType($days));
      if ($days < 0)                                   throw new InvalidArgumentException('Invalid argument $days: '.$days);

      $range = array();
      $date  = new \DateTime($startDate);

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
      if (Validator::isDateTime($date) === false) throw new InvalidArgumentException('Invalid argument $date: '.$date);
      if (!is_int($days))                         throw new IllegalTypeException('Illegal type of parameter $days: '.getType($days));

      $parts = explode('-', $date);
      $year  = (int) $parts[0];
      $month = (int) $parts[1];
      $day   = (int) $parts[2];

      return date('Y-m-d', mkTime(0, 0, 0, $month, $day+$days, $year));
   }


   /**
    * Format a datetime string with the specified format.
    *
    * @param  string $datetime - date/datetime string parsable by strToTime()
    * @param  string $format   - string with format codes according to the PHP function date()
    *
    * @return string - formatted date/datetime value in the local timezone
    */
   public static function format($datetime, $format) {
      if (!is_string($datetime)) throw new IllegalTypeException('Illegal type of parameter $datetime: '.getType($datetime));
      if (!is_string($format))   throw new IllegalTypeException('Illegal type of parameter $format: '.getType($format));

      if ($datetime < '1970-01-01 00:00:00') {
         if ($format != 'd.m.Y') {
            trigger_error('Cannot format datetime before 1970-01-01 ("'.$datetime.'") with format "'.$format.'"', E_USER_NOTICE);
            return preg_replace('/[1-9]/', '0', date($format, time()));
         }
         $parts = explode('-', substr($datetime, 0, 10));
         return $parts[2].'.'.$parts[1].'.'.$parts[0];
      }

      $timestamp = strToTime($datetime);
      if (!is_int($timestamp)) throw new InvalidArgumentException('Invalid argument $datetime: '.$datetime);

      return date($format, $timestamp);
   }
}
