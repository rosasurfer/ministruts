<?php
namespace rosasurfer\file\xml;

use rosasurfer\core\ObjectTrait;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\di\DiAwareTrait;

use function rosasurfer\strRightFrom;

use const rosasurfer\NL;


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
     * @return SimpleXMLElement
     */
    public static function from($data, $options=0, $dataIsUri=false, $ns='', $nsIsPrefix=false) {
        $errors = [];
        $oldHandler = set_error_handler(function($level, $message, $file, $line, $context=null) use (&$errors, &$oldHandler) {
            if ($oldHandler && in_array($level, [E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE, E_USER_WARNING]))
                return $oldHandler(...func_get_args());
            $errors[] = func_get_args();
            return true;
        });

        /** @var SimpleXMLElement $xml */
        $xml = $ex = null;
        try {
            $xml = new static(...func_get_args());
        }
        catch (IRosasurferException $ex) {}
        catch (\Throwable           $ex) { $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex); }
        catch (\Exception           $ex) { $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex); }

        finally {
            restore_error_handler();
            if (!$xml) {
                $ex = $ex ?: new RuntimeException('PHP errors/warnings while parsing the XML');
                foreach ($errors as $i => $error) {
                    $errors[$i] = strRightFrom($error[1], 'SimpleXMLElement::__construct(): ', 1, false, $error[1]);
                }
                throw $ex->addMessage(join(NL, $errors));
            }

            foreach ($errors as $error) {
                $oldHandler && $oldHandler(...$error);      // pass on errors to the original handler
            }
        }
        return $xml;
    }
}
