<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Expr\StaticCall;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

use rosasurfer\core\Object;
use rosasurfer\db\orm\PersistableObject;

use function rosasurfer\echoPre;
use PhpParser\Node\Expr\MethodCall;


class PersistableObject_Dao_ReturnType extends Object implements DynamicMethodReturnTypeExtension,
                                                                 DynamicStaticMethodReturnTypeExtension {

    const CLASS_NAME  = PersistableObject::class;
    const METHOD_NAME = 'dao';


    /**
     * @return string
     */
    public static function getClass() : string {
        return self::CLASS_NAME;
    }


    /**
     * @return bool
     */
    public function isMethodSupported(MethodReflection $methodReflection) : bool {
        return $methodReflection->getName() === self::METHOD_NAME;
    }


	/**
     * @return bool
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection) : bool {
        return $methodReflection->getName() === self::METHOD_NAME;
    }


    /**
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        /*
        if      (method_exists($scope, $method='getClassReflection')) $callee = $scope->$method()->getName();   // master
        else if (method_exists($scope, $method='getClass'          )) $callee = $scope->$method();              // 0.6.x
        else                                                          $callee = '(unknown callee)';
        echoPre($callee.': '.baseName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$methodReflection->getReturnType()->getClass());
        */
        return $methodReflection->getReturnType();
    }


    /**
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        /*
        if      (method_exists($scope, $method='getClassReflection')) $callee = $scope->$method()->getName();   // master
        else if (method_exists($scope, $method='getClass'          )) $callee = $scope->$method();              // 0.6.x
        else                                                          $callee = '(unknown callee)';
        echoPre($callee.': '.baseName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$methodReflection->getReturnType()->getClass());
        */
        return $methodReflection->getReturnType();
    }
}
