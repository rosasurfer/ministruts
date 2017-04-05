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


class DAO_Refresh_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension {


    const CLASS_NAME  = DAO::class;
    const METHOD_NAME = 'refresh';


    /**
     * Resolve the return type of an instance call to DAO->refresh().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if (sizeOf($methodCall->args)) {
            $arg = $methodCall->args[0]->value;
            if ($arg instanceof Variable) {
                $var = $arg->name;
                if ($var == 'this') {
                    $returnClass = $this->getScopeName($scope);
                    $returnType  = $this->createObjectType($returnClass);
                } //else $error = _true(echoPre('cannot resolve variable "'.$var.'"'));
            } else       $error = _true(echoPre('cannot resolve argument: '.get_class($arg)));
        }

        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
        return $returnType;
    }
}
