<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PhpParser\Node\Name;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use rosasurfer\db\orm\DAO;

use function rosasurfer\echoPre;
use function rosasurfer\simpleClassName;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\true;


/**
 *
 */
class DAO_FindAll_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension {


    /** @var string */
    protected static $className = DAO::class;

    /** @var string[] */
    protected static $methodNames = ['findAll', 'getAll'];


    /**
     * Resolve the return type of an instance call to DAO->findAll().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        /** @var ArrayType $returnType */
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getItemType()->getClass().'[]';
        $error = false;

        if ($methodCall->var instanceof StaticCall) {
            if ($methodCall->var->class instanceof Name) {
                /** @var Name $name */
                $name = $methodCall->var->class;
                if ($name->isFullyQualified()) {
                    $returnType  = new ArrayType(new ObjectType((string)$name));
                    $returnClass = $returnType->getItemType()->getClass().'[]';
                }
                else $error = true(echoPre('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class).' (not fully qualified)'));
            } else   $error = true(echoPre('(2) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class)));
        }
        else if ($methodCall->var instanceof Variable) {
            $daoClass = $scope->getType($methodCall->var)->getClass();
            if ($daoClass != static::$className) {                      // skip self-referencing DAO calls
                if (strEndsWith($daoClass, 'DAO')) {
                    $returnType  = new ArrayType(new ObjectType(strLeft($daoClass, -3)));
                    $returnClass = $returnType->getItemType()->getClass().'[]';
                }
            }
        } else $error = true(echoPre('(3) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var) = '.get_class($methodCall->var)));

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }


    /**
     * Resolve the return type of a static call to DAO::findAll(). The only pseudo-static invocation is parent::findAll().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type {
        /** @var ArrayType $returnType */
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getItemType()->getClass().'[]';
        $error = false;

        if ($methodCall->class instanceof Name) {
            $name = $methodCall->class;
            if ((string)$name == 'parent') {
                $scopeName = $scope->getClassReflection()->getName();
                if ($scopeName != static::$className) {                   // skip self-referencing DAO calls
                    $returnClass = strLeft($scopeName, -3);
                    $returnType  = new ArrayType(new ObjectType($returnClass));
                }
            }
            else $error = true(echoPre('(1) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: name "'.$name.'"'));
        } else   $error = true(echoPre('(2) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: class($methodCall->class) = '.get_class($methodCall->class)));

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'::'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }
}
