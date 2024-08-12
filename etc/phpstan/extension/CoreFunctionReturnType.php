<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan\extension;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Type\NullType;

use function PHPStan\dumpType;


/**
 * Overwrites the return type of many PHP core functions, taking into account the installed error handler.
 * If a function emits an internal PHP error on failure, the error handler ensures that an exception is thrown instead.
 * Removing the return type of the error condition (usually FALSE or NULL) from the resulting return type
 * simplifies PHPStan analysis considerably.
 */
class CoreFunctionReturnType implements DynamicFunctionReturnTypeExtension {

    /** @var array<string, Type> */
    protected array $supportedFunctions;


    /**
     * Constructor
     */
    public function __construct() {
        $bool = new BooleanType();
        $null = new NullType();

        $this->supportedFunctions = [
            'file'         => $bool,        // array|false       => array
            'fopen'        => $bool,        // resource|false    => resource
            'getcwd'       => $bool,        // string|false      => string
            'ob_get_clean' => $bool,        // string|false      => string
            'preg_replace' => $null,        // string|array|null => string|array
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

        if ($name == 'preg_replace') {
            // it seems the extension is skipped for this fucntion
            \file_put_contents('phpstan-extension.log', __METHOD__.'()  '.$name.PHP_EOL, FILE_APPEND);
        }

        $originalType = $this->getOriginalReturnType($function, $call, $scope);
        $typeToRemove = $this->supportedFunctions[$name] ?? null;

        if ($typeToRemove) {
            $newReturnType = $originalType->tryRemove($typeToRemove);
            if ($newReturnType) {
                return $newReturnType;
            }
        }
        return $originalType;
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
