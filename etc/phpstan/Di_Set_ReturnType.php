<?php
declare(strict_types=1);

namespace rosasurfer\phpstan;

use PhpParser\Node\Name;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use PHPStan\Type\Type;

use rosasurfer\di\Di;

use function rosasurfer\echoPre;
use function rosasurfer\simpleClassName;


/**
 *
 */
class Di_Set_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension {


    /** @var string */
    protected static $className = Di::class;

    /** @var string[] */
    protected static $methodNames = ['set'];


    /**
     * Resolve the return type of instance calls to {@link Di::set()}.
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        if ($returnType instanceof UnionType && $origReturnDescribe=='object|string') {
            if (($args = $methodCall->args) && sizeof($args) > 1) {
                if (($definition = $args[1]->value) instanceof New_) {
                    if (($type = $definition->class) instanceof FullyQualified) {
                        $returnType = new ObjectType($type->toString());
                    } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered non FullyQualified service definition: '.get_class($class)));
                } else     $error = true(echoPre('(2) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered unexpected service definition type: '.get_class($definition)));
            }
        } else             $error = true(echoPre('(3) '.simpleClassName(static::$className).'->'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'->'.$methodCall->name.'()  in: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)' : ' (was '.$origReturnDescribe.')'));
        return $returnType;
    }
}
