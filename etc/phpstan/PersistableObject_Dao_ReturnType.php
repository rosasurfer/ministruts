<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Name;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use rosasurfer\core\Object;
use rosasurfer\db\orm\PersistableObject;

use function rosasurfer\_true;
use function rosasurfer\echoPre;


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
        return $methodReflection->getName() == self::METHOD_NAME;
    }


	/**
     * @return bool
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection) : bool {
        return $methodReflection->getName() == self::METHOD_NAME;
    }


    /**
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : Type {
        $returnType  = $origReturnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $origReturnType->getClass();

        $error = false;

        if ($methodCall->var instanceof Variable) {
            $var = $methodCall->var;
            if (is_string($var->name)) {
                if ($var->name == 'this') {
                    $scopeName = $this->getScopeName($scope);
                    if ($scopeName != self::CLASS_NAME) {
                        $returnClass = $scopeName.'DAO';
                        $returnType  = $this->createObjectType($returnClass);
                    }
                } //else $error = _true(echoPre('cannot resolve callee of instance method call: variable "'.$var->name.'"'));
            } else       $error = _true(echoPre('cannot resolve callee of instance method call: class($var->name)='.get_class($var->name)));
        } else           $error = _true(echoPre('cannot resolve callee of instance method call: class($methodCall->var)='.get_class($methodCall->var)));

        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'->'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
        return $returnType;
    }


    /**
     * @return Type
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope) : Type {
        $returnType  = $origReturnType  = $methodReflection->getReturnType();
        $returnClass = $origReturnClass = $origReturnType->getClass();

        $error = false;

        if ($methodCall->class instanceof Name) {
            $name = $methodCall->class;
            if ($name->isFullyQualified()) {
                $returnClass = $name.'DAO';
                $returnType  = $this->createObjectType($returnClass);
            }
            else if ((string)$name == 'self') {
                $scopeName = $this->getScopeName($scope);
                if ($scopeName != self::CLASS_NAME) {
                    $returnClass = $scopeName.'DAO';
                    $returnType  = $this->createObjectType($returnClass);
                }
            } else $error = _true(echoPre('cannot resolve callee of static method call: name "'.$name.'"'));
        } else     $error = _true(echoPre('cannot resolve callee of static method call: class($methodCall->class)='.get_class($methodCall->class)));

        if (0 || $error)   echoPre($this->getScopeName($scope).': '.baseName(self::CLASS_NAME).'::'.self::METHOD_NAME.'() => '.$returnClass.($returnClass==$origReturnClass ? ' (pass through)':''));
        if (0 && $error) { echoPre($methodCall); exit(); }
        return $returnType;
    }


    /**
     * Return the name of the calling scope.
     *
     * @return string - class name or "{main}" for calls from outside a class; "(unknown)" in case of errors
     */
    private function getScopeName(Scope $scope) : string {
        if (method_exists($scope, $method='getClass')) {                    // branch 0.6.x
            $name = $scope->$method() ?: '{main}';
        }
        else if (method_exists($scope, $method='getClassReflection')) {     // branch master
            $reflection = $scope->$method();
            $name = $reflection ? $reflection->getName() : '{main}';
        }
        else {
            $name = '(unknown)';
        }
        return $name;
    }


    /**
     * @return ObjectType
     */
    private function createObjectType($class) : ObjectType {
        return new ObjectType(...[$class, false]);                          // branches 0.6.x and master
    }
}
