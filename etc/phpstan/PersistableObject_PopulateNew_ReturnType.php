<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

use rosasurfer\db\orm\PersistableObject;

use function rosasurfer\echoPre;
use function rosasurfer\simpleClassName;


/**
 *
 */
class PersistableObject_PopulateNew_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension,
                                                                                    DynamicStaticMethodReturnTypeExtension {

    /** @var string */
    protected static $className = PersistableObject::class;

    /** @var string[] */
    protected static $methodNames = ['populateNew'];


    /**
     * Resolve the return type of an instance call to PersistableObject->populateNew().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if (0 || $error) echoPre('call of: '.simpleClassName(static ::$className).'->'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }


    /**
     * Resolve the return type of a static call to PersistableObject::populateNew().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if (0 || $error) echoPre('call of: '.simpleClassName(static ::$className).'::'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }
}
