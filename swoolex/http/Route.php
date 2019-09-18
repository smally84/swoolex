<?php
// +----------------------------------------------------------------------
// | Swoolex 路由-仅支持pathinfo模式
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\http;
use Swoolex\Middleware;
class Route
{
    /**
     * @var string
     */
    private $_module = '';
    /**
     * @var string
     */
    private $_controller = '';
    /**
     * @var string
     */
    private $_method = '';

    /**
     * Route constructor.
     */
    public function __construct()
    {
        $this->_middlewareClasses = config('middleware');
    }

    /**
     * 查找模块、控制器进行路由
     */
    public function route(){

        /* +--------------------------------------------------------------
         * 获取动态的模块、控制器、方法
         * +--------------------------------------------------------------*/
        if(module())$module = module();
        else $module      = config('app.default_module');
        if(controller())$controller = controller();
        else $controller  = config('app.default_controller');
        if(method())$method = method();
        else $method      = config('app.default_method');

        /** 保存到类的路由属性
         */
        $this->_module = $module;
        $this->_controller = $controller;
        $this->_method = $method;
        /* +--------------------------------------------------------------
         * 执行路由中间件
         * +--------------------------------------------------------------*/
        bean(Middleware::class)->before();

        /* +--------------------------------------------------------------
         * 解析分层控制器
         * +--------------------------------------------------------------*/
        $controller = preg_replace('/(\.)\1+/u','$1',$controller);
        $controllerArray = explode('.',$controller);
        $controllerCount = count($controllerArray);
        foreach ($controllerArray as $index => $child_controller)
        {
            //自动转换控制器首字母为大写
            if($index+1==$controllerCount)
            {
                $child_controller[0] = strtoupper($child_controller[0]);
            }
            //分层控制器的，自动按命名空间连接
            if($index>0)
            {
                $controller = $controller.'\\'.$child_controller;
            }
            else
            {
                $controller = $child_controller;
            }
        }
        $controller = str_replace('.', '\\', $controller);
        /* +--------------------------------------------------------------
         * 执行用户控制器方法
         * +--------------------------------------------------------------*/
        if(config('app.app_multi_module'))$controller  = "\\".config('app.app_namespace')."\\$module\\controller\\$controller";
        else $controller  = "\\".config('app.app_namespace')."\\controller\\{$controller}";

        if(!class_exists($controller) || !method_exists($controller, $method)){
            response()->status(404);
            response()->end('404');
        }else{
            aop($controller)->$method();
        }
    }

}