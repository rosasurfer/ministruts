<?
/**
 * Barcode renderer class
 *
 * @version  0.0.7a  2001-04-01
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 *
 * @author   refactored & extended by pewa
 */


/* Styles */

/* Global */
define("BCS_BORDER"        ,    1);
define("BCS_TRANSPARENT"   ,    2);
define("BCS_ALIGN_CENTER"  ,    4);
define("BCS_ALIGN_LEFT"    ,    8);
define("BCS_ALIGN_RIGHT"   ,   16);
define("BCS_IMAGE_JPEG"    ,   32);
define("BCS_IMAGE_PNG"     ,   64);
define("BCS_DRAW_TEXT"     ,  128);
define("BCS_STRETCH_TEXT"  ,  256);
define("BCS_REVERSE_COLOR" ,  512);

/* For I25 only  */
define("BCS_I25_DRAW_CHECK", 2048);

/* Default values */

/* Global */
define("BCD_DEFAULT_BACKGROUND_COLOR", 0xFFFFFF);
define("BCD_DEFAULT_FOREGROUND_COLOR", 0x000000);
define("BCD_DEFAULT_STYLE"           , BCS_BORDER | BCS_ALIGN_CENTER | BCS_IMAGE_PNG);
define("BCD_DEFAULT_WIDTH"           , 460);
define("BCD_DEFAULT_HEIGHT"          , 120);
define("BCD_DEFAULT_FONT"            ,   5);
define("BCD_DEFAULT_XRES"            ,   2);

/* Margins */
define("BCD_DEFAULT_MAR_Y1"     , 10);
define("BCD_DEFAULT_MAR_Y2"     , 10);
define("BCD_DEFAULT_TEXT_OFFSET",  2);

/* For I25 only */
define("BCD_I25_NARROW_BAR", 1);
define("BCD_I25_WIDE_BAR"  , 2);

/* For C39 only */
define("BCD_C39_NARROW_BAR", 1);
define("BCD_C39_WIDE_BAR"  , 2);

/* For Code 128 */
define("BCD_C128_BAR_1", 1);
define("BCD_C128_BAR_2", 2);
define("BCD_C128_BAR_3", 3);
define("BCD_C128_BAR_4", 4);


abstract class BarCode extends Object {

   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;

   var $mWidth, $mHeight, $mStyle, $mBgcolor, $mBrush;
   var $mImg, $mFont;
   var $mError;

   /**
    * Geschützter Konstruktor, Instanzen können nur über abgeleitete Klassen erzeugt werden.
    */
   protected function __construct($Width = BCD_DEFAULT_Width, $Height = BCD_DEFAULT_HEIGHT, $Style = BCD_DEFAULT_STYLE) {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      $this->mWidth   = $Width;
      $this->mHeight  = $Height;
      $this->mStyle   = $Style;
      $this->mFont    = BCD_DEFAULT_FONT;
      $this->mImg     = ImageCreate($this->mWidth, $this->mHeight);
      $dbColor        = $this->mStyle & BCS_REVERSE_COLOR ? BCD_DEFAULT_FOREGROUND_COLOR : BCD_DEFAULT_BACKGROUND_COLOR;
      $dfColor        = $this->mStyle & BCS_REVERSE_COLOR ? BCD_DEFAULT_BACKGROUND_COLOR : BCD_DEFAULT_FOREGROUND_COLOR;
      $this->mBgcolor = ImageColorAllocate($this->mImg, ($dbColor & 0xFF0000) >> 16, ($dbColor & 0x00FF00) >> 8 , $dbColor & 0x0000FF);
      $this->mBrush   = ImageColorAllocate($this->mImg, ($dfColor & 0xFF0000) >> 16, ($dfColor & 0x00FF00) >> 8 , $dfColor & 0x0000FF);
      if (!($this->mStyle & BCS_TRANSPARENT)) {
         ImageFill($this->mImg, $this->mWidth, $this->mHeight, $this->mBgcolor);
      }
   }

   /**
    *
    */
   abstract protected function DrawObject($xres) {}

   /**
    *
    */
   function DrawBorder() {
      ImageRectangle($this->mImg, 0, 0, $this->mWidth-1, $this->mHeight-1, $this->mBrush);
   }

   /**
    *
    */
   function DrawChar($Font, $xPos, $yPos, $Char) {
      ImageString($this->mImg, $Font, $xPos, $yPos, $Char, $this->mBrush);
   }

   /**
    *
    */
   function DrawText($Font, $xPos, $yPos, $Char) {
      ImageString($this->mImg, $Font, $xPos, $yPos, $Char, $this->mBrush);
   }

   /**
    *
    */
   function DrawSingleBar($xPos, $yPos, $xSize, $ySize) {
      if ($xPos>=0 && $xPos<=$this->mWidth && ($xPos+$xSize)<=$this->mWidth && $yPos>=0 && $yPos<=$this->mHeight && ($yPos+$ySize)<=$this->mHeight) {
         for ($i=0; $i<$xSize; $i++) {
            ImageLine($this->mImg, $xPos+$i, $yPos, $xPos+$i, $yPos+$ySize, $this->mBrush);
         }
         return true;
      }
      __DEBUG__("DrawSingleBar: Out of range");
      return false;
   }

   /**
    *
    */
   function GetError() {
      return $this->mError;
   }

   /**
    *
    */
   function GetFontHeight($font) {
      return ImageFontHeight($font);
   }

   /**
    *
    */
   function GetFontWidth($font) {
      return ImageFontWidth($font);
   }

   /**
    *
    */
   function SetFont($font) {
      $this->mFont = $font;
   }

   /**
    *
    */
   function GetStyle() {
      return $this->mStyle;
   }

   /**
    *
    */
   function SetStyle($Style) {
      $this->mStyle = $Style;
   }

   /**
    *
    */
   function FlushObject() {
      if (($this->mStyle & BCS_BORDER)) {
         $this->DrawBorder();
      }
      if ($this->mStyle & BCS_IMAGE_PNG) {
         Header("Content-Type: image/png");
         ImagePng($this->mImg);
      }
      else if ($this->mStyle & BCS_IMAGE_JPEG) {
         Header("Content-Type: image/jpeg");
         ImageJpeg($this->mImg);
      }
      else
         __DEBUG__("FlushObject: No output type");
   }

   /**
    * Destructor
    */
   public function __destruct() {
      ImageDestroy($this->mImg);
   }
}
?>
