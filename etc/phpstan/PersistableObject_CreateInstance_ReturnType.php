<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;

use rosasurfer\db\orm\PersistableObject;
use rosasurfer\phpstan\DynamicReturnType;

use function rosasurfer\echoPre;


class PersistableObject_CreateInstance_ReturnType extends DynamicReturnType {


    const CLASS_NAME  = PersistableObject::class;
    const METHOD_NAME = 'createInstance';


    /**
     * Resolve the return type of an instance call to PersistableObject->createInstance().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $origReturnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $origReturnType->getClass();
        $error = false;
        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
        return $returnType;
    }


    /**
     * Resolve the return type of a static call to PersistableObject::createInstance().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        $returnType  = $origReturnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $origReturnType->getClass();
        $error = false;
        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'::'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
        return $returnType;
    }
}
