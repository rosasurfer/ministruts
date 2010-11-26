<?
define('APPLICATION_NAME', 'barcode_test');

$type   = isSet($_REQUEST['type'  ]) ? $_REQUEST['type'  ] : null;
$width  = isSet($_REQUEST['width' ]) ? $_REQUEST['width' ] : BarCode ::DEFAULT_WIDTH;;
$height = isSet($_REQUEST['height']) ? $_REQUEST['height'] : BarCode ::DEFAULT_HEIGHT;;
$style  = isSet($_REQUEST['style' ]) ? $_REQUEST['style' ] : BarCode ::DEFAULT_STYLE;;
$font   = isSet($_REQUEST['font'  ]) ? $_REQUEST['font'  ] : BarCode ::DEFAULT_FONT;;
$xres   = isSet($_REQUEST['xres'  ]) ? $_REQUEST['xres'  ] : BarCode ::DEFAULT_XRES;;

switch ($type) {
   case "I25":
   case "C39":
   case "C128A":
   case "C128B":
   case "C128C":
      $class = "${type}BarCode";
      $barcode = new $class($width, $height, $style, $value);
      $barcode->SetFont($font);
      $barcode->DrawObject($xres);
      $barcode->FlushObject();
      break;

   default:
      // missing or invalid barcode type
}
?>
