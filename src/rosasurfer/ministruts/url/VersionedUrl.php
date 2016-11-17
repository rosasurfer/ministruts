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
      $url = parent::__toString();

      if (file_exists($fileName=APPLICATION_ROOT.'/www/'.$this->uri)) {
         if (strPos($url, '?') === false) $url .= '?';
         else                             $url .= '&';
         $url .= decHex(crc32(fileSize($fileName).'|'.fileMtime($fileName)));
      }
      return $url;
   }
}
