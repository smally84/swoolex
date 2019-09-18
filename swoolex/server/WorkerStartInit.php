<?php
/**
 * Created by PhpStorm.
 * User: smally
 * Date: 2019-07-20
 * Time: 16:22
 */

namespace Swoolex\server;

use Swoolex\context\CorContext;
use Swoolex\context\SessContext;
use Swoolex\asyncTask\CronHandle;

class WorkerStartInit
{
    /**
     * worker进程内容监控
     */
    static function memoryMonitor($server,$worker_id)
    {
        $server->tick(500,function() use ($server,$worker_id){
            try{
                $memory_get_usage = memory_get_usage()/(1024*1024);
                $memory_get_peak_usage = memory_get_peak_usage()/(1024*1024);
                if($server->taskworker)$worker_id = $worker_id.'_task';
                else $worker_id = $worker_id.'_worker';
                redis()->hSet(config('app.name').':'.'worker_memory_use',$worker_id,$memory_get_usage.'_'.$memory_get_peak_usage);
            }catch (\Throwable $e) {
                echo date('Y-m-d H:i:s')."workerTimerHandleExcepiton".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
            }
        });
    }
    /**
     * server定时重启,防止内存泄露-15分钟
     */
     static function serverReload($server)
     {
         /** 定时重启，防止内存泄露,默认30分钟
          */
         $interval = config('server_options.reload_interval')?config('server_options.reload_interval'):30;
         $server->tick($interval*60*1000,function() use ($server) {
             try{
                 $server->reload();
             }catch (\Throwable $e) {
                 echo date('Y-m-d H:i:s')."gcProcessHandleExcepiton".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
             }
         });
     }
     /**
      * 初始化数据表
      */
     static function initDbTable()
     {
         /**
          * 尝试创建task_list表
          */
         $sql =
             /** @lang text */
             "CREATE TABLE `task_list` 
                (
                    `id` bigint(20) NOT NULL auto_increment,
                    `params` text NOT NULL,
                    `retry_times` tinyint(3) unsigned DEFAULT '0' COMMENT '重试的周期标志 1：1分钟 2：3分钟 3：10分钟 4：30分钟 5：1小时 6：3小时  7：10小时 8：1天 9：3天 10：10天 11：1个月',
                    `is_running` tinyint(3) DEFAULT '0' COMMENT '任务是否正在进行中 0：未进行 1：进行中',
                    `create_time` int(10) unsigned DEFAULT '0' COMMENT '创建时间',
                    `update_time` int(10) unsigned DEFAULT '0' COMMENT '最后更新时间',
                     KEY `retry_times` (`retry_times`),
                     KEY `is_running` (`is_running`),
                     PRIMARY KEY (`id`) USING BTREE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;";

         db()->execute($sql); //执行

         /**
          * 尝试创建debug_log表
          */
         $sql = /** @lang text */
             "CREATE TABLE `debug_log` (
                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                          `msg` text COMMENT '错误信息',
                          `type` varchar(300) DEFAULT NULL,
                          `time` varchar(50) DEFAULT NULL,
                          PRIMARY KEY (`id`) USING BTREE
                        ) ENGINE=InnoDB AUTO_INCREMENT=481752 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='调试日志';";

         db()->execute($sql);//执行
     }
     /**
      * server状态监控
      */
     static function serverStatsMonitor($server)
     {
         $server->tick(1000,function () use ($server){
             try{
                 /** 控制台消息推送
                  */
                 \console('serverStats');
             }catch (\Throwable $e) {
                 echo date('Y-m-d H:i:s')."consoleHandleExcepiton".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
             }
         });
     }
    /**
     * 清理上下文
     */
    static function clearContext($server,$worker_id)
    {
        $server->tick(5000,function () use ($server,$worker_id){
            try{
                //获取当前进行全部协程
                $coros = \co::list();//获取进程的全部协程数组
                $corosArray =  iterator_to_array($coros);

                /*清理协程上下文*/
                foreach (CorContext::$pool as $CorContextKey => $CorContextValue)
                {
                    if(!in_array($CorContextKey,$corosArray))unset(CorContext::$pool[$CorContextKey]);
                }

                //获取进程全部协程最大ID值
                sort($corosArray);
                if(count($corosArray)>0){
                    $maxCoroId = isset($corosArray[count($corosArray)-1])?$corosArray[count($corosArray)-1]:-1;
                }else $maxCoroId = -1;


                //获取会话根协程id（重置数组key）
                $rootCids = config('swoolex_root_cids');
                if(!is_array($rootCids))$rootCids = [];
                sort($rootCids);
                $rootCids = array_values($rootCids);


                //遍历清除会话上下文
                foreach ($rootCids as $rootCidKey=>$rootCid)
                {
                    //如果进程存在多个会话请求
                    if(isset($rootCids[$rootCidKey+1]))
                    {
                        $rootNextCid = $rootCids[$rootCidKey+1];
                        //判断相邻会话根协程id和当前进程协程组是否有交集
                        $res = array_intersect(range($rootCid,$rootNextCid-1),$corosArray);
                        //如果没有交集，则清除会话上下文
                        if(empty($res)){
                            unset(SessContext::$pool[$rootCid]);
                            unset($rootCids[$rootCidKey]);
                        }else continue;
                    }
                    //如果进程当前只要一个会话
                    else{
                        //如果会话没有创建新的子协程(新的子协程id大于跟协程id)
                        if($maxCoroId<$rootCid){
                            unset(SessContext::$pool[$rootCid]);
                            unset($rootCids[$rootCidKey]);
                        }else continue;
                    }
                }
            }catch (\Throwable $e) {
                echo date('Y-m-d H:i:s')."clearContextExcepiton".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
            }
        });
    }
     /**
      * 定时器
      */
     static function crontab($server)
     {
         /** 秒级定时器
          */
         $server->tick(1000, function ($timer_id){
             try{
                 $CronHandle = new CronHandle();
                 $CronHandle -> exec();
             }catch(\Throwable $e){
                 echo date('Y-m-d H:i:s')."crontabHandleExcepiton".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
                 \console('crontabHandleExcepiton',$e->getMessage());
             }
         });
     }
      /**
       * 自定义workerstart启动程序
       */
      static function custom($server,$worker_id)
      {
          $workerStartCallback = config('server_options.onWorkerStart');
          $classMethod = explode(':', $workerStartCallback);
          if(isset($classMethod[0])&&isset($classMethod[1]))
          {
              $class  = $classMethod[0];
              $method = $classMethod[1];
              if(class_exists($class)&&method_exists($class, $method))
              {
                  (new $class())->$method($server, $worker_id);
              }
          }

      }
}