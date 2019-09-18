<?php
// +----------------------------------------------------------------------
// | Swoolex 服务器
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\server;
use Swoolex\config\Config;
/**
 * Class Server
 * @package Swoolex\server
 */
class Server
{
    /**
     * @var array  监控（重启）路径
     */
    private $_reloadMonitorPath = [];
    /**
     * @var int 重启间隔
     */
    private $_reloadInterval    = 1000;
    /**
     * @var int 上次重启时间
     */
    private $_lastReloadTime    =  null;
    /**
     * @var \Swoole\Server|null
     */
    private $_server      = null;

    /**
     * 任务忙状态指示
     * @var null
     */
    private $_fetchTaskBusyFlag = null;

	/**
	 * 服务器构造函数
	 */
	public function __construct()
	{
        /*
         *  创建http服务器
         */
		if(true == config('server_options.enable_http'))
		{
			$host      = config('server_options.http_host');
			$port      = config('server_options.http_port');
			$options   = config('server_options');
			if( $host && $port)
			{
                $this->_server = new \Swoole\WebSocket\Server($host, $port,SWOOLE_PROCESS,SWOOLE_SOCK_TCP);
                $this->_server -> set($options);
                $http_server = $this->_server;

                /**
                 *  默认开启Http处理，并设置OnRequst事件回调
                 */
				$http_server -> on('request', function ($request,$response){
				    try{
                        HttpInit::onRequest($request,$response);//系统默认的http处理程序
                    }catch (\Throwable $e){
				        $error = date("Y-m-d H:i:s").",httpHandleException".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
				        echo $error;
				        console('httpHandleException',$error);
				        /*浏览器输出*/
                        response()->status(500);
				        if(config('app.debug'))response()->end($error);
                        else response()->end('httpHandleException');
                    }
                    //执行后置中间件
                    bean("\Swoolex\Middleware")->after();
                    //调用end表示结束
                    if(!response()->end)sess_context('swoolex_response')->end();

				});
			    if(true == config('server_options.enable_websocket'))
                {
                    // open事件
                    $http_server -> on('Open',function($server,$request) {
                        WebsocketInit::customOnOpen($server,$request);
                    });
                    // websocket请求处理
                    $http_server -> on('message', function ($server,$frame) {
                        try{
                            WebsocketInit::onMessage($server,$frame);
                        }catch(\Throwable $e){
                            echo date('Y-m-d H:i:s')."wsOnMessageException".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
                            \console('websocketHandleException',$e->getMessage());
                        }
                    });
                }
			}
		}
        /*
         * 创建tcp服务器
         */
		if(true == config('server_options.enable_tcp'))
		{
			$host      = config('server_options.tcp_host');
			$port      = config('server_options.tcp_port');
			$sockType  = config('server_options.tcp_sock_type')?config('server_options.tcp_sock_type'):SWOOLE_SOCK_TCP;
			$options   = config('server_options');
			if($host && $port && $sockType)
			{
				if($this->_server){
					$tcp_server = $this->_server -> listen($host, $port, $sockType);
					if($tcp_server) $tcp_server -> set($options);
				}
				else
				{
					$this->_server = new \Swoole\Server($host, $port, SWOOLE_PROCESS,$sockType);
					$this->_server -> set($options);
					$tcp_server = $this ->_server;
				}
				// connect事件 
				$tcp_server -> on('connect',function($server, int $fd, int $reactorId){
				    TcpInit::customOnConnect($server,$fd,$reactorId);
				});
				//tcp请求处理
				$tcp_server -> on('receive', function ($server, int $fd, int $reactor_id, string $data) {
				    try{
                        TcpInit::onReceive($server, $fd, $reactor_id,$data);
                    }catch (\Throwable $e){
                        echo date('Y-m-d H:i:s')."tcpOnReceiveException".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
				        \console('tcpHandleException',$e->getMessage());
                    }
				});
				// close事件
				$tcp_server -> on('close', function($server, int $fd, int $reactorId){
                    TcpInit::customOnClose($server,$fd,$reactorId);
				});
			}
		}
        /** 设置进程全局server对象常量
         */
        config('swoolex_server',$this->_server);
		/*
		 * onStart
		 */
        $this->_server -> on('Start',function (){
            echo "server have already started".PHP_EOL;
        });
        /*
         * WorkerStart启动回调
         */
		$this->_server -> on('WorkerStart',function($server, $worker_id){
            if (0 == $worker_id) {
                // 定时重启，防止内存泄露-15分钟
                WorkerStartInit::serverReload($server);
                // 重置内存统计
                redis()->delete(config('app.name').':'.'worker_memory_use');
                // 尝试创建task_list表
                WorkerStartInit::initDbTable();
                // 服务状态监控
                WorkerStartInit::serverStatsMonitor($server);
                // 秒级定时器
                WorkerStartInit::crontab($server);
                // 异步任务定时调度
                TaskInit::taskHandle($server);
                // 异步僵尸任务定时检测处理
                TaskInit::resetZombieTask($server);
                //文件更新reload进程监听
                $this -> monitorToReload();
            }
            //进程定期清理上下文
            WorkerStartInit::clearContext($server,$worker_id);
            // 为每个work设置一个定时器，用于统计worker的信息
            WorkerStartInit::memoryMonitor($server,$worker_id);
            // 自定义WorkerStart回调
            WorkerStartInit::custom($server,$worker_id);
		});
        /*
         * task执行事件回调
         */
        $this->_server -> on('task',function($server,$task){
            TaskInit::onTask($server,$task);
        });
        /*
         *task执行完成回调
         */
		$this->_server->on('Finish',function (){
            echo "finish".PHP_EOL;
        });
	}


	/**
	 * 启动服务器
	 */
	public function start()
	{
        //创建重启时间记录器
        $this->_lastReloadTime = new \Swoole\Atomic(time());
        //异步任务获取繁忙锁标记
        $this->_fetchTaskBusyFlag = new \Swoole\Atomic(0);
        //启动服务器
		$res = $this->_server -> start();
		//返回启动结果
		return $res;
	}


	/**
	 * 服务器软启动监测
	 */
	public function monitorToReload()
	{
        // 监听路径，默认APP路径和配置路径
        $paths = !empty($this->_reloadMonitorPath)?$this->_reloadMonitorPath:[APP_PATH];
        // 监听事件间隔默认1s
        $this->_server->tick($this->_reloadInterval, function () use ($paths) {
            $lastMtime = $this->_lastReloadTime->get();
            $temp_lastMtime = 0;
            foreach ($paths as $path) {
                $dir      = new \RecursiveDirectoryIterator($path);
                $iterator = new \RecursiveIteratorIterator($dir);

                foreach ($iterator as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) != 'php') {
                        continue;
                    }
                    if ($temp_lastMtime < $file->getMTime()) {
                        $temp_lastMtime = $file->getMTime();
                    }
                }
            }
            if($lastMtime < $temp_lastMtime){
                $this->_lastReloadTime->set($temp_lastMtime);
                $this->_server->reload();
                console('server-auto-hot-reload');
            }
        });
	}

}