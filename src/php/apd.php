<?
// ob das Script in der Konsole oder auf einem Webserver ausgeführt wird
$console = !isSet($_SERVER['REQUEST_METHOD']);

if (!$console) {
   $request = Request ::me();

   if (!isSet($_REQUEST['file'])) {
      echoPre('Missing "file" parameter');
      die();
   }

   $file    = $_REQUEST['file'];
   $profile = new ApdProfile($file);
   $profile->display();
   die();
}



// -------------------------------------------------------------------------------------------------


// Kommandozeilenargumente einlesen
$arguments = parseArguments('cilmO:rRsStTuUvzZ');

$options['O'] = 40;
foreach ($arguments[0] as $arg) {
   $options[$arg[0]] = $arg[1];
}


// Datei öffnen
($arguments[1] && ($dataFile = $arguments[1][0])) || printUsage();
if (($hFile = fOpen($dataFile, 'r')) == false) {
   print("Failed to open \"$dataFile\" for reading\n");
   exit(1);
}

// Header einlesen
$config = array();
parseInfoBlock('HEADER', $hFile, $config);

$files         = array();
$functions     = array();
$functionTypes = array();

$callstack     = array();
$calls         = array();
$mem           = array();
$rtimes        = array();
$utimes        = array();
$stimes        = array();
$c_rtimes      = array();
$c_utimes      = array();
$c_stimes      = array();
$rtotal        = 0;
$utotal        = 0;
$stotal        = 0;

$lastMemory    = 0;
$lastFunction  = null;
$indent_cur    = 0;
$indent_last   = null;
$repcnt        = 0;


// TODO: Fehler, wenn Profiler in einer Funktion aktiviert wird (entry/exit-Level unausgeglichen)

// Datenbereich zeilenweise einlesen
while ($line = fGets($hFile)) {
   $line = rTrim($line);
   if (preg_match('/^END_TRACE/', $line))
      break;

   list($token, $data) = preg_split('/ /', $line, 2);

   // A file (2) is encountered, it is assigned an index (1).
   if ($token == '!') {
      list($index, $file) = preg_split('/ /', $data, 2);
      $files[$index] = $file;
      continue;
   }

   // A function (2) is encountered, it is assigned an index (1) and is noted as internal '1' or userspace function '2' (3).
   if ($token == '&') {
      list($index, $name, $type) = preg_split('/ /', $data, 3);
      $functions    [$index] = str_replace('->', '::', $name).'()';
      $functionTypes[$index] = $type;
      $calls        [$index] = 0;
      $utimes       [$index] = 0;
      $stimes       [$index] = 0;
      $rtimes       [$index] = 0;
      $c_utimes     [$index] = 0;
      $c_stimes     [$index] = 0;
      $c_rtimes     [$index] = 0;
      $mem          [$index] = 0;
      continue;
   }

   // An indexed function (1) is called from file (2) at line (3).
   if ($token == '+') {
      list($function, $file, $line) = preg_split('/ /', $data, 3);
      // skip it if builtin function reporting is disabled
      if (isOptionSet('i') && $functionTypes[$function] == 1)
         continue;

      $calls[$function]++;
      array_push($callstack, $function);
      if (isOptionSet('T')) {
         if (isOptionSet('c')) {
            printF('%2.02f ', $rtotal/1000000);
         }
         print(str_repeat('  ', $indent_cur).$functions[$function]."\n");
         if (isOptionSet('m')) {
            print(str_repeat('  ', $indent_cur)."C: $files[$file]:$line M: $memory\n");
         }
      }
      elseif (isOptionSet('t')) {
         if ($indent_last == $indent_cur && $lastFunction == $function) {
            ++$repcnt;
         }
         else {
            if ($repcnt) {
               $repstr = ' ('.++$repcnt.'x)';
            }
            if (isOptionSet('c')) {
               printF('%2.02f ', $rtotal/1000000);
            }
            if ($lastFunction !== null)
               print(str_repeat('  ', $indent_last).$functions[$lastFunction].$repstr."\n");
            if (isOptionSet('m'))
               print(str_repeat('  ', $indent_cur)."C: $files[$file_last]:$line_last M: $memory\n");

            $repstr       = '';
            $repcnt       = 0;
            $lastFunction = $function;
            $indent_last  = $indent_cur;
            $file_last    = $file;
            $line_last    = $line;
         }
      }
      ++$indent_cur;
      continue;
   }

   // A timing is recorded at file (1), line (2): (3) user usecs, (4) system usecs, (5) realtime usecs.
   if ($token == '@') {
      list($file_no, $line_no, $ut, $st, $rt) = preg_split("/ /", $data);

      $utotal += $ut;
      $stotal += $st;
      $rtotal += $rt;

      $function = array_pop($callstack);

      $utimes[$function] += $ut;
      $stimes[$function] += $st;
      $rtimes[$function] += $rt;

      foreach ($callstack as $function) {
         $c_utimes[$function] += $ut;
         $c_stimes[$function] += $st;
         $c_rtimes[$function] += $rt;
      }

      array_push($callstack, $function);
      continue;
   }

   // A function call ends (1), script memory usage is also recorded (2), but near worthless.
   if ($token == '-') {
      list($function, $memory) = preg_split("/ /", $data, 2);
      // skip it if builtin function reporting is disabled
      if (isOptionSet('i') && $functionTypes[$function] == 1)
         continue;

      $mem[$function] += ($memory - $lastMemory);
      $lastMemory = $memory;
      --$indent_cur;
      $tmp = array_pop($callstack);
   }
}


// Footer einlesen
parseInfoBlock('FOOTER', $hFile, $config);


// Sortierung festlegen und Ergebnisse sortieren
$comparator = 'sortByUserSystemTime';

if (isOptionSet('l')) { $comparator = 'sortByCalls'                  ; }
if (isOptionSet('m')) { $comparator = 'sortByMemory'                 ; }
if (isOptionSet('a')) { $comparator = 'sortByName'                   ; }
if (isOptionSet('v')) { $comparator = 'sortByAvgCPU'                 ; }
if (isOptionSet('r')) { $comparator = 'sortByRealTime'               ; }
if (isOptionSet('R')) { $comparator = 'sortByCumulatedRealTime'      ; }
if (isOptionSet('s')) { $comparator = 'sortBySystemTime'             ; }
if (isOptionSet('S')) { $comparator = 'sortByCumulatedSystemTime'    ; }
if (isOptionSet('u')) { $comparator = 'sortByUserTime'               ; }
if (isOptionSet('U')) { $comparator = 'sortByCumulatedUserTime'      ; }
if (isOptionSet('Z')) { $comparator = 'sortByCumulatedUserSystemTime'; }

if ($functions) {
   ukSort($functions, $comparator);
}


// Header ausgeben
printF("
Trace for %s
Total Elapsed Time = %4.2f
Total User Time    = %4.2f
Total System Time  = %4.2f
", $config['caller'], $rtotal/1000000, $utotal/1000000, $stotal/1000000);


// Daten berechnen und ausgeben
print("\n
          Real         User        System            secs/   cumm
%Time  (excl/cumm)  (excl/cumm)  (excl/cumm)  Calls  call    s/call  Memory Usage   Name
----------------------------------------------------------------------------------------\n");
$l = $itotal = $percall = $cpercall = 0;


foreach ($functions as $j => $name) {
   if (isOptionSet('i') && $functionTypes[$j] == 1)
      continue;

   if ($l++ < $options['O']) {
      $pcnt    = 100 * ($stimes  [$j] + $utimes  [$j])/($utotal + $stotal + $itotal);
      $c_pcnt  = 100 * ($c_stimes[$j] + $c_utimes[$j])/($utotal + $stotal + $itotal);
      $rsecs   = $rtimes  [$j]/1000000;
      $ssecs   = $stimes  [$j]/1000000;
      $usecs   = $utimes  [$j]/1000000;
      $c_rsecs = $c_rtimes[$j]/1000000;
      $c_ssecs = $c_stimes[$j]/1000000;
      $c_usecs = $c_utimes[$j]/1000000;
      $ncalls  = $calls[$j];

      if (isOptionSet('z')) {
         $percall  = ($usecs   + $ssecs  )/$ncalls;
         $cpercall = ($c_usecs + $c_ssecs)/$ncalls;
         if ($utotal + $stotal) $pcnt = 100 * ($stimes[$j] + $utimes[$j])/($utotal + $stotal);
         else                   $pcnt = 100;
      }

      if (isOptionSet('Z')) {
         $percall  = ($usecs   + $ssecs  )/$ncalls;
         $cpercall = ($c_usecs + $c_ssecs)/$ncalls;
         if ($utotal + $stotal) $pcnt = 100 * ($c_stimes[$j] + $c_utimes[$j])/($utotal + $stotal);
         else                   $pcnt = 100;
      }

      if (isOptionSet('r')) {
         $percall  = ($rsecs  )/$ncalls;
         $cpercall = ($c_rsecs)/$ncalls;
         if ($rtotal) $pcnt = 100 * $rtimes[$j]/$rtotal;
         else         $pcnt = 100;
      }

      if (isOptionSet('R')) {
         $percall  = ($rsecs  )/$ncalls;
         $cpercall = ($c_rsecs)/$ncalls;
         if ($rtotal) $pcnt = 100 * $c_rtimes[$j]/$rtotal;
         else         $pcnt = 100;
      }

      if (isOptionSet('u')) {
         $percall  = ($usecs  )/$ncalls;
         $cpercall = ($c_usecs)/$ncalls;
         if ($utotal) $pcnt = 100 * $utimes[$j]/$utotal;
         else         $pcnt = 100;
      }

      if (isOptionSet('U')) {
         $percall  = ($usecs  )/$ncalls;
         $cpercall = ($c_usecs)/$ncalls;
         if ($utotal) $pcnt = 100 * $c_utimes[$j]/$utotal;
         else         $pcnt = 100;
      }

      if (isOptionSet('s')) {
         $percall  = ($ssecs  )/$ncalls;
         $cpercall = ($c_ssecs)/$ncalls;
         if ($stotal) $pcnt = 100 * $stimes[$j]/$stotal;
         else         $pcnt = 100;
      }

      if (isOptionSet('S')) {
         $percall  = ($ssecs  )/$ncalls;
         $cpercall = ($c_ssecs)/$ncalls;
         if ($stotal) $pcnt = 100 * $c_stimes[$j]/$stotal;
         else         $pcnt = 100;
      }

      //$cpercall = ($c_usecs + $c_ssecs)/$ncalls;
      $mem_usage = $mem[$j];
      printF("%5.01f %5.02f %5.02f  %5.02f %5.02f  %5.02f %5.02f  %5d  %7.04f %7.04f  %12d   %s\n", $pcnt, $rsecs, $c_rsecs, $usecs, $c_usecs, $ssecs, $c_ssecs, $ncalls, $percall, $cpercall, $mem_usage, $name);
   }
}


/**
 */
function parseInfoBlock($tag, $hFile, &$config) {
    while($line = fGets($hFile)) {
        $line = rTrim($line);
        if (preg_match("/^END_$tag$/", $line)) {
            break;
        }
        if (preg_match('/(\w+)=(.*)/', $line, $matches)) {
            $config[$matches[1]] = $matches[2];
        }
    }
}


/**
 * Ob die angegebene Option gesetzt ist.
 *
 * @return bool
 */
function isOptionSet($option) {
    return array_key_exists($option, $GLOBALS['options']);
}


/**
 */
function compareAsInt($a, $b) {
   if     ((int)$a > (int)$b) { return  1; }
   elseif ((int)$a < (int)$b) { return -1; }
   else                       { return  0; }
}


/**
 */
function sortByUserSystemTime($a, $b) {
   global $stimes;
   global $utimes;
   return compareAsInt(($stimes[$b] + $utimes[$b]), ($stimes[$a] + $utimes[$a]));
}


/**
 */
function sortByCumulatedUserSystemTime($a, $b) {
   global $c_stimes;
   global $c_utimes;
   return compareAsInt(($c_stimes[$b] + $c_utimes[$b]),($c_stimes[$a] + $c_utimes[$a]));
}


/**
 */
function sortByAvgCPU($a, $b) {
   global $stimes;
   global $utimes;
   global $calls;
   return compareAsInt(($stimes[$b] + $utimes[$b])/$calls[$b],($stimes[$a] + $utimes[$a])/$calls[$a]);
}


/**
 */
function sortByCalls($a, $b) {
   global $calls;
   return compareAsInt($calls[$b], $calls[$a]);
}


/**
 */
function sortByRealTime           ($a, $b) { global $rtimes  ; return compareAsInt($rtimes  [$b], $rtimes  [$a]); }
function sortByCumulatedRealTime  ($a, $b) { global $c_rtimes; return compareAsInt($c_rtimes[$b], $c_rtimes[$a]); }
function sortBySystemTime         ($a, $b) { global $stimes  ; return compareAsInt($stimes  [$b], $stimes  [$a]); }
function sortByCumulatedSystemTime($a, $b) { global $c_stimes; return compareAsInt($c_stimes[$b], $c_stimes[$a]); }
function sortByUserTime           ($a, $b) { global $utimes  ; return compareAsInt($utimes  [$b], $utimes  [$a]); }
function sortByCumulatedUserTime  ($a, $b) { global $c_utimes; return compareAsInt($c_utimes[$b], $c_utimes[$a]); }
function sortByMemory             ($a, $b) { global $mem     ; return compareAsInt($mem     [$b], $mem     [$a]); }


/**
 * Liest die Kommandozeilenargumente ein und parst sie.
 */
function parseArguments($pattern) {
   $options = $nonOptions = array();

   $args = getArgvArray();
   if (!$args)
      return array($options, $nonOptions);

   foreach ($args as $i => $arg) {
      if ($arg == '--') {                                                     // '--' means explicit end of options
         $nonOptions = array_merge($nonOptions, array_slice($args, $i+1));
         break;
      }
      if ($arg{0}!='-' || (strLen($arg) > 1 && $arg{1}=='-') || $arg=='-') {  // '-' is stdin
         $nonOptions = array_merge($nonOptions, array_slice($args, $i));
         break;
      }
      parseShortCmdOption(subStr($arg, 1), $pattern, $options, $args);
   }
   return array($options, $nonOptions);
}


/**
 */
function parseShortCmdOption($param, $pattern, &$results, &$args) {
   for ($i=0; $i < strLen($param); $i++) {
      $option    = $param{$i};
      $optionArg = null;

      // Look up the option in the pattern string
      if (($specifier = strStr($pattern, $option))===false || $param{$i}==':') {
         echo("unrecognized option: $option\n");
         printUsage() && die(1);
      }

      if (strLen($specifier) > 1 && $specifier{1}==':') {
         if (strLen($specifier) > 2 && $specifier{2}==':') {
            if ($i + 1 < strLen($param)) {
               // Option takes an optional argument. Use the remainder of the arg string if there is anything left.
               $results[] = array($option, subStr($param, $i+1));
               break;
            }
         }
         else {
            // Option requires an argument. Use the remainder of the arg string if there is anything left.
            if ($i + 1 < strLen($param)) {
               $results[] = array($option, subStr($param, $i+1));
               break;
            }
            if (!(list(, $optionArg) = each($args)) || isShortCmdOption($optionArg)) {
               echo("option requires an argument: $option\n");
               printUsage() && die(1);
            }
         }
      }
      $results[] = array($option, $optionArg);
   }
}


/**
 */
function isShortCmdOption($param) {
   return strLen($param)==2 && $param{0}=='-' && ctype_alpha($param{1});
}


/**
 * Syntax error, print help screen.
 */
function printUsage() {
   print <<<EOD

pprofp <flags> <trace file>
    Sort options
    -l          Sort by number of calls to subroutines.
    -m          Sort by memory used in a function call.
    -r          Sort by real time spent in subroutines (default).
    -R          Sort by real time spent in subroutines inclusive of child calls.
    -s          Sort by system time spent in subroutines.
    -S          Sort by system time spent in subroutines inclusive of child calls.
    -u          Sort by user time spent in subroutines.
    -U          Sort by user time spent in subroutines inclusive of child calls.
    -v          Sort by average amount of time spent in subroutines.
    -z          Sort by user+system time spent in subroutines.
    -Z          Sort by user+system time spent in subroutines inclusive of child calls.

    Display options
    -c          Display Real time elapsed alongside call tree.
    -i          Suppress reporting for php builtin functions.
    -O <cnt>    Specifies maximum number of subroutines to display (default 40).
    -t          Display compressed call tree.
    -T          Display uncompressed call tree.

EOD;
    exit(1);
}
?>
