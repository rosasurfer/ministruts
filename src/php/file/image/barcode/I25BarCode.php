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

   const /*int*/ STYLE_DRAW_CHECK = 2048;

   const /*int*/ DEFAULT_NARROW_BAR = 1;
   const /*int*/ DEFAULT_WIDE_BAR   = 2;


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
    * Constructor
    */
   public function __construct($width, $height, $style, $xres, $font, $value) {
      if ($value!==(string)$value) throw new IllegalTypeException('Illegal type of argument $value: '.getType($value));

      $len = strLen($value);
      for ($i=0; $i<$len; $i++) {
         if (ord($value[$i]) < 48 || ord($value[$i]) > 57)
            throw new InvalidArgumentException("Invalid barcode value \"$value\" (standard 'Interleave 2 of 5' contains numeric characters only)");
      }
      if ($len % 2 != 0)
         throw new InvalidArgumentException("Invalid length of barcode value \"$value\" (standard 'Interleave 2 of 5' requires an even number of characters)");

      parent:: __construct($width, $height, $style, $xres, $font, $value);
   }

   /**
    *
    */
   protected function GetSize() {
      $len  = strLen($this->value);
      $xres = $this->xres;

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
            $type1 = ($cset1[$i]==0 ? self:: DEFAULT_NARROW_BAR : self:: DEFAULT_WIDE_BAR) * $xres;
            $type2 = ($cset2[$i]==0 ? self:: DEFAULT_NARROW_BAR : self:: DEFAULT_WIDE_BAR) * $xres;
            $sPos += $type1 + $type2;
         }
         $cPos+=2;
      } while ($cPos < $len);

      return $sPos + $StartSize + $StopSize;
   }

   /**
    * @return the BarCode instance
    */
   public function RenderImage() {
      $len  = strLen($this->value);
      $size = $this->GetSize();
      $xres = $this->xres;

      if ($this->style & self:: STYLE_DRAW_TEXT) $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2 - $this->GetFontHeight();
      else                                       $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2;

      if      ($this->style & self:: STYLE_ALIGN_CENTER) $sPos = (int) (($this->width - $size) / 2);
      else if ($this->style & self:: STYLE_ALIGN_RIGHT ) $sPos = $this->width - $size;
      else                                               $sPos = 0;

      if ($this->style & self:: STYLE_DRAW_TEXT) {
         if ($this->style & self:: STYLE_STRETCH_TEXT) {
            /* Stretch */
            for ($i=0; $i<$len; $i++) {
               $this->DrawChar($sPos + self:: DEFAULT_NARROW_BAR * 4 * $xres + ($size/$len) *$i,
                               $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET,
                               $this->value[$i]);
            }
         }
         else {
            /* Center */
            $text_width = $this->GetFontWidth() * strLen($this->value);
            $this->DrawText($sPos + ($size-$text_width)/2,                 // + self:: DEFAULT_NARROW_BAR*4*$xres,   (pewa)
                            $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET,
                            $this->value);
         }
      }

      $sPos = $this->DrawStart($sPos, self:: DEFAULT_MARGIN_Y1, $ysize, $xres);
      $cPos = 0;
      do {
         $c1    = $this->value[$cPos];
         $c2    = $this->value[$cPos+1];
         $cset1 = $this->charSet[$c1];
         $cset2 = $this->charSet[$c2];

         for ($i=0; $i<5; $i++) {
            $type1 = ($cset1[$i]==0 ? self:: DEFAULT_NARROW_BAR : self:: DEFAULT_WIDE_BAR) * $xres;
            $type2 = ($cset2[$i]==0 ? self:: DEFAULT_NARROW_BAR : self:: DEFAULT_WIDE_BAR) * $xres;
            $this->DrawSingleBar($sPos, self:: DEFAULT_MARGIN_Y1, $type1 , $ysize);
            $sPos += $type1 + $type2;
         }
         $cPos+=2;
      } while ($cPos<$len);

      $sPos = $this->DrawStop($sPos, self:: DEFAULT_MARGIN_Y1, $ysize, $xres);
      return $this;
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
