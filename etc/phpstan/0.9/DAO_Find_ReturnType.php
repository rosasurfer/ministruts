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
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

use rosasurfer\db\orm\DAO;
use rosasurfer\db\orm\PersistableObject;

use function rosasurfer\echof;
use function rosasurfer\simpleClassName;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\true;


/**
 *
 */
class DAO_Find_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension {


    /** @var string */
    protected static $className = DAO::class;

    /** @var string[] */
    protected static $methodNames = ['find', 'get'];


    /**
     * Resolve the return type of instance calls to {@link DAO::find()} and {@link DAO::get()}.
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        $resolver = function(Type $type) use ($methodCall, $scope, &$error) : Type {
            if ($type instanceof ObjectType) {
                if ($type->describe() == PersistableObject::class) {
                    if ($methodCall->var instanceof StaticCall) {
                        if ($methodCall->var->class instanceof Name) {
                            /** @var Name $name */
                            $name = $methodCall->var->class;
                            if ($name->isFullyQualified()) {
                                if ((string)$name != static::$className)                    // skip self-referencing calls
                                    $type = new ObjectType((string)$name);
                            } else $error = true(echof('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class).' (not fully qualified)'));
                        } else     $error = true(echof('(2) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class)));
                    }
                    else if ($methodCall->var instanceof Variable) {
                        $scopedType = $scope->getType($methodCall->var);
                        if ($class = $this->findSubclass($scopedType, static::$className)) {
                            if (strEndsWith($class, 'DAO')) {
                                $type = new ObjectType(strLeft($class, -3));
                            } else $error = true(echof('(3) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered unexpected callee class name: '.$class));
                        }
                    } else         $error = true(echof('(4) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var) = '.get_class($methodCall->var)));
                }
            }
            else if (!$type instanceof NullType) $error = true(echof('(5) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered unexpected return type: '.get_class($type).' => '.$type->describe()));
            return $type;
        };

        $returnType = $this->resolveReturnType($returnType, $resolver);
        $returnDescribe = $returnType->describe();

        if (0 || $error) echof('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  in: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)' : ' (was '.$origReturnDescribe.')'));
        return $returnType;
    }


    /**
     * Resolve the return type of static calls to {@link DAO::find()} and {@link DAO::get()}. The only pseudo-static
     * invocations are parent::find|get().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        $resolver = function(Type $type) use ($methodCall, $scope, &$error) : Type {
            if ($type instanceof ObjectType) {
                if ($type->describe() == PersistableObject::class) {
                    if ($methodCall->class instanceof Name) {
                        $name = $methodCall->class;
                        if ((string)$name == 'parent') {
                            $scopeName = $scope->getClassReflection()->getName();
                            if (strEndsWith($scopeName, 'DAO')) {
                                $type = new ObjectType(strLeft($scopeName, -3));
                            } else $error = true(echof('(1) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected callee class name: '.$scopeName));
                        } else     $error = true(echof('(2) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: name "'.$name.'"'));
                    } else         $error = true(echof('(3) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve callee of static method call: class($methodCall->class) = '.get_class($methodCall->class)));
                }
            }
            else if (!$type instanceof NullType) $error = true(echof('(4) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected return type: '.get_class($type).' => '.$type->describe()));
            return $type;
        };

        $returnType = $this->resolveReturnType($returnType, $resolver);
        $returnDescribe = $returnType->describe();

        if (0 || $error) echof('call of: '.simpleClassName(static::$className).'::'.$methodCall->name.'()  in: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)' : ' (was '.$origReturnDescribe.')'));
        return $returnType;
    }


    /**
     * Resolve a return type using a resolver function.
     *
     * @return Type
     */
    protected function resolveReturnType(Type $type, \Closure $resolver) : Type {
        if ($type instanceof UnionType) {
            $old = $type->getTypes();
            $new = [];
            foreach ($old as $subtype)
                $new[] = $resolver($subtype);
            return ($old===$new) ? $type : new UnionType($new);
        }
        return $resolver($type);
    }
}
