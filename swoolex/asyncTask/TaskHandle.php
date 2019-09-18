<?php
// +----------------------------------------------------------------------
// | Swoolex 任务处理
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\asyncTask;

use \Swoolex\Validate;
use Swoolex\Console;

class TaskHandle {

	/**
	 * 任务接收
	 * @param  $params[array]
	 *          |_ _ _ _ _ _ _task_type[必须]-任务类型 string
	 *          |_ _ _ _ _ _ _task_data[必须]-任务数据 array
     * @return array  succss/msg
     * @throws
	 */
	public function addTask($params){

	    try{
            /*---------------------参数验证--------------------*/
            $rule = [
                ['task_type'          ,'require', '任务类型必须'],
                ['task_data'          ,'require', '任务数据必须'],
            ];
            $validate = new Validate($rule);
            $result = $validate->check($params);
            if($result==false)throw new \Exception($validate->getError(),1);
            /*---------------------保存任务至数据库----------------*/
            $data = [];
            $data['params']       = json_encode($params);
            $data['create_time']  = time();
            $data['update_time']  = time();
            $res = db('task_list')->insert($data);
            if($res==1){
                $ret = [];
                $ret['success'] = true;
                $ret['msg']     = '任务加入成功！';
            }else
            {
                $ret =[];
                $ret['success'] = false;
                $ret['msg']     = '任务加入失败！';
            }
            return $ret;
        }catch (\Throwable $e) {
            $ret =[];
            $ret['success'] = false;
            $ret['msg']     = '任务加入失败！';
            return $ret;
        }

	}


	/**
	 * 异步任务执行
     * @param  array $data
     * @return bool
     * @throws
	 */
	public function exec($data){
		$params = isset($data['params'])?$data['params']:'';
		if($params){
			$params = json_decode($params,true);
			$params['task_data']['retry_times']  = $data['retry_times'];
            $params['task_data']['create_time']  = $data['create_time'];
            $params['task_data']['update_time']  = $data['update_time'];
		}
		$ret = false;
		if(isset($params['task_type'])&&!empty($params['task_type'])){
			//获取任务类型和任务数据
			$task_type = $params['task_type'];
			$task_data = $params['task_data'];

			/** 从配置文件中获得对应任务类型的执行类
			 */
            $taskConf = config('task');
            if(!isset($taskConf[$task_type]))
            {
                $task = include(CONF_PATH . DS . 'task.php');
                $taskConf = isset($task['task'])?$task['task']:'';
                $execMethod = isset($taskConf[$task_type])?$taskConf[$task_type]:'';
            }else{
                $execMethod = isset($taskConf[$task_type])?$taskConf[$task_type]:'';
            }
            /** 获取执行类的类型及方法
             */
			$execMethodArr = explode(':', $execMethod);
            $class  = trim($execMethodArr[0]);
            $method = trim($execMethodArr[1]);

			//判断任务执行的类或方法是否存在
			if(!class_exists($class))throw new \Exception("crontab exec_class not exists", 0);
			if(!method_exists($class, $method))throw new \Exception("crontab exec_class_method not exists", 0);

			$rm = new \ReflectionMethod($class,$method);
            if($rm->isStatic()){
                $res = $class::$method($task_data);
            }else{
                $obj = new $class();
                $res = $obj->$method($task_data);
            }
            return $res;

		}

		return $ret;

	}
	/**
	 * 定时取任务(供swoole的定时任务调用)
	 * 注意事项,避免任务的重复载入，通过is_running字段标记，执行完成后复位为0
	 */	
 	public function fetchTask($server){

 	    $fetchNumber = config('server_options.task_worker_num')?config('server_options.task_worker_num'):10;
        /*1.取任务*/
        $taskList = db('task_list')
            ->where('is_running',0)
            ->order('retry_times asc,update_time asc')
            ->limit($fetchNumber)
            ->select();
        if(!empty($taskList)){
            foreach ($taskList as $key => &$value) {
                $remainingTime = (config('task_retry_interval'))[$value['retry_times']];
                /*判断重试时间是否到*/
                if(time()- $value['create_time']<$remainingTime){
                    unset($taskList[$key]);//时间未到，则先移除不做处理
                }else{
                    $value['update_time'] = time(); //更新任务更新时间
                    $value['retry_times'] ++;       //更新重试次数
                    $value['is_running']  = 1;      //标记任务正在进行中
                }
            }
            /*2.定时检测投递任务*/
            $stats = $server->stats();//获得当前server的活动信息
            if( count($taskList)>0 && isset($stats['tasking_num'])&&$stats['tasking_num']<100){
                //加入消息队列成功，则改变任务在数据库的状态,并投递
                foreach ($taskList as $key => $value) {
                    $res =  db('task_list')->where('id',$value['id'])->update($value);
                    if(1===$res){
                        $task_id = $server->task(json_encode($value),-1);
                        if($task_id===false){
                            $value['retry_times']--;
                            $value['is_running'] = 0;
                            db('task_list')->where('id',$value['id'])->update($value);
                        }
                    }
                }
            }
        }
 	}
 	/**
 	 * 任务执行结果的处理
     * @throws
 	 */
 	public function taskExecFinishCallback($data,$result=false){

 		if(!is_array($data)){
 			throw new \Exception('任务执行的数据，必须为数组',0);
 		}
 		if(!is_bool($result)){
 			throw new \Exception('任务执行的结果，必须为bool型',0);
 		}
 		if(!isset($data['id'])){
 			throw new \Exception('任务执行结果处理中，id不能为空',0);
 		}
 		$taskListId = $data['id'];
 		//执行成功的任务，直接从数据库中删除
 		if($result==true){
 			$res = db('task_list')
 			->where('id',$taskListId)
 			->delete(true);
 		}
 		//重试次数超出最大限制的，直接从数据库删除
 		else if($result==false){
 			if(isset($data['retry_times'])&&$data['retry_times']>11){
	 			db('task_list')
	 			->where('id',$taskListId)
	 			->delete(true);
 			}else{
 				db('task_list')
 				->where('id',$taskListId)
 				->update(['is_running'=>0]);//恢复任务的状态为可取状态
 			}
 		}
 		
 	}

 	/**
 	 * 复位僵尸任务
 	 *   |_ _ _ 1.长时间running=1的状态的任务属于异常任务，由程序自动化复位）
 	 *   |_ _ _ 2.任务已经到期却没有删除的（retry_times>11）
     * @throws
 	 */
 	public function resetZombieTask(){

        /*1.长时间running=1的状态的任务属于异常任务，由程序自动化复位）*/
        $taskList = db('task_list')
            ->where('is_running',1)
            ->limit(1000)
            ->select();
        //检出超过1天处于running状态的。
        foreach ($taskList as $key => &$value) {
            if(time()-$value['update_time']<86400){
                unset($taskList[$key]);
            }
        }
        if(!empty($taskList))
        {
            //先删除记录，再插入记录
            $delNum =db('task_list')
                    ->where('id','in',array_column($taskList,'id'))
                    ->update([
                        'is_running' => 0
                    ]);
            if($delNum<=0)throw new \Exception("任务状态修改失败", 1);
        }
 	}

}