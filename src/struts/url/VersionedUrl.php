<?php
namespace rosasurfer\struts\url;


/**
 * Version-aware URL generation helper. Appends a hash of size and last modification time of a local file to the generated
 * URL to automatically invalidate browser and proxy caches.
 */
class VersionedUrl extends Url {


   /**
    * Return a text presentation of this Url.
    *
    * @return string
    */
   public function __toString() {
      $uri = parent::__toString();

      $relativeUri = $this->appRelativeUri;
      if (($pos=strPos($relativeUri, '?')) === false) $name = $relativeUri;
      else                                            $name = subStr($relativeUri, 0, $pos);

      // TODO: replace static directory references by configuration value
      foreach (['/public/', '/web/', '/www/'] as $dir) {
         if (file_exists($fileName=APPLICATION_ROOT.$dir.$name)) {
            if ($pos === false) $uri .= '?';
            else                $uri .= '&';
            $uri .= decHex(crc32(fileSize($fileName).'|'.fileMtime($fileName)));
            break;
         }
      }
      return $uri;
   }
}
