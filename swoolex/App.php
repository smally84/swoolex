<?php
// +----------------------------------------------------------------------
// | Swoolex - 应用类
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex;

use Swoolex\config\Config;
use Swoolex\server\Server;


class App {

	/**
	 * 应用初始化
	 */
	static function init(){
    	/* +--------------------------------------------------------------
    	 * 常量配置
    	 * +--------------------------------------------------------------*/
		define('SWOOLEX_VERSION', '1.0.0');
		define('EXT', '.php');
		define('DS', DIRECTORY_SEPARATOR);
		defined('SWOOLEX_PATH') or define('SWOOLEX_PATH', __DIR__ .DS );
        defined('SHELL_PATH')   or define('SHELL_PATH', __DIR__ . DS.'..' .DS.'shell'.DS);
		defined('APP_PATH')     or define('APP_PATH', __DIR__ .DS.'..'.DS.'app'.DS);
        defined('ETC_PATH')     or define('ETC_PATH', __DIR__ .DS.'..'.DS.'etc'.DS);
        defined('CONF_PATH')    or define('CONF_PATH', ETC_PATH.DS.'config'.DS);

    	/* +--------------------------------------------------------------
    	 * 注册错误和异常处理机制
    	 * +--------------------------------------------------------------*/
        Error::register();
         
    	/* +--------------------------------------------------------------
    	 * 加载助手函数
    	 * +--------------------------------------------------------------*/
        include(SWOOLEX_PATH . 'helper.php');

    	/* +--------------------------------------------------------------
    	 * 配置项加载
    	 * +--------------------------------------------------------------*/

		/*基本配置*/
		$config   = include(CONF_PATH . 'base.php');                //加载基本配置
		if($config)Config::$pool = array_merge(Config::$pool, $config);  //合并配置

        /*默认配置*/
        $config   = include(SWOOLEX_PATH.DS.'config'.DS.'default.php');//加载默认配置
        if($config)Config::$pool = array_merge(Config::$pool, $config);  //合并配置

        /*redis分区设置*/
        $config   = include(CONF_PATH.'redis.php');                 //加载redis分区配置
        if($config)Config::$pool = array_merge(Config::$pool, $config);  //合并配置

		/*多语言设置*/
		$lang1 = include(CONF_PATH . 'langMsg.php');                       //加载语言码定义
        $lang2 = include(SWOOLEX_PATH . DS . '/i18n/langMsg.php');
        Config::$pool = array_merge(Config::$pool, ['lang'=>$lang2+$lang1]);

		/*Aop配置*/
        $config   = include(CONF_PATH . 'aop.php');                  //加载redis分区配置
        $configN  = [];
        foreach ($config as $aopKey => $aopValue)
        {
            if($aopKey[0]!="\\")$aopKey = "\\".$aopKey;//为了和aop配置统一，自动补齐首位反斜杠
            $configN[$aopKey] = $aopValue;
        }
        if($config)Config::$pool = array_merge(Config::$pool, ['aop'=>$config]);  //合并配置

        /*中间件配置*/
        $config   = include(CONF_PATH.'middleware.php');
        if($config)Config::$pool = array_merge(Config::$pool, ['middleware'=>$config]);  //合并配置

        /*定时器配置*/
        $config = [];
        $frameConfig = include(SWOOLEX_PATH.DS.'config'.DS.'default.php');//框架的默认配置
        $userConfig  = include(CONF_PATH.DS.'task.php');//用户自定义任务配置
        $userCrontab = isset($userConfig['crontab'])?$userConfig['crontab']:[];//用户自定义定时器配置
        $userTask = isset($userConfig['task'])?$userConfig['task']:[];//用户异步任务配置
        $config['crontab'] = $frameConfig['crontab']+$userCrontab;//合并
        $config['task'] = $frameConfig['task']+$userTask;//合并
        if(isset($userConfig['task_retry_interval']))$config['task_retry_interval'] = $userConfig['task_retry_interval'];
        else $config['task_retry_interval'] = $frameConfig['task_retry_interval'];
        if($config)Config::$pool = array_merge(Config::$pool, $config);  //合并配置


        /* +--------------------------------------------------------------
         * 设置系统时区
         * +--------------------------------------------------------------*/
        date_default_timezone_set(config('default_timezone'));

        /* +--------------------------------------------------------------
         * 加载全局常量
         * +--------------------------------------------------------------*/
        $constPath = ETC_PATH . DS .'const';
        $dir      = new \RecursiveDirectoryIterator($constPath);
        $iterator = new \RecursiveIteratorIterator($dir);
        //文件迭代器
        foreach ($iterator as $file) {
            $pathInfo = pathinfo($file);
            if ( !isset($pathInfo['extension']) || 'php' != $pathInfo['extension']) {
                continue;
            }
            include_once($pathInfo['dirname'].DS.$pathInfo['filename'].'.'.$pathInfo['extension']);
        }
        /* +--------------------------------------------------------------
         * 加载全局函数
         * +--------------------------------------------------------------*/
        $functionPath = ETC_PATH . DS .'function';
        $dir      = new \RecursiveDirectoryIterator($functionPath);
        $iterator = new \RecursiveIteratorIterator($dir);
        //文件迭代器
        foreach ($iterator as $file) {

            $pathInfo = pathinfo($file);
            if ( !isset($pathInfo['extension']) || 'php' != $pathInfo['extension']) {
                continue;
            }
            include_once($pathInfo['dirname'].DS.$pathInfo['filename'].'.'.$pathInfo['extension']);
        }
        /* +--------------------------------------------------------------
         * 加载env
         * +--------------------------------------------------------------*/
        $envFilePath = SWOOLEX_PATH.'/../.env';
        if(file_exists($envFilePath)){
            $env = file_get_contents($envFilePath);
            $env = str_replace(["\n\r","\n","\r"],"\n",$env);
            $envLines = explode("\n",$env);
            foreach ($envLines as $envLine){
                $envLineArray = explode('=',$envLine,2);
                if(count($envLineArray)==2){
                    $configKey   = trim($envLineArray[0]);
                    $configValue = trim($envLineArray[1]);
                    if(isset($configKey[0])&&$configKey[0]=="#")continue;//判断是不是注释
                    if(!empty($configKey))config($configKey,$configValue);
                }
            }
        }
	}

	/**
	 * 启动应用
	 * @return  bool true/false
	 */
	static function start()
	{
		$Server = new Server();
		$res = $Server->start();
		return $res;
	}

}