Error handling of destructor exceptions
---------------------------------------

Attempting to throw an exception from an object's destructor during script shutdown causes a fatal PHP error. Therefore
special care needs to be paid to destructors that might trigger an exception. It is recommended to wrap all user land
destructors with the following code to protect against such errors.

```php
use rosasurfer\debug\ErrorHandler;

class Foo {
   public function __destruct() {
      try {
         //...a task that might trigger an exception
      }
      catch (\Exception $ex) {
         ErrorHandler::handleDestructorException($ex);
         throw $ex;     // only in shutdown this line is never reached
      }
   }
}
```

@see http://php.net/manual/en/language.oop5.decon.php
