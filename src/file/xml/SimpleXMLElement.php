<?php
namespace rosasurfer\file\xml;

use rosasurfer\core\ObjectTrait;
use rosasurfer\di\DiAwareTrait;


/**
 * SimpleXMLElement
 *
 * A {@link \SimpleXMLElement} with additional functionalities.
 */
class SimpleXMLElement extends \SimpleXMLElement {

    use ObjectTrait, DiAwareTrait;
}
