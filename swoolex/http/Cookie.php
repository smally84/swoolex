<?php
// +----------------------------------------------------------------------
// | Swoolex cookie
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------

namespace Swoolex\http;


/**
 * Class Cookie
 * @package Swoolex\server\http
 */
class Cookie
{

    /**
     * 设置cookie的值
     */
    public function set(string $key, string $value='',int $expireTime=0,string $path='/',string $domain='',bool $secure=false,bool $httponly = true)
    {
        response()->cookie($key,$value,$expireTime,$path,$domain,$secure,$httponly);
    }
    /**s
     * 获取cookie的值
     */
    public function get($key)
    {
        return input('cookie.'.$key);
    }
    /**
     * 删除cookie,仅设置cookie的值为空，也是可以删除cookie的-源码中有说明
     */
    public function delete($key)
    {
        if(null!==$key)
        {
            self::set($key,null,time()-3600);
        }
        else {
            $cookies = input('cookie');
            foreach ($cookies as $k => $v)
            {
                self::delete($k);
            }
        }
    }
}