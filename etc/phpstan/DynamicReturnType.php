<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use rosasurfer\core\Object;
use rosasurfer\exception\RuntimeException;


/**
 *
 */
abstract class DynamicReturnType extends Object {


    /** @var string */
    protected static $className = null;

    /** @var string[] */
    protected static $methodNames = [];


    /**
     * @return string
     */
    public static function getClass() : string {
        if (!static::$className) throw new RuntimeException('The class property '.static::class.'::$className must be defined.');
        return static::$className;
    }


    /**
     * @return bool
     */
    public function isMethodSupported(MethodReflection $methodReflection) : bool {
        if (!static::$methodNames) throw new RuntimeException('The class property '.static::class.'::$methodNames must be defined.');
        return in_array($methodReflection->getName(), static::$methodNames);
    }


    /**
     * @return bool
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection) : bool {
        if (!static::$methodNames) throw new RuntimeException('The class constant '.static::class.'::$methodNames must be defined.');
        return in_array($methodReflection->getName(), static::$methodNames);
    }


    /**
     * Return the name of the calling scope.
     *
     * @return string - class name or "{main}" for calls from outside a class; "(unknown)" on errors
     */
    protected function getScopeName(Scope $scope) : string {
        $reflection = $scope->getClassReflection();
        return $reflection ? $reflection->getName() : '{main}';
    }
}
