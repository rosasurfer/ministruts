<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

use rosasurfer\ministruts\db\orm\DAO;
use rosasurfer\ministruts\db\orm\PersistableObject;

use function rosasurfer\ministruts\simpleClassName;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeft;

/**
 * Overwrites the return types of {@link DAO::find()}, {@link DAO::findAll()}, {@link DAO::get()} and {@link DAO::getAll()}
 * so that they return concrete model types instead of the general {@link PersistableObject} type.
 */
class DaoReturnType extends Extension implements DynamicMethodReturnTypeExtension {

    /** @var string[] */
    protected static array $supportedMethods = [
      // method name =>  ReturnType::describe(TYPE_ONLY)
        'get'        =>  PersistableObject::class,
        'find'       =>  PersistableObject::class.'|null',
        'getAll'     => 'array<'.PersistableObject::class.'>',
        'findAll'    => 'array<'.PersistableObject::class.'>',
    ];


    /**
     * Return the name of the class supported by this extension.
     *
     * @return string
     */
    public function getClass(): string {
        return DAO::class;
    }


    /**
     * Whether the passed method is supported by this extension.
     *
     * @param  MethodReflection $method
     *
     * @return bool
     */
    public function isMethodSupported(MethodReflection $method): bool {
        return isset(self::$supportedMethods[$method->getName()]);
    }


    /**
     * Resolve the original return type of the passed method call.
     *
     * @param  MethodReflection $method
     * @param  MethodCall       $methodCall
     * @param  Scope            $scope
     *
     * @return Type
     */
    protected function getOriginalTypeFromMethodCall(MethodReflection $method, MethodCall $methodCall, Scope $scope): Type {
        $args = $methodCall->getArgs();
        $variants = $method->getVariants();
        $signature = ParametersAcceptorSelector::selectFromArgs($scope, $args, $variants);
        return $signature->getReturnType();
    }


    /**
     * Resolve the new return type of the passed method call.
     *
     * @param  MethodReflection $method
     * @param  MethodCall       $methodCall
     * @param  Scope            $scope
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $method, MethodCall $methodCall, Scope $scope): Type {
        $methodName = $method->getName();
        $call = simpleClassName($this->getClass())."->$methodName()";

        $scopeType      = $scope->getType($methodCall->var);
        $scopeTypeDescr = $scopeType->describe(VerbosityLevel::typeOnly());
        $scopeDescr     = simpleClassName(get_class($scopeType)).'('.simpleClassName($scopeTypeDescr).')';

        // get the class of the call
        $classNames = $scopeType->getObjectClassNames();
        if (sizeof($classNames) != 1) throw new ExtensionException("$call: unexpected scope class name: ".join(', ', $classNames).' (expected exactly one)');
        $scopeClass = $classNames[0];

        // skip calls from the base DAO itself
        $origType = $this->getOriginalTypeFromMethodCall($method, $methodCall, $scope);
        if ($scopeClass == DAO::class) {
            return $origType;
        }

        // validate the model class
        if (!strEndsWith($scopeClass, 'DAO')) throw new ExtensionException("$call: unexpected scope class $scopeClass (expected a model DAO)");
        $modelClass = strLeft($scopeClass, -3);
        if (!is_subclass_of($modelClass, PersistableObject::class)) {                                           // @phpstan-ignore phpstanApi.runtimeReflection (PHPStan 2.+)
            throw new ExtensionException("$call: invalid model class $modelClass (not an PersistableObject)");  // Extensions only rule, but it doesn't check origin of arguments.
        }

        // validate the supported return types
        $origTypeDescr = $origType->describe(VerbosityLevel::typeOnly());
        $expectedDescr = self::$supportedMethods[$methodName];
        if (!\in_array($origTypeDescr, self::$supportedMethods, true)) throw new ExtensionException("$call: unexpected return type $origTypeDescr (expected $expectedDescr)");

        // branch according to the original return type
        $newType = null;

        switch (get_class($origType)) {
            case ObjectType::class:
            case UnionType::class:
                $newType = TypeCombinator::remove($origType, new ObjectType(PersistableObject::class));
                $newType = TypeCombinator::union($newType, new ObjectType($modelClass));
                break;

            case ArrayType::class:
                $newType = new ArrayType($origType->getKeyType(), new ObjectType($modelClass));
                break;

            default:
                throw new ExtensionException("$call: unsupported return type ".simpleClassName(get_class($origType))."($origTypeDescr)");
        }

        //self::log("$call: $origTypeDescr  scope=".$scopeDescr."  => ".$newType->describe(VerbosityLevel::typeOnly()));
        return $newType;
    }
}
