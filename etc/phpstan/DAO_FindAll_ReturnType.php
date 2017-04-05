<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use rosasurfer\db\orm\DAO;
use rosasurfer\phpstan\DynamicReturnType;

use function rosasurfer\_true;
use function rosasurfer\echoPre;
use function rosasurfer\strLeft;
use PHPStan\Type\ArrayType;


class DAO_FindAll_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension {


    const CLASS_NAME  = DAO::class;
    const METHOD_NAME = 'findAll';


    /**
     * Resolve the return type of an instance call to DAO->findAll().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        /** @var ArrayType $returnType */
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getItemType()->getClass().'[]';
        $error = false;

        if ($methodCall->var instanceof Variable) {
            $var = $methodCall->var;
            if (is_string($var->name)) {
                if ($var->name == 'this') {
                    $daoName = $this->getScopeName($scope);
                    $class = strLeft($daoName, -3);
                    if ($class) {
                        $returnClass = $class;
                        $returnType  = $this->createArrayType($returnClass);
                        $returnClass = $returnType->getItemType()->getClass().'[]';
                    }
                } //else $error = _true(echoPre('cannot resolve callee of instance method call: $'.$var->name.'->'.self::METHOD_NAME.'()'));
            } else       $error = _true(echoPre('cannot resolve callee of instance method call: class($var->name)='.get_class($var->name)));
        } else           $error = _true(echoPre('cannot resolve callee of instance method call: class($methodCall->var)='.get_class($methodCall->var)));

        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
        return $returnType;
    }
}
