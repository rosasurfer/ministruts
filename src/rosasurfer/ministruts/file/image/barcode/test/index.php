<?php
use rosasurfer\ministruts\exceptions\InvalidArgumentException;


// input parameters
$type        = isSet($_REQUEST['type'       ]) ? $_REQUEST['type'       ] : 'I25';
$format      = isSet($_REQUEST['format'     ]) ? $_REQUEST['format'     ] : 'png';
$border      = isSet($_REQUEST['border'     ]) ? $_REQUEST['border'     ] : '0';
$drawtext    = isSet($_REQUEST['drawtext'   ]) ? $_REQUEST['drawtext'   ] : '0';
$stretchtext = isSet($_REQUEST['stretchtext']) ? $_REQUEST['stretchtext'] : '0';
$negative    = isSet($_REQUEST['negative'   ]) ? $_REQUEST['negative'   ] : '0';
$width       = isSet($_REQUEST['width'      ]) ? $_REQUEST['width'      ] : '460';
$height      = isSet($_REQUEST['height'     ]) ? $_REQUEST['height'     ] : '120';
$xres        = isSet($_REQUEST['xres'       ]) ? $_REQUEST['xres'       ] : BarCode ::DEFAULT_XRES;
$font        = isSet($_REQUEST['font'       ]) ? $_REQUEST['font'       ] : BarCode ::DEFAULT_FONT;
$value       = isSet($_REQUEST['value'      ]) ? $_REQUEST['value'      ] : null;

?>
<html>
<head>
   <title>Barcode Test Page</title>
</head>

<body bgcolor="#FFFFCC">

<table align="center" width="600" border=0>
<tr>
   <td>
      BarCode is a small implementation of a barcode rendering class using <a href="http://www.php.net/">PHP</a> language
      and the <a href="http://www.boutell.com/gd/">GD graphics library</a>.
   </td>
</tr>
<tr>
   <td><br></td>
</tr>
<tr>
   <td>Original implementation: <a href="http://www.mribti.com/barcode/">www.mribti.com</a></td>
</tr>
</table>
<br>
<br>
<br>

<form _method="post" action="?">

   <table align="center" border="1" cellpadding="1" cellspacing="1">
   <tr>
      <td bgcolor="#EFEFEF"><b>Type</b></td>
      <td>
         <select name="type" style="width:270px" size="1">
            <option value="I25"   <?=$type=='I25'   ? 'selected':null?>>Interleaved 2 of 5
            <option value="C39"   <?=$type=='C39'   ? 'selected':null?>>Code 39
            <option value="C128A" <?=$type=='C128A' ? 'selected':null?>>Code 128-A
            <option value="C128B" <?=$type=='C128B' ? 'selected':null?>>Code 128-B
            <option value="C128C" <?=$type=='C128C' ? 'selected':null?>>Code 128-C
         </select>
      </td>
   </tr>

   <tr>
      <td bgcolor="#EFEFEF"><b>Format</b></td>
      <td>
         <select name="format" style="width:270px" size="1">
            <option value="png"  <?=$format=='png' ?'selected':null?>>Portable Network Graphics (PNG)
            <option value="jpeg" <?=$format=='jpeg'?'selected':null?>>Joint Photographic Experts Group(JPEG)
         </select>
      </td>
   </tr>

   <tr>
      <td rowspan="4" bgcolor="#EFEFEF"><b>Styles</b></td>
      <td><input type="checkbox" name="border" <?=$border=='on'?'checked':null?>> Draw border</td>
   </tr>

   <tr>
      <td><input type="checkbox" name="drawtext" <?=$drawtext=='on'?'checked':null?>> Draw value text</td>
   </tr>

   <tr>
     <td><input type="checkbox" name="stretchtext" <?=$stretchtext=='on'?'checked':null?>> Stretch text</td>
   </tr>

   <tr>
     <td><input type="checkbox" name="negative" <?=$negative=='on'?'checked':null?>> Negative (white on black)</td>
   </tr>

   <tr>
     <td rowspan="2" bgcolor="#EFEFEF"><b>Size</b></td>
     <td>Width: <input type="text" size="6" name="width" value="<?=$width?>"></td>
   </tr>

   <tr>
     <td>Height: <input type="text" size="6" name="height" value="<?=$height?>"></td>
   </tr>

   <tr>
     <td bgcolor="#EFEFEF"><b>xRes</b></td>
     <td>
         <input type="radio" name="xres" value="1" <?=$xres=='1'?'checked':null?>> 1&nbsp;&nbsp;&nbsp;&nbsp;
         <input type="radio" name="xres" value="2" <?=$xres=='2'?'checked':null?>> 2&nbsp;&nbsp;&nbsp;&nbsp;
         <input type="radio" name="xres" value="3" <?=$xres=='3'?'checked':null?>> 3&nbsp;&nbsp;&nbsp;&nbsp;
     </td>
   </tr>

   <tr>
    <td bgcolor="#EFEFEF"><b>Text Font</b></td>
    <td>
        <input type="radio" name="font" value="1" <?=$font=='1'?'checked':null?>> 1&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="radio" name="font" value="2" <?=$font=='2'?'checked':null?>> 2&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="radio" name="font" value="3" <?=$font=='3'?'checked':null?>> 3&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="radio" name="font" value="4" <?=$font=='4'?'checked':null?>> 4&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="radio" name="font" value="5" <?=$font=='5'?'checked':null?>> 5&nbsp;&nbsp;&nbsp;&nbsp;
    </td>
   </tr>

   <tr>
      <td bgcolor="#EFEFEF"><b>Value</b></td>
      <td><input type="text" size="24" name="value" style="width:270px" value="<?=$value?>"></td>
   </tr>

   <tr>
      <td colspan="2" align="center"><input type="submit" name="submit" value="Create"></td>
   </tr>
   </table>

</form>

<?php
if (strLen($value) > 0) {
   $style  = BarCode ::STYLE_ALIGN_CENTER;
   $style |= ($format     =='png' ) ? BarCode ::STYLE_IMAGE_PNG     : 0;
   $style |= ($format     =='jpeg') ? BarCode ::STYLE_IMAGE_JPEG    : 0;
   $style |= ($border     =='on'  ) ? BarCode ::STYLE_BORDER        : 0;
   $style |= ($drawtext   =='on'  ) ? BarCode ::STYLE_DRAW_TEXT     : 0;
   $style |= ($stretchtext=='on'  ) ? BarCode ::STYLE_STRETCH_TEXT  : 0;
   $style |= ($negative   =='on'  ) ? BarCode ::STYLE_REVERSE_COLOR : 0;

   switch ($type) {
      case 'I25'  :
      case 'C39'  :
      case 'C128A':
      case 'C128B':
      case 'C128C':
         $class   = "{$type}BarCode";
         $barcode = new $class($value, $width, $height, $style, $xres, $font);

         $content     = $barcode->toString();
         $contentType = $barcode->getContentType();
         $data        = base64_encode($content);

         $params = "type=$type&value=$value&width=$width&height=$height&style=$style&xres=$xres&font=$font";
         ?>
         <table align="center"><tr><td><a href="image.php?<?=$params?>" target="barcode_image"><img src="data:<?=$contentType?>;base64,<?=$data?>" border=0></a></td></tr></table>
         <?php
         break;

      default:
         throw new InvalidArgumentException("Unknown barcode type \"$type\"");
   }
}
else if (isSet($_REQUEST['submit'])) {
   ?> <table align="center"><tr><td><font color="red">missing barcode value</font></td></tr></table> <?php
}
?>

</body>
</html>
