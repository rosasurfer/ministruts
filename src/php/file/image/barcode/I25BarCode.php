<?
/**
 * Barcode renderer for "Interleaved 2 of 5", a numeric only bar code with an optional check number.
 *
 * @version  0.0.8a  2001-08-03
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 * @author   extended & refactored by pewa
 */

class I25BarCode extends BarCode {

   // style flags
   const /*int*/ STYLE_DRAW_CHECK = 2048;

   // default values
   const /*int*/ DEFAULT_NARROW_BAR = 1;
   const /*int*/ DEFAULT_WIDE_BAR   = 2;


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;

   private /*string[]*/ $charSet = array('00110',     // 0
                                         '10001',     // 1
                                         '01001',     // 2
                                         '11000',     // 3
                                         '00101',     // 4
                                         '10100',     // 5
                                         '01100',     // 6
                                         '00011',     // 7
                                         '10010',     // 8
                                         '01010');    // 9

   /**
    *
    */
   public function __construct($Width, $Height, $Style, $Value) {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      parent:: __construct($Width, $Height, $Style, $Value);
   }

   /**
    *
    */
   private function GetSize($xres) {
      $len = strlen($this->value);

      if ($len == 0) {
         $this->error = "Null value";
         __DEBUG__("GetRealSize: null barcode value");
         return false;
      }

      for ($i=0; $i<$len; $i++) {
         if ((ord($this->value[$i])<48) || (ord($this->value[$i])>57)) {
            $this->error = "I25 is numeric only";
            return false;
         }
      }

      if (($len%2) != 0) {
         $this->error = "The length of barcode value must be even";
         __DEBUG__("GetSize: failed I25 requirement");
         return false;
      }
      $StartSize = self:: DEFAULT_NARROW_BAR * 4  * $xres;
      $StopSize  = self:: DEFAULT_WIDE_BAR * $xres + 2 * self:: DEFAULT_NARROW_BAR * $xres;
      $cPos = 0;
      $sPos = 0;
      do {
         $c1    = $this->value[$cPos];
         $c2    = $this->value[$cPos+1];
         $cset1 = $this->charSet[$c1];
         $cset2 = $this->charSet[$c2];

         for ($i=0; $i<5; $i++) {
            $type1 = ($cset1[$i]==0) ? (self:: DEFAULT_NARROW_BAR * $xres) : (self:: DEFAULT_WIDE_BAR * $xres);
            $type2 = ($cset2[$i]==0) ? (self:: DEFAULT_NARROW_BAR * $xres) : (self:: DEFAULT_WIDE_BAR * $xres);
            $sPos += ($type1 + $type2);
         }
         $cPos+=2;
      } while ($cPos<$len);

      return $sPos + $StartSize + $StopSize;
   }

   /**
    *
    */
   public function DrawObject($xres) {
      $len = strlen($this->value);

      if (($size = $this->GetSize($xres))==0) {
         __DEBUG__("GetSize: failed");
         return false;
      }

      $cPos  = 0;

      if ($this->style & self:: STYLE_DRAW_TEXT) $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2 - $this->GetFontHeight($this->font);
      else                               $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2;

      if      ($this->style & self:: STYLE_ALIGN_CENTER) $sPos = (integer)(($this->width - $size ) / 2);
      else if ($this->style & self:: STYLE_ALIGN_RIGHT ) $sPos = $this->width - $size;
      else                                       $sPos = 0;

      if ($this->style & self:: STYLE_DRAW_TEXT) {
         if ($this->style & self:: STYLE_STRETCH_TEXT) {
            /* Stretch */
            for ($i=0; $i<$len; $i++) {
               $this->DrawChar($this->font, $sPos+self:: DEFAULT_NARROW_BAR*4*$xres+($size/$len)*$i,
               $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET , $this->value[$i]);
            }
         }
         else {
            /* Center */
            $text_width = $this->GetFontWidth($this->font) * strlen($this->value);
            $this->DrawText($this->font, $sPos+(($size-$text_width)/2)+(self:: DEFAULT_NARROW_BAR*4*$xres),
            $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET, $this->value);
         }
      }

      $sPos = $this->DrawStart($sPos, self:: DEFAULT_MARGIN_Y1, $ysize, $xres);
      do {
         $c1    = $this->value[$cPos];
         $c2    = $this->value[$cPos+1];
         $cset1 = $this->charSet[$c1];
         $cset2 = $this->charSet[$c2];

         for ($i=0; $i<5; $i++) {
            $type1 = ($cset1[$i]==0) ? (self:: DEFAULT_NARROW_BAR * $xres) : (self:: DEFAULT_WIDE_BAR * $xres);
            $type2 = ($cset2[$i]==0) ? (self:: DEFAULT_NARROW_BAR * $xres) : (self:: DEFAULT_WIDE_BAR * $xres);
            $this->DrawSingleBar($sPos, self:: DEFAULT_MARGIN_Y1, $type1 , $ysize);
            $sPos += ($type1 + $type2);
         }
         $cPos+=2;
      } while ($cPos<$len);

      $sPos =  $this->DrawStop($sPos, self:: DEFAULT_MARGIN_Y1, $ysize, $xres);
      return true;
   }

   /**
    *
    */
   private function DrawStart($DrawPos, $yPos, $ySize, $xres) {
      /* Start code is "0000" */
      $this->DrawSingleBar($DrawPos, $yPos, self:: DEFAULT_NARROW_BAR  * $xres , $ySize);
      $DrawPos += self:: DEFAULT_NARROW_BAR  * $xres;
      $DrawPos += self:: DEFAULT_NARROW_BAR  * $xres;
      $this->DrawSingleBar($DrawPos, $yPos, self:: DEFAULT_NARROW_BAR  * $xres , $ySize);
      $DrawPos += self:: DEFAULT_NARROW_BAR  * $xres;
      $DrawPos += self:: DEFAULT_NARROW_BAR  * $xres;
      return $DrawPos;
   }

   /**
    *
    */
   private function DrawStop($DrawPos, $yPos, $ySize, $xres) {
      /* Stop code is "100" */
      $this->DrawSingleBar($DrawPos, $yPos, self:: DEFAULT_WIDE_BAR * $xres , $ySize);
      $DrawPos += self:: DEFAULT_WIDE_BAR  * $xres;
      $DrawPos += self:: DEFAULT_NARROW_BAR  * $xres;
      $this->DrawSingleBar($DrawPos, $yPos, self:: DEFAULT_NARROW_BAR  * $xres , $ySize);
      $DrawPos += self:: DEFAULT_NARROW_BAR  * $xres;
      return $DrawPos;
   }
}
?>
