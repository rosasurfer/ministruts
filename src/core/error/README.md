
Error handling of destructor exceptions
---------------------------------------

Attempting to throw an exception from an object destructor during script shutdown causes a fatal error which is not catchable
by an installed error handler.

 @see  [http://php.net/manual/en/language.oop5.decon.php](http://php.net/manual/en/language.oop5.decon.php)

To catch and handle such exceptions the destructor must be wrapped. It's recommended to wrap any destructor regardless of the task.

```php
use rosasurfer\core\error\ErrorHandler;

class Foo {
    public function __destruct() {
        try {
            // a task that might throw an exception
        }
        catch (\Throwable $ex) {
            // during script shutdown the error handler early-exits and the "throw" statement will not be reached
            throw ErrorHandler::handleDestructorException($ex);         
        }
    }
}
```
