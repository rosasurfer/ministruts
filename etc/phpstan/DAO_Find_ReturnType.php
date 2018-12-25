<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PhpParser\Node\Name;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
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
class DAO_Find_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension {


    /** @var string */
    protected static $className = DAO::class;

    /** @var string[] */
    protected static $methodNames = ['find', 'get'];


    /**
     * Resolve the return type of an instance call to DAO->find().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if ($methodCall->var instanceof StaticCall) {
            if ($methodCall->var->class instanceof Name) {
                /** @var Name $name */
                $name = $methodCall->var->class;
                if ($name->isFullyQualified()) {
                    $returnClass = (string)$name;
                    $returnType  = new ObjectType($returnClass);
                }
                else $error = true(echoPre('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class).' (not fully qualified)'));
            } else   $error = true(echoPre('(2) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var->class) = '.get_class($methodCall->var->class)));
        }
        else if ($methodCall->var instanceof Variable) {
            $daoClass = $scope->getType($methodCall->var)->getClass();
            if ($daoClass != static::$className) {                      // skip self-referencing DAO calls
                if (strEndsWith($daoClass, 'DAO')) {
                    $returnClass = strLeft($daoClass, -3);
                    $returnType  = new ObjectType($returnClass);
                }
            }
        } else $error = true(echoPre('(3) '.simpleClassName(static::$className).'->'.$methodCall->name.'() cannot resolve callee of instance method call: class($methodCall->var) = '.get_class($methodCall->var)));

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  from: '.$this->getScopeDescription($scope).'  shall return: '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }
}
