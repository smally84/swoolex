<?php
// +----------------------------------------------------------------------
// | Swoolex - 请求会话上下文
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\context;

class SessContext {

    //会话上下文存储池
    public static $pool = [];

    /**
     * 动态方法转静态调用
     */
    public function __call($name, $arguments)
    {
        return self::$name(...$arguments);
    }
    /**
     * 获取当前环境的根协程id
     * @param void
     * @return int
     */
    public static function getRootCid()
    {
        $cid = \co::getCid();
        $rootCids = config('swoolex_root_cids');
        if(!$rootCids)$rootCids = [];
        sort($rootCids);
        $key = array_search($cid, $rootCids);
        //如果存在，则当前出在根协程环境中
        if(false!==$key)return $cid;
        //如果不存在，则合并入根协程id集合，获得前一项
        else{
            array_push($rootCids, $cid);
            sort($rootCids);
            $rootCids = array_values($rootCids);
            $key = array_search($cid, $rootCids);
            if($key==0)return 0;
            else return $rootCids[$key-1];
        }
    }

    /**
     * 获取会话变量值
     * @param string $key
     * @return mixed
     */
    public static function get(string $key,$root_cid=null) {
        $rootCid = isset($root_cid)?$root_cid:self::getRootCid();
        if (isset(self::$pool[$rootCid][$key])) {
            return self::$pool[$rootCid][$key];
        }
        return null;
    }
    /**
     * 设置会话变量
     * @param string $key
     * @param mixed  $value
     * @return bool
     */
    public static function set(string $key,$value,$root_cid=null) {
        $rootCid = isset($root_cid)?$root_cid:self::getRootCid();
        if(isset($key)&&!empty($key)){
            self::$pool[$rootCid][$key] = $value;
            if($value == self::get($key))return true;
            else return false;
        }
        else
        {
            return false;
        }

    }
    /**
     * 删除会话变量
     * @param string $key
     * @return bool
     */
    public static function delete($key,$root_cid=null) {
        $rootCid = isset($root_cid)?$root_cid:self::getRootCid();
        //如果key为null,则删除全部会话变量
        if(null == $key){
            unset((self::$pool)[$rootCid]);
            $deleteRes = isset( (self::$pool)[$rootCid] );
            if(!$deleteRes)return true;
            else return false;
        }
        else{
            if(isset((self::$pool)[$rootCid][$key])){
                unset((self::$pool)[$rootCid][$key]);
                if(null == self::get($key))return true;
                else return true;
            }
        }
        return true;
    }

}