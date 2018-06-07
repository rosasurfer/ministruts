<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use rosasurfer\db\orm\DAO;
use rosasurfer\phpstan\DynamicReturnType;

use function rosasurfer\echoPre;
use function rosasurfer\simpleClassName;
use function rosasurfer\strLeft;
use function rosasurfer\true;


/**
 *
 */
class DAO_Find_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension {


    const CLASS_NAME  = DAO::class;
    const METHOD_NAME = 'find';


    /**
     * Resolve the return type of an instance call to DAO->find().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if ($methodCall->var instanceof Variable) {
            $var = $methodCall->var;
            if (is_string($var->name)) {
                if ($var->name == 'this') {
                    $daoClass = $this->getScopeName($scope);
                    if ($daoClass != self::CLASS_NAME) {
                        $returnClass = strLeft($daoClass, -3);
                        $returnType  = new ObjectType($returnClass);
                    }
                } //else $error = true(echoPre(simpleClassName(self::CLASS_NAME).'->'.self::METHOD_NAME.'(1) cannot resolve callee of instance method call: $'.$var->name.'->'.self::METHOD_NAME.'()'));
            } else       $error = true(echoPre(simpleClassName(self::CLASS_NAME).'->'.self::METHOD_NAME.'(2) cannot resolve callee of instance method call: class($var->name)='.get_class($var->name)));
        } else           $error = true(echoPre(simpleClassName(self::CLASS_NAME).'->'.self::METHOD_NAME.'(3) cannot resolve callee of instance method call: class($methodCall->var)='.get_class($methodCall->var)));

        if (0 || $error) echoPre($this->getScopeName($scope).': '.simpleClassName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        return $returnType;
    }
}
