<?php declare(strict_types=1);

namespace rosasurfer\phpstan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use rosasurfer\core\Singleton;

use function rosasurfer\echoPre;
use function rosasurfer\simpleClassName;
use function rosasurfer\true;


/**
 *
 */
class Singleton_GetInstance_ReturnType extends DynamicReturnType implements DynamicStaticMethodReturnTypeExtension {

    /** @var string */
    protected static $className = Singleton::class;

    /** @var string[] */
    protected static $methodNames = ['getInstance'];


    /**
     * Resolve the return type of static calls to {@link Singleton::getInstance()}.
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        $returnType = $methodReflection->getReturnType();
        $origReturnDescribe = $returnType->describe();
        $error = false;

        if ($returnType instanceof ObjectType) {
            if ($args = $methodCall->args) {
                $arg = $args[0]->value;

                // check for a constant class name parameter
                if ($arg instanceof String_) {                                      // constant
                    $returnType = new ObjectType($arg->value);
                }
                else if ($arg instanceof ClassConstFetch) {                         // constant
                    if ($class = $this->classConstFetchToStr($arg, $scope)) {
                        $returnType = new ObjectType($class);
                    } else $error = true(echoPre('(1) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve class constant "'.$arg->class.'::'.$arg->name.'"'));
                }
                else if ($arg instanceof BinaryOp) {                                // constant
                    if ($class = $this->binaryOpToStr($arg, $scope)) {
                        $returnType = new ObjectType($class);
                    } else $error = true(echoPre('(2) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot convert binary operator argument to string: '.get_class($arg)));
                }
                else if ($arg instanceof Variable) {                                // variables can only be resolved at runtime:
                } else $error = true(echoPre('(3) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot resolve argument type: '.get_class($arg)));
            } else     $error = true(echoPre('(4) '.simpleClassName(static::$className).'::'.$methodCall->name.'() cannot find class name argument: sizeof($args) = 0'));
        } else         $error = true(echoPre('(5) '.simpleClassName(static::$className).'::'.$methodCall->name.'() encountered unexpected return type: '.get_class($returnType).' => '.$returnType->describe()));

        $returnDescribe = $returnType->describe();

        if (0 || $error) echoPre('call of: '.simpleClassName(static::$className).'::'.$methodCall->name.'()  in: '.$this->getScopeDescription($scope).'  shall return: '.$returnDescribe.($returnDescribe==$origReturnDescribe ? ' (pass through)' : ' (was '.$origReturnDescribe.')'));
        return $returnType;
    }


    /**
     * @return string|null
     */
    private function binaryOpToStr(BinaryOp $op, Scope $scope) {
        $left  = $this->exprToStr($op->left,  $scope);
        $right = $this->exprToStr($op->right, $scope);

        if (!$left || !$right)
            return null;
        if ($op instanceof Concat)
            return $left.$right;
        return null;
    }


    /**
     * @return string|null
     */
    private function classConstFetchToStr(ClassConstFetch $fetch, Scope $scope) {
        $class = (string) $fetch->class;
        $const = $fetch->name;

        if ($const == 'class') {
            if ($class == 'self'  ) return $scope->getClassReflection()->getName();
            if ($class == 'static') return $scope->getClassReflection()->getName();
            return $class;
        }
        return null;
    }


    /**
     * @return string|null
     */
    private function exprToStr(Expr $expr, Scope $scope) {
        if ($expr instanceof String_)
            return $expr->value;
        if ($expr instanceof ClassConstFetch)
            return $this->classConstFetchToStr($expr, $scope);
        return null;
    }
}
