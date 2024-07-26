<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts\url;

use rosasurfer\ministruts\config\ConfigInterface;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\error\ErrorHandler;


/**
 * Version-aware URL generation helper. Appends a hash of size and last modification time of a local file to the generated
 * URL to automatically invalidate browser and proxy caches.
 */
class VersionedUrl extends Url {


    /**
     * @return string
     */
    public function __toString() {
        $uri = '';
        try {
            $uri = parent::__toString();
            $relativeUri = $this->appRelativeUri;
            if (($pos=strpos($relativeUri, '?')) === false) $name = $relativeUri;
            else                                            $name = substr($relativeUri, 0, $pos);

            /** @var ConfigInterface $config */
            $config = $this->di('config');
            $webDir = $config->get('app.dir.web', null);

            if ($webDir && file_exists($fileName=$webDir.'/'.$name)) {
                if ($pos === false) $uri .= '?';
                else                $uri .= '&';
                $uri .= dechex(crc32(filesize($fileName).'|'.filemtime($fileName)));
            }
            Assert::string($uri);
        }                                                                       // Ensure __toString() doesn't throw an exception as otherwise
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }  // PHP < 7.4 will trigger a non-catchable fatal error.
        return $uri;                                                            // @see  https://bugs.php.net/bug.php?id=53648
    }
}
