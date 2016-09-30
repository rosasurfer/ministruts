Error handling of destructor exceptions
---------------------------------------

Attempting to throw an exception from an object's destructor during script shutdown
causes a fatal PHP error which is not catchable by the error handler.

@see http://php.net/manual/en/language.oop5.decon.php

To cover this situations with the frameworks regular error handling it is required
to wrap destructors that might throw exceptions with the following code:

```php
use rosasurfer\debug\ErrorHandler;

class Foo {

   public function __destruct() {
      try {
         //...a task that might throw an exception
      }
      catch (\Exception $ex) {
         ErrorHandler::handleDestructorException($ex);
         throw $ex;     // in shutdown this line will never be reached
      }
   }
}
```
The recommended practice is to use it for all destructors.
