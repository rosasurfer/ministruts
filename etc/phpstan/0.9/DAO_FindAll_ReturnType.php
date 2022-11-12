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

use function rosasurfer\echof;
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
                        if ((string)$name != static::$className)                            // skip self-referencing calls
                            $returnType = $this->copyArrayType($returnType, new ObjectType((string)$name));
                    } else $error = true(echof('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class).' (not fully qualified)'));
                } else     $error = true(echof('(2) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class)));
            }
            else if ($methodCall->var instanceof Variable) {
                $scopedType = $scope->getType($methodCall->var);
                $class = $this->findSubclass($scopedType, static::$className);
                if ($class) {
                    if (strEndsWith($class, 'DAO')) {
                        $returnType = $this->copyArrayType($returnType, new ObjectType(strLeft($class, -3)));
                    }
                    else $error = true(echof('(3) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: scoped type = '.get_class($scopedType).' => '.$scopedType->describe()));
                }
            } else       $error = true(echof('(4) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var) = '.get_class($methodCall->var)));
        } else           $error = true(echof('(5) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echof('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  in: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)' : ' (was '.$origReturnDescribe.')'));
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
                        $returnType = $this->copyArrayType($returnType, new ObjectType(strLeft($scopeName, -3)));
                    } else $error = true(echof('(1) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected callee class name: '.$scopeName));
                } else     $error = true(echof('(2) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: name "'.$name.'"'));
            } else         $error = true(echof('(3) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: class($methodCall->class) = '.get_class($methodCall->class)));
        } else             $error = true(echof('(4) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echof('call of: '.simpleClassName(static::$className).'::'.$methodCall->name.'()  in: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)' : ' (was '.$origReturnDescribe.')'));
        return $returnType;
    }


    /**
     * Copy an existing {@link ArrayType} and set a new item type.
     *
     * @return ArrayType
     */
    protected function copyArrayType(ArrayType $arrayType, ObjectType $itemType) : ArrayType {
        return new ArrayType(
            $arrayType->getIterableKeyType(),
            $itemType,
            $arrayType->isItemTypeInferredFromLiteralArray(),
            $arrayType->isCallable()
        );
    }
}
