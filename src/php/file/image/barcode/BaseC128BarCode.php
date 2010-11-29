<?
/**
 * Abstract base class for "Code 128" bar codes.
 *
 * @version  0.0.8a  2001-08-03
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 * @author   extended & refactored by pewa
 */

abstract class BaseC128BarCode extends BarCode {

   const /*int*/ DEFAULT_BAR_1 = 1;
   const /*int*/ DEFAULT_BAR_2 = 2;
   const /*int*/ DEFAULT_BAR_3 = 3;
   const /*int*/ DEFAULT_BAR_4 = 4;


   protected /*string*/   $chars   = null,
             /*string[]*/ $charSet = array();

   /**
    *
    */
   protected function getCharIndex($char) {
      $pos = strPos($this->chars, $char);
      if ($pos === false)
         return -1;
      return $pos;
   }

   /**
    *
    */
   protected function getSize() {
      $len     = strLen($this->value);
      $charLen = strLen($this->chars[0]);
      $xres    = $this->xres;

      $ret = 0;
      for ($i=0; $i<$len; $i+=$charLen) {
         $idx  = $this->getCharIndex(subStr($this->value, $i, $charLen));
         $cset = $this->charSet[$idx];
         $ret += $this->getBarSize($xres, $cset[0]);
         $ret += $this->getBarSize($xres, $cset[1]);
         $ret += $this->getBarSize($xres, $cset[2]);
         $ret += $this->getBarSize($xres, $cset[3]);
         $ret += $this->getBarSize($xres, $cset[4]);
         $ret += $this->getBarSize($xres, $cset[5]);
      }

      // length of Check character
      $checkSize = 0;
      $cset = $this->getCheckCharValue();
      for ($i=0; $i<6; $i++) {
         $checkSize += $this->getBarSize($cset[$i], $xres);
      }

      $startSize = 2*self:: DEFAULT_BAR_2*$xres + 3*self:: DEFAULT_BAR_1*$xres +   self:: DEFAULT_BAR_4*$xres;
      $stopSize  = 2*self:: DEFAULT_BAR_2*$xres + 3*self:: DEFAULT_BAR_1*$xres + 2*self:: DEFAULT_BAR_3*$xres;
      return $startSize + $ret + $checkSize + $stopSize;
   }

   /**
    *
    */
   protected function getBarSize($xres, $char) {
      switch ($char) {
         case '1':
            $cVal = self:: DEFAULT_BAR_1;
            break;
         case '2':
            $cVal = self:: DEFAULT_BAR_2;
            break;
         case '3':
            $cVal = self:: DEFAULT_BAR_3;
            break;
         case '4':
            $cVal = self:: DEFAULT_BAR_4;
            break;
         default:
            $cVal = 0;
      }
      return $cVal * $xres;
   }

   /**
    *
    */
   abstract protected function getCheckCharValue();
}
?>
