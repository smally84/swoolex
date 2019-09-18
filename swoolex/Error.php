<?php
// +----------------------------------------------------------------------
// | Swoolex - 错误处理
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex;

class Error
{
    private const ERROR = [
        1       => 'E_ERROR',
        2       => 'E_WARNING',
        4       => 'E_PARSE',
        8       => 'E_NOTICE',
        16      => 'E_CORE_ERROR',
        32      => 'E_CORE_WARNING',
        64      => 'E_COMPILE_ERROR',
        128     => 'E_COMPILE_WARNING',
        256     => 'E_USER_ERROR',
        512     => 'E_USER_WARNING',
        1024    => 'E_USER_NOTICE',
        2048    => 'E_STRICT',
        4096    => 'E_RECOVERABLE_ERROR',
        8192    => 'E_DEPRECATED',
        16384   => 'E_USER_DEPRECATED',
        30719   => 'E_ALL',
    ];
	/**
	 * 注册异常处理
     * swoole不支持 set_exception_handler
	 */
	public static function register(){
		error_reporting(E_ALL);
		set_error_handler([__CLASS__,'appError']);
		register_shutdown_function([__CLASS__,'appShutdown']);
	}
	/**
	 * 错误处理
 	 */
	static function appError(int $errno , string $errstr , string $errfile , int $errline)
    {
        echo date('Y-m-d H:i;s')."errno:{$errno},errstr:{$errstr},errfile:{$errfile},errline:{$errline}"."\n";
	}

	/**
	 * 异常终止
	 */
	static function appShutdown(){
	    $error = error_get_last();
	    echo date('Y-m-d H:i:s').',errorType:'.self::ERROR[$error['type']].',message:'.$error['message'].',file:'.$error['file'].',line:'.$error['file']."\n";
	}
}