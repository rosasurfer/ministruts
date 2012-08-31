<?php
/**
 * Einfache Superklasse für nur statisch zu verwendende Klassen.
 *
 * Klassen, die von StaticClass abgeleitet sind, können nicht instantiiert werden.
 */
abstract class StaticClass extends Object {


   final private function __construct() {/* you can't instantiate me */}
}
?>
