<?php declare(strict_types=1);

namespace rosasurfer\core\phpstan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\BinaryOp\Concat;

use PhpParser\Node\Scalar\String_;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use rosasurfer\core\Object;
use rosasurfer\core\Singleton;

use function rosasurfer\echoPre;
use rosasurfer\db\orm\DAO;


class SingletonGetInstanceReturnType extends Object implements DynamicStaticMethodReturnTypeExtension {

    const CLASS_NAME  = Singleton::class;
    const METHOD_NAME = 'getInstance';


    /**
     * @return string
     */
    public static function getClass() : string {
        //echoPre(__METHOD__.'()');
        return self::CLASS_NAME;
    }


    /**
     * @return bool
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection) : bool {
        //echoPre(__METHOD__.'()  '.$methodReflection->getName());
        return $methodReflection->getName() === self::METHOD_NAME;
    }


    /**
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        if (count($methodCall->args) === 0) {
            return $methodReflection->getReturnType();
        }
        $arg = $methodCall->args[0]->value;

        if ($arg instanceof String_) {
            $class = $this->fixClass($arg->value);
            return new ObjectType(...[$class, false]);              // compatible for 0.6.x and master
        }

        if ($arg instanceof ClassConstFetch) {
            if (($class = $this->classConstFetchToStr($arg, $scope)) !== null) {
                $class = $this->fixClass($class);
                return new ObjectType(...[$class, false]);          // compatible for 0.6.x and master
            }
        }

        if ($arg instanceof BinaryOp) {
            if (($class = $this->binaryOpToStr($arg, $scope)) !== null) {
                $class = $this->fixClass($class);
                return new ObjectType(...[$class, false]);          // compatible for 0.6.x and master
            }
        }

        if ($arg instanceof Variable) {
            //echoPre('cannot resolve return type of: '.self::CLASS_NAME.'::'.self::METHOD_NAME.'($'.$arg->name.')  in: '.$scope->getClass());
        }
        else {
            //echoPre('cannot resolve return type of: '.self::CLASS_NAME.'::'.self::METHOD_NAME.'('.get_class($arg).')  in: '.$scope->getClass());
        }
        return $methodReflection->getReturnType();
    }


    /**
     * @return string|null
     */
    private function binaryOpToStr(BinaryOp $op, Scope $scope) {
        $left  = $this->exprToStr($op->left,  $scope);
        $right = $this->exprToStr($op->right, $scope);

        if (is_null($left) || is_null($right))
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
            if ($class == 'static') {
                if (method_exists($scope, $method='getClass'))           return $scope->$method();             // 0.6.x
                if (method_exists($scope, $method='getClassReflection')) return $scope->$method()->getName();  // master
            }
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
            if (($expr = $this->classConstFetchToStr($expr, $scope)) !== null)
                return $expr;
        return null;
    }


    /**
     * @return string
     */
    private function fixClass($class) {
        if ($class == 'rosasurfer\db\orm\PersistableObjectDAO') {
            return DAO::class;
        }
        return $class;
    }
}
