<?php
namespace rosasurfer\ministruts\url;


/**
 * Version-aware URL generation helper. Appends a hash of file size and file last modification time of a local file
 * to the generated URL to automatically invalidate browser and proxy caches.
 */
class VersionedUrl extends Url {


   /**
    * Return a text presentation of this Url.
    *
    * @return string
    */
   public function __toString() {
      $uri = parent::__toString();

      if (($pos=strPos($uri, '?')) === false) $name = $uri;
      else                                    $name = subStr($uri, 0, $pos);

      // TODO: replace static web directory reference by configuration value

      if (file_exists($fileName=APPLICATION_ROOT.'/www/'.$name)) {
         if ($pos === false) $uri .= '?';
         else                $uri .= '&';
         $uri .= decHex(crc32(fileSize($fileName).'|'.fileMtime($fileName)));
      }
      return $uri;
   }
}
