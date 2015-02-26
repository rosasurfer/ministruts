<?php
/**
 * Barcode renderer for "Code 128-C", a numeric only bar code.
 *
 * @version  0.0.8a  2001-08-03
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 * @author   extended & refactored by pewa
 */

class C128CBarCode extends BaseC128BarCode {

   protected /*string[]*/ $chars = array('00', '01', '02', '03', '04', '05', '06', '07', '08', '09',
                                         '10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
                                         '20', '21', '22', '23', '24', '25', '26', '27', '28', '29',
                                         '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
                                         '40', '41', '42', '43', '44', '45', '46', '47', '48', '49',
                                         '50', '51', '52', '53', '54', '55', '56', '57', '58', '59',
                                         '60', '61', '62', '63', '64', '65', '66', '67', '68', '69',
                                         '70', '71', '72', '73', '74', '75', '76', '77', '78', '79',
                                         '80', '81', '82', '83', '84', '85', '86', '87', '88', '89',
                                         '90', '91', '92', '93', '94', '95', '96', '97', '98', '99');

   protected /*string[]*/ $charSet = array('212222',   // 00
                                           '222122',   // 01
                                           '222221',   // 02
                                           '121223',   // 03
                                           '121322',   // 04
                                           '131222',   // 05
                                           '122213',   // 06
                                           '122312',   // 07
                                           '132212',   // 08
                                           '221213',   // 09
                                           '221312',   // 10
                                           '231212',   // 11
                                           '112232',   // 12
                                           '122132',   // 13
                                           '122231',   // 14
                                           '113222',   // 15
                                           '123122',   // 16
                                           '123221',   // 17
                                           '223211',   // 18
                                           '221132',   // 19
                                           '221231',   // 20
                                           '213212',   // 21
                                           '223112',   // 22
                                           '312131',   // 23
                                           '311222',   // 24
                                           '321122',   // 25
                                           '321221',   // 26
                                           '312212',   // 27
                                           '322112',   // 28
                                           '322211',   // 29
                                           '212123',   // 30
                                           '212321',   // 31
                                           '232121',   // 32
                                           '111323',   // 33
                                           '131123',   // 34
                                           '131321',   // 35
                                           '112313',   // 36
                                           '132113',   // 37
                                           '132311',   // 38
                                           '211313',   // 39
                                           '231113',   // 40
                                           '231311',   // 41
                                           '112133',   // 42
                                           '112331',   // 43
                                           '132131',   // 44
                                           '113123',   // 45
                                           '113321',   // 46
                                           '133121',   // 47
                                           '313121',   // 48
                                           '211331',   // 49
                                           '231131',   // 50
                                           '213113',   // 51
                                           '213311',   // 52
                                           '213131',   // 53
                                           '311123',   // 54
                                           '311321',   // 55
                                           '331121',   // 56
                                           '312113',   // 57
                                           '312311',   // 58
                                           '332111',   // 59
                                           '314111',   // 60
                                           '221411',   // 61
                                           '431111',   // 62
                                           '111224',   // 63
                                           '111422',   // 64
                                           '121124',   // 65
                                           '121421',   // 66
                                           '141122',   // 67
                                           '141221',   // 68
                                           '112214',   // 69
                                           '112412',   // 70
                                           '122114',   // 71
                                           '122411',   // 72
                                           '142112',   // 73
                                           '142211',   // 74
                                           '241211',   // 75
                                           '221114',   // 76
                                           '413111',   // 77
                                           '241112',   // 78
                                           '134111',   // 79
                                           '111242',   // 80
                                           '121142',   // 81
                                           '121241',   // 82
                                           '114212',   // 83
                                           '124112',   // 84
                                           '124211',   // 85
                                           '411212',   // 86
                                           '421112',   // 87
                                           '421211',   // 88
                                           '212141',   // 89
                                           '214121',   // 90
                                           '412121',   // 91
                                           '111143',   // 92
                                           '111341',   // 93
                                           '131141',   // 94
                                           '114113',   // 95
                                           '114311',   // 96
                                           '411113',   // 97
                                           '411311',   // 98
                                           '113141');  // 99

   /**
    * Constructor
    */
   public function __construct($value, $width, $height, $style=null, $xres=null, $font=null) {
      parent:: __construct($value, $width, $height, $style, $xres, $font);

      $len = strLen($value);
      for ($i=0; $i<$len; $i++) {
         if (ord($value[$i]) < 48 || ord($value[$i]) > 57)
            throw new plInvalidArgumentException("Invalid barcode value \"$value\" (standard 'Class 128-C' contains numeric characters only)");
      }
      if ($len % 2 != 0)
         throw new plInvalidArgumentException("Invalid length of barcode value \"$value\" (standard 'Class 128-C' requires an even number of characters, pad the value with zeros)");
   }

   /**
    *
    */
   protected function getCharIndex($char) {
      $key = array_search($char, $this->chars);
      if ($key === false)
         return -1;
      return $key;
   }

   /**
    *
    */
   protected function getCheckCharValue() {
      $len = strLen($this->value);
      $sum = 105;    // 'C' type;
      $m   = 0;
      for ($i=0; $i<$len; $i+=2) {
         $m++;
         $sum += $this->getCharIndex($this->value[$i].$this->value[$i+1]) * $m;
      }
      return $this->charSet[$sum % 103];
   }

   /**
    *
    */
   private function drawCheckChar($DrawPos, $yPos, $ySize, $xres) {
      $cset = $this->getCheckCharValue();
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize($cset[0], $xres) , $ySize);
      $DrawPos += $this->getBarSize($cset[0], $xres);
      $DrawPos += $this->getBarSize($cset[1], $xres);
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize($cset[2], $xres) , $ySize);
      $DrawPos += $this->getBarSize($cset[2], $xres);
      $DrawPos += $this->getBarSize($cset[3], $xres);
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize($cset[4], $xres) , $ySize);
      $DrawPos += $this->getBarSize($cset[4], $xres);
      $DrawPos += $this->getBarSize($cset[5], $xres);
      return $DrawPos;
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

      if      ($this->style & self:: STYLE_ALIGN_CENTER) $sPos = (int) (($this->width - $size ) / 2);
      else if ($this->style & self:: STYLE_ALIGN_RIGHT ) $sPos = $this->width - $size;
      else                                               $sPos = 0;

      /* Total height of bar code -Bars only- */
      if ($this->style & self:: STYLE_DRAW_TEXT) $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2 - $this->getFontHeight();
      else                                       $ysize = $this->height - self:: DEFAULT_MARGIN_Y1 - self:: DEFAULT_MARGIN_Y2;

      /* Draw text */
      if ($this->style & self:: STYLE_DRAW_TEXT) {
         if ($this->style & self:: STYLE_STRETCH_TEXT) {
            for ($i=0; $i<$len; $i++) {
               $this->drawChar($sPos + ($size/$len)*$i + 2*self:: DEFAULT_BAR_2*$xres + 3*self:: DEFAULT_BAR_1*$xres + self:: DEFAULT_BAR_4*$xres,
               $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET,
               $this->value[$i]);
            }
         }
         else {
            /* Center */
            $text_width = $this->getFontWidth() * strLen($this->value);
            $this->drawText($sPos + ($size-$text_width)/2,  // 2*self:: DEFAULT_BAR_2*$xres + 3*self:: DEFAULT_BAR_1*$xres + self:: DEFAULT_BAR_4*$xres,  (pewa)
                            $ysize + self:: DEFAULT_MARGIN_Y1 + self:: DEFAULT_TEXT_OFFSET,
                            $this->value);
         }
      }

      $cPos = 0;
      $DrawPos = $this->drawStart($sPos, self:: DEFAULT_MARGIN_Y1 , $ysize, $xres);
      do {
         $c     = $this->getCharIndex($this->value[$cPos].$this->value[$cPos+1]);
         $cset  = $this->charSet[$c];
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize($cset[0], $xres) , $ysize);
         $DrawPos += $this->getBarSize($cset[0], $xres);
         $DrawPos += $this->getBarSize($cset[1], $xres);
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize($cset[2], $xres) , $ysize);
         $DrawPos += $this->getBarSize($cset[2], $xres);
         $DrawPos += $this->getBarSize($cset[3], $xres);
         $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize($cset[4], $xres) , $ysize);
         $DrawPos += $this->getBarSize($cset[4], $xres);
         $DrawPos += $this->getBarSize($cset[5], $xres);
         $cPos += 2;
      } while ($cPos<$len);

      $DrawPos = $this->drawCheckChar($DrawPos, self:: DEFAULT_MARGIN_Y1 , $ysize, $xres);
      $DrawPos = $this->drawStop($DrawPos, self:: DEFAULT_MARGIN_Y1 , $ysize, $xres);

      if (($this->style & self:: STYLE_BORDER))
         $this->drawBorder();

      $this->isRendered = true;
      return $this;
    }

   /**
    *
    */
   private function drawStart($DrawPos, $yPos, $ySize, $xres) {
      /* Start code is '211232' */
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize('2', $xres) , $ySize);
      $DrawPos += $this->getBarSize('2', $xres);
      $DrawPos += $this->getBarSize('1', $xres);
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize('1', $xres) , $ySize);
      $DrawPos += $this->getBarSize('1', $xres);
      $DrawPos += $this->getBarSize('2', $xres);
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize('3', $xres) , $ySize);
      $DrawPos += $this->getBarSize('3', $xres);
      $DrawPos += $this->getBarSize('2', $xres);
      return $DrawPos;
   }

   /**
    *
    */
   private function drawStop($DrawPos, $yPos, $ySize, $xres) {
      /* Stop code is '2331112' */
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize('2', $xres) , $ySize);
      $DrawPos += $this->getBarSize('2', $xres);
      $DrawPos += $this->getBarSize('3', $xres);
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize('3', $xres) , $ySize);
      $DrawPos += $this->getBarSize('3', $xres);
      $DrawPos += $this->getBarSize('1', $xres);
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize('1', $xres) , $ySize);
      $DrawPos += $this->getBarSize('1', $xres);
      $DrawPos += $this->getBarSize('1', $xres);
      $this->drawSingleBar($DrawPos, self:: DEFAULT_MARGIN_Y1, $this->getBarSize('2', $xres) , $ySize);
      $DrawPos += $this->getBarSize('2', $xres);
      return $DrawPos;
   }
}
