<?
/**
 * Barcode renderer class
 *
 * @version  0.0.8a  2001-08-03
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 * @author   extended & refactored by pewa
 */

// stupid hack to circumvent PHP's inability to resolve combined constants at compile time
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


   protected /*string*/ $value,
             /*int*/    $width,
             /*int*/    $height,
             /*int*/    $style,
             /*bool*/   $reverseColor,
             /*int*/    $bgColor,
             /*int*/    $fgColor,
             /*int*/    $xres,
             /*int*/    $font,
             /*hImg*/   $hImg,
             /*bool*/   $isRendered = false;


   /**
    * Geschützter Konstruktor, Instanzen können nur von abgeleiteten Klassen erzeugt werden.
    */
   protected function __construct($value, $width=self:: DEFAULT_WIDTH, $height=self:: DEFAULT_HEIGHT, $style=self:: DEFAULT_STYLE, $xres=self:: DEFAULT_XRES, $font=self:: DEFAULT_FONT) {
      if ($value!==(string)$value) throw new IllegalTypeException('Illegal type of argument $value: '.getType($value));
      if (strLen($value)==0)       throw new InvalidArgumentException('Invalid barcode $value: "'.$value.'"');

      if (!($style & self:: STYLE_IMAGE_PNG) && !($style & self:: STYLE_IMAGE_JPEG))
         throw new InvalidArgumentException("Invalid parameter style: $style (missing or unknown grapic format)");

      $this->width  = $width;
      $this->height = $height;
      $this->style  = $style;
      $this->xres   = $xres;
      $this->font   = $font;
      $this->value  = $value;
      $this->hImg   = ImageCreate($this->width, $this->height);

      $this->reverseColor = $this->style & self:: STYLE_REVERSE_COLOR;
      $bgColor = $this->reverseColor ? self:: DEFAULT_FOREGROUND_COLOR : self:: DEFAULT_BACKGROUND_COLOR;
      $fgColor = $this->reverseColor ? self:: DEFAULT_BACKGROUND_COLOR : self:: DEFAULT_FOREGROUND_COLOR;

      $this->bgColor = ImageColorAllocate($this->hImg, ($bgColor & 0xFF0000) >> 16, ($bgColor & 0x00FF00) >> 8 , $bgColor & 0x0000FF);
      $this->fgColor = ImageColorAllocate($this->hImg, ($fgColor & 0xFF0000) >> 16, ($fgColor & 0x00FF00) >> 8 , $fgColor & 0x0000FF);

      if (!($this->style & self:: STYLE_TRANSPARENT))
         ImageFill($this->hImg, $this->width, $this->height, $this->bgColor);
   }

   /**
    *
    */
   final public function getWidth() {
      return $this->width;
   }

   /**
    *
    */
   final public function getHeight() {
      return $this->height;
   }

   /**
    *
    */
   final public function getStyle() {
      return $this->style;
   }

   /**
    *
    */
   final public function isStyle($styleFlag) {
      return ($this->style & $styleFlag);
   }

   /**
    *
    */
   final public function getXResolution() {
      return $this->xres;
   }

   /**
    *
    */
   final public function getFont() {
      return $this->font;
   }

   /**
    *
    */
   final public function getValue() {
      return $this->value;
   }

   /**
    *
    */
   final public function getContentType() {
      if ($this->style & self:: STYLE_IMAGE_PNG)
         return "image/png";

      if ($this->style & self:: STYLE_IMAGE_JPEG)
         return "image/jpeg";

      return null;
   }

   /**
    * @return the BarCode instance
    */
   abstract protected function Render();

   /**
    *
    */
   protected function DrawBorder() {
      ImageRectangle($this->hImg, 0, 0, $this->width-1, $this->height-1, $this->fgColor);
   }

   /**
    *
    */
   protected function DrawChar($xPos, $yPos, $char) {
      ImageString($this->hImg, $this->font, round($xPos), round($yPos), $char, $this->fgColor);
      return $this;
   }

   /**
    *
    */
   protected function DrawText($xPos, $yPos, $char) {
      ImageString($this->hImg, $this->font, round($xPos), round($yPos), $char, $this->fgColor);
   }

   /**
    *
    */
   protected function DrawSingleBar($xPos, $yPos, $xSize, $ySize) {
      $xPos  = round($xPos);
      $yPos  = round($yPos);
      $xSize = round($xSize);
      $ySize = round($ySize);

      if ($xPos>=0 && $xPos<=$this->width && ($xPos+$xSize)<=$this->width && $yPos>=0 && $yPos<=$this->height && ($yPos+$ySize)<=$this->height) {
         for ($i=0; $i<$xSize; $i++)
            ImageLine($this->hImg, $xPos+$i, $yPos, $xPos+$i, $yPos+$ySize, $this->fgColor);
         return;
      }
      throw new RuntimeException("Drawing position out of range: Increase the image size or choose a smaller xRes value (bar spacing)");
   }

   /**
    *
    */
   protected function GetFontHeight() {
      return ImageFontHeight($this->font);
   }

   /**
    *
    */
   protected function GetFontWidth() {
      return ImageFontWidth($this->font);
   }

   /**
    * @return the BarCode instance
    */
   public function stream() {
      if (!$this->isRendered)
         $this->Render();

      header("Content-Type: ".$this->getContentType());

      if      ($this->style & self:: STYLE_IMAGE_PNG ) ImagePng($this->hImg);
      else if ($this->style & self:: STYLE_IMAGE_JPEG) ImageJpeg($this->hImg);

      return $this;
   }

   /**
    */
   public function toString() {
      if (!$this->isRendered)
         $this->Render();

      if (ob_start()) {
         if      ($this->style & self:: STYLE_IMAGE_PNG ) ImagePng($this->hImg);
         else if ($this->style & self:: STYLE_IMAGE_JPEG) ImageJpeg($this->hImg);
         $content = ob_get_clean();
         return $content;
      }
      return null;
   }

   /**
    * Destructor
    */
   public function __destruct() {
      try {
         if ($this->hImg)
            ImageDestroy($this->hImg);
      }
      catch (Exception $ex) {
         Logger ::handleException($ex, true);
         throw $ex;
      }
   }
}
?>
