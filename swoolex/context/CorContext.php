<?php
// +----------------------------------------------------------------------
// | Swoolex - 协程上下文
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\context;

class CorContext {

	//上下文存储池
    static $pool = [];
    /**
     * 动态方法转静态调用
     */
    public function __call($name, $arguments)
    {
        return self::$name(...$arguments);
    }
    //获取协程资源
    static function get($key, int $cid = null) {
        $cid = $cid ?? \co::getCid();
        if (isset(self::$pool[$cid][$key])) {
            return self::$pool[$cid][$key];
        }
        return null;
    }
    /**
     * 设置协程变量
     * @param string $key
     * @param mixed  $value
     * @param int    $cid
     * @return bool
     */
    static function set(string $key, $value, int $cid = null) {
        $cid = $cid??\co::getCid();
        if(isset($key)&&!empty($key)){
            self::$pool[$cid][$key] = $value;
            if($value == self::get($key,$cid))return true;
            else return false;
        }
        else
        {
            return false;
        }
    }

    /**
     * @param  string $key
     * @param  int    $cid
     * @return bool
     */
    static function delete($key, int $cid = null) {

        $cid = $cid ?? \co::getCid();
        //如果key为null,则删除全部会话变量
        if(null == $key){
            unset((self::$pool)[$cid]);
            return !isset((self::$pool)[$cid]);
        }
        else{
            if(isset((self::$pool)[$cid][$key]))
            {
                unset((self::$pool)[$cid][$key]);
                if(null == self::get($key))return true;
                else return true;
            }
        }
        return true;
    }

}