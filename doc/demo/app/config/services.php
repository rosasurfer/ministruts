<?php
use rosasurfer\core\Object;
use rosasurfer\net\mail\SMTPMailer;


/**
 * Service configuration.
 *
 * Services can be accessed via the dependency container. The definition does not specify a type (service locator or factory
 * pattern). Instead the type is determined at runtime from the used DI resolver.
 *
 * <pre>
 *  $di = $this->di();                                      // getting the default container inside of a class context
 *  $di = Application::getDi();                             // getting the default container outside of a class context
 *
 *  // defining a parameterless service using a string
 *  $di->set('request', \rosasurfer\ministruts\Request::class);
 *
 *  // defining a parameterized service using an anonymous function
 *  $di->set('tile', function(...$args) {
 *      return new \rosasurfer\ministruts\Tile(...$args);
 *  });
 *
 *  $request = $di->get('request');                         // resolving a shared instance using the service locator pattern
 *  $tile    = $di->factory('tile', ...$args);              // resolving a new instance using the factory pattern
 * </pre>
 */

return [
    'mail' => function(...$args) {
        return new SMTPMailer(...$args);
    },
    'object' => Object::class,

    'user-service' => function() {
        return new Object();
    },
];
