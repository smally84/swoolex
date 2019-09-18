<?php
namespace app\middleware;

/**
 * Class Demo
 * @package etc\middleware
 */
class Demo
{

    /**
     * 前置中间件
     * @param $next \Closure
     * @return  \Closure
     */
    public function before(\Closure $next)
    {
        if(controller()=='index')dump('beforeMiddleware');
        return $next();
    }
    /**
     * 后置中间件
     * @param $next \Closure
     * @return \Closure
     */
    public function after(\Closure $next)
    {
        if(controller()=='index')dump('afterMiddleware');
        return $next();
    }
}