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

use function rosasurfer\simpleClassName;


/**
 * Overwrites the return type of many PHP core functions, taking into account the installed error handler.
 * If a function emits an internal PHP error on failure, the error handler ensures that an exception is thrown instead.
 * Removing the return type of the error condition (usually FALSE or NULL) from the resulting return type
 * simplifies PHPStan analysis considerably.
 */
class CoreFunctionReturnType implements DynamicFunctionReturnTypeExtension {

    /** @var array<string, Type> */
    protected $interceptedFunctions;

    /**
     * Constructor
     */
    public function __construct() {
        $bool = new BooleanType();
        $null = new NullType();

        $this->interceptedFunctions = [
            // function            type to remove       original return type
            // -------------------------------------------------------------------------
            'curl_init'            => $bool,            // resource|CurlHandle|false        @see  https://www.php.net/manual/en/function.curl-init.php
            'file'                 => $bool,            // array|false
            'file_get_contents'    => $bool,            // string|false                     @see  https://www.php.net/manual/en/function.file-get-contents.php
            'filemtime'            => $bool,            // int|false                        @see  https://www.php.net/manual/en/function.filemtime.php
            'fopen'                => $bool,            // resource|false
            'getcwd'               => $bool,            // string|false
            'ini_get_all'          => $bool,            // array|false                      @see  https://www.php.net/manual/en/function.ini-get-all.php
            'ob_get_clean'         => $bool,            // string|false
            'opendir'              => $bool,            // resource|false
            'pg_escape_identifier' => $bool,            // string (1)                       @see  https://www.php.net/manual/en/function.pg-escape-identifier.php
            'pg_escape_literal'    => $bool,            // string (1)                       @see  https://www.php.net/manual/en/function.pg-escape-literal.php
            'preg_replace'         => $null,            // string|array|null (2)
            'preg_split'           => $bool,            // array|false
            'proc_open'            => $bool,            // resource|false                   @see  https://www.php.net/manual/en/function.proc-open.php
            'session_id'           => $bool,            // string|false                     @see  https://www.php.net/manual/en/function.session-id.php
            'session_name'         => $bool,            // string|false                     @see  https://www.php.net/manual/en/function.session-name.php
            'shell_exec'           => $bool,            // string|false|null                @see  https://www.php.net/manual/en/function.shell-exec.php
            'stream_get_contents'  => $bool,            // string|false                     @see  https://www.php.net/manual/en/function.stream-get-contents.php

            // (1) either PHPStan or the PHP documentation are wrong
            // (2) the extension is not called by PHPStan
        ];
    }

    /**
     *
     */
    public function isFunctionSupported(FunctionReflection $function): bool {
        $name = $function->getName();
        return isset($this->interceptedFunctions[$name]);
    }

    /**
     *
     */
    public function getTypeFromFunctionCall(FunctionReflection $function, FuncCall $call, Scope $scope): Type {
        $name = $function->getName();

        if (\in_array($name, ['preg_replace'])) {
            // throw an exception to get notified when PHPStan bugs are fixed
            throw new \Exception("pewa: PHPStan bugfix -> core function $name is now passed to the ".simpleClassName(__CLASS__).' extension');
        }

        $originalType = $this->getOriginalReturnType($function, $call, $scope);
        $typeToRemove = $this->interceptedFunctions[$name] ?? null;

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
