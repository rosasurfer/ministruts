<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;


/**
 * Overwrites the return type of many PHP core functions, taking into account the installed error handler.
 * The error handler ensures that functions always throw exceptions instead of returning error values.
 * Removing the type of the error value from the resulting return type simplifies function signatures and
 * PHPStan analysis considerably.
 */
class CoreFunctionReturnType implements DynamicFunctionReturnTypeExtension {

    /**
     *
     */
    public function isFunctionSupported(FunctionReflection $function): bool {
        $supportedFunctions = ['realpath'];
        return in_array($function->getName(), $supportedFunctions, true);
    }

    /**
     *
     */
    public function getTypeFromFunctionCall(FunctionReflection $function, FuncCall $call, Scope $scope): Type {
        switch ($function->getName()) {
            case 'realpath': return new StringType();                   // string|false => string|throws
        }
        return $this->getOriginalReturnType($function, $call, $scope);
    }

    /**
     *
     */
    protected function getOriginalReturnType(FunctionReflection $function, FuncCall $call, Scope $scope): Type {
        /** @var Arg[] $args */
        $args = $call->args;
        $variants = $function->getVariants();

        // select the matching function signature
        $signature = ParametersAcceptorSelector::selectFromArgs($scope, $args, $variants);

        return $signature->getReturnType();
    }
}
