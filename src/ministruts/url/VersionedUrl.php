<?php
namespace rosasurfer\ministruts\url;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;


/**
 * Version-aware URL generation helper. Appends a hash of size and last modification time of a local file to the generated
 * URL to automatically invalidate browser and proxy caches.
 */
class VersionedUrl extends Url {


    /**
     * @return string
     */
    public function __toString() {
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

            Assert::string($uri);                               // Ensure __toString() returns a string as otherwise...
            return $uri;                                        // PHP will trigger a non-catchable fatal error.
        }
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }
    }
}
