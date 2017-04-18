<?php
namespace rosasurfer\ministruts\url;

use rosasurfer\config\Config;


/**
 * Version-aware URL generation helper. Appends a hash of size and last modification time of a local file to the generated
 * URL to automatically invalidate browser and proxy caches.
 */
class VersionedUrl extends Url {


    /**
     * {@inheritDoc}
     */
    public function __toString() {
        $uri = parent::__toString();

        $relativeUri = $this->appRelativeUri;
        if (($pos=strPos($relativeUri, '?')) === false) $name = $relativeUri;
        else                                            $name = subStr($relativeUri, 0, $pos);

        $webDir = Config::getDefault()->get('app.dir.web');

        if (file_exists($fileName=$webDir.'/'.$name)) {
            if ($pos === false) $uri .= '?';
            else                $uri .= '&';
            $uri .= decHex(crc32(fileSize($fileName).'|'.fileMtime($fileName)));
        }
        return $uri;
    }
}
