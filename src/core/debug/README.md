Error handling of destructor exceptions
---------------------------------------

Attempting to throw an exception from an object's destructor during script shutdown causes a fatal PHP error which is not
catchable by an installed error handler.

@see  http://php.net/manual/en/language.oop5.decon.php

To cover such exceptions with the framework's error handler the destructor which might throw an exception must be wrapped:

```php
use rosasurfer\debug\ErrorHandler;

class Foo {

    public function __destruct() {
        try {
            //...task which might throw an exception
        }
        catch (\Exception $ex) {
            throw ErrorHandler::handleDestructorException($ex);   // in shutdown the throw clause is not reached
        }
    }
}
```

It is recommended to wrap any destructor, regardless of the tasks.
