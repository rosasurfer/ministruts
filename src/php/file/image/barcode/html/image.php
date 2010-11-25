<?
/**
 * @version  0.0.7a  2001-04-01
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 */

define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

if (!isSet($style )) $style  = BCD_DEFAULT_STYLE;
if (!isSet($width )) $width  = BCD_DEFAULT_WIDTH;
if (!isSet($height)) $height = BCD_DEFAULT_HEIGHT;
if (!isSet($xres  )) $xres   = BCD_DEFAULT_XRES;
if (!isSet($font  )) $font   = BCD_DEFAULT_FONT;

switch ($type) {
   case "I25":
      $obj = new I2Of5BarCode($width, $height, $style, $code);
      break;
   case "C39":
      $obj = new C39BarCode($width, $height, $style, $code);
      break;
   case "C128A":
      $obj = new C128ABarCode($width, $height, $style, $code);
      break;
   case "C128B":
      $obj = new C128BBarCode($width, $height, $style, $code);
      break;
   case "C128C":
      $obj = new C128CBarCode($width, $height, $style, $code);
      break;
   default:
      echo "Need bar code type ex. C39";
      $obj = false;
}

if ($obj) {
   $obj->SetFont($font);
   $obj->DrawObject($xres);
   $obj->FlushObject();
   $obj->DestroyObject();
   unset($obj);  /* clean */
}
?>
