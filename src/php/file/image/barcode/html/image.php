<?
/**
 * @version  0.0.7a  2001-04-01
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 *
 * @author   refactored & extended by pewa
 */

if (!isSet($style )) $style  = BCD_DEFAULT_STYLE;
if (!isSet($width )) $width  = BCD_DEFAULT_WIDTH;
if (!isSet($height)) $height = BCD_DEFAULT_HEIGHT;
if (!isSet($xres  )) $xres   = BCD_DEFAULT_XRES;
if (!isSet($font  )) $font   = BCD_DEFAULT_FONT;

switch ($type) {
   case "I25":
      $barcode = new I2Of5BarCode($width, $height, $style, $code);
      break;
   case "C39":
      $barcode = new C39BarCode($width, $height, $style, $code);
      break;
   case "C128A":
      $barcode = new C128ABarCode($width, $height, $style, $code);
      break;
   case "C128B":
      $barcode = new C128BBarCode($width, $height, $style, $code);
      break;
   case "C128C":
      $barcode = new C128CBarCode($width, $height, $style, $code);
      break;
   default:
      echo "Need bar code type ex. C39";
      $barcode = null;
}

if ($barcode) {
   $barcode->SetFont($font);
   $barcode->DrawObject($xres);
   $barcode->FlushObject();
}
?>
