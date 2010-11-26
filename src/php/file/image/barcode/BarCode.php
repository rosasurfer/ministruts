<?
/**
 * Barcode renderer class
 *
 * @version  0.0.8a  2001-08-03
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 * @author   extended & refactored by pewa
 */

// really bad hack to circumvent PHP's inability to resolve combined constant declarations at compile time
define('BARCODE_DEFAULT_STYLE', 1 | 4 | 64);   // STYLE_BORDER | STYLE_ALIGN_CENTER | STYLE_IMAGE_PNG


abstract class BarCode extends Object {

   // style flags
   const /*int*/ STYLE_BORDER             =   1;
   const /*int*/ STYLE_TRANSPARENT        =   2;
   const /*int*/ STYLE_ALIGN_CENTER       =   4;
   const /*int*/ STYLE_ALIGN_LEFT         =   8;
   const /*int*/ STYLE_ALIGN_RIGHT        =  16;
   const /*int*/ STYLE_IMAGE_JPEG         =  32;
   const /*int*/ STYLE_IMAGE_PNG          =  64;
   const /*int*/ STYLE_DRAW_TEXT          = 128;
   const /*int*/ STYLE_STRETCH_TEXT       = 256;
   const /*int*/ STYLE_REVERSE_COLOR      = 512;

   // default values
   const /*int*/ DEFAULT_BACKGROUND_COLOR = 0xFFFFFF;
   const /*int*/ DEFAULT_FOREGROUND_COLOR = 0x000000;
   const /*int*/ DEFAULT_STYLE            = BARCODE_DEFAULT_STYLE;   // see hack above class declaration
   const /*int*/ DEFAULT_WIDTH            = 460;
   const /*int*/ DEFAULT_HEIGHT           = 120;
   const /*int*/ DEFAULT_FONT             =   5;
   const /*int*/ DEFAULT_XRES             =   2;
   const /*int*/ DEFAULT_MARGIN_Y1        =  10;
   const /*int*/ DEFAULT_MARGIN_Y2        =  10;
   const /*int*/ DEFAULT_TEXT_OFFSET      =   2;


   private static /*bool*/ $logDebug,
                  /*bool*/ $logInfo,
                  /*bool*/ $logNotice;

   private /*string*/ $error;

   protected /*int*/    $width,
             /*int*/    $height,
             /*int*/    $style,
             /*int*/    $bgColor,
             /*int*/    $brushColor,
             /*int*/    $font,
             /*string*/ $value,
             /*hImg*/   $hImg;


   /**
    * Geschützter Konstruktor, Instanzen können nur über abgeleitete Klassen erzeugt werden.
    */
   protected function __construct($width=self:: DEFAULT_WIDTH, $height=self:: DEFAULT_HEIGHT, $style=self:: DEFAULT_STYLE, $value) {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      $this->width  = $width;
      $this->height = $height;
      $this->style  = $style;
      $this->value  = $value;
      $this->font   = self:: DEFAULT_FONT;
      $this->hImg   = ImageCreate($this->width, $this->height);

      $bgColor = $this->style & self:: STYLE_REVERSE_COLOR ? self:: DEFAULT_FOREGROUND_COLOR : self:: DEFAULT_BACKGROUND_COLOR;
      $fgColor = $this->style & self:: STYLE_REVERSE_COLOR ? self:: DEFAULT_BACKGROUND_COLOR : self:: DEFAULT_FOREGROUND_COLOR;

      $this->bgColor    = ImageColorAllocate($this->hImg, ($bgColor & 0xFF0000) >> 16, ($bgColor & 0x00FF00) >> 8 , $bgColor & 0x0000FF);
      $this->brushColor = ImageColorAllocate($this->hImg, ($fgColor & 0xFF0000) >> 16, ($fgColor & 0x00FF00) >> 8 , $fgColor & 0x0000FF);

      if (!($this->style & self:: STYLE_TRANSPARENT))
         ImageFill($this->hImg, $this->width, $this->height, $this->bgColor);
   }

   /**
    *
    */
   abstract protected function DrawObject($xres);

   /**
    *
    */
   private function DrawBorder() {
      ImageRectangle($this->hImg, 0, 0, $this->width-1, $this->height-1, $this->brushColor);
   }

   /**
    *
    */
   protected function DrawChar($Font, $xPos, $yPos, $Char) {
      ImageString($this->hImg, $Font, $xPos, $yPos, $Char, $this->brushColor);
   }

   /**
    *
    */
   protected function DrawText($Font, $xPos, $yPos, $Char) {
      ImageString($this->hImg, $Font, $xPos, $yPos, $Char, $this->brushColor);
   }

   /**
    *
    */
   protected function DrawSingleBar($xPos, $yPos, $xSize, $ySize) {
      if ($xPos>=0 && $xPos<=$this->width && ($xPos+$xSize)<=$this->width && $yPos>=0 && $yPos<=$this->height && ($yPos+$ySize)<=$this->height) {
         for ($i=0; $i<$xSize; $i++) {
            ImageLine($this->hImg, $xPos+$i, $yPos, $xPos+$i, $yPos+$ySize, $this->brushColor);
         }
         return true;
      }
      __DEBUG__("DrawSingleBar: Out of range");
      return false;
   }

   /**
    *
    */
   protected function GetFontHeight($font) {
      return ImageFontHeight($font);
   }

   /**
    *
    */
   protected function GetFontWidth($font) {
      return ImageFontWidth($font);
   }

   /**
    *
    */
   public function SetFont($font) {
      $this->font = $font;
   }

   /**
    *
    */
   public function FlushObject() {
      if (($this->style & self:: STYLE_BORDER)) {
         $this->DrawBorder();
      }
      if ($this->style & self:: STYLE_IMAGE_PNG) {
         Header("Content-Type: image/png");
         ImagePng($this->hImg);
      }
      else if ($this->style & self:: STYLE_IMAGE_JPEG) {
         Header("Content-Type: image/jpeg");
         ImageJpeg($this->hImg);
      }
      else
         __DEBUG__("FlushObject: No output type");
   }

   /**
    * Destructor
    */
   public function __destruct() {
      ImageDestroy($this->hImg);
   }

   /**
    *
    */
   public function GetError() {
      return $this->error;
   }
}
?>
