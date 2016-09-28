Handling of object destructor exceptions
----------------------------------------

```php
use rosasurfer\debug\ErrorHandler;

/**
 * Example class with a destructor
 */
class Foo {
   /**
    * Destructor
    */
   public function __destruct() {
      try {
         //...some work that might trigger an exception
      }
      catch (\Exception $ex) {
         ErrorHandler::handleDestructorException($ex);
         throw $ex;
      }
   }
}
```
