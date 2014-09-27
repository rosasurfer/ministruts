<?php
/**
 * ApdProfile
 *
 * Helferklasse, die ein APD-Profile auf verschiedene Weise auswerten und an der Konsole oder als HTML
 * darstellen kann.
 */
final class ApdProfile extends Object {


   // Dumpeinstellungen
   private $config  = array();

   // Anzeigeoptionen
   private $options = null;   // array


   private $files                 = array();    // alle inkludierten Dateien

   private $functions             = array();    // alle aufgerufenen Funktionen
   private $functionTypes         = array();    // Typ der jeweiligen Funktion
   private $functionCalls         = array();    // Aufrufe der jeweiligen Funktion
   private $functionUserTime      = array();    // User-µSekunden in der jeweiligen Funktion   (ohne Subroutinen)
   private $functionSystemTime    = array();    // System-µSekunden in der jeweiligen Funktion (ohne Subroutinen)
   private $functionRealTime      = array();    // Reale µSekunden in der jeweiligen Funktion  (ohne Subroutinen)
   private $functionCumUserTime   = array();    // User-µSekunden in der jeweiligen Funktion   (mit Subroutinen)
   private $functionCumSystemTime = array();    // System-µSekunden in der jeweiligen Funktion (mit Subroutinen)
   private $functionCumRealTime   = array();    // Reale µSekunden in der jeweiligen Funktion  (mit Subroutinen)
   private $functionMemoryUsage   = array();    // Speicherverbrauch der jeweiligen Funktion

   private $totalUserTime         = 0;          // total verbrauchte Userzeit
   private $totalSystemTime       = 0;          // total verbrauchte Systemzeit
   private $totalRealTime         = 0;          // total verbrauchte reale Zeit


   private $comparators = array('r' => 'compareByRealTime'               ,
                                'R' => 'compareByCumulatedRealTime'      ,
                                's' => 'compareBySystemTime'             ,
                                'S' => 'compareByCumulatedSystemTime'    ,
                                'u' => 'compareByUserTime'               ,
                                'U' => 'compareByCumulatedUserTime'      ,
                                'z' => 'compareByUserSystemTime'         ,
                                'Z' => 'compareByCumulatedUserSystemTime',
                                'l' => 'compareByCalls'                  ,
                                'v' => 'compareByCallTime'               ,
                                'm' => 'compareByMemoryUsage'            ,
                                'n' => 'compareByName'                   ,
                                );


   /**
    * Konstruktor
    *
    * @param string $file - Name der Dumpdatei mit den Profilerdaten
    */
   public function __construct($file) {
      if (!is_string($file)) throw new IllegalTypeException('Illegal type of parameter $file: '.getType($file));
      if (!strLen($file))    throw new plInvalidArgumentException('Invalid argument $file: '.$file);
      if (!is_file($file))   throw new FileNotFoundException('File not found: '.$file);

      // Datei einlesen
      $hFile = fOpen($file, 'r');
      $this->parseInfoBlock('HEADER', $hFile);
      $this->parseDataBlock($hFile);
      $this->parseInfoBlock('FOOTER', $hFile);
      fClose($hFile);

      // tatsächlichen Aufrufer einlesen
      if (is_file($file.'.caller')) {
         $hFile = fOpen($file.'.caller', 'r');
         $this->parseInfoBlock('HEADER', $hFile);
         fClose($hFile);
      }

      // Daten sortieren
      $this->sortData();
   }


   /**
    * Zeigt den Profilerreport an.
    */
   public function display() {
      $this->renderHeader();
      $this->renderData();
   }


   /**
    */
   private function parseInfoBlock($tag, $hFile) {
      while ($line = fGets($hFile)) {
         $line = rTrim($line);

         if ($line == 'END_'.$tag)
            break;

         if (preg_match('/(\w+)=(.*)/', $line, $matches)) {
            $this->config[$matches[1]] = $matches[2];
         }
      }
   }


   /**
    */
   private function parseDataBlock($hFile) {
      $callstack  = array();
      $lastMemory = 0;

      while ($line = fGets($hFile)) {
         $line = rTrim($line);

         if ($line == 'END_TRACE')
            break;

         list($token, $data) = explode(' ', $line, 2);

         switch ($token) {
            case '!':
               // A file is encountered, it is assigned an index.
               list($index, $file) = explode(' ', $data, 2);
               $this->files[$index] = $file;
               break;

            case '&':
               // A function is encountered, it is assigned an index and is noted as internal '1' or userspace function '2'.
               list($index, $function, $type) = explode(' ', $data, 3);
               $this->functions            [$index] = str_replace('->', '::', $function).'()';
               $this->functionTypes        [$index] = $type;

               $this->functionCalls        [$index] = 0;
               $this->functionUserTime     [$index] = 0;
               $this->functionSystemTime   [$index] = 0;
               $this->functionRealTime     [$index] = 0;
               $this->functionCumUserTime  [$index] = 0;
               $this->functionCumSystemTime[$index] = 0;
               $this->functionCumRealTime  [$index] = 0;
               $this->functionMemoryUsage  [$index] = 0;
               break;

            case '+':
               // A function is called from a file at a line.
               list($function, $file, $line) = explode(' ', $data, 3);

               // skip it if builtin function reporting is disabled
               if ($this->isOption('i') && $this->functionTypes[$function] == '1')
                  break;

               $this->functionCalls[$function]++;
               array_push($callstack, $function);  // bei Betreten der Funktion wird sie auf den Stack gepackt
               break;

            case '@':
               // A function timing is recorded at file, line: it took (3) user µsecs, (4) system µsecs, (5) realtime µsecs.
               list($file, $line, $user, $system, $real) = explode(' ', $data);

               $current = array_pop($callstack);   // Timings werden VORM Verlassen der Funktion gemessen (sie ist noch auf dem Stack)
               $this->functionUserTime  [$current] += $user;
               $this->functionSystemTime[$current] += $system;
               $this->functionRealTime  [$current] += $real;

               foreach ($callstack as $function) {
                  $this->functionCumUserTime  [$function] += $user;
                  $this->functionCumSystemTime[$function] += $system;
                  $this->functionCumRealTime  [$function] += $real;
               }
               array_push($callstack, $current);

               $this->totalUserTime   += $user;
               $this->totalSystemTime += $system;
               $this->totalRealTime   += $real;
               break;

            case '-':
               // A function call ends, memory usage is recorded but near worthless.
               list($function, $memory) = explode(' ', $data, 2);

               // skip it if builtin function reporting is disabled
               if ($this->isOption('i') && $this->functionTypes[$function] == '1')
                  break;

               if ($lastMemory)
                  $this->functionMemoryUsage[$function] += ($memory - $lastMemory);

               $lastMemory = $memory;
               array_pop($callstack);     // bei Verlassen der Funktion wird sie vom Stack entfernt
               break;
         }
      }
   }


   /**
    * Sortiert die eingelesenen Daten.
    */
   private function sortData() {
      if ($this->functions) {
         $order      = $this->getSortOrder();
         $comparator = $this->comparators[$order];

         ukSort($this->functions, array($this, $comparator));
      }
   }


   /**
    * Gibt den Header des Reports aus.
    */
   private function renderHeader() {
      $d = 2;
      $m = $d + 1 + strLen((string)(int) ($this->totalRealTime/1000000));

      $header = "
      Trace for %s

      Total Elapsed Time = %$m.{$d}f
      Total User Time    = %$m.{$d}f
      Total System Time  = %$m.{$d}f
      ";
      echoPre(sprintf($header, $this->config['caller'], $this->totalRealTime/1000000, $this->totalUserTime/1000000, $this->totalSystemTime/1000000));
   }


   /**
    * Gibt den Datenbereich des Reports aus.
    */
   private function renderData() {
      $files                 = $this->files;

      $functions             = $this->functions;
      $functionTypes         = $this->functionTypes;
      $functionCalls         = $this->functionCalls;
      $functionUserTime      = $this->functionUserTime;
      $functionSystemTime    = $this->functionSystemTime;
      $functionRealTime      = $this->functionRealTime;
      $functionCumUserTime   = $this->functionCumUserTime;
      $functionCumSystemTime = $this->functionCumSystemTime;
      $functionCumRealTime   = $this->functionCumRealTime;
      $functionMemoryUsage   = $this->functionMemoryUsage;

      $totalUserTime         = $this->totalUserTime  /1000000;
      $totalSystemTime       = $this->totalSystemTime/1000000;
      $totalRealTime         = $this->totalRealTime  /1000000;

      $string = "
                Real         User        System            secs/   cumm
      %Time  (excl/cumm)  (excl/cumm)  (excl/cumm)  Calls  call    s/call  Memory Usage   Name
      ----------------------------------------------------------------------------------------";
      echoPre($string);


      $limit = $this->getLimit();
      $line  = 0;

      foreach ($this->functions as $i => $function) {
         if ($this->isOption('i') && $this->functionTypes[$i] == '1')
            continue;

         if ($limit && ++$line > $limit)
            break;

         $realTime      = $functionRealTime     [$i]/1000000;
         $userTime      = $functionUserTime     [$i]/1000000;
         $systemTime    = $functionSystemTime   [$i]/1000000;
         $cumRealTime   = $functionCumRealTime  [$i]/1000000;
         $cumUserTime   = $functionCumUserTime  [$i]/1000000;
         $cumSystemTime = $functionCumSystemTime[$i]/1000000;
         $calls         = $functionCalls        [$i];
         $memory        = $functionMemoryUsage  [$i];

         $percent = 0;

         switch ($this->getSortOrder()) {
            case 'R':         // compareByCumulatedRealTime
               $perCall    = $realTime   /$calls;
               $cumPerCall = $cumRealTime/$calls;
               if ($totalRealTime)
                  $percent = 100 * $cumRealTime/$totalRealTime;
               break;

            case 'u':         // compareByUserTime
               $perCall    = $userTime   /$calls;
               $cumPerCall = $cumUserTime/$calls;
               if ($totalUserTime)
                  $percent = 100 * $userTime/$totalUserTime;
               break;

            case 'U':         // compareByCumulatedUserTime
               $perCall    = $userTime   /$calls;
               $cumPerCall = $cumUserTime/$calls;
               if ($totalUserTime)
                  $percent = 100 * $cumUserTime/$totalUserTime;
               break;

            case 's':         // compareBySystemTime
               $perCall    = $systemTime   /$calls;
               $cumPerCall = $cumSystemTime/$calls;
               if ($totalSystemTime)
                  $percent = 100 * $systemTime/$totalSystemTime;
               break;

            case 'S':         // compareByCumulatedSystemTime
               $perCall    = $systemTime   /$calls;
               $cumPerCall = $cumSystemTime/$calls;
               if ($totalSystemTime)
                  $percent = 100 * $cumSystemTime/$totalSystemTime;
               break;

            case 'z':         // compareByUserSystemTime
               $perCall    = ($userTime    + $systemTime   )/$calls;
               $cumPerCall = ($cumUserTime + $cumSystemTime)/$calls;
               if ($totalUserTime + $totalSystemTime)
                  $percent = 100 * ($userTime + $systemTime)/($totalUserTime + $totalSystemTime);
               break;

            case 'Z':         // compareByCumulatedUserSystemTime
               $perCall    = ($userTime    + $systemTime   )/$calls;
               $cumPerCall = ($cumUserTime + $cumSystemTime)/$calls;
               if ($totalUserTime + $totalSystemTime)
                  $percent = 100 * ($cumUserTime + $cumSystemTime)/($totalUserTime + $totalSystemTime);
               break;

            default:          // wie compareByRealTime
               $perCall    = $realTime   /$calls;
               $cumPerCall = $cumRealTime/$calls;
               if ($totalRealTime)
                  $percent = 100 * $realTime/$totalRealTime;
         }

         $string = '      %5.01f %5.02f %5.02f  %5.02f %5.02f  %5.02f %5.02f  %5d  %7.04f %7.04f  %12d   %s';
         echoPre(sprintf($string, $percent, $realTime, $cumRealTime, $userTime, $cumUserTime, $systemTime, $cumSystemTime, $calls, $perCall, $cumPerCall, $memory, $function));
      }
   }


   /**
    * Ob die angegebene Anzeigeoption gesetzt ist.
    *
    * @return bool
    */
   private function parseOptions() {
      $this->options = array();

      $console = !isSet($_SERVER['REQUEST_METHOD']);

      if ($console) {
      }
      else {
         $request = Request ::me();

         if (isSet($_REQUEST['order']) && isSet($this->comparators[$_REQUEST['order']])) {
            $order = $_REQUEST['order'];
            $this->options['order'] = $order;
            $this->options[$order]  = true;
         }
         if (isSet($_REQUEST['limit'])) {
            $limit = trim($_REQUEST['limit']);

            if (cType_digit($limit))
               $this->options['O'] = $this->options['limit']  = (int) $limit;
         }
      }
   }


   /**
    * Ob die angegebene Anzeigeoption gesetzt ist.
    *
    * @return bool
    */
   private function isOption($option) {
      if ($this->options === null)
         $this->parseOptions();

      return array_key_exists($option, $this->options);
   }


   /**
    * Gibt die angegebene Anzeigeoption zurück.
    *
    * @return string
    */
   private function getOption($option) {
      if ($this->isOption($option))
         return $this->options[$option];

      return null;
   }


   /**
    * Gibt einen Bezeichner für die gewünschte Sortierreihenfolge zurück.  Dieser Bezeichner entspricht
    * auch bei HTTP-Aufruf dem Bezeichner an der Konsole.
    *
    * @return string - Bezeichner
    */
   private function getSortOrder() {
      if ($order = $this->getOption('order'))
         return $order;

      return 'r';  // Default
   }


   /**
    * Gibt die Anzahl höchstens anzuzeigender Datensätze zurück.
    *
    * @return int - Anzahl
    */
   private function getLimit() {
      $limit = $this->getOption('O');
      if ($limit !== null)
         return $limit;

      return 40;     // Default
   }


   /**
    */
   private function compareByRealTime($a, $b) {
      $result = self:: compareIntegers($this->functionRealTime[$b], $this->functionRealTime[$a]);
      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareByCumulatedRealTime($a, $b) {
      $result = self:: compareIntegers($this->functionCumRealTime[$b], $this->functionCumRealTime[$a]);
      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareBySystemTime($a, $b) {
      $result = self:: compareIntegers($this->functionSystemTime[$b], $this->functionSystemTime[$a]);
      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareByCumulatedSystemTime($a, $b) {
      $result = self:: compareIntegers($this->functionCumSystemTime[$b], $this->functionCumSystemTime[$a]);
      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareByUserTime($a, $b) {
      $result = self:: compareIntegers($this->functionUserTime[$b], $this->functionUserTime[$a]);
      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareByCumulatedUserTime($a, $b) {
      $result = self:: compareIntegers($this->functionCumUserTime[$b], $this->functionCumUserTime[$a]);
      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareByUserSystemTime($a, $b) {
      $sa = $this->functionUserTime[$a] + $this->functionSystemTime[$a];
      $sb = $this->functionUserTime[$b] + $this->functionSystemTime[$b];
      $result = self:: compareIntegers($sb, $sa);

      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareByCumulatedUserSystemTime($a, $b) {
      $sa = $this->functionCumUserTime[$a] + $this->functionCumSystemTime[$a];
      $sb = $this->functionCumUserTime[$b] + $this->functionCumSystemTime[$b];
      $result = self:: compareIntegers($sb, $sa);

      if (!$result)
         $result = self:: compareIntegers($a, $b);
      return $result;
   }


   /**
    */
   private function compareByCalls($a, $b) {
      $result = self:: compareIntegers($this->functionCalls[$b], $this->functionCalls[$a]);
      if (!$result)
         $result = self:: compareByName($a, $b);
      return $result;
   }


   /**
    */
   private function compareByCallTime($a, $b) {
      $sa = ($this->functionUserTime[$a] + $this->functionSystemTime[$a])/$this->functionCalls[$a];
      $sb = ($this->functionUserTime[$b] + $this->functionSystemTime[$b])/$this->functionCalls[$b];
      $result = self:: compareFloats($sb, $sa);

      if (!$result)
         $result = self:: compareByCalls($a, $b);
      return $result;
   }


   /**
    */
   private function compareByMemoryUsage($a, $b) {
      $result = self:: compareIntegers($this->functionMemoryUsage[$b], $this->functionMemoryUsage[$a]);
      if (!$result)
         $result = self:: compareByName($a, $b);
      return $result;
   }


   /**
    */
   private function compareByName($a, $b) {
      $a = $this->functions[$a];
      $b = $this->functions[$b];

      $al = ($a{0} == strToLower($a{0}));
      $bl = ($b{0} == strToLower($b{0}));

      // Kleine Anfangsbuchstaben werden vor große sortiert
      if (($al && $bl) || (!$al && !$bl))
         return self:: compareStrings($a, $b);

      return $al ? -1 : 1;
   }


   /**
    */
   private static function compareIntegers($a, $b) {
      if     ((int) $a > (int) $b) { return  1; }
      elseif ((int) $a < (int) $b) { return -1; }
      else                         { return  0; }
   }


   /**
    */
   private static function compareFloats($a, $b) {
      if     ((float) $a > (float) $b) { return  1; }
      elseif ((float) $a < (float) $b) { return -1; }
      else                             { return  0; }
   }


   /**
    */
   private static function compareStrings($a, $b) {
      if     ((string) $a > (string) $b) { return  1; }
      elseif ((string) $a < (string) $b) { return -1; }
      else                               { return  0; }
   }
}
?>
