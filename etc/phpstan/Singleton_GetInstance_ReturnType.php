<?php declare(strict_types=1);

namespace rosasurfer\core\phpstan;

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
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use rosasurfer\core\Singleton;
use rosasurfer\db\orm\DAO;
use rosasurfer\db\orm\PersistableObject;
use rosasurfer\phpstan\DynamicReturnType;

use function rosasurfer\_true;
use function rosasurfer\echoPre;


class Singleton_GetInstance_ReturnType extends DynamicReturnType implements DynamicMethodReturnTypeExtension,
                                                                            DynamicStaticMethodReturnTypeExtension {

    const CLASS_NAME  = Singleton::class;
    const METHOD_NAME = 'getInstance';


    /**
     * Resolve the return type of an instance call to Singleton->getInstance().
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;
        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
        return $returnType;
    }


    /**
     * Resolve the return type of a static call to Singleton::getInstance().
     *
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        $returnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $returnType->getClass();
        $error = false;

        if (sizeOf($methodCall->args)) {
            $arg = $methodCall->args[0]->value;

            if ($arg instanceof String_) {
                $returnClass = $arg->value;
                $returnType  = $this->createObjectType($returnClass);
            }
            else if ($arg instanceof ClassConstFetch) {
                if ($class = $this->classConstFetchToStr($arg, $scope)) {
                    $returnClass = $class;
                    $returnType  = $this->createObjectType($returnClass);
                }
                else $error = _true(echoPre('cannot resolve class constant "'.$arg->class.'::'.$arg->name.'"'));
            }
            else if ($arg instanceof BinaryOp) {
                if ($class = $this->binaryOpToStr($arg, $scope)) {
                    if ($class == PersistableObject::class.'DAO') $class = DAO::class;
                    $returnClass = $class;
                    $returnType  = $this->createObjectType($returnClass);
                }
                else $error = _true(echoPre('cannot convert binary operator argument to string: '.get_class($arg)));
            }
            else if ($arg instanceof Variable) {
                $error = _true(echoPre('cannot resolve variable "'.$arg->name.'"'));
            }
            else {
                $error = _true(echoPre('cannot resolve argument: '.get_class($arg)));
            }
        }

        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'::'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
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
            if ($class == 'self'  ) return $this->getScopeName($scope);
            if ($class == 'static') return $this->getScopeName($scope);
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
