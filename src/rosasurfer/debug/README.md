Error handling of destructor exceptions
---------------------------------------

Attempting to throw an exception from an object's destructor during script shutdown causes a fatal PHP error.

@see http://php.net/manual/en/language.oop5.decon.php

To cover this situations with accurate error handling it is recommended to wrap all user land destructors that might throw exceptions with the following code, to protect against such errors.

```php
use rosasurfer\debug\ErrorHandler;

class Foo {
   public function __destruct() {
      try {
         //...a task that might throw an exception
      }
      catch (\Exception $ex) {
         ErrorHandler::handleDestructorException($ex);
         throw $ex;     // in shutdown this line is never reached
      }
   }
}
```

