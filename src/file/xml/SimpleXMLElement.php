<?php
namespace rosasurfer\file\xml;

use rosasurfer\core\ObjectTrait;
use rosasurfer\di\DiAwareTrait;


/**
 * SimpleXMLElement
 *
 * A SimpleXMLElement drop-in replacement that will make you smile.
 */
class SimpleXMLElement extends \SimpleXMLElement {

    use ObjectTrait, DiAwareTrait;
}
