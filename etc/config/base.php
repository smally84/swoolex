<?php
// +----------------------------------------------------------------------
// | Swoolex - 基础配置
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
return [
    // +----------------------------------------------------------------------
    // | 基本设置
    // +----------------------------------------------------------------------
    // 默认时区
    'default_timezone'       => 'PRC',
    //默认多语言
    'default_lang'           => 'zh',
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    'app' => [
        //应用名称
        'name'                    => 'swoolex',
        //应用的命名空间
        'app_namespace'           => 'app',
        // 应用调试模式
        'debug'                   => true,
        // 是否支持多模块
        'app_multi_module'        => true,
        //默认模块
        'default_module'          => 'admin',
        //默认控制器
        'default_controller'      => 'index',
        //默认方法
        'default_method'          => 'index',
    ],
    // +----------------------------------------------------------------------
    // | server服务设置 - 混合服务器
    // +----------------------------------------------------------------------
    'server_options' => [
        'daemonize'          => 1, //开启守护进程
        'log_file'           => __DIR__.'/../../runtime/run.log',
        //启用tcp开关
        'enable_tcp'    => true,
        'tcp_host'      => '0.0.0.0',
        'tcp_port'      => 9591,
        'tcp_sock_type' => SWOOLE_SOCK_TCP,
        //启用http开关
        'enable_http'    => true,
        'http_host'      => '0.0.0.0',
        'http_port'      => 9592,
        //启用websocket开关
        'enable_websocket'    => true,

        //server属性设置
        'worker_num'                      => 4, //worker进程数量
        'max_request'                     => 20000,//进程重启设置（接受一定数量请求后重启，防止内存泄露）
        'task_worker_num'                 => 4,//task_worker进程数
        'task_enable_coroutine'           => true,//启用task协程
        'task_max_request'                => 20000,//进程重启设置（接受一定数量请求后重启，防止内存泄露）
        'dispatch_mode'                   => 3,
        'task_ipc_mode'                   => 1,//开启协程的Task进程无法使用消息队列
        'heartbeat_check_interval'        => 60,//启用心跳检测，每隔60秒检测一次
        'heartbeat_idle_time'             => 300,//允许最大的空闲时间，300秒
        'open_eof_split'                  => true,//打开EOF_SPLIT检测
        'package_eof'                     => "\0",//设置EOF

        //异步重启特性
        'reload_async'                    => true,//异步安全重启
        'max_wait_time'                   => 3,//等待时间
        //server定时重启间隔(单位分钟)-放置内存泄漏
        'reload_interval'                 => 100000,

        /*自定义事件回调  $class:$method*/
        'onOpen'      => '',//websocket连接事件,支持多个
        'onConnect'   => '',//tcp connect事件
        'onClose'     => '',//tcp close事件
        'onWorkerStart' => '',//workerStart事件

        /*文件上传*/
        'package_max_length'=> 20000000,             //最大允许包的大小20M，包括消息及文件上传
        'upload_tmp_dir'    => '/temp/',             //文件上传的临时目录
    ],
    // +----------------------------------------------------------------------
    // | 数据库设置
    // +----------------------------------------------------------------------
    'db_mysql_options' =>[
        /*主数据库*/
        'master' => [
            'name'        => 'master',
            'uri'         => [],
            'minActive'   => 1,
            'maxActive'   => 20,
            'maxWait'     => 8,
            'timeout'     => 8,
            'maxIdleTime' => 3600,
            'maxWaitTime' => 3,
        ],
        /*从数据库*/
        'slave' => [
            'name'        => 'slave',
            'uri'         => [],
            'minActive'   => 1,
            'maxActive'   => 20,
            'maxWait'     => 8,
            'timeout'     => 8,
            'maxIdleTime' => 3600,
            'maxWaitTime' => 3,
        ],
        'soft_delete_field' => 'delete_time',//伪删除标记字段
    ],
    'db_redis_options' => [
        /*主数据库*/
        'master' => [
            'name'        => 'master',
            'uri'         => [],
            'minActive'   => 2,
            'maxActive'   => 20,
            'maxWait'     => 8,
            'timeout'     => 3,
            'maxIdleTime' => 3600,
            'maxWaitTime' => 3,
        ],
        /*从数据库*/
        'slave' => [
            'name'        => 'slave',
            'uri'         => [],
            'minActive'   => 2,
            'maxActive'   => 20,
            'maxWait'     => 8,
            'timeout'     => 3,
            'maxIdleTime' => 3600,
            'maxWaitTime' => 3,
        ],
    ],
    // +----------------------------------------------------------------------
    // | session设置
    // +----------------------------------------------------------------------

    'session'         => [
        'name'   => 'SWXSESSID',
        'expire' =>  3600,
    ],
];