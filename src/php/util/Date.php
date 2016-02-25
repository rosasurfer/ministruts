<?php
/**
 * Date
 */
final class Date extends StaticClass {


   /**
    * Berechnet die Anzahl von Tagen zwischen zwei Zeitpunkten.
    *
    * @param string $start - erster Zeitpunkt  (Format: yyyy-mm-dd)
    * @param string $end   - zweiter Zeitpunkt (Format: yyyy-mm-dd)
    *
    * @return int - Tage
    */
   public static function diffDays($start, $end) {
      if (!is_string($start))                throw new IllegalTypeException('Illegal type of parameter $start: '.getType($start));
      if (!CommonValidator ::isDate($start)) throw new plInvalidArgumentException('Invalid argument $start: "'.$start.'"');
      if (!is_string($end))                  throw new IllegalTypeException('Illegal type of parameter $end: '.getType($end));
      if (!CommonValidator ::isDate($end))   throw new plInvalidArgumentException('Invalid argument $end: "'.$end.'"');

      $ts1 = strToTime($start.' GMT'); // ohne Angabe einer Zeitzone wird die lokale DST einkalkuliert
      $ts2 = strToTime($end.' GMT');

      $diff = $ts2 - $ts1;

      if ($diff % DAYS)
         Logger ::log("($ts2-$ts1) % DAYS != 0: ".($diff%DAYS), L_WARN, __CLASS__);

      return (int) ($diff / DAYS);
   }


   /**
    * Gibt eine Anzahl von Datumswerten zurück.
    *
    * @param string $startDate - Startzeitpunkt (Format: yyyy-mm-dd)
    * @param int    $days      - Anzahl der zurückzugebenden Werte
    *
    * @return array
    */
   public static function getDateRange($startDate, $days) {
      if (!is_string($startDate))                throw new IllegalTypeException('Illegal type of parameter $startDate: '.getType($startDate));
      if (!CommonValidator ::isDate($startDate)) throw new plInvalidArgumentException('Invalid argument $startDate: "'.$startDate.'"');
      if (!is_int($days))                        throw new IllegalTypeException('Illegal type of parameter $days: '.getType($days));
      if ($days < 0)                             throw new plInvalidArgumentException('Invalid argument $days: '.$days);

      $range = array();
      $date  = new DateTime($startDate);

      for ($i=0; $i < $days; ++$i) {
         $range[] = $date->format('Y-m-d');
         $date->modify('+1 day');
      }
      return $range;
   }
}
