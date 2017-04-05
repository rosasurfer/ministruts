<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ObjectType;

use rosasurfer\core\Object;
use rosasurfer\exception\RuntimeException;
use PHPStan\Type\ArrayType;


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
     * @return string - class name or "{main}" for calls from outside a class; "(unknown)" in case of errors
     */
    protected function getScopeName(Scope $scope) : string {
        if (method_exists($scope, $method='getClass')) {                    // branch 0.6.x
            $name = $scope->$method() ?: '{main}';
        }
        else if (method_exists($scope, $method='getClassReflection')) {     // branch master
            $reflection = $scope->$method();
            $name = $reflection ? $reflection->getName() : '{main}';
        }
        else {
            $name = '(unknown)';
        }
        return $name;
    }


    /**
     * Return a new {@link ObjectType} in a branch compatible way.
     *
     * @return ObjectType
     */
    protected function createObjectType($class) : ObjectType {
        return new ObjectType(...[$class, false]);                          // branches 0.6.x and master
    }


    /**
     * Return a new {@link ArrayType}.
     *
     * @return ArrayType
     */
    protected function createArrayType($class) : ArrayType {
        $objectType = $this->createObjectType($class);
        return new ArrayType($objectType, false);
    }
}
