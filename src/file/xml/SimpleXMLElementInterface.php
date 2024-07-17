<?php
namespace rosasurfer\file\xml;


/**
 * SimpleXMLElementInterface
 *
 * Since PHP 8.0 {@link \SimpleXMLElement::__construct()} is not final anymore. This interface prevents subclasses from modifying the
 * constructor.
 */
interface SimpleXMLElementInterface {

	/**
	 * PHP 7
	 *
	 * @param  mixed $data
	 * @param  mixed $options           [optional]
	 * @param  mixed $dataIsUrl         [optional]
	 * @param  mixed $namespaceOrPrefix [optional]
	 * @param  mixed $isPrefix          [optional]
	 */
	public function __construct($data, $options = null, $dataIsUrl = null, $namespaceOrPrefix = null, $isPrefix = null);


	/**
	 * PHP 8
	 *
	 * @param  string $data
	 * @param  int    $options           [optional]
	 * @param  bool   $dataIsUrl         [optional]
	 * @param  string $namespaceOrPrefix [optional]
	 * @param  bool   $isPrefix          [optional]
	 *
	public function __construct(string $data, int $options = 0, bool $dataIsUrl = false, string $namespaceOrPrefix = '', bool $isPrefix = false);
	 */
}
