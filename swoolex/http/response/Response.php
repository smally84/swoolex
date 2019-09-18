<?php
// +----------------------------------------------------------------------
// | Swoolex - HttpInit Response
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\http\response;


/**
 * Class Response
 * @package Swoolex\server\http\response
 * @method header(string $key,string $value)
 * @method cookie(string $key,string $value='',int $expireTime=0,string $path='/',string $domain='',bool $secure=false,bool $httponly = true)
 * @method status(int $status)
 * @method end(string $str)
 * @method write(string $str)
 */
class Response
{

    /**
     * Response constructor.
     */
    public function __construct()
    {

    }

    /**
     * 属性获取，魔术方法
     */
    public function __get($name)
    {
        return sess_context('swoolex_response_'.$name);
    }

    /**
     * 匿名调用，可以调取原始swoole架构response所支持的方法
     * @param string $method
     * @param mixed $arguments
     */
    public function __call($method, $arguments)
    {
        //判断连接是否已经断开，断开则不再操作
        $server =  config('swoolex_server');
        $fd     =  request()->fd;
        if(!$server->exist($fd))return;
        //write分段发送数据后，end方法将不接受任何参数
        if('end'==$method && isset($arguments[0]) && $arguments[0])
        {
            if(sess_context('swoolex_response_write'))$method = 'write';
        }
        /** 保存方法的数据操作，便于后续查询
         */
        switch ($method)
        {
            //header
            case 'header':
                $oldHeader = sess_context('swoolex_response_header');
                if(!$oldHeader)$oldHeader = [];
                $oldHeader[][$arguments[0]] = $arguments[1];
                sess_context('swoolex_response_header',$oldHeader);
            break;
            //cookie
            case 'cookie':
                $oldCookie = sess_context('swoolex_response_cookie');
                if(!$oldCookie)$oldCookie = [];
                isset($arguments[1])?$oldCookie[][$arguments[0]] = $arguments[1]:$oldCookie[$arguments[0]]='';//value
                isset($arguments[2])?$oldCookie[][$arguments[0]] = $arguments[2]:$oldCookie[$arguments[0]]=0;//expire
                isset($arguments[3])?$oldCookie[][$arguments[0]] = $arguments[3]:$oldCookie[$arguments[0]]='/';//path
                isset($arguments[4])?$oldCookie[][$arguments[0]] = $arguments[4]:$oldCookie[$arguments[0]]='';//domain
                isset($arguments[5])?$oldCookie[][$arguments[0]] = $arguments[5]:$oldCookie[$arguments[0]]=false;//secure
                isset($arguments[6])?$oldCookie[][$arguments[0]] = $arguments[6]:$oldCookie[$arguments[0]]=false;//httponly
                sess_context('swoolex_response_cookie',$oldCookie);
            break;
            //status
            case 'status':
                sess_context('swoolex_response_status',$arguments[0]);
            break;
            //end
            case 'end':
                /*保存本次会话的响应内容*/
                sess_context('swoolex_response_end',$arguments[0]);
                break;
            //write
            case 'write':
                //保存分段输出的内容
                $oldWrite = sess_context('swoolex_response_write');
                if(!$oldWrite)$oldWrite = [];
                $oldWrite[] = isset($arguments[0])?$arguments[0]:null;
                sess_context('swoolex_response_write',$oldWrite);
                break;

        }

        /* 执行原生方法 */
        sess_context('swoolex_response')->$method(...$arguments);

    }

}