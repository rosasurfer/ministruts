<?php
use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use const rosasurfer\mkDirWritable;


/**
 * Barcode renderer class
 *
 * @version  0.0.8a  2001-08-03
 * @author   barcode@mribti.com
 * @link     http://www.mribti.com/barcode/
 * @author   extended & refactored by pewa
 */

// stupid hack to circumvent PHP's inability to resolve combined constants at compile time
define('BARCODE_DEFAULT_STYLE', 4 | 64);   // STYLE_ALIGN_CENTER | STYLE_IMAGE_PNG

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
   const /*int*/ DEFAULT_STYLE            = BARCODE_DEFAULT_STYLE;   // see hack above class declaration
   const /*int*/ DEFAULT_FOREGROUND_COLOR = 0x000000;                // black
   const /*int*/ DEFAULT_BACKGROUND_COLOR = 0xFFFFFF;                // white
   const /*int*/ DEFAULT_FONT             =  5;
   const /*int*/ DEFAULT_XRES             =  2;
   const /*int*/ DEFAULT_MARGIN_Y1        = 10;
   const /*int*/ DEFAULT_MARGIN_Y2        = 10;
   const /*int*/ DEFAULT_TEXT_OFFSET      =  2;


   protected /*string*/ $value,
             /*int*/    $width,
             /*int*/    $height,
             /*int*/    $style   = self:: DEFAULT_STYLE,
             /*bool*/   $reverseColor,
             /*int*/    $bgColor,
             /*int*/    $fgColor,
             /*int*/    $xres    = self:: DEFAULT_XRES,
             /*int*/    $font    = self:: DEFAULT_FONT,
             /*bool*/   $isRendered;

   private   /*string*/ $imgData,
             /*hImg*/   $hImg;


   /**
    * Geschützter Constructor, Instanzen können nur von abgeleiteten Klassen erzeugt werden.
    */
   protected function __construct($value, $width, $height, $style=null, $xres=null, $font=null) {
      // Parameter validieren und speichern
      // $value
      if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
      if (strLen($value)==0)  throw new InvalidArgumentException('Invalid barcode $value: "'.$value.'"');
      $this->value = $value;

      // $width
      if (is_string($width)) {
         if (!cType_digit($width)) throw new InvalidArgumentException('Invalid barcode $width: "'.$width.'"');
         $width = (int) $width;
      }
      else if (!is_int($width))    throw new IllegalTypeException('Illegal type of parameter $width: '.getType($width));
      if ($width <= 0)             throw new InvalidArgumentException('Invalid barcode $width: "'.$width.'"');
      $this->width = $width;

      // $height
      if (is_string($height)) {
         if (!cType_digit($height)) throw new InvalidArgumentException('Invalid barcode $height: "'.$height.'"');
         $height = (int) $height;
      }
      else if (!is_int($height))    throw new IllegalTypeException('Illegal type of parameter $height: '.getType($height));
      if ($height <= 0)             throw new InvalidArgumentException('Invalid barcode $height: "'.$height.'"');
      $this->height = $height;

      // $style
      if (!is_null($style)) {
         if (is_string($style)) {
            if (!cType_digit($style)) throw new InvalidArgumentException('Invalid barcode $style: "'.$style.'"');
            $style = (int) $style;
         }
         else if (!is_int($style))    throw new IllegalTypeException('Illegal type of parameter $style: '.getType($style));
         $alignCenter = (bool)($style & self:: STYLE_ALIGN_CENTER);
         $alignLeft   = (bool)($style & self:: STYLE_ALIGN_LEFT  );
         $alignRight  = (bool)($style & self:: STYLE_ALIGN_RIGHT );
         if ($alignCenter + $alignLeft + $alignRight > 1)                               throw new InvalidArgumentException('Invalid barcode $style, multiple alignment flags given: '.$this->getStyleDescription($style));
         if (!($style & self:: STYLE_IMAGE_PNG) && !($style & self:: STYLE_IMAGE_JPEG)) throw new InvalidArgumentException('Invalid barcode $style, missing or unknown graphic format: '.$this->getStyleDescription($style));
         $this->style = $style;
      }

      // $xres
      if (!is_null($xres)) {
         if (is_string($xres)) {
            if (!cType_digit($xres)) throw new InvalidArgumentException('Invalid barcode $xres: "'.$xres.'"');
            $xres = (int) $xres;
         }
         else if (!is_int($xres))    throw new IllegalTypeException('Illegal type of parameter $xres: '.getType($xres));
         if ($xres < 1 || 3 < $xres) throw new InvalidArgumentException('Invalid barcode $xres: "'.$xres.'"');
         $this->xres = $xres;
      }

      // $font
      if (!is_null($font)) {
         if (is_string($font)) {
            if (!cType_digit($font)) throw new InvalidArgumentException('Invalid barcode $font: "'.$font.'"');
            $font = (int) $font;
         }
         else if (!is_int($font))    throw new IllegalTypeException('Illegal type of parameter $font: '.getType($font));
         if ($font < 1 || 5 < $font) throw new InvalidArgumentException('Invalid barcode $font: "'.$font.'"');
         $this->font = $font;
      }

      // Image erzeugen
      $this->hImg = imageCreate($this->width, $this->height);

      $this->reverseColor = $this->style & self:: STYLE_REVERSE_COLOR;
      $bgColor = $this->reverseColor ? self:: DEFAULT_FOREGROUND_COLOR : self:: DEFAULT_BACKGROUND_COLOR;
      $fgColor = $this->reverseColor ? self:: DEFAULT_BACKGROUND_COLOR : self:: DEFAULT_FOREGROUND_COLOR;

      $this->bgColor = imageColorAllocate($this->hImg, ($bgColor & 0xFF0000) >> 16, ($bgColor & 0x00FF00) >> 8 , $bgColor & 0x0000FF);
      $this->fgColor = imageColorAllocate($this->hImg, ($fgColor & 0xFF0000) >> 16, ($fgColor & 0x00FF00) >> 8 , $fgColor & 0x0000FF);

      if (!($this->style & self:: STYLE_TRANSPARENT))
         imageFill($this->hImg, $this->width, $this->height, $this->bgColor);
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
   final public function getStyleDescription($style=null) {
      if (func_num_args()) {
         if (!is_int($style)) throw new IllegalTypeException('Illegal type of parameter $style: '.getType($style));
      }
      else {
         $style = $this->style;
      }
      $strings = array();

      if ($style & self:: STYLE_IMAGE_JPEG   ) $strings[] = 'STYLE_IMAGE_JPEG';
      if ($style & self:: STYLE_IMAGE_PNG    ) $strings[] = 'STYLE_IMAGE_PNG';
      if ($style & self:: STYLE_ALIGN_CENTER ) $strings[] = 'STYLE_ALIGN_CENTER';
      if ($style & self:: STYLE_ALIGN_LEFT   ) $strings[] = 'STYLE_ALIGN_LEFT';
      if ($style & self:: STYLE_ALIGN_RIGHT  ) $strings[] = 'STYLE_ALIGN_RIGHT';
      if ($style & self:: STYLE_DRAW_TEXT    ) $strings[] = 'STYLE_DRAW_TEXT';
      if ($style & self:: STYLE_STRETCH_TEXT ) $strings[] = 'STYLE_STRETCH_TEXT';
      if ($style & self:: STYLE_BORDER       ) $strings[] = 'STYLE_BORDER';
      if ($style & self:: STYLE_TRANSPARENT  ) $strings[] = 'STYLE_TRANSPARENT';
      if ($style & self:: STYLE_REVERSE_COLOR) $strings[] = 'STYLE_REVERSE_COLOR';

      return join(' | ', $strings).' ('.$style.')';
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
   abstract protected function render();


   /**
    *
    */
   protected function drawBorder() {
      imageRectangle($this->hImg, 0, 0, $this->width-1, $this->height-1, $this->fgColor);
   }


   /**
    *
    */
   protected function drawChar($xPos, $yPos, $char) {
      imageString($this->hImg, $this->font, (int)round($xPos), (int)round($yPos), $char, $this->fgColor);
      return $this;
   }


   /**
    *
    */
   protected function drawText($xPos, $yPos, $char) {
      imageString($this->hImg, $this->font, (int)round($xPos), (int)round($yPos), $char, $this->fgColor);
   }


   /**
    *
    */
   protected function drawSingleBar($xPos, $yPos, $xSize, $ySize) {
      $xPos  = (int) round($xPos);
      $yPos  = (int) round($yPos);
      $xSize = (int) round($xSize);
      $ySize = (int) round($ySize);

      if ($xPos>=0 && $xPos<=$this->width && ($xPos+$xSize)<=$this->width && $yPos>=0 && $yPos<=$this->height && ($yPos+$ySize)<=$this->height) {
         for ($i=0; $i<$xSize; $i++)
            imageLine($this->hImg, $xPos+$i, $yPos, $xPos+$i, $yPos+$ySize, $this->fgColor);
         return;
      }
      throw new RuntimeException("Drawing position out of range: Increase the image size or choose a smaller xRes value (bar spacing)");
   }


   /**
    *
    */
   protected function getFontHeight() {
      return imageFontHeight($this->font);
   }


   /**
    *
    */
   protected function getFontWidth() {
      return imageFontWidth($this->font);
   }


   /**
    * @return the BarCode instance
    */
   public function stream() {
      if (!$this->isRendered)
         $this->render();

      header("Content-Type: ".$this->getContentType());

      if      ($this->style & self:: STYLE_IMAGE_PNG ) imagePng($this->hImg);
      else if ($this->style & self:: STYLE_IMAGE_JPEG) imageJpeg($this->hImg);

      return $this;
   }


   /**
    *
    */
   public function toString() {
      if (!$this->isRendered)
         $this->render();

      if ($this->imgData===null && ob_start()) {
         if      ($this->style & self:: STYLE_IMAGE_PNG ) imagePng($this->hImg);
         else if ($this->style & self:: STYLE_IMAGE_JPEG) imageJpeg($this->hImg);
         $this->imgData = ob_get_clean();
      }

      return $this->imgData;
   }


   /**
    * Speichert das Barcode-Image in einer Datei.
    *
    * @param  string $filename  - Dateiname
    * @param  bool   $owerwrite - ob eine vorhandene Datei überschrieben werden soll (default: nein)
    *
    * @return BarCode instance
    */
   public function saveAs($filename, $overwrite=false) {
      if (!is_string($filename)) throw new IllegalTypeException('Illegal type of parameter $filename: '.getType($filename));
      if (!is_bool($overwrite))  throw new IllegalTypeException('Illegal type of parameter $overwrite: '.getType($overwrite));

      // Datei schreiben
      mkDirWritable(dirName($filename), 0755);
      $fileExisted = is_file($filename);
      $hFile = $ex = null;
      try {
         $hFile = fOpen($filename, ($overwrite ? 'w':'x').'b');
         fWrite($hFile, $this->toString());
         fClose($hFile);
      }
      catch (\Exception $ex) {
         if (is_resource($hFile))                 fClose($hFile);       // Unter Windows kann die Datei u.U. nicht im Exception-Handler gelöscht werden (gesperrt).
      }                                                                 // Das File-Handle muß innerhalb UND außerhalb des Exception-Handlers geschlossen werden,
      if ($ex) {                                                        // erst dann läßt sich die Datei unter Windows löschen.
         if (is_resource($hFile))                 fClose($hFile);
         if (!$fileExisted && is_file($filename)) unlink($filename);
         throw $ex;
      }
      return $this;
   }


   /**
    * Destructor
    */
   public function __destruct() {
      // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
      // @see http://php.net/manual/en/language.oop5.decon.php
      try {
         if ($this->hImg)
            imageDestroy($this->hImg);
      }
      catch (\Exception $ex) {
         System::handleDestructorException($ex);
         throw $ex;
      }
   }
}
