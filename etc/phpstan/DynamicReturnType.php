<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

use PHPStan\Type\UnionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

use rosasurfer\core\Object;
use rosasurfer\core\exception\RuntimeException;


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
    public function getClass() : string {
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


    /**
     * Analyse a {@link Type} and find a subclass of the specified base class.
     *
     * @param  Type   $type
     * @param  string $baseClass
     *
     * @return string|null - subclass or NULL if no such subclass was found
     */
    protected function findSubclass(Type $type, $baseClass) {
        $subclass = $name = null;

        if ($type instanceof UnionType) {
            $self = __FUNCTION__;
            foreach ($type->getTypes() as $subtype) {
                $subclass = $this->$self($subtype, $baseClass);
                if ($subclass) break;
            }
            return $subclass;
        }

        if ($type instanceof TypeWithClassName) $name = $type->getClassName();
        else                                    $name = $type->describe();

        if ($name!=$baseClass && is_a($name, $baseClass, $allowString=true)) {
            $subclass = $name;
        }
        return $subclass;
    }
}
