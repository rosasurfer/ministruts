<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts\url;

use rosasurfer\ministruts\config\ConfigInterface as Config;

/**
 * Version-aware URL generation helper. Appends a hash of size and last modification time of a
 * local file to the generated URL to automatically invalidate browser and proxy caches.
 */
class VersionedUrl extends Url {

    /**
     * {@inheritDoc}
     */
    public function __toString(): string {
        $uri = parent::__toString();
        $relativeUri = $this->appRelativeUri;

        $pos = strpos($relativeUri, '?');
        if ($pos === false) $name = $relativeUri;
        else                $name = substr($relativeUri, 0, $pos);

        /** @var Config $config */
        $config = $this->di('config');
        $webDir = $config->getString('app.dir.web', '');

        if ($webDir && file_exists($fileName = $webDir.'/'.$name)) {
            if ($pos === false) $uri .= '?';
            else                $uri .= '&';
            $uri .= dechex(crc32(filesize($fileName).'|'.filemtime($fileName)));
        }
        return $uri;
    }
}
