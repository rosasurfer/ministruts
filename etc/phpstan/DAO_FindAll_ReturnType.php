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
use PHPStan\Type\StaticType;


/**
 *
 */
class DAO_FindAll_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension {


    /** @var string */
    protected static $className = DAO::class;

    /** @var string[] */
    protected static $methodNames = ['findAll', 'getAll'];


    /**
     * Resolve the return type of instance calls to {@link DAO::findAll()} and {@link DAO::getAll()}.
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        if ($returnType instanceof ArrayType) {
            if ($methodCall->var instanceof StaticCall) {
                if ($methodCall->var->class instanceof Name) {
                    /** @var Name $name */
                    $name = $methodCall->var->class;
                    if ($name->isFullyQualified()) {
                        $returnType = new ArrayType(new ObjectType((string)$name));
                    } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class).' (not fully qualified)'));
                } else     $error = true(echoPre('(2) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class)));
            }
            else if ($methodCall->var instanceof Variable) {
                $scopedType = $scope->getType($methodCall->var);
                $class = $scopedType instanceof StaticType ? $scopedType->getClass() : $scopedType->describe();
                if ($class != static::$className) {                                         // skip self-referencing calls
                    if (strEndsWith($class, 'DAO')) {
                        $returnType = new ArrayType(new ObjectType(strLeft($class, -3)));
                    } else $error = true(echoPre('(3) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: scoped type = '.get_class($scopedType).' => '.$scopedType->describe()));
                }
            } else         $error = true(echoPre('(4) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var) = '.get_class($methodCall->var)));
        } else             $error = true(echoPre('(5) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)':''));
        return $returnType;
    }


    /**
     * Resolve the return type of static calls to {@link DAO::findAll()} and {@link DAO::getAll()}. The only pseudo-static
     * invocation are parent::findAll|getAll().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        if ($returnType instanceof ArrayType) {
            if ($methodCall->class instanceof Name) {
                $name = $methodCall->class;
                if ((string)$name == 'parent') {
                    $scopeName = $scope->getClassReflection()->getName();
                    if (strEndsWith($scopeName, 'DAO')) {
                        $returnType = new ArrayType(new ObjectType(strLeft($scopeName, -3)));
                    } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected callee class name: '.$scopeName));
                } else     $error = true(echoPre('(2) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: name "'.$name.'"'));
            } else         $error = true(echoPre('(3) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: class($methodCall->class) = '.get_class($methodCall->class)));
        } else             $error = true(echoPre('(4) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'::'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)':''));
        return $returnType;
    }
}
