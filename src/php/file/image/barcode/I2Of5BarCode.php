<?
/**
 * Barcode renderer for "Interleaved 2 of 5", a numeric only bar code with an optional check number.
 *
 * @version  0.0.7a  2001-04-01
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 *
 * @author   refactored & extended by pewa
 */

class I2Of5BarCode extends BarCode {

   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;

   var $mCharSet;

   /**
    *
    */
   public function __construct($Width, $Height, $Style, $Value) {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      parent:: __construct($Width, $Height, $Style);
      $this->mValue   = $Value;
      $this->mCharSet = array(/* 0 */ "00110",
                              /* 1 */ "10001",
                              /* 2 */ "01001",
                              /* 3 */ "11000",
                              /* 4 */ "00101",
                              /* 5 */ "10100",
                              /* 6 */ "01100",
                              /* 7 */ "00011",
                              /* 8 */ "10010",
                              /* 9 */ "01010");
   }

   /**
    *
    */
   function GetSize($xres) {
      $len = strlen($this->mValue);

      if ($len == 0) {
         $this->mError = "Null value";
         __DEBUG__("GetRealSize: null barcode value");
         return false;
      }

      for ($i=0; $i<$len; $i++) {
         if ((ord($this->mValue[$i])<48) || (ord($this->mValue[$i])>57)) {
            $this->mError = "I25 is numeric only";
            return false;
         }
      }

      if (($len%2) != 0) {
         $this->mError = "The length of barcode value must be even";
         __DEBUG__("GetSize: failed I25 requirement");
         return false;
      }
      $StartSize = BCD_I25_NARROW_BAR * 4  * $xres;
      $StopSize  = BCD_I25_WIDE_BAR * $xres + 2 * BCD_I25_NARROW_BAR * $xres;
      $cPos = 0;
      $sPos = 0;
      do {
         $c1    = $this->mValue[$cPos];
         $c2    = $this->mValue[$cPos+1];
         $cset1 = $this->mCharSet[$c1];
         $cset2 = $this->mCharSet[$c2];

         for ($i=0; $i<5; $i++) {
            $type1 = ($cset1[$i]==0) ? (BCD_I25_NARROW_BAR  * $xres) : (BCD_I25_WIDE_BAR * $xres);
            $type2 = ($cset2[$i]==0) ? (BCD_I25_NARROW_BAR  * $xres) : (BCD_I25_WIDE_BAR * $xres);
            $sPos += ($type1 + $type2);
         }
         $cPos+=2;
      } while ($cPos<$len);

      return $sPos + $StartSize + $StopSize;
   }

   /**
    *
    */
   function DrawStart($DrawPos, $yPos, $ySize, $xres) {
      /* Start code is "0000" */
      $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
      $DrawPos += BCD_I25_NARROW_BAR  * $xres;
      $DrawPos += BCD_I25_NARROW_BAR  * $xres;
      $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
      $DrawPos += BCD_I25_NARROW_BAR  * $xres;
      $DrawPos += BCD_I25_NARROW_BAR  * $xres;
      return $DrawPos;
   }

   /**
    *
    */
   function DrawStop($DrawPos, $yPos, $ySize, $xres) {
      /* Stop code is "100" */
      $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_WIDE_BAR * $xres , $ySize);
      $DrawPos += BCD_I25_WIDE_BAR  * $xres;
      $DrawPos += BCD_I25_NARROW_BAR  * $xres;
      $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
      $DrawPos += BCD_I25_NARROW_BAR  * $xres;
      return $DrawPos;
   }

   /**
    *
    */
   function DrawObject($xres) {
      $len = strlen($this->mValue);

      if (($size = $this->GetSize($xres))==0) {
         __DEBUG__("GetSize: failed");
         return false;
      }

      $cPos  = 0;

      if ($this->mStyle & BCS_DRAW_TEXT) $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2 - $this->GetFontHeight($this->mFont);
      else                               $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2;

      if      ($this->mStyle & BCS_ALIGN_CENTER) $sPos = (integer)(($this->mWidth - $size ) / 2);
      else if ($this->mStyle & BCS_ALIGN_RIGHT ) $sPos = $this->mWidth - $size;
      else                                       $sPos = 0;

      if ($this->mStyle & BCS_DRAW_TEXT) {
         if ($this->mStyle & BCS_STRETCH_TEXT) {
            /* Stretch */
            for ($i=0; $i<$len; $i++) {
               $this->DrawChar($this->mFont, $sPos+BCD_I25_NARROW_BAR*4*$xres+($size/$len)*$i,
               $ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET , $this->mValue[$i]);
            }
         }
         else {
            /* Center */
            $text_width = $this->GetFontWidth($this->mFont) * strlen($this->mValue);
            $this->DrawText($this->mFont, $sPos+(($size-$text_width)/2)+(BCD_I25_NARROW_BAR*4*$xres),
            $ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET, $this->mValue);
         }
      }

      $sPos = $this->DrawStart($sPos, BCD_DEFAULT_MAR_Y1, $ysize, $xres);
      do {
         $c1    = $this->mValue[$cPos];
         $c2    = $this->mValue[$cPos+1];
         $cset1 = $this->mCharSet[$c1];
         $cset2 = $this->mCharSet[$c2];

         for ($i=0; $i<5; $i++) {
            $type1 = ($cset1[$i]==0) ? (BCD_I25_NARROW_BAR * $xres) : (BCD_I25_WIDE_BAR * $xres);
            $type2 = ($cset2[$i]==0) ? (BCD_I25_NARROW_BAR * $xres) : (BCD_I25_WIDE_BAR * $xres);
            $this->DrawSingleBar($sPos, BCD_DEFAULT_MAR_Y1, $type1 , $ysize);
            $sPos += ($type1 + $type2);
         }
         $cPos+=2;
      } while ($cPos<$len);

      $sPos =  $this->DrawStop($sPos, BCD_DEFAULT_MAR_Y1, $ysize, $xres);
      return true;
   }
}
?>
