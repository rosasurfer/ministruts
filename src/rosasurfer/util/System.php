<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\WINDOWS;


/**
 * General system-wide functionality
 */
class System extends StaticClass {


   /**
    * Trigger execution of the garbage collector.
    */
   public static function collectGarbage() {
      $wasEnabled = gc_enabled();
      !$wasEnabled && gc_enable();

      gc_collect_cycles();

      !$wasEnabled && gc_disable();
   }


   /**
    * Execute a shell command in a cross-platform compatible way and return STDOUT. Works around a Windows bug where
    * a DOS EOF character (0x1A = ASCII 26) in the STDOUT stream causes further reading to stop.
    *
    * @param  string $cmd - shell command to execute
    *
    * @return string - content of STDOUT
    */
   public static function shell_exec($cmd) {
      if (!is_string($cmd)) throw new IllegalTypeException('Illegal type of parameter $cmd: '.getType($cmd));

      if (!WINDOWS) return shell_exec($cmd);

      // pOpen() suffers from the same bug, probably caused by both using feof()

      $descriptors = [0 => ['pipe', 'rb'],         // stdin
                      1 => ['pipe', 'wb'],         // stdout
                      2 => ['pipe', 'wb']];        // stderr
      $pipes = [];
      $hProc = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell'=>true]);

      $stdout = stream_get_contents($pipes[1]);    // $pipes now looks like this:
      fClose($pipes[0]);                           // 0 => writeable handle connected to child stdin
      fClose($pipes[1]);                           // 1 => readable handle connected to child stdout
      fClose($pipes[2]);                           // 2 => readable handle connected to child stderr
      proc_close($hProc);                          // we MUST close the pipes before proc_close() to avoid a deadlock

      return $stdout;
   }
}
