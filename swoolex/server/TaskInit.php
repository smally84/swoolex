<?php
/**
 * Created by PhpStorm.
 * User: smally
 * Date: 2019-07-20
 * Time: 17:27
 */

namespace Swoolex\server;

use Swoolex\asyncTask\TaskHandle;

class TaskInit
{
    /**
     * 异步任务调度
     */
    static function taskHandle($server)
    {
        /** 异步任务定时调度
         */
        $server->tick(500, function () use ($server){
            try{
                //调用异步任务处理逻辑，取任务并投递
                $TaskHandle = new TaskHandle();
                $TaskHandle -> fetchTask($server);
            }catch(\Throwable $e){
                echo date('Y-m-d H:i:s')."taskFetchTaskException".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
                \console('taskFetchTaskException',$e->getMessage());
            }
        });
    }
    /**
     * OnTask事件处理程序
     */
    static function onTask($server,$task)
    {
        /*保存rootCid*/
        if(!config('swoolex_root_cids'))config('swoolex_root_cids',[]);
        $rootCids = config('swoolex_root_cids');
        $rootCids[] = \co::getCid();
        config('swoolex_root_cids',$rootCids);

        /**保存请求响应对象，供上下文使用
         */
        sess_context('swoolex_task',$task);   //请求对象上下文资源保存

        /*系统默认的处理程序*/
        try{
            $AsyncTaskHandle = new TaskHandle();
            $data = json_decode($task->data,true);
            $res = $AsyncTaskHandle->exec($data);
            if($res==true)$res = true;
            else $res = false;
            //执行成功的任务，改变任务的数据库状态
            $AsyncTaskHandle->taskExecFinishCallback($data,$res);
        }catch (\Throwable $e){
            echo date('Y-m-d H:i:s')."asyncTaskHandleExcepiton".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
            \console('asyncTaskHandleExcepiton',$e->getMessage());
        }
    }
    /**
     * 僵尸异步任务处理
     */
    static function resetZombieTask($server)
    {
        /** 异步僵尸任务定时检测处理
         */
        $server->tick(1800000, function (){
            db()->startTrans();
            try{
                //调用异步任务处理逻辑，取任务并投递
                $TaskHandle = new TaskHandle();
                $TaskHandle -> resetZombieTask();
                db()->commit();
            }catch(\Throwable $e){
                db()->rollback();
                echo date('Y-m-d H:i:s')."resetZombieTaskHandleExcepiton".",File:".$e->getFile().",Line:".$e->getLine().",Msg:".$e->getMessage().PHP_EOL;
                \console('resetZombieTaskHandleExcepiton',$e->getMessage());
            }
        });
    }
}