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

   // default values
   const /*int*/ DEFAULT_NARROW_BAR = 1;
   const /*int*/ DEFAULT_WIDE_BAR   = 2;


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;

   private /*string*/   $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. *$/+%';

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
   private function GetCharIndex($char) {
      for ($i=0; $i<44; $i++) {
         if ($this->chars[$i] == $char)
            return $i;
      }
      return -1;
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
         if ($this->GetCharIndex($this->value[$i]) == -1 || $this->value[$i] == '*') {
            /* The asterisk is only used as a start and stop code */
            $this->error = "C39 not include the char '".$this->value[$i]."'";
            return false;
         }
      }

      /* Start, Stop is 010010100 == '*'  */
      $StartSize = self:: DEFAULT_NARROW_BAR * $xres * 6 + self:: DEFAULT_WIDE_BAR * $xres * 3;
      $StopSize  = self:: DEFAULT_NARROW_BAR * $xres * 6 + self:: DEFAULT_WIDE_BAR * $xres * 3;
      $CharSize  = self:: DEFAULT_NARROW_BAR * $xres * 6 + self:: DEFAULT_WIDE_BAR * $xres * 3; /* Same for all chars */

      return $CharSize * $len + $StartSize + $StopSize + /* Space between chars */ self:: DEFAULT_NARROW_BAR * $xres * ($len-1);
   }

   /**
    *
    */
   public function DrawObject($xres) {
      $len = strlen($this->value);

      $narrow = self:: DEFAULT_NARROW_BAR * $xres;
      $wide   = self:: DEFAULT_WIDE_BAR * $xres;

      if (($size = $this->GetSize($xres))==0) {
         __DEBUG__("GetSize: failed");
         return false;
      }

      $cPos = 0;
      if      ($this->style & self:: STYLE_ALIGN_CENTER) $sPos = (integer)(($this->width - $size ) / 2);
      else if ($this->style & self:: STYLE_ALIGN_RIGHT ) $sPos = $this->width - $size;
      else                                       $sPos = 0;

      /* Total height of bar code -Bars only- */
      if ($this->style & self:: STYLE_DRAW_TEXT) $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2 - $this->GetFontHeight($this->font);
      else                               $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2;

      /* Draw text */
      if ($this->style & self:: STYLE_DRAW_TEXT) {
         if ($this->style & self:: STYLE_STRETCH_TEXT) {
            for ($i=0; $i<$len; $i++) {
               $this->DrawChar($this->font, $sPos+($narrow*6+$wide*3)+($size/$len)*$i,
               $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET, $this->value[$i]);
            }
         }
         else {
            /* Center */
            $text_width = $this->GetFontWidth($this->font) * strlen($this->value);
            $this->DrawText($this->font, $sPos+(($size-$text_width)/2)+($narrow*6+$wide*3),
            $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET, $this->value);
         }
      }

      $DrawPos = $this->DrawStart($sPos, self:: DEFAULT_MARGIN_Y1 , $ysize, $xres);
      do {
         $c     = $this->GetCharIndex($this->value[$cPos]);
         $cset  = $this->charSet[$c];
         $this->DrawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[0] == '0') ? $narrow : $wide , $ysize);
         $DrawPos += ($cset[0] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[1] == '0') ? $narrow : $wide;
         $this->DrawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[2] == '0') ? $narrow : $wide , $ysize);
         $DrawPos += ($cset[2] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[3] == '0') ? $narrow : $wide;
         $this->DrawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[4] == '0') ? $narrow : $wide , $ysize);
         $DrawPos += ($cset[4] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[5] == '0') ? $narrow : $wide;
         $this->DrawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[6] == '0') ? $narrow : $wide , $ysize);
         $DrawPos += ($cset[6] == '0') ? $narrow : $wide;
         $DrawPos += ($cset[7] == '0') ? $narrow : $wide;
         $this->DrawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, ($cset[8] == '0') ? $narrow : $wide , $ysize);
         $DrawPos += ($cset[8] == '0') ? $narrow : $wide;
         $DrawPos += $narrow; /* Space between chars */
         $cPos++;
      } while ($cPos<$len);
      $DrawPos =  $this->DrawStop($DrawPos, self:: DEFAULT_MARGIN_Y1 , $ysize, $xres);
      return true;
    }

   /**
    *
    */
   private function DrawStart($DrawPos, $yPos, $ySize, $xres) {
      /* Start code is '*' */
      $narrow = self:: DEFAULT_NARROW_BAR * $xres;
      $wide   = self:: DEFAULT_WIDE_BAR * $xres;
      $this->DrawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $wide;
      $this->DrawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $narrow;
      $this->DrawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->DrawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->DrawSingleBar($DrawPos, $yPos, $narrow, $ySize);
      $DrawPos += $narrow;
      $DrawPos += $narrow; /* Space between chars */
      return $DrawPos;
   }

   /**
    *
    */
   private function DrawStop($DrawPos, $yPos, $ySize, $xres) {
      /* Stop code is '*' */
      $narrow = self:: DEFAULT_NARROW_BAR * $xres;
      $wide   = self:: DEFAULT_WIDE_BAR * $xres;
      $this->DrawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $wide;
      $this->DrawSingleBar($DrawPos, $yPos, $narrow , $ySize);
      $DrawPos += $narrow;
      $DrawPos += $narrow;
      $this->DrawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->DrawSingleBar($DrawPos, $yPos, $wide , $ySize);
      $DrawPos += $wide;
      $DrawPos += $narrow;
      $this->DrawSingleBar($DrawPos, $yPos, $narrow, $ySize);
      $DrawPos += $narrow;
      return $DrawPos;
   }
}
?>
