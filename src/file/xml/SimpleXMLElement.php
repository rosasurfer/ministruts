<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\file\xml;

use Throwable;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\strRightFrom;

use const rosasurfer\ministruts\NL;

/**
 * SimpleXMLElement
 *
 * A {@link \SimpleXMLElement} with additional functionalities.
 */
class SimpleXMLElement extends \SimpleXMLElement {

    use ObjectTrait, DiAwareTrait;

    /**
     * Create a new SimpleXMLElement instance.
     *
     * @param  string $data                  - XML string or the path or URL to an XML document if $dataIsUri is TRUE
     * @param  int    $options    [optional] - additional Libxml options (default: none)
     * @param  bool   $dataIsUri  [optional] - whether $data is a URI (default: no)
     * @param  string $ns         [optional] - namespace prefix or URI (default: empty)
     * @param  bool   $nsIsPrefix [optional] - whether $ns is a prefix or a URI (default: a URI)
     *
     * @return static
     */
    public static function from(string $data, int $options=0, bool $dataIsUri=false, string $ns='', bool $nsIsPrefix=false): self {
        $errors = [];
        $origHandler = null;
        $origHandler = set_error_handler(static function($level, $message, $file, $line, $context=null) use (&$errors, &$origHandler) {
            if ($origHandler && \in_array($level, [E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE, E_USER_WARNING], true)) {
                return ($origHandler)(...func_get_args());
            }
            $errors[] = func_get_args();
            return true;
        });

        $xml = $ex = null;
        try {
            $xml = new static(...func_get_args());
        }
        catch (Throwable $ex) {
            if (!$ex instanceof IRosasurferException) {
                $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
            }
        }
        finally {
            restore_error_handler();
            if (!$xml) {
                $errorMsg = [];
                foreach ($errors as $error) {
                    $errorMsg[] = strRightFrom($error[1], 'SimpleXMLElement::__construct(): ', 1, false, $error[1]);
                }
                throw $ex->appendMessage(join(NL, $errorMsg));
            }
            if ($origHandler) {
                foreach ($errors as $error) {
                    ($origHandler)(...$error);              // pass errors on to the original handler
                }
            }
        }
        return $xml;
    }
}
