<?php
use rosasurfer\exception\InvalidArgumentException;


$type   = isSet($_REQUEST['type'  ]) ? $_REQUEST['type'  ] : null;
$value  = isSet($_REQUEST['value' ]) ? $_REQUEST['value' ] : '';
$width  = isSet($_REQUEST['width' ]) ? $_REQUEST['width' ] : null;
$height = isSet($_REQUEST['height']) ? $_REQUEST['height'] : null;
$style  = isSet($_REQUEST['style' ]) ? $_REQUEST['style' ] : null;
$xres   = isSet($_REQUEST['xres'  ]) ? $_REQUEST['xres'  ] : null;
$font   = isSet($_REQUEST['font'  ]) ? $_REQUEST['font'  ] : null;

switch ($type) {
   case "I25":
   case "C39":
   case "C128A":
   case "C128B":
   case "C128C":
      $class   = "{$type}BarCode";
      $barcode = new $class($value, $width, $height, $style, $xres, $font);
      $barcode->stream();
      break;

   default:
      throw new InvalidArgumentException("Unknown barcode type \"$type\"");
}
