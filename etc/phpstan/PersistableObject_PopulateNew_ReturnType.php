<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;
use rosasurfer\db\orm\PersistableObject;
use rosasurfer\phpstan\DynamicReturnType;

use function rosasurfer\echoPre;
use function rosasurfer\simpleClassName;


/**
 *
 */
class PersistableObject_PopulateNew_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension,
                                                                                    DynamicStaticMethodReturnTypeExtension {

    const CLASS_NAME  = PersistableObject::class;
    const METHOD_NAME = 'populateNew';


    /**
     * Resolve the return type of an instance call to PersistableObject->populateNew().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if (0 || $error) echoPre($this->getScopeName($scope).': '.simpleClassName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }


    /**
     * Resolve the return type of a static call to PersistableObject::populateNew().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if (0 || $error) echoPre($this->getScopeName($scope).': '.simpleClassName(self::CLASS_NAME).'::'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }
}
