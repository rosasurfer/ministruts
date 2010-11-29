<?
define('APPLICATION_NAME', 'barcode_test');

$type   = isSet($_REQUEST['type'  ]) ? $_REQUEST['type'  ] : null;
$width  = isSet($_REQUEST['width' ]) ? $_REQUEST['width' ] : BarCode ::DEFAULT_WIDTH;
$height = isSet($_REQUEST['height']) ? $_REQUEST['height'] : BarCode ::DEFAULT_HEIGHT;
$style  = isSet($_REQUEST['style' ]) ? $_REQUEST['style' ] : BarCode ::DEFAULT_STYLE;
$xres   = isSet($_REQUEST['xres'  ]) ? $_REQUEST['xres'  ] : BarCode ::DEFAULT_XRES;
$font   = isSet($_REQUEST['font'  ]) ? $_REQUEST['font'  ] : BarCode ::DEFAULT_FONT;
$value  = isSet($_REQUEST['value' ]) ? $_REQUEST['value' ] : "";

switch ($type) {
   case "I25":
   case "C39":
   case "C128A":
   case "C128B":
   case "C128C":
      $class   = "${type}BarCode";
      $barcode = new $class($value, $width, $height, $style, $xres, $font);
      $barcode->stream();
      break;

   default:
      throw new InvalidArgumentException("Unknown barcode type \"$type\"");
}
?>
