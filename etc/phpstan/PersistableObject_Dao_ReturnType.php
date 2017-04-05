<?php declare(strict_types=1);

namespace rosasurfer\db\orm\phpstan;

use PhpParser\Node\Name;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;

use rosasurfer\db\orm\PersistableObject;
use rosasurfer\phpstan\DynamicReturnType;

use function rosasurfer\_true;
use function rosasurfer\echoPre;


class PersistableObject_Dao_ReturnType extends DynamicReturnType {


    const CLASS_NAME  = PersistableObject::class;
    const METHOD_NAME = 'dao';


    /**
     * Resolve the return type of an instance call to PersistableObject->dao().
     *
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
     * Resolve the return type of a static call to PersistableObject::dao().
     *
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
}
