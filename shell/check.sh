#!/bin/sh
#php可执行文件安装的位置 注：可根据实际的位置进行修改
phpPath='/usr/local/php7/bin/php';
#-----------------------------------------------------------
# 日志文件路径
#-----------------------------------------------------------
startPath=$(dirname $0)'/start.php';#服务启动文件
runtime=$(dirname $0)'/../runtime';
statFile=$runtime'/status';#运行状态存储文件
bootLog=$runtime'/boot.log';#启动的错误日志
runLog=$runtime'/run.log';#swoole运行错误
#-----------------------------------------------------------
# 重启服务函数
#-----------------------------------------------------------
function restart()
{
    ps -eaf|grep $startPath | grep -v "grep" | awk '{print $2}' | xargs kill -9
    $phpPath $startPath
}
#-----------------------------------------------------------
# 服务停止函数
#-----------------------------------------------------------
function stop()
{
    ps -eaf|grep $startPath | grep -v "grep" | awk '{print $2}' | xargs kill -9
}
#-----------------------------------------------------------
# log日志文件的大小检测
#-----------------------------------------------------------
if [ ! -d $runtime ];then
    mkdir -p $runtime
    chmod -R 777 $runtime
fi
#bootLog处理
if [ -f $bootLog ];then
    filesize=`ls -l $bootLog | awk '{ print $5 }'`
    maxsize=10000;
    if [ $filesize -gt $maxsize ]
    then
        echo ''>$bootLog
    fi
else
    touch $bootLog
    touch $statFile
    chmod 777 $bootLog
    chmod 777 $statFile
fi
#runLog处理
if [ -f $runLog ];then
    filesize=`ls -l $runLog | awk '{ print $5 }'`
    maxsize=10000;
    if [ $filesize -gt $maxsize ]
    then
        echo ''>$runLog
    fi
else
    touch $runLog
    chmod 777 $runLog
fi
#循环60次，变换分钟定时器为秒级定时器
for((i = 0;i < 59;i=i+1));
do
    #-----------------------------------------------------------
    #swoolex控制
    #-----------------------------------------------------------
    #读取文件内容
    stat=$( cat $statFile );
    #字符分隔，获取每个操作的状态。服务启用标志/服务重启标志/服务运行状态
    statArray=(${stat//,/ });
    swoolex_enable=${statArray[0]};
    swoolex_restart=${statArray[1]};
    swoolex_state=${statArray[2]};
    #检测是否要重启，如果需要则清除标志并重启
    if [[ $swoolex_restart = '1' ]]
    then
        swoolex_restart=0;
        swoolex_enable=1;
        #更新重启标志
        echo ${swoolex_enable}","${swoolex_restart}","${swoolex_state} > $statFile;
        #重启服务
        restart
    fi
    #判断服务是否启用
    if [[ $swoolex_enable = '1' ]]
    then
        #否则检测服务是否正常，不正常则重启
        if [[ $swoolex_state = '0' ]]
        then
            swoolex_state=1;
            #更新重启标志
            echo ${swoolex_enable}","${swoolex_restart}","${swoolex_state} > $statFile;
            #重启服务
            restart
        fi
    else
        stop
    fi
    sleep 1;
done