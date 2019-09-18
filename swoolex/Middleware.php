<?php
// +----------------------------------------------------------------------
// | Swoolex 中间件
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex;

class Middleware 
{
    /**
     * 前置中间件的执行
     */
    public function before()
    {
        $middlewareClasses = config('middleware');
        $this->next('before',$middlewareClasses);
    }

    /**
     * 后置中间件的执行
     */
    public function after()
    {
        $middlewareClasses = config('middleware');
        $this->next('after',$middlewareClasses);
    }

    /**
     * 循环执行中间件方法
     */
    public function  next($type,&$middlewareClasses)
    {
        if(is_array($middlewareClasses) && !empty($middlewareClasses)){
            $middlewareClass = array_shift($middlewareClasses);
            if(class_exists($middlewareClass)&&method_exists($middlewareClass, $type))
            {
                (new $middlewareClass()) -> $type(function() use ($type,$middlewareClasses) {
                    $this->next($type,$middlewareClasses);
                });
            }
        }
    }
}