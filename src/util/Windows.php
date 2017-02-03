<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;


/**
 * Windows constants
 */
class Windows extends StaticClass {


   /**
    * @var int - for example the maximum path on drive D is "D:\some-256-character-path-string<NUL>"
    */
   const MAX_PATH = 260;
}
