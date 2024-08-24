<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use rosasurfer\ministruts\db\orm\DAO;


/**
 *
 */
class DaoGetReturnType extends Extension implements DynamicMethodReturnTypeExtension {


    /**
     * Gibt den Namen der Klasse zurück, für die diese Extension gebaut wurde.
     *
     *
     * Return the name of the class containing the method with dynamic return types.
     *
     * @return string
     */
    public function getClass(): string {
        return DAO::class;
    }


    /**
     * Whether the passed method is supported by this extension.
     *
     * @param  MethodReflection $method
     *
     * @return bool
     */
    public function isMethodSupported(MethodReflection $method): bool {
        return ($method->getName() == 'get');
    }


    /**
     * Resolve the dynamic return type of the passed method.
     *
     * @param  MethodReflection $method
     * @param  MethodCall       $call
     * @param  Scope            $scope
     *
     * @return Type
     */
    public function getTypeFromMethodCall(MethodReflection $method, MethodCall $call, Scope $scope): Type {
        $originalType = $this->getOriginalReturnType($method, $call, $scope);

        //$this->log(__METHOD__.'()  method='.$method->getName().'  origReturnType='.$originalType->describe(VerbosityLevel::typeOnly()));
        return $originalType;
    }


    /**
     * Resolve the original return type of the passed method.
     *
     * @param  MethodReflection $method
     * @param  MethodCall       $call
     * @param  Scope            $scope
     *
     * @return Type
     */
    protected function getOriginalReturnType(MethodReflection $method, MethodCall $call, Scope $scope): Type {
        $args = $call->getArgs();
        $variants = $method->getVariants();
        $signature = ParametersAcceptorSelector::selectFromArgs($scope, $args, $variants);
        return $signature->getReturnType();
    }
}
