<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan;

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
use function rosasurfer\ministruts\normalizeEOL;

use const rosasurfer\ministruts\ERROR_LOG_DEFAULT;
use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\WINDOWS;


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
            // function    => return type to remove
            'file'         => $bool,                //: array|false
            'fopen'        => $bool,                //: resource|false
            'getcwd'       => $bool,                //: string|false
            'ob_get_clean' => $bool,                //: string|false
            'preg_replace' => $null,                //: string|array|null
            'preg_split'   => $bool,                //: array|false
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
            // it seems the extension is skipped for preg_replace()
            $this->log(__METHOD__.'()  '.$name);
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


    /**
     * Log to the system logger.
     */
    protected function log(string $message): void {
        // replace NUL bytes which mess up the logfile
        $message = str_replace(chr(0), '\0', $message);
        $message = normalizeEOL($message);
        if (WINDOWS) {
            $message = str_replace(NL, PHP_EOL, $message);
        }
        error_log($message, ERROR_LOG_DEFAULT);
    }
}
