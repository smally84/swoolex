<?php
/**
 * mysql数据库连接池
 */
namespace Swoolex\db\pool;

use Swoolex\XLang;
use Swoolex\db\pool\AbstractPool;

class RedisPool extends AbstractPool
{
    //单例对象变量
    private static $_instance;
    //数据库配置
    private $_masterDbConfig = [];
    //从数据库配置
    private $_slaveDbConfig =[];
    /**
     * 单例-防止构造函数创建对象
     */
    private function __construct(){
        //获取主从配置
        $this->_masterDbConfig = config('db_redis_options.master');
        $this->_slaveDbConfig  = config('db_redis_options.slave');
        //检测主配置
        if(!isset($this->_masterDbConfig['uri']) || empty($this->_masterDbConfig['uri']))lang()->throwException(107004);
        //初始化数据库连接池
        $this->init($this->_masterDbConfig,$this->_slaveDbConfig);
    }
    /**
     * 单例-防止被克隆
     */
    private function __clone(){

    }
    /**
     * 获取单例对象
     */
    public static function getInstance(){

         if (!self::$_instance instanceof self) {
              self::$_instance = new self();
         }
         
         return self::$_instance;
    }

    /**
     * 新增数据库连接
     * @param  string $hostType master/slave
     * @return array $connInfo  redis连接信息
     * @throws
     */
    public  function newConnection($hostType ='master'){
    	$timeout = 0;
        //数据库连接
        if('master' == $hostType){
            //随机获取一个主配置
            $redisUriCount = count($this->_masterDbConfig['uri']);
            $redisUri = $this->_masterDbConfig['uri'];
            $redisUri = array_values($redisUri);
            $timeout  = $this->_masterDbConfig['timeout'];
            if($redisUriCount == 1)$redisUri = $redisUri[0];
            else if($redisUriCount > 1){
                $uriNumber = mt_rand(0,$redisUriCount);
                $redisUri = $redisUri[$uriNumber];
            }
        }
        //从数据库连接
        else if('slave' == $hostType){
            //随机获取一个主配置
            $redisUriCount = count($this->_slaveDbConfig['uri']);
            $redisUri = $this->_slaveDbConfig['uri'];
            $redisUri = array_values($redisUri);
            $timeout  = $this->_masterDbConfig['timeout'];
            if($redisUriCount == 1)$redisUri = $redisUri[0];
            else if($redisUriCount > 1){
                $uriNumber = mt_rand(0,$redisUriCount);
                $redisUri = $redisUri[$uriNumber];
            }
        }
        /*url解析,获取数据库连接信息*/
        $redisUriParse = parse_url($redisUri);

        //mysql主机
        $host = $redisUriParse['host'];
        //mysql端口
        $port = $redisUriParse['port'];

        //获取用户配置
        isset($redisUriParse['query'])&&parse_str($redisUriParse['query'],$params);

        //登录的密码
        isset($params['password'])&&$password = $params['password'];



    	//实例化Redis客户端
    	$conn = new \Redis();  
    	//redis服务连接
		$res = $conn->connect($host, $port, $timeout);
		if(!$res)lang()->throwException(107301);

		//密码认证,成功:true,失败:false,认证失败则抛出异常
		if($password){
			$res = $conn->auth($password);
			if(!$res)lang()->throwException(107302);
		}
        $connInfo = [
            'conn'      => $conn,
            'host_type' => $hostType,
            'last_used_time'=>time(),
        ];
        //返回连接对象
        return $connInfo;
    }

    
        
}