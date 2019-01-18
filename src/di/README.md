
### Dependency injection and service location

Services are configured in the application's config directory in the file ```services.php```. The file must return an array
with service definitions in the form ```$name => $definition```.

- ```$name```: A service identifier.
- ```$definition```: A service definition. May be a ```string``` (interpreted as a service classname), a ```\Closure``` or
  anonymous function (interpreted as a service factory) or another ```object``` instance (interpreted as a service itself).

A definition does not define whether a service can be used in a dependency injection or in a service location context. The
usage pattern is determined at runtime depending on the used DI accessor method:

- ```DI::get($name)```: Uses the dependency in a service location context and returns always the same instance. This method
  does not support additional instantiation parameters. All required parameters must be specified in the service definition.
- ```DI::factory($name, ...$params)```: Uses the dependency in a dependency injection context and returns always a new
  instance. This method supports additional instantiation parameters for each new instance.
  
Dependencies are lazy-loaded, i.e. they are instantiated on first use.


#### Example configuration file ```services.php```

```php
<?php
// services.php
return [
    // defining a parameterless dependency using a string
    'fruit' => \rosasurfer\model\Banana::class,

    // defining a parameterless dependency using an anonymous function
    'logger' => function() {
        return new \rosasurfer\log\Logger();
    },

    // defining a parameterized dependency using an anonymous function
    'mail' => function(...$args) {
        return new \rosasurfer\net\mail\SMTPMailer(...$args);
    },
];
```

Dependencies can be defined at runtime:
```php
$di = $this->di();                         // getting the default container in a class context
$di = Application::getDi();                // getting the default container if not in a class context

// defining a parameterless dependency
$di->set('warrior', \rosasurfer\model\Hobbit::class);
```
A parameterless definition can be used in a parameterized factory context. The opposite (using a definition which requires
arguments in a parameterless context, i.e. service location) is not possible:
```php
$di = Application::getDi();
$options = ['status'=>'not-afraid'];
$warrior = $di->factory('warrior', $options);
```

On dependency instantiation closures/anonymous functions are invoked in the class context of the DI container. In the function
body they have access to all registered application services:
```php
<?php
// services.php
return [
    // defining a dependency which requires the application configuration
    'db' => function() {
        $options = $this['config']['db.mysql'];
        return new \rosasurfer\db\mysql\MySQLConnector($options);
    },
];
```
