<?php
namespace rosasurfer\file\xml;

use rosasurfer\core\ObjectTrait;
use rosasurfer\di\DiAwareTrait;


/**
 * SimpleXMLElement
 *
 * A {@link \SimpleXMLElement} drop-in replacement with added DI awareness and error handling.
 */
class SimpleXMLElement extends \SimpleXMLElement {

    use ObjectTrait, DiAwareTrait;
}
