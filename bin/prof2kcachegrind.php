#!/usr/bin/php
<?
error_reporting(E_ALL | E_STRICT);

define('APPLICATION_NAME', 'prof2kcachegrind');
define('APPLICATION_ROOT',  dirName(__FILE__));


// MiniStruts einbinden
require(dirName(__FILE__).'/../src/phpLib.php');


// Kommandozeilenargumente einlesen
$arguments = parseArguments('f:');

$options = array();
foreach ($arguments[0] as $arg) {
   $options[$arg[0]] = $arg[1];
}

if (!isSet($options['f'])) {
   printUsage();
}

if (!file_exists($options['f'])) {
   print("Trace file {$opt['f']} does not exist\n");
   exit(1);
}

$IN = fOpen($options['f'], "r");
if (!$IN) {
   print("Trace file {$opt['f']} could not be opened\n");
   exit(1);
}

$path_parts = pathInfo($options['f']);
$outfile = "cachegrind.out.".$path_parts['basename'];
$OUT = fOpen($outfile, "w");
if (!$OUT) {
   print("Destination file $outfile could not be opened.\n");
   exit(1);
}

while (($line = fGets($IN)) !== false) {
   $line = rTrim($line);
   if ($line == "END_HEADER") {
      break;
   }
}

$tree = array();
$callstack = array();

while (($line = fGets($IN)) !== false) {
   $line = rTrim($line);
   $args = explode(" ", $line);
   if ($args[0] == '!') {
      $file_lookup[$args[1]] = $args[2];
   }
   elseif ($args[0] == '&') {
      $function_lookup[$args[1]] = $args[2];
      $function_type[$args[1]] = ($args[3] == 2)?"USER":"INTERNAL";
   }
   elseif ($args[0] == '+') {
      $val = array('function_id' => $args[1],
                   'file_id'     => $args[2],
                   'line'        => $args[3],
                   'cost'        => 0);
      array_push($callstack, $val);
   }
   elseif ($args[0] == '-') {
       // retrieve $called to discard
       $called = array_pop($callstack);
       // retrieve $caller for reference
       $caller = array_pop($callstack);
       $called_id = $called['function_id'];

       // Set meta data if not already set
       if (!array_key_exists($called_id, $tree)) {
          $tree[$called_id] = $called;
          // initialize these to 0
          $tree[$called_id]['cost_per_line'] = array();
       }
       if ($caller !== null) {
          @$caller['child_calls']++;
          $caller_id = $caller['function_id'];
          if (!array_key_exists($caller_id, $tree)) {
             $tree[$caller_id] = $caller;
          }
          $caller['cost'] += $called['cost'];
          @$tree[$caller_id]['called_funcs'][$tree[$caller_id]['call_counter']++][$called_id][$called['file_id']][$called['line']] += $called['cost'];
          array_push($callstack, $caller);
       }
       if (is_array($called['cost_per_line'])) {
          foreach ($called['cost_per_line'] as $file => $lines) {
             foreach ($lines as $line => $cost) {
                @$tree[$called_id]['cost_per_line'][$file][$line] += $cost;
             }
          }
       }
   }
   elseif($args[0] == '@') {
      $called = array_pop($callstack);
      switch (count($args)) {
         case 6:
            $file = $args[1];
            $line = $args[2];
            $real_tm = $args[5];
            break;
         case 4:
            $file = $called['file_id'];
            $line = $called['line'];
            $real_tm = $args[3];
            break;
      }
      @$called['cost_per_line'][$file][$line] += $real_tm;
       $called['cost'] += $real_tm;
      @$total_cost += $real_tm;
      array_push($callstack, $called);
   }
}

ob_start();
print("events: Tick\n");
print("summary: $total_cost\n");
printF("cmd: %s\n", $file_lookup[1]);
print("\n");

foreach ($tree as $caller => $data) {
   $filename = $file_lookup[$data['file_id']]?$file_lookup[$data['file_id']]:"???";
   printF("ob=%s\n", $function_type[$caller]);
   printF("fl=%s\n", $filename);
   printF("fn=%s\n", $function_lookup[$caller]);
   if (is_array($data['cost_per_line'])) {
      foreach ($data['cost_per_line'] as $file => $lines) {
         foreach ($lines as $line => $cost) {
            print("$line $cost\n");
         }
      }
   }
   elseif ($data['cost']) {
      printF("COST %s %s\n", $items['line'], $items['cost']);
   }
   else {
      print_r($items);
   }
   if (isSet($data['called_funcs']) && is_array($data['called_funcs'])) {
      foreach ($data['called_funcs'] as $counter => $items) {
         foreach ($items as $called_id => $costs) {
            if (is_array($costs)) {
               printF("cob=%s\n", $function_type[$called_id]);
               printF("cfn=%s\n", $function_lookup[$called_id]);
               foreach ($costs as $file => $lines) {
                  printF("cfi=%s\ncalls=1\n", $file_lookup[$file]);
                  foreach ($lines as $line => $cost) {
                     print("$line $cost\n");
                  }
               }
            }
         }
      }
   }
   print("\n");
}

$buffer = ob_get_clean();
print("Writing kcachegrind compatible output to $outfile\n");
fWrite($OUT, $buffer);


/**
 * Liest die Kommandozeilenargumente ein und parst sie.
 */
function parseArguments($pattern) {
   $args = getArgvArray();
   if (empty($args))
      return array(array(), array());

   $options    = array();
   $nonOptions = array();

   reset($args);
   while (list($i, $arg) = each($args)) {
      if ($arg == '--') {
         // '--' means explicit end of options
         $nonOptions = array_merge($nonOptions, array_slice($args, $i + 1));
         break;
      }
      if ($arg{0} != '-' || (strLen($arg) > 1 && $arg{1} == '-') || $arg == '-') {
         // '-' is stdin
         $nonOptions = array_merge($nonOptions, array_slice($args, $i));
         break;
      }

      try {
         parseShortCmdOption(subStr($arg, 1), $pattern, $options, $args);
      }
      catch (plInvalidArgumentException $ex) {
         echo $ex->getMessage()."\n";
         printUsage();
      }
   }
   return array($options, $nonOptions);
}


/**
 */
function parseShortCmdOption($param, $pattern, &$results, &$args) {
   for ($i=0; $i < strLen($param); $i++) {
      $option = $param{$i};
      $optionArg = null;

      // Try to find the option in the pattern string
      if (($specifier = strStr($pattern, $option)) === false || $param{$i} == ':') {
         throw new plInvalidArgumentException("unrecognized option: $option");
      }

      if (strLen($specifier) > 1 && $specifier{1} == ':') {
         if (strLen($specifier) > 2 && $specifier{2} == ':') {
            if ($i + 1 < strLen($param)) {
               // Option takes an optional argument. Use the remainder of the arg string if there is anything left.
               $results[] = array($option, subStr($param, $i + 1));
               break;
            }
         }
         else {
            // Option requires an argument. Use the remainder of the arg string if there is anything left.
            if ($i + 1 < strLen($param)) {
               $results[] = array($option, subStr($param, $i + 1));
               break;
            }
            if (!(list(, $optionArg) = each($args)) || isShortCmdOption($optionArg)) {
               throw new plInvalidArgumentException("option requires an argument: $option");
            }
         }
      }
      $results[] = array($option, $optionArg);
   }
}


/**
 */
function isShortCmdOption($param) {
   return strLen($param) == 2 && $param{0} == '-' && preg_match('/[a-zA-Z]/', $param{1});
}


/**
 * Syntax error, print help screen.
 */
function printUsage() {
    print <<<EOD

pprof2calltree -f <tracefile>

EOD;
    exit(1);
}
?>
