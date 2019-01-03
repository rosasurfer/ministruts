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
     * Return the name of the class containing methods with dynamic return types.
     *
     * @return string
     */
    public static function getClass() : string {
        if (!static::$className) throw new RuntimeException('The class property '.static::class.'::$className must be defined.');
        return static::$className;
    }


    /**
     * Whether the named instance method returns dynamic types.
     *
     * @return bool
     */
    public function isMethodSupported(MethodReflection $methodReflection) : bool {
        if (!static::$methodNames) throw new RuntimeException('The class property '.static::class.'::$methodNames must be defined.');
        return in_array($methodReflection->getName(), static::$methodNames);
    }


    /**
     * Whether the named static method returns dynamic types.
     *
     * @return bool
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection) : bool {
        if (!static::$methodNames) throw new RuntimeException('The class constant '.static::class.'::$methodNames must be defined.');
        return in_array($methodReflection->getName(), static::$methodNames);
    }


    /**
     * Return a description of the passed scope.
     *
     * @param  Scope $scope
     *
     * @return string - scope description
     */
    protected function getScopeDescription(Scope $scope) : string {
        if ($scope->isInClass()) {
            $description = $scope->getClassReflection()->getName();
            if ($scope->getFunctionName())       $description .= '::'.$scope->getFunctionName().'()';
            if ($scope->isInAnonymousFunction()) $description .= '{closure}';
            return $description;
        }

        if ($scope->getFunctionName()) {
            $description = $scope->getFunctionName().'()';
            if ($scope->isInAnonymousFunction()) $description .= '{closure}';
            return $description;
        }

        $description = $scope->isInAnonymousFunction() ? '{closure}' : '{main}';
        $description = trim($scope->getNamespace().'\\'.$description, '\\');
        return $description;
    }
}
