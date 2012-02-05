<?
/**
 * Barcode renderer for "Code-39", an alphanumeric bar code that can encode decimal numbers, upper and lower case
 * alphabetic characters and some special symbols.
 *
 * @version  0.0.8a  2001-08-03
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 * @author   extended & refactored by pewa
 */

class C39BarCode extends BarCode {

   const /*int*/ DEFAULT_NARROW_BAR = 1;
   const /*int*/ DEFAULT_WIDE_BAR   = 2;


   private /*string*/   $chars   = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. *$/+%';

   private /*string[]*/ $charSet = array('000110100',    //  0
                                         '100100001',    //  1
                                         '001100001',    //  2
                                         '101100000',    //  3
                                         '000110001',    //  4
                                         '100110000',    //  5
                                         '001110000',    //  6
                                         '000100101',    //  7
                                         '100100100',    //  8
                                         '001100100',    //  9
                                         '100001001',    //  A
                                         '001001001',    //  B
                                         '101001000',    //  C
                                         '000011001',    //  D
                                         '100011000',    //  E
                                         '001011000',    //  F
                                         '000001101',    //  G
                                         '100001100',    //  H
                                         '001001100',    //  I
                                         '000011100',    //  J
                                         '100000011',    //  K
                                         '001000011',    //  L
                                         '101000010',    //  M
                                         '000010011',    //  N
                                         '100010010',    //  O
                                         '001010010',    //  P
                                         '000000111',    //  Q
                                         '100000110',    //  R
                                         '001000110',    //  S
                                         '000010110',    //  T
                                         '110000001',    //  U
                                         '011000001',    //  V
                                         '111000000',    //  W
                                         '010010001',    //  X
                                         '110010000',    //  Y
                                         '011010000',    //  Z
                                         '010000101',    //  -
                                         '110000100',    //  .
                                         '011000100',    // SP
                                         '010010100',    //  *
                                         '010101000',    //  $
                                         '010100010',    //  /
                                         '010001010',    //  +
                                         '000101010');   //  %

   /**
    * Constructor
    */
   public function __construct($value, $width, $height, $style=null, $xres=null, $font=null) {
      parent:: __construct($value, $width, $height, $style, $xres, $font);

      $len = strLen($value);
      for ($i=0; $i<$len; $i++) {
         if ($this->getCharIndex($value[$i])==-1 || $value[$i]=='*')
            throw new plInvalidArgumentException("Invalid barcode value \"$value\" (standard 'C-39' does not contain character '$value[$i]')");
      }
   }

   /**
    *
    */
   private function getCharIndex($char) {
      $pos = strPos($this->chars, $char);
      if ($pos === false)
         return -1;
      return $pos;
   }

   /**
    *
    */
   protected function getSize() {
      $len  = strLen($this->value);
      $xres = $this->xres;

      // start & stop is 010010100 => '*'
      $startSize = self:: DEFAULT_NARROW_BAR * $xres * 6 + self:: DEFAULT_WIDE_BAR * $xres * 3;
      $stopSize  = self:: DEFAULT_NARROW_BAR * $xres * 6 + self:: DEFAULT_WIDE_BAR * $xres * 3;
      $charSize  = self:: DEFAULT_NARROW_BAR * $xres * 6 + self:: DEFAULT_WIDE_BAR * $xres * 3; // same for all chars

      return $charSize * $len + $startSize + $stopSize + /*space between chars*/ self:: DEFAULT_NARROW_BAR * $xres * ($len-1);
   }

   /**
    * @return the BarCode instance
    */
   protected function render() {
      if ($this->isRendered)
         return $this;

      $len  = strLen($this->value);
      $size = $this->getSize();
      $xres = $this->xres;

      $narrow = self:: DEFAULT_NARROW_BAR * $xres;
      $wide   = self:: DEFAULT_WIDE_BAR   * $xres;

      $cPos = 0;
      if      ($this->style & self:: STYLE_ALIGN_CENTER) $sPos = (int) (($this->width - $size) / 2);
      else if ($this->style & self:: STYLE_ALIGN_RIGHT ) $sPos = $this->width - $size;
      else                                               $sPos = 0;

      /* Total height of bar code -Bars only- */
      if ($this->style & self:: STYLE_DRAW_TEXT) $ySize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2 - $this->getFontHeight();
      else                                       $ySize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2;

      /* Draw text */
      if ($this->style & self:: STYLE_DRAW_TEXT) {
         if ($this->style & self:: STYLE_STRETCH_TEXT) {
            for ($i=0; $i<$len; $i++) {
               $this->drawChar($sPos + ($size/$len)*$i + $narrow*6+$wide*3,
                               $ySize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET,
                               $this->value[$i]);
            }
         }
         else {
            /* Center */
            $text_width = $this->getFontWidth() * strLen($this->value);
            $this->drawText($sPos + ($size-$text_width)/2,           // + $narrow*6 + $wide*3 (pewa)
                            $ySize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET,
                            $this->value);
         }
      }

      $DrawPos = $this->drawStart($sPos, self:: DEFAULT_MARGIN_Y1 , $ySize, $xres);
      do {
         $c     = $this->getCharIndex($this->value[$cPos]);
         $cset  = $this->charSet[$c];
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[0] == '0') ? $narrow : $wide , $ySize);
         $DrawPos += ($cset[0] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[1] == '0') ? $narrow : $wide;
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[2] == '0') ? $narrow : $wide , $ySize);
         $DrawPos += ($cset[2] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[3] == '0') ? $narrow : $wide;
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[4] == '0') ? $narrow : $wide , $ySize);
         $DrawPos += ($cset[4] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[5] == '0') ? $narrow : $wide;
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[6] == '0') ? $narrow : $wide , $ySize);
         $DrawPos += ($cset[6] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[7] == '0') ? $narrow : $wide;
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[8] == '0') ? $narrow : $wide , $ySize);
         $DrawPos += ($cset[8] == '0') ? $narrow : $wide;
         $DrawPos += $narrow; /* Space between chars */
         $cPos++;
      } while ($cPos<$len);

      $DrawPos =  $this->drawStop($DrawPos, self:: DEFAULT_MARGIN_Y1 , $ySize, $xres);

      if (($this->style & self:: STYLE_BORDER))
         $this->drawBorder();

      $this->isRendered = true;
      return $this;
    }

   /**
    *
    */
   private function drawStart($DrawPos, $yPos, $ySize, $xres) {
      /* Start code is '*' */
      $narrow = self:: DEFAULT_NARROW_BAR * $xres;
      $wide   = self:: DEFAULT_WIDE_BAR * $xres;
      $this->drawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $wide;
      $this->drawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $narrow;
      $this->drawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->drawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->drawSingleBar($DrawPos, $yPos, $narrow, $ySize);
      $DrawPos += $narrow;
      $DrawPos += $narrow; /* Space between chars */
      return $DrawPos;
   }

   /**
    *
    */
   private function drawStop($DrawPos, $yPos, $ySize, $xres) {
      /* Stop code is '*' */
      $narrow = self:: DEFAULT_NARROW_BAR * $xres;
      $wide   = self:: DEFAULT_WIDE_BAR * $xres;
      $this->drawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $wide;
      $this->drawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $narrow;
      $this->drawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->drawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->drawSingleBar($DrawPos, $yPos, $narrow, $ySize);
      $DrawPos += $narrow;
      return $DrawPos;
   }
}
?>
