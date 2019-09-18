<?php
/**
 * 连接池抽象类
 */
namespace Swoolex\db\pool;

use Swoolex\XLang;

abstract class AbstractPool {

    //主数据库最少连接数
    private $_masterMinActive = 1;   
    //主数据库最大连接数
    private $_masterMaxActive = 10;
    //主连接池组
    private  $_master_connections = null;
    //可用的主连接计数
    private  $_currentMasterActive = 0;
    //最大等待时间
    private $_masterMaxWaitTime = 3;
    //最大闲置时间
    private $_masterMaxIdleTime = 3600;
    //从数据库最少连接数
    private $_slaveMinActive = 1;   
    //从数据库最大连接数
    private $_slaveMaxActive = 10;  
    //从连接池组
    private  $_slave_connections = null;
    //可用的从连接计数
    private  $_currentSlaveActive = 0;
    //最大等待时间
    private $_slaveMaxWaitTime = 3;
    //最大闲置时间
    private $_slaveMaxIdleTime = 3600;
    /**
     * 初始化
     * @param array $dbConfig       数据库配置
     * @param array $minActive      最小连接数
     * @throws
     */
    public function init($masterConfig =[],$slaveConfig =[])
    {
      
        //主数据库初始化
        isset($masterConfig['maxActive'])&&$this->_masterMaxActive=$masterConfig['maxActive'];
        isset($masterConfig['minActive'])&&$this->_masterMinActive=$masterConfig['minActive'];
        isset($masterConfig['masterMaxWaitTime'])&&$this->_masterMaxWaitTime=$masterConfig['masterMaxWaitTime'];
        isset($masterConfig['masterMaxIdleTime'])&&$this->_masterMaxIdleTime=$masterConfig['masterMaxIdleTime'];
        //从数据库初始化
        isset($slaveConfig['maxActive'])&&$this->_slaveMaxActive=$slaveConfig['maxActive'];
        isset($slaveConfig['minActive'])&&$this->_slaveMinActive=$slaveConfig['minActive'];
        isset($slaveConfig['slaveMaxWaitTime'])&&$this->_slaveMaxWaitTime=$slaveConfig['slaveMaxWaitTime'];
        isset($slaveConfig['slaveMaxIdleTime'])&&$this->_slaveMaxIdleTime=$slaveConfig['slaveMaxIdleTime'];
        //初始化主数据库连接池协程通道
        if(!empty($masterConfig['uri'])){
            $this->_master_connections = new \Swoole\Coroutine\Channel($this->_masterMaxActive);
            /*初始化连接池*/
            for($i=$this->_masterMinActive;$i>0;$i--){
                $connInfo = $this->newConnection('master');
                if($connInfo){
                    $res = $this->_master_connections->push($connInfo);
                    if(true == $res)$this->_currentMasterActive++;
                }
            }
        }
        //初始化从数据库连接池协程通道
        if(isset($slaveConfig['uri']) && !empty($slaveConfig['uri'])){
            $this->_slave_connections = new \Swoole\Coroutine\Channel($this->_slaveMaxActive);
            /*初始化连接池*/
            for($i=$this->_slaveMinActive;$i>0;$i--){
                $connInfo = $this->newConnection('slave');
                if($connInfo){
                    $res = $this->_slave_connections->push($connInfo);
                    if(true == $res)$this->_currentSlaveActive++;
                }
            }
        }  

    }

    /**
     * 抽象方法-新增数据库连接
     * @return db连接
     */
    abstract function newConnection($hostType ='master');

    /**
     * 获取主数据库连接
     * @param string $hostType
     * @return
     * @throws
     */
    public function get($hostType="master")
    {
        $conn = null;
        if(null == $this->_slave_connections)$hostType = "master";
        if("master"==$hostType){
            $Channel = $this->_master_connections;
            $CurrentActive = $this->_currentMasterActive;
            $maxActive = $this->_masterMaxActive;
            $maxWaitTime = $this->_masterMaxWaitTime;
            $maxIdleTime = $this->_masterMaxIdleTime;
        }
        else if("slave"==$hostType){
            $Channel = $this->_slave_connections;
            $CurrentActive = $this->_currentSlaveActive;
            $maxActive = $this->_slaveMaxActive;
            $maxWaitTime = $this->_slaveMaxWaitTime;
            $maxIdleTime = $this->_slaveMaxIdleTime;
        }
        if ($Channel->isEmpty()) {
            if ($CurrentActive < $maxActive) {//连接数没达到最大，新建连接入池
            	$connInfo = $this->newConnection($hostType);
                if('master'==$hostType)$this->_currentMasterActive++;
                else if('slave'==$hostType)$this->_currentSlaveActive++;
            } else {
                $connInfo = $Channel->pop($maxWaitTime);//timeout为出队的最大的等待时间
                if(false == $connInfo)lang()->throwException(107003);//获取数据库连接失败
            }
        } else {
            $connInfo = $Channel->pop($maxWaitTime);
            if(false == $connInfo)lang()->throwException(107003);//获取数据库连接失败
            //过期的连接废掉，重新获取
            if(time()-$connInfo['last_used_time']>$maxIdleTime){
                if('master'==$hostType)$this->_currentMasterActive--;
                else if('slave'==$hostType)$this->_currentSlaveActive--;                
                $connInfo = $this->get($hostType);
            }
        }
        return $connInfo;
    }
    /**
     * 释放数据库连接
     */
    public function put($connInfo)
    { 
        if ($connInfo) {
            $connInfo = [
                'conn'             => $connInfo['conn'],
                'host_type'        => $connInfo['host_type'],
                'last_used_time'   => time(),
            ];
            //依据数据库的连接主机类型，放入对应的连接池
            if('master' == $connInfo['host_type'])$this->_master_connections->push($connInfo);
            else if('slave' == $connInfo['host_type'])$this->_slave_connections->push($connInfo);
        }
    }


}