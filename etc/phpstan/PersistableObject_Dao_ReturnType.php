<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PhpParser\Node\Name;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use rosasurfer\db\orm\PersistableObject;

use function rosasurfer\echoPre;
use function rosasurfer\simpleClassName;
use function rosasurfer\true;


/**
 *
 */
class PersistableObject_Dao_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension,
                                                                            DynamicStaticMethodReturnTypeExtension {

    /** @var string */
    protected static $className = PersistableObject::class;

    /** @var string[] */
    protected static $methodNames = ['dao'];


    /**
     * Resolve the return type of an instance call to PersistableObject->dao().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if ($methodCall->var instanceof Variable) {
            $entityClass = $scope->getType($methodCall->var)->getClass();
            if ($entityClass != static::$className) {
                $returnClass = $entityClass.'DAO';
                $returnType  = new ObjectType($returnClass);
            }
        } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var) = '.get_class($methodCall->var)));

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }


    /**
     * Resolve the return type of a static call to PersistableObject::dao().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if ($methodCall->class instanceof Name) {
            $name = $methodCall->class;
            if ($name->isFullyQualified()) {
                $returnClass = $name.'DAO';
                $returnType  = new ObjectType($returnClass);
            }
            else if ((string)$name == 'self') {
                $scopeName = $scope->getClassReflection()->getName();
                if ($scopeName != static::$className) {
                    $returnClass = $scopeName.'DAO';
                    $returnType  = new ObjectType($returnClass);
                }
            } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: name "'.$name.'"'));
        } else     $error = true(echoPre('(2) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: class($methodCall->class) = '.get_class($methodCall->class)));

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'::'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }
}
