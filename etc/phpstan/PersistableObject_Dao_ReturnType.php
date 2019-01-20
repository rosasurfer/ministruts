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
use PHPStan\Type\StaticType;
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
     * Resolve the return type of instance calls to {@link PersistableObject::dao()}.
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        if ($returnType instanceof ObjectType) {
            if ($methodCall->var instanceof Variable) {
                $scopedType = $scope->getType($methodCall->var);
                $class = $scopedType instanceof StaticType ? $scopedType->getClass() : $scopedType->describe();
                if ($class != static::$className)                                           // skip self-referencing calls
                    $returnType = new ObjectType($class.'DAO');
            } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var) = '.get_class($methodCall->var)));
        } else     $error = true(echoPre('(2) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)':''));
        return $returnType;
    }


    /**
     * Resolve the return type of static calls to {@link PersistableObject::dao()}.
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        if ($returnType instanceof ObjectType) {
            if ($methodCall->class instanceof Name) {
                $name = $methodCall->class;
                if ($name->isFullyQualified()) {
                    $returnType = new ObjectType($name.'DAO');
                }
                else if ((string)$name == 'self') {
                    $class = $scope->getClassReflection()->getName();
                    if ($class != static::$className)                                       // skip self-referencing calls
                        $returnType = new ObjectType($class.'DAO');
                } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: name "'.$name.'"'));
            } else     $error = true(echoPre('(2) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: class($methodCall->class) = '.get_class($methodCall->class)));
        } else         $error = true(echoPre('(3) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'::'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)':''));
        return $returnType;
    }
}
