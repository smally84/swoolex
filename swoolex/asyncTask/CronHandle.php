<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-04-09
 * Time: 15:19
 */

namespace Swoolex\asyncTask;


class CronHandle
{
    public function exec(){
        try{
            /*--------------执行时间段匹配检测,符合条件的执行-------------*/
            $task = include(CONF_PATH . 'task.php');
            $crontabConf = $task['crontab'];
            $nowTime  = [
                0=>(int)date('s'),//此时的秒
                1=>(int)date('i'),//此时的分
                2=>(int)date('H'),//此时的小时
                3=>(int)date('d'),//此时的天
                4=>(int)date('m'),//此时的月
                5=>(int)date('w'),//此时的星期
            ];
            if($nowTime[5]==0)$nowTime[5]=7;

            foreach ($crontabConf as $cronset => $api) {
                //分割时间段为数组
                $pattern = "/\s+/";
                $timeConfig=preg_split($pattern,$cronset);
                //必须满足秒、分、时、日期、月份、星期六项配置
                if(count($timeConfig)<6)return;
                /*------------时间比对----------*/
                $timeTypeRange = [
                    [0,59],//0-59秒
                    [0,59],//0-59分
                    [0,23],//0-23时
                    [1,31],//1-31日
                    [1,12],//1-12月
                    [1,7], //星期1-星期日
                ];
                $timeType = -1;
                foreach ($timeConfig as $k => $v) {
                    $timeType ++;//时间段类型(0：秒,1：分钟,2：小时,3：日期,4：月,5：星期)
                    //所有值
                    if($v=='*'){
                        $isMatch = 1; //是否匹配
                        continue;
                    }
                    //时间点 ','
                    else if(preg_match("/,/",$v))
                    {
                        $isMatch = 0; //默认不匹配
                        $slotTime=preg_split("/,/",$v);
                        foreach ($slotTime as $sk => $sv) {
                            if(5==$timeType && 0==$sv)$sv=7;
                            if($sv==$nowTime[$timeType]){
                                $isMatch = 1;
                                continue;//存在匹配,继续匹配
                            }
                        }
                        if(0===$isMatch)break;//没有匹配，直接退出
                        else continue;
                    }
                    //一段范围 '-'
                    else if(preg_match("/-/",$v))
                    {
                        $isMatch = 0;//默认不匹配
                        $slotTime=preg_split("/-/",$v);
                        //星期值0转为7
                        foreach ($slotTime as $sk => &$sv) {
                            if(5==$timeType && 0==$sv)$sv=7;
                        }
                        //必须是长度为2的闭区间
                        if(count($slotTime)!=2){
                            $isMatch = 0;
                            break;//非法值，终止匹配
                        }
                        //中间一段 [小，大]
                        else if($slotTime[0]<=$slotTime[1]){
                            if($nowTime[$timeType]>=(int)$slotTime[0] && $nowTime[$timeType]<=(int)$slotTime[1])
                            {
                                $isMatch = 1;
                                continue;//存在匹配,继续匹配
                            }
                        }
                        //末尾一段+开始一段[大，小]
                        else if($slotTime[0]>=$slotTime[1]){
                            if($nowTime[$timeType]>=(int)$slotTime[0] || $nowTime[$timeType]<=(int)$slotTime[1])
                            {
                                $isMatch = 1;
                                continue;//存在匹配,继续匹配
                            }
                        }
                        if(0===$isMatch)break;//没有匹配，直接退出
                        else continue;
                    }
                    //指定时间的间隔频率
                    else if(preg_match("/\//",$v))
                    {
                        $isMatch = 0; //默认不匹配
                        $slotTime=preg_split("/\//",$v);
                        if(count($slotTime)!=2 || $slotTime[1]<=0 )
                        {
                            $isMatch = 0;
                            break;//非法值，终止匹配
                        }
                        else{
                            $slotTime0 = $slotTime[0];
                            $slotTime1 = $slotTime[1];
                            if($slotTime0=="*"){
                                if($nowTime[$timeType]%$slotTime1==0)
                                {
                                    $isMatch = 1;
                                    continue;//存在匹配,继续匹配
                                }
                            }else{
                                $slotTime0 = preg_split("/-/",$slotTime0);
                                if(count($slotTime0)!=2){
                                    $isMatch = 0;
                                    break;//非法值，终止匹配
                                }
                                //中间一段 [小，大]
                                else if($slotTime0[0]<=$slotTime0[1]){
                                    if( $nowTime[$timeType]>=$slotTime0[0] && $nowTime[$timeType]<=$slotTime0[1] )
                                    {
                                        if(($nowTime[$timeType]-(int)$slotTime0[0])%(int)$slotTime1==0)
                                        {
                                            $isMatch = 1;
                                            continue;//存在匹配,继续匹配
                                        }
                                    }
                                }
                                //末尾一段+开始一段[大，小]
                                else if($slotTime0[0]>=$slotTime0[1]){
                                    if( $nowTime[$timeType]>=$slotTime0[0])
                                    {
                                        if(($nowTime[$timeType]-(int)$slotTime0[0])%(int)$slotTime1==0)
                                        {
                                            $isMatch = 1;
                                            continue;//存在匹配,继续匹配
                                        }
                                    }
                                    else if( $nowTime[$timeType]<=$slotTime0[1] )
                                    {
                                        /*计算距离开始的每隔时间*/
                                        if(0==$timeTypeRange[$timeType][0]){
                                            $everyTime = $timeTypeRange[$timeType][0] - (int)$slotTime0[0] + 1 + $nowTime[$timeType];
                                        }else{
                                            $everyTime = $timeTypeRange[$timeType][0] - (int)$slotTime0[0] + $nowTime[$timeType];
                                        }
                                        if($everyTime%(int)$slotTime1==0)
                                        {
                                            $isMatch = 1;
                                            continue;//存在匹配,继续匹配
                                        }
                                    }
                                }
                            }
                        }
                        if(0===$isMatch)break;
                        else continue;
                    }
                    //特定值
                    else{
                        if((int)$v==$nowTime[$timeType])continue;
                        else break;
                    }
                }
                //若最终符合定时规则，则投递相应的执行任务
                if(5 == $timeType && 1== $isMatch){
                    $pushData = [
                        'task_type' => 'crontab',
                        'task_data' => [
                            'exec_method' => $api,
                        ],
                    ];
                    //判断任务执行的类或方法是否存在
                    $execMethodArr = explode(':',$api);
                    if(2!=count($execMethodArr))throw new \Exception("crontab etc error", 0);
                    else
                    {
                        $class  = $execMethodArr[0];
                        $method = $execMethodArr[1];

                    }
                    if(!class_exists($class))throw new \Exception("crontab exec_class not exists", 0);
                    if(!method_exists($class, $method))throw new \Exception("crontab exec_class_method not exists", 0);

                    (new $class())->$method();
                }
            }
        }catch(\Throwable $e){
            echo $e->getMessage();
        }
    }
}