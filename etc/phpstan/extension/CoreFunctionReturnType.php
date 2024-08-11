<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan\extension;

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

    /** @var array<string, Type> */
    protected array $supportedFunctions;


    /**
     * Constructor
     */
    public function __construct() {
        $this->supportedFunctions = [
            'file_get_contents' => new StringType(),        // string|false => string|throws
            'realpath'          => new StringType(),        // string|false => string|throws
        ];
    }

    /**
     *
     */
    public function isFunctionSupported(FunctionReflection $function): bool {
        $name = $function->getName();
        return isset($this->supportedFunctions[$name]);
    }

    /**
     *
     */
    public function getTypeFromFunctionCall(FunctionReflection $function, FuncCall $call, Scope $scope): Type {
        $name = $function->getName();
        return $this->supportedFunctions[$name] ?? $this->getOriginalReturnType($function, $call, $scope);
    }

    /**
     *
     */
    protected function getOriginalReturnType(FunctionReflection $function, FuncCall $call, Scope $scope): Type {
        /** @var Arg[] $args */
        $args = $call->args;
        $variants = $function->getVariants();

        $signature = ParametersAcceptorSelector::selectFromArgs($scope, $args, $variants);
        return $signature->getReturnType();
    }
}
