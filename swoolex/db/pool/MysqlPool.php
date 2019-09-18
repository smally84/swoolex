<?php
/**
 * mysql数据库连接池
 */
namespace Swoolex\db\pool;

use Swoolex\XLang;
use Swoolex\db\pool\AbstractPool;

class MysqlPool extends AbstractPool
{
    //单例对象变量
    private static $_instance;
    //数据库配置
    private $_masterDbConfig = [];
    //从数据库配置
    private $_slaveDbConfig =[];
    /**
     * 单例-防止构造函数创建对象
     * @throws
     */
    private function __construct(){
        //获取主从配置
        $this->_masterDbConfig = config('db_mysql_options.master');
        $this->_slaveDbConfig  = config('db_mysql_options.slave');
        //检测主配置
        if(!isset($this->_masterDbConfig['uri']) || empty($this->_masterDbConfig['uri']))lang()->throwException(107004);
        //初始化数据库连接池
        $this->init($this->_masterDbConfig,$this->_slaveDbConfig);
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
     * @throws
     */
    public  function newConnection($hostType ='master'){
       if(empty($this->_slaveDbConfig))$hostType ='master';
        //数据库连接
        if('master' == $hostType){
            //随机获取一个主配置
            $mysqlUriCount = count($this->_masterDbConfig['uri']);
            $mysqlUri = $this->_masterDbConfig['uri'];
            $mysqlUri = array_values($mysqlUri);
            if($mysqlUriCount == 1)$mysqlUri = $mysqlUri[0];
            else if($mysqlUriCount > 1){
                $uriNumber = mt_rand(0,$mysqlUriCount);
                $mysqlUri = $mysqlUri[$uriNumber];
            }
        }
        //从数据库连接
        else if('slave' == $hostType){
            //随机获取一个主配置
            $mysqlUriCount = count($this->_slaveDbConfig['uri']);
            $mysqlUri = $this->_slaveDbConfig['uri'];
            $mysqlUri = array_values($mysqlUri);
            if($mysqlUriCount == 1)$mysqlUri = $mysqlUri[0];
            else if($mysqlUriCount > 1){
                $uriNumber = mt_rand(0,$mysqlUriCount);
                $mysqlUri = $mysqlUri[$uriNumber];
            }
        }
        /*url解析,获取数据库连接信息*/
        $mysqlUriParse = parse_url($mysqlUri);
        //mysql主机
        $host = $mysqlUriParse['host'];
        //mysql端口
        $port = $mysqlUriParse['port'];
        //数据库名字
        $dbname = str_replace('/', '', $mysqlUriParse['path']);
        //获取用户配置
        parse_str($mysqlUriParse['query'],$params);
        //登录的用户名
        $user = $params['user'];
        //登录的密码
        $password = $params['password'];
        //mysql字符集
        $charset  = isset($params['charset'])?$params['charset']:'uft8';

        //数据库连接源信息组装
        $dsn = "mysql:dbname={$dbname};host={$host};port={$port};charset={$charset}";

        /*pdo连接数据库*/
        try {
            //实例化pdo长连接
            $conn = new \PDO($dsn, $user, $password); 
            //禁止PHP本地转义而交由MySQL Server转义
            $conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); 
            $connInfo = [
                'conn'      => $conn,
                'host_type' => $hostType,
                'last_used_time'=>time(),
            ];
        } catch(\PDOException $e) {
            xlang()->throwException(107002);//数据库连接失败
        }
        //返回连接对象
        return $connInfo;
    }

    
        
}