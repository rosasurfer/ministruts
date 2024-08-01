
Error handling of destructor exceptions
---------------------------------------

Attempting to throw an exception from an object destructor during script shutdown causes a fatal error
which is not catchable by a custom exception handler.

 @see  https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.destructor

To catch and handle such exceptions the destructor must be wrapped as shown. It's recommended to wrap any destructor regardless of the task.

```php
use rosasurfer\ministruts\core\error\ErrorHandler;

class Foo {
    public function __destruct() {
        try {
            // a task that might throw an exception
        }
        catch (\Throwable $ex) {
            // Only during script shutdown the handler will early-exit to prevent the following internal PHP error.
            $ex = ErrorHandler::handleDestructorException($ex);
            throw $ex;
        }
    }
}
```
