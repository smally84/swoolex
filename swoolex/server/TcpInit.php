<?php
/**
 * Created by PhpStorm.
 * User: smally
 * Date: 2019-07-20
 * Time: 16:03
 */

namespace Swoolex\server;


class TcpInit
{
    /**
     * TcpInit 自定义连接成功处理事件
     */
    static function customOnConnect($server, int $fd, int $reactorId)
    {
        if(config('server_options.onConnect'))
        {
            $connectCallback = config('server_options.onConnect');
            $classMethod = explode(':', $connectCallback);
            if(isset($classMethod[0])&&isset($classMethod[1]))
            {
                $class  = $classMethod[0];
                $method = $classMethod[1];
                if(class_exists($class)&&method_exists($class, $method))
                {
                    (new $class())->$method($server,$fd,$reactorId);
                }
            }
        }
    }
    /**
     * TcpInit 自定义关闭处理事件
     */
    static function customOnClose($server, int $fd, int $reactorId)
    {
        if(config('server_options.onClose'))
        {
            $closeCallback = config('server_options.onClose');
            $classMethod = explode(':', $closeCallback);
            if(isset($classMethod[0])&&isset($classMethod[1]))
            {
                $class  = $classMethod[0];
                $method = $classMethod[1];
                if(class_exists($class)&&method_exists($class, $method))
                {
                    (new $class())->$method($server,$fd,$reactorId);
                }
            }

        }
    }
    /**
     * TcpInit onReceive回调
     * @throws
     */
    static function onReceive($server, int $fd, int $reactor_id, string $data)
    {
        self::defaultOnReceive($server,$fd,$data);
    }
    /**
     * 默认的Tcp onReceive处理程序
     * @param \Swoole\Server $server
     * @param int $fd
     * @param mixed $data
     * @throws
     */
    static function defaultOnReceive($server,$fd, $data)
    {
        /**保存rootCid
         */
        if(!config('swoolex_root_cids'))config('swoolex_root_cids',[]);

        $rootCids = config('swoolex_root_cids');
        $rootCids[] = \co::getCid();
        config('swoolex_root_cids',$rootCids);

        /**保存请求响应对象，供上下文使用
         */
        sess_context('swoolex_receive',$data);   //请求对象上下文资源保存

        //确认连接是否有效
        $packageEof = config('server_options.package_eof');
        $connection_info = $server->getClientInfo($fd);
        if(false == $connection_info)lang()->throwException(101001);

        //ping 信号响应
        $data = trim($data,$packageEof);
        if(strtoupper($data)=='PING'){
            $server->send($fd,"pong".$packageEof);
        }else{
            //转换json字符串为php数组(第二个参数必须为true)
            $receiveData = json_decode($data,true);
            //解析$class,method,params
            $api  = isset($receiveData['uri'])?$receiveData['uri']:'';
            $api  = str_replace('/', '\\', $api);
            $data = isset($receiveData['data'])?$receiveData['data']:[];
            $apiArr = explode(':', $api);
            if(2!=count($apiArr))throw new \Exception("uri violation", 0);
            else
            {
                $class  = $apiArr[0];
                $method = $apiArr[1];
            }
            //保存会话fd连接
            sess_context('swoolex_fd',$fd);
            //实例化控制器类，并调用执行方法
            $obj = new $class();
            $obj->$method($data);
        }
    }
}