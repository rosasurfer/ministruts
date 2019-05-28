Error handling of destructor exceptions
---------------------------------------

Attempting to throw an exception from an object destructor during script shutdown causes a fatal error which is not catchable
by an installed error handler.

@see  http://php.net/manual/en/language.oop5.decon.php

To catch such exceptions the destructor must be wrapped:

```php
use rosasurfer\core\debug\ErrorHandler;

class Foo {
    public function __destruct() {
        try {
            // task that might throw an exception...
        }
        catch (\Throwable $ex) {
            throw ErrorHandler::handleDestructorException($ex);     // only during script shutdown "throw" is not reached
        }
    }
}
```

It is recommended to wrap any destructor regardless of the task.
