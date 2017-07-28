<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

use rosasurfer\core\Object;
use rosasurfer\exception\RuntimeException;


abstract class DynamicReturnType extends Object {


    const CLASS_NAME  = null;
    const METHOD_NAME = null;


    /**
     * @return string
     */
    public static function getClass() : string {
        if (!static::CLASS_NAME) throw new RuntimeException('The class constant '.static::class.'::CLASS_NAME must be defined.');
        return static::CLASS_NAME;
    }


    /**
     * @return bool
     */
    public function isMethodSupported(MethodReflection $methodReflection) : bool {
        if (!static::METHOD_NAME) throw new RuntimeException('The class constant '.static::class.'::METHOD_NAME must be defined.');
        return $methodReflection->getName() == static::METHOD_NAME;
    }


    /**
     * @return bool
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection) : bool {
        if (!static::METHOD_NAME) throw new RuntimeException('The class constant '.static::class.'::METHOD_NAME must be defined.');
        return $methodReflection->getName() == static::METHOD_NAME;
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
