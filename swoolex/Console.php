<?php
// +----------------------------------------------------------------------
// | Swoolex 控制台实现
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------

namespace Swoolex;
require_once (SHELL_PATH.'/index.php');
class Console
{
    /**
     * 订阅控制台消息
     */
    public function subscribe()
    {
        $fd = sess_context('swoolex_fd');
        if(config('swoolex_server')->exist($fd))
        {
            redis()->sAdd(config('app.name').':'.'swoolex_console_fds',$fd);
            $res['code'] = 0;
            $res['msg']  = 'success';
            config('swoolex_server')->push($fd,json_encode($res).config('server_options.package_eof'));
        }
        else
        {
            $res['code'] = -1;
            $res['msg']  = 'failed';
        }
        return $res;
    }
    /**
     * 绑定服务
     */
    public function bindService($params)
    {
        $connectionInfo  = isset($params['connection_info'])?$params['connection_info']:[];
        if(isset($connectionInfo['fd'])&&is_numeric($connectionInfo['fd']))
        {
            isset($params['data'])?$params['data']:[];
            $fd = $connectionInfo['fd'];
            redis()->sAdd(config('app.name').':'.'swoolex_console_fds',$fd);
        }
    }
    /**
     * 解除服务
     */
    public function unbindService($parmas)
    {

    }
    /**
     * 发布控制台消息
     * @param string  $msgType  消息类型
     * @param mixed   $data
     * @return  bool
     */
    static function publish($msgType,$data=null)
    {
        /** 判断控制台消息类型，设置推送内容
         */
        if ('serverStats' === $msgType ) {
            $SwoolexCtl = new \SwoolexCtl();
            $res = $SwoolexCtl->getStatus();
            if($res['code'] === 0)
            {
                $swoolex_enable = is_numeric($res['data']['swoolex_enable']) ? (int)$res['data']['swoolex_enable'] : 0;
                $swoolex_restart = is_numeric($res['data']['swoolex_restart']) ? (int)$res['data']['swoolex_restart'] : 0;
                $swoolex_state = is_numeric($res['data']['swoolex_state']) ? (int)$res['data']['swoolex_state'] : 0;
            }
            //获取服务器连接信息
            $stats = config('swoolex_server')->stats();
            //附加控制状态
            $stats['swoolex_enable'] = $swoolex_enable;
            $stats['swoolex_restart'] = $swoolex_restart;
            $stats['swoolex_state'] = $swoolex_state;
            //内存使用情况
            $stats['memory_usage'] = redis()->hGetAll(config('app.name') . ':' . 'worker_memory_use');
            krsort($stats['memory_usage']);
            //向控制台客户端推送
            $send = [];
            $send['code'] = 0;
            $send['msgType'] = 'serverStats';
            $send['data'] = $stats;
        }
        else {
            if($data == null)
            {
                $data = $msgType;
                $msgType = 'swoolex-log';
            }
            $send = [];
            $send['code'] = 0;
            $send['msgType'] = $msgType;
            $send['data']    = '<pre>' . var_export($data,true) . '</pre>';
        }
        /** 推送swoole连接信息
         */
        $fds = redis()->sMembers(config('app.name').':'.'swoolex_console_fds');
        foreach ($fds as $index => $fd)
        {
            //移除已经断开的客户端连接
            if(!config('swoolex_server')->exist($fd))
            {
                unset($fds[$index]);
                redis()->sRemove(config('app.name').':'.'swoolex_console_fds',$fd);
            }
            //向处于连接状态的客户端发送消息
            else
            {
                config('swoolex_server')->push($fd,json_encode($send).config('server_options.package_eof'));
            }
        }
        return true;
    }
}