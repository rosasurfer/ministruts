<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Expr\StaticCall;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

use rosasurfer\core\Object;
use rosasurfer\db\orm\PersistableObject;

use function rosasurfer\echoPre;


class PersistableObjectDaoReturnType extends Object implements DynamicStaticMethodReturnTypeExtension {

    const CLASS_NAME  = PersistableObject::class;
    const METHOD_NAME = 'dao';


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
        return $methodReflection->getReturnType();
    }
}
