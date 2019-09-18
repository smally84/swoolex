<?php
/**
 * Swoolex
 * Mysql模型
 */
declare(strict_types = 1);
namespace Swoolex\db\mysql;

use Swoolex\db\pool\MysqlPool;
use Swoolex\XLang;

/**
 * @method count(string $field='')
 * @method max(string $field='')
 * @method min(string $field='')
 * @method avg(string $field='')
 * @method sum(string $field='')
 */

class Db {

    //数据库连接
	private $_connInfo;
    //要操作的表
    private $_table = '';
    //别名
    private $_alias = '';
    //联表
    private $_join = '';
    //sql语句模板
	private $_sql  = '';
    //绑定的值
	private $_bind = [];
    //获取的字段参数
    private $_fields = '*';
    //条数限制
    private $_limit = -1;
    //分页限制
    private $_page  = 1;
    //最后执行的sql语句
    private static $_last_sql = '';
    //返回sql
    private $_fetchSql = false;
    //排序
    private $_order = "";
    //更新表达式
    private $_exp = '';
    //伪删除标识字段
    private $_softDelField = 'delete_time';
    //重连次数
    private $_reconnectTimes = 0;
    //自增id
    public $_id = null;
	/**
	 * 构造函数初始化
	 */
	public function __construct($table='',$config=[]){

        $this->_table    = $table;
        $this->_sql = '';
        $this->_bind = [];
        /** 获取连接池实例
         */
        if(null == config('swoolex_mysql_pool'))
        {
            config('swoolex_mysql_pool', MysqlPool::getInstance());
        }
        /** 创建协程主数据库连接
         */
        $masterMysql = cor_context('swoolex_master_mysql');
        if(!$masterMysql){
            $connInfo = config('swoolex_mysql_pool')->get('master');
            cor_context('swoolex_master_mysql',$connInfo);
        }
        /** 创建协程主数据库连接
         */
        $slaveMysql = cor_context('swoolex_slave_mysql');
        if(!$slaveMysql){
            $connInfo = config('swoolex_mysql_pool')->get('slave');
            cor_context('swoolex_slave_mysql',$connInfo);
        }
        /**资源释放函数注册
          */
        if(!cor_context('swoolex_mysql_defer')){
            \defer(function(){
                //释放主连接
                $masterMysql = cor_context('swoolex_master_mysql');
                if($masterMysql){
                    config('swoolex_mysql_pool')->put($masterMysql);
                    cor_context('swoolex_master_mysql',null);
                }
                //释放从连接
                $slaveMysql = cor_context('swoolex_slave_mysql');
                if($slaveMysql){
                    config('swoolex_mysql_pool')->put($slaveMysql);
                    cor_context('swoolex_slave_mysql',null);
                }
            });
            cor_context('swoolex_mysql_defer',1);
        }
	}
    /**
     * 析构函数
     */
    public function __destruct(){

    }
    /**
     * 统计查询
     */
    public function __call($method,$args){
        if(in_array($method, ['count','max','min','avg','sum'])){
            $args_0 = isset($args[0])?$args[0]:'';
            return $this->aggregate($method,$args_0); //合计函数
        }
        return $this;
    }
    /**
     * 获取自增id
     * @throws
     */
    public function id()
    {
        return $this->query("SELECT LAST_INSERT_ID()");
    }
    /**
     * where条件查询
     */
    public function where($field,$compare=null,$value=null)
    {
        $Query = new Query($this->_sql,$this->_bind);
        $res = $Query -> where($field,$compare,$value);
        if(isset($res[0]))$this->_sql  = $res[0];
        if(isset($res[1]))$this->_bind = $res[1];
        return $this;
    }
    /**
     * whereOr条件查询
     */
    public function whereOr($field,$compare=null,$value=null)
    {
        $Query = new Query($this->_sql,$this->_bind);
        $res = $Query -> whereOr($field,$compare,$value);
        if(isset($res[0]))$this->_sql  = $res[0];
        if(isset($res[1]))$this->_bind = $res[1];
        return $this;
    }
    /**
     * 获取sql语句
     */
    public function getSql(){
        return $this->_sql;
    }
    /**
     * 获取绑定参数
     */
    public function getBind(){
        return $this->_bind;
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql():string
    {
        return self::$_last_sql;
    }

    /**
     * 设置最近一次操作sql语句
     * @param  string $sql 预处理语句
     * @param  array  $bind 绑定参数
     */
    public function setLastSql($sql,$bind)
    {
        $_last_sql = $sql;
        foreach ($bind as $key => $value) {
            if(is_numeric($key)){
                $_last_sql = preg_replace('/\?/',$value,$_last_sql,1);
            }else{
                $_last_sql = preg_replace("/:{1}{$key}/", $value,$_last_sql);
            }
        }
        $_last_sql = preg_replace('/(\s)\1+/u','$1',$_last_sql);
        self::$_last_sql = $_last_sql;
    }


    /*-------------------------预处理查询---------------------*/
	/**
     * 查询操作
     * @throws
     */
	public  function query($sql,$bind = []){
        try{
            $this->_connInfo = cor_context('swoolex_slave_mysql');
            $this->setLastSql($sql,$bind);
            /** 判断是否是仅仅获取sql语句
             */
            if($this->_fetchSql)return self::$_last_sql;
            /*获取mysql连接*/
            $connection = $this->_connInfo['conn'];

            /*准备sql语句*/
            $ps = $connection -> prepare($sql);
            if(false == $ps){
               lang()->throwException(107201,$connection -> errorInfo()[2]);
            }
                
            /*执行查询*/
            if(!empty($bind) && is_array($bind)) $ps -> execute($bind);
            else $ps -> execute();
            $result = $ps->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\Throwable $e) {

            if ($this->isBreak($e) && 0==$this->_reconnectTimes) {
                $this->_reconnectTimes++;
                $connInfo = config('swoolex_mysql_pool')->newConnection('slave');
                cor_context('swoolex_slave_mysql',$connInfo);
                return $this->query($sql, $bind);
            }
            else throw $e;
        }

	}
	/**
     * 写入操作
     * @throws
     */
	public  function execute($sql,$bind = []){

        try{
            $this->_connInfo = cor_context('swoolex_master_mysql');
            $this->setLastSql($sql,$bind);
            /** 判断是否是仅仅获取sql语句
             */
            if($this->_fetchSql)return self::$_last_sql;
            /*获取mysql连接*/
            $conn = $this->_connInfo['conn'];

            /*准备sql语句*/
            $ps = $conn -> prepare($sql);
            if(false == $ps){
               lang()->throwException(107201,$conn -> errorInfo()[2]);
            }

            /*执行查询*/
            if(!empty($bind) && is_array($bind)) $ps -> execute($bind);
            else $ps -> execute();
            $res =  $ps->rowCount();

            /*获取自增id*/
            $this->_id = $this->query("SELECT LAST_INSERT_ID()");
            return $res;
        } catch (\Throwable $e) {
            if ($this->isBreak($e)) {
                $connInfo = config('swoolex_mysql_pool')->newConnection('master');
                cor_context('swoolex_master_mysql',$connInfo);
                return $this->execute($sql, $bind);
            }
            throw $e;
        }

	}
    /**
     * 设置操作表的别名
     * @return  Db     $this
     */
    public function alias($alias){
        $this->_alias = $alias;
        return $this;
    }
    /**
     * 获取表信息
     * @throws
     */
    public function getColumnsInfo(){

        if(empty($this->_table))lang()->throwException(107104);
        $table = $this->_table;
        $res = $this->query("SHOW COLUMNS FROM {$table}");
        $fieldArr = array_map('array_shift',$res);
        $columnsInfo = [];
        foreach ($fieldArr as $key => $value) {
            $columnsInfo[$value] = $res[$key];
        }
        return $columnsInfo;
    }

    //-----------------------------------------------------*/
    // 新增数据
    //-----------------------------------------------------*/

	/**
	 * 增加一条数据,自动过滤不存在的字段
	 * @param $data[一维数据]
     * @throws
	 */
	public function insert(array $data)
    {
        $fields = '';
        $values = '';
        $sql    = $this->_sql;
        $table  = $this->_table;
        $bind   = [];

        // 获取表字段信息
        $columnsInfo = $this->getColumnsInfo();
        // 检测字段是否存在
        foreach ($data as $key => $value) {
            if(array_key_exists($key, $columnsInfo)){
                // 拼接表字段
                if(empty($values))$fields .="`{$key}`";
                else $fields .=",`{$key}`";
                // 拼接占位符
                if(empty($values))$values .='?';
                else $values .=',?';
                // 组合绑定值
                $bind[]  = $value;
            }
        }
        //检查是否有可用的字段
        if(empty($fields))lang()->throwException(107105);
        //拼接sql
        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$values})";

        //清理缓存的属性
        $this->_table = '';
        $this->_sql = '';
        $this->_bind = '';
        //执行插入
        return $this->execute($sql,$bind);

	}
	/**
	 * 批量增加数据,自动过滤不存在的字段
	 * @param $allData[二维数组]
     * @throws
	 */
	public function insertAll(array $allData)
    {

        // 要插入的字段
        $fields = '';
        $fieldsArr = [];
        // 占位符
        $values = '';
        // 绑定的值
        $bind   = [];
        // 获取表字段信息
        $columnsInfo = $this->getColumnsInfo();
        // 要操作的表
        $table  = $this->_table;
        /** 遍历二维数据，得到每条数据
         *  $rowKey  
         *  $rowValue
         */
        foreach ($allData as $rowKey => $rowValue) {
            // 临时用单条数据的占位符
            $values_tmp = '';
            
            /** 遍历单条数据，获取每个字段的值
             *  $key
             *  $value
             */  
            foreach ($rowValue as $key => $value) {

                /** 遍历到第一条数据时，获取插入的数据字段
                 */
                if(0 == $rowKey){
                    // 检测插入的字段是否真实存在
                    if(array_key_exists($key, $columnsInfo)){
                        if(empty($fields))$fields .="`{$key}`";
                        else $fields .=",`{$key}`";
                    }
                    // 是否有可用的字段
                    if(empty($fields))lang()->throwException(107105);
                    // 转为数组形式
                    $fieldsArr = explode(',', $fields);
    
                } 

                /**拼接占位符 和 绑定值
                 */
                if(in_array($key, $fieldsArr)){
                    // 拼接占位符
                    if(empty($values_tmp))$values_tmp .='(?';
                    else $values_tmp .=',?';
                    // 拼接绑定的数据
                    $bind[]  = $value;
                }
            }
            /**闭合本次占位符及总占位符
             */
            if(!empty($values_tmp)){
                $values_tmp .=')';
                if(empty($values))$values .= $values_tmp;
                else $values .= ",$values_tmp";
            }

        }

        //拼接sql
        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES {$values}";
        //清理缓存的属性
        $this->_table = '';
        $this->_sql = '';
        $this->_bind = '';
        //执行插入
        return $this->execute($sql,$bind);

	}

    //-----------------------------------------------------*/
    // 删除数据
    //-----------------------------------------------------*/

    /**
     * 删除数据
     * @param bool  $softDel 伪删除，默认伪删除
     * @throws
     */
    public function delete($realDel = false){

        //操作的表
        $table = $this->_table;
        //已拼接的sql
        $sql = $this->_sql;
        //已绑定的值
        $bind = $this->_bind;
        //伪删除字段
        $softDelField = $this->_softDelField;

        /** 伪删除
         */
        if(false == $realDel){
            //判断是否存在伪删除字段
            $columnsInfo = $this->getColumnsInfo();
            if(empty($this->_softDelField)||!array_key_exists($softDelField, $columnsInfo))
            {
                lang()->throwException(107106);
            }
            //修改伪删除字段的值
            $data = [
                $this->_softDelField => time(),
            ];
            return $this->update($data);
        }
        /** 物理删除
         */
        else
        {
            $sql = "DELETE FROM `{$table}` "." {$sql}";
        }
        //清理缓存的属性
        $this->_table = '';
        $this->_sql = '';
        $this->_bind = '';
        //执行操作
        return $this->execute($sql,$bind);
    }

    //-----------------------------------------------------*/
    // 更新数据
    //-----------------------------------------------------*/

    /**
     * 更新数据,自动过滤不存在的字段
     * @return  Db     $this
     */
    public function exp($key,$value){
        $exp = $this->_exp;
        if(empty($exp))$this->_exp = " {$key} = {$value}";
        else $this->_exp .= ",{$key} = {$value}";
        return $this;
    }

    /**
     * 更新数据,自动过滤不存在的字段
     * @param $data
     * @return int
     */
    public function update($data=[]){

        //要更新的字段
        $fields = '';
        //已拼接的sql
        $sql = $this->_sql;
        //更新表达式
        $exp = $this->_exp;
        //更新的表
        $table = $this->_table;

        if(!empty($data)){
            //获取数据表字段
            $columnsInfo = $this->getColumnsInfo();

            foreach ($data as $key => $value) {

                //过滤不存在的字段
                if(array_key_exists($key, $columnsInfo))
                {
                    if(empty($fields))$fields = " `{$key}` = ?";
                    else $fields .=" , `{$key}` = ?";
                    $bind[] = $value;
                }
            }
            //拼接绑定值和sql预处理语句
            $bind = array_merge($bind,$this->_bind);
            $sql  = "UPDATE `{$table}` SET {$fields}".$sql;
        }else{
            $sql  = "UPDATE `{$table}` SET {$exp}".$sql;
        }

        //清理缓存的属性
        $this->_table = '';
        $this->_sql   = '';
        $this->_bind  = [];
        $this->_exp   = '';

        //执行更新
        return  $this->execute($sql,$bind);
    }


    //-----------------------------------------------------*/
    // 查询数据
    //-----------------------------------------------------*/


    /**
     * 获取一条数据
     */
    public function find(){
        $this->_limit = 1;
        $res = $this->select();
        if(empty($res))return null;
        if(is_array($res))return current($res);
        else return $res;
    }
    /**
     * 获取多条数据
     * @throws
     */
    public function select(){

        //转存
        $table   = $this->_table;
        $alias   = $this->_alias;
        $join    = $this->_join;
        $sql     = $this->_sql;
        $bind    = $this->_bind;
        $fields  = $this->_fields;
        $limit   = $this->_limit;
        $page    = $this->_page;
        $order   = $this->_order;
        //清空以便于新的一次查询
        $this->_table  = '';
        $this->_alias  = '';
        $this->_sql    = '';
        $this->_bind   = [];
        $this->_fields = '';
        $this->_limit  = -1;
        $this->_page   = 1;
        $this->_order  = '';
        //select拼接
        $sql = "SELECT {$fields} FROM {$table} {$alias} {$join} ".$sql;
        //排序
        if($order){
            $sql = $sql.$order;
        }
        //分页
        $offset = ($page-1)*$limit;
        if($limit>0){
            $sql     = $sql." LIMIT ?,?";
            $bind[]  = $offset;
            $bind[]  = $limit;
        }
        //执行查询

        $res =  $this->query($sql,$bind);
        if(empty($res))return [];
        else return $res;
    }
    /**
     * 只返回sql语句，不进行实际的查询
     * @param bool $fetch
     * @return  Db     $this
     */
    public function fetchSql(bool $fetch=true)
    {
        if($fetch)$this->_fetchSql = true;
        else $this->_fetchSql = false;
        return $this;
    }
    /**
     * 字段过滤
     * @param  string $fields
     * @return  Db     $this
     */
    public function field(string $fields){
        $this->_fields = $fields;
        return $this;
    }
    /**
     * 条数限制
     * @param int $limit
     * @return  Db     $this
     */
    public function limit(int $limit){
        if($limit>0)$this->_limit = $limit;
        return $this;
    }
    /**
     * 分页限制
     * @param int $page
     * @return  Db     $this
     */
    public function page(int $page){
        if($page>1)$this->_page = $page;
        return $this;
    }
    /**
     * 分组
     * @param string $groups
     * @return  Db     $this
     */
    public function group(string $groups){
        $groups = (string)$groups;
        if(!empty($groups))$this->_sql .= " GROUP BY {$groups}";
        return $this;
    }

    /**
     * 排序
     * @param  string $orders
     * @return  Db     $this
     */
    public function order(string $orders){
        //order字符串转数组
        $order_arr = explode(',', $orders);
        //临时拼接排序sql
        $order_sql = '';
        foreach ($order_arr as $key => $value) {
            if(empty($value))break;
            //去掉两端的空格，和中间多余的空格
            $value = trim($value);
            $value = preg_replace('/(\s)\1+/u','$1',$value);
            $value_arr = explode(' ',$value);
            //转换排序类型为大写
            if(isset($value_arr[1])&&!empty($value_arr[1])){
                $value_arr[1] = strtoupper($value_arr[1]);
                //不合法的排序类型转换为ASC降序
                if(!in_array(strtoupper($value_arr[1]),['ASC','DESC']))$orderType = "ASC";
                else $orderType = strtoupper($value_arr[1]);
            }
            else $orderType = "ASC";
            //组合排序sql
            if(!empty($order_sql))$order_sql .=",{$value_arr[0]} {$orderType}";
            else $order_sql .=" ORDER BY {$value_arr[0]} {$orderType}";
        }
        if($order_sql)$this->_order .= $order_sql;
        return $this;
    }

    //-----------------------------------------------------*/
    // 聚合查询
    //-----------------------------------------------------*/

    /**
     * 统计数量
     * @param string $method
     * @param string field
     * @return number
     * @throws
     */
    public function aggregate(string $method,string $field=''){

        $table = $this->_table;
        $sql   = $this->_sql;
        $bind  = $this->_bind;
        $join  = $this->_join;
        $alias = $this->_alias;
        /** 检测要统计的字段
         */
        if($method=='count' && $field==='')$field = '*';
        else if(!$field)lang()->throwException(107107);
        //拼接sql
        $method = strtoupper($method);
        $sql = "SELECT {$method}({$field}) FROM {$table} {$alias} {$join}".$sql;

        //清理缓存的属性
        $this->_table = '';
        $this->_sql = '';
        $this->_bind = '';
        $this->_join = '';
        $this->_alias = '';

        //执行查询
        $res =  $this->query($sql,$bind);
        if(is_array($res))return current(current($res));
        else return $res;
    }


    //-----------------------------------------------------*/
    // 联表
    //-----------------------------------------------------*/

    /**
     * @param  mixed   $table
     * @param  string  $cond
     * @param  string  $joinType
     * @return  Db     $this
     * @throws \Exception
     */
    public function join($table='', string $cond='', string $joinType=''){
        if(is_array($table))
        {
            foreach ($table as $value)
            {
                $_table   = isset($value[0])?$value[0]:'';
                $_cond    = isset($value[1])?$value[1]:'';
                $_joinType = isset($value[2])?$value[2]:'';
                if(!in_array(strtoupper($_joinType),['LEFT','RIGHT','INNER','FULL']))throw new \Exception('join type error!',1);
                $_joinType = strtoupper($_joinType);
                $this->_join .= " {$_joinType} JOIN {$_table} ON {$_cond} ";
            }
        }
        else{
            if(!in_array(strtoupper($joinType),['LEFT','RIGHT','INNER','FULL']))throw new \Exception('join type error!',1);
            $this->_join .= " {$joinType} JOIN {$table} ON {$cond} ";
        }
        return $this;
    }

    //-----------------------------------------------------*/
    // 事务
    //-----------------------------------------------------*/

    /**
     * 启动事务
     * @throws
     */
    public function startTrans(){
        try{
            $connInfo = cor_context('swoolex_master_mysql');
            $conn = $connInfo['conn'];
            $transTimes = cor_context('swoolex_trans_times');
            $transTimes++;
            //首次，启动事务
            if(1 == $transTimes){
                //启动事务
                $res = $conn -> beginTransaction();           
            }
            //其余仅仅保存做标记
            else if($transTimes>1){
                //设置事务标记
                $savePoint = 'trans'.$transTimes;
                $conn -> exec("SAVEPOINT {$savePoint}");

            }
            cor_context('swoolex_trans_times',$transTimes);

            return $res;
        } catch (\Throwable $e) {

            if ($this->isBreak($e)) {
                $connInfo = config('swoolex_mysql_pool')->newConnection('master');
                cor_context('swoolex_master_mysql',$connInfo);
                return $this->startTrans();
            }
            throw $e;
        }
    }

    /**
     * 提交事务
     * @throws
     */
    public function commit(){
        try{
            $transTimes = cor_context('swoolex_trans_times');
            $connInfo   =  cor_context('swoolex_master_mysql');
            $conn = $connInfo?$connInfo['conn']:null;
            if($conn){

                //首次，启动事务的，直接提交事务
                if(1 == $transTimes){
                    $res = $conn -> commit();
                }
                else{
                    //事务次数自减
                    $transTimes --;
                    cor_context('swoolex_trans_times',$transTimes);
                }
            }
            return $res;
        } catch (\Throwable $e) {

            if ($this->isBreak($e)) {
                $connInfo = config('swoolex_mysql_pool')->newConnection('master');
                cor_context('swoolex_master_mysql',$connInfo);
                return $this->commit();
            }
            throw $e;
        }
    }
    /**
     * 回滚事务
     * @throws
     */
    public function rollback(){
        try{
            $transTimes = cor_context('swoolex_trans_times');
            $connInfo   =  cor_context('swoolex_master_mysql');
            $conn = $connInfo?$connInfo['conn']:null;
            if($conn){
                //首次，启动事务
                if(1 == $transTimes){
                    //回滚事务
                    $res = $conn -> rollBack();
                }
                //其余回滚到当前的事务起始点,事务次数自减
                else if($transTimes>1){

                    $savePoint = 'trans'.$transTimes;
                    $res = $conn -> exec("ROLLBACK TO SAVEPOINT {$savePoint}");

                    $transTimes --;
                    cor_context('swoolex_trans_times',$transTimes);
                }
            }
            return $res;   
        } catch (\Throwable $e) {

            if ($this->isBreak($e)) {
                $connInfo = config('swoolex_mysql_pool')->newConnection('master');
                cor_context('swoolex_master_mysql',$connInfo);
                return $this->rollback();
            }
            throw $e;
        }

    }

    /**
     * 是否断线
     * @access protected
     * @param $e \PDOException|\Exception 异常对象
     * @return bool
     */
    protected function isBreak($e)
    {
        $info = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'failed with errno',
        ];

        $error = $e->getMessage();

        foreach ($info as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        return false;
    }

}