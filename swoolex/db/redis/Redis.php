<?php
// +----------------------------------------------------------------------
// | Redis 操作逻辑层
// +----------------------------------------------------------------------
// | Author: smally <xl_wang@coolshow.com.cn>
// +----------------------------------------------------------------------

namespace Swoolex\db\redis;

use Swoolex\db\pool\RedisPool;
/**
 * @method append
 * @method bitop
 * @method decr
 * @method decrby
 * @method getset
 * @method incr
 * @method incrby
 * @method incrbyfloat
 * @method mset
 * @method msetnx
 * @method set
 * @method setbit
 * @method setex
 * @method psetex
 * @method setnx
 * @method setrange
 * @method del
 * @method delete(string $key)
 * @method unlink
 * @method expire
 * @method settimeout
 * @method pexpire
 * @method expireat
 * @method pexpireat
 * @method move
 * @method persist
 * @method rename
 * @method renamekey
 * @method renamenx
 * @method sort
 * @method restore
 * @method hdel
 * @method hincrby
 * @method hincrbyfloat
 * @method hmset
 * @method hset
 * @method hsetnx
 * @method blpop
 * @method brpop
 * @method brpoplpush
 * @method linsert
 * @method lpop
 * @method lpush
 * @method lpushx
 * @method lrem
 * @method lremove
 * @method lset
 * @method ltrim
 * @method listtrim
 * @method rpop
 * @method rpoplpush
 * @method rpush
 * @method rpushx
 * @method sadd
 * @method sdiffstore
 * @method sinter
 * @method sinterstore
 * @method smove
 * @method spop
 * @method srem
 * @method sremove
 * @method sunionstore
 * @method zadd
 * @method zincrby
 * @method zinter
 * @method zrem
 * @method zdelete
 * @method zremrangebyrank
 * @method zdeleterangebyrank
 * @method zremrangebyscore
 * @method zdeleterangebyscore
 * @method zunion
 * @method bitcount
 * @method get
 * @method getbit
 * @method getrange
 * @method mget
 * @method getmultiple
 * @method strlen
 * @method dump
 * @method exists
 * @method keys
 * @method getkeys
 * @method scan
 * @method object
 * @method randomkey
 * @method type
 * @method ttl
 * @method pttl
 * @method hexists
 * @method hget
 * @method hgetall
 * @method hkeys
 * @method hlen
 * @method hmget
 * @method hvals
 * @method hscan
 * @method hstrlen
 * @method lindex
 * @method lget
 * @method llen
 * @method lsize
 * @method lrange
 * @method lgetrange
 * @method scard
 * @method ssize
 * @method sdiff
 * @method sismember
 * @method scontains
 * @method smembers
 * @method sgetmembers
 * @method srandmember
 * @method sunion
 * @method sscan
 * @method zcard
 * @method zsize
 * @method zcount
 * @method zrange
 * @method zrangebyscore
 * @method zrevrangebyscore
 * @method zrangebylex
 * @method zrank
 * @method zrevrank
 * @method zrevrange
 * @method zscore
 * @method zscan
 */
class Redis {

    //数据库分区
	public $_database = 0;
    //前缀
    public $_prefix   = '';
    //超时时间
    public $_expire   = '';


    //读写操作定义分类
    private static $OPERATION_TYPE = [

        'write'=>[
            //STRINGS
            'APPEND','BITOP','DECR','DECRBY','GETSET','INCR','INCRBY','INCRBYFLOAT','MSET','MSETNX','SET','SETBIT','SETEX','PSETEX','SETNX','SETRANGE',
            //KEYS
            'DEL','DELETE','UNLINK','EXPIRE','SETTIMEOUT','PEXPIRE','EXPIREAT','PEXPIREAT','MOVE','PERSIST','RENAME','RENAMEKEY','RENAMENX','SORT','RESTORE',
            //HASHES
            'HDEL','HINCRBY','HINCRBYFLOAT','HMSET','HSET','HSETNX',
            //LISTS
            'BLPOP','BRPOP','BRPOPLPUSH','LINSERT','LPOP','LPUSH','LPUSHX','LREM','LREMOVE','LSET','LTRIM','LISTTRIM','RPOP','RPOPLPUSH','RPUSH','RPUSHX',
            //SETS 
            'SADD','SDIFFSTORE','SINTER','SINTERSTORE','SMOVE','SPOP','SREM','SREMOVE','SUNIONSTORE',
            //SORTED SETS
            'ZADD','ZINCRBY','ZINTER','ZREM','ZDELETE','ZREMRANGEBYRANK','ZDELETERANGEBYRANK','ZREMRANGEBYSCORE','ZDELETERANGEBYSCORE','ZUNION',
        ],
        'read'=>[
            //STRINGS
            'BITCOUNT','GET','GETBIT','GETRANGE','MGET','GETMULTIPLE','STRLEN',
            //KEYS
            'DUMP','EXISTS','KEYS','GETKEYS','SCAN','OBJECT','RANDOMKEY','TYPE','TTL','PTTL',
            //HASHES
            'HEXISTS','HGET','HGETALL','HKEYS','HLEN','HMGET','HVALS','HSCAN','HSTRLEN',
            //LISTS
            'LINDEX','LGET','LLEN','LSIZE','LRANGE','LGETRANGE',
            //SETS
            'SCARD','SSIZE','SDIFF','SISMEMBER','SCONTAINS','SMEMBERS','SGETMEMBERS','SRANDMEMBER','SUNION','SSCAN',
            //SORTED SETS
            'ZCARD','ZSIZE','ZCOUNT','ZRANGE','ZRANGEBYSCORE','ZREVRANGEBYSCORE','ZRANGEBYLEX','ZRANK','ZREVRANK','ZREVRANGE','ZSCORE','ZSCAN',
        ],
    ];
    /**
     * 单例-防止构造函数创建对象
     */
    public function __construct(){

    }
    /**
     * redid钩子函数
     */
    public function __call($method,$arguments) 
    {

        /** 获取连接池实例
         */
        if(null == config('swoolex_redis_pool'))
        {
            config('swoolex_redis_pool', RedisPool::getInstance());
        }

        /** 创建redis协程主连接
         */
        $masterRedis = cor_context('swoolex_master_redis');
        if(!$masterRedis){
            $connInfo = config('swoolex_redis_pool')->get('master');
            cor_context('swoolex_master_redis',$connInfo);
        }
        /** 创建redis协程从连接
         */
        $slaveRedis = cor_context('swoolex_slave_redis');
        if(!$slaveRedis){
            $connInfo = config('swoolex_redis_pool')->get('slave');
            cor_context('swoolex_slave_redis',$connInfo);
        }

        /**资源释放函数注册
          */
        if(!cor_context('swoolex_redis_defer')){

            \defer(function(){
                //释放主连接
                $masterRedis = cor_context('swoolex_master_redis');
                if($masterRedis){
                    config('swoolex_redis_pool')->put($masterRedis);
                    cor_context('swoolex_master_redis',null);
                }
                //释放从连接
                $slaveRedis = cor_context('swoolex_slave_redis');
                if($slaveRedis){
                    config('swoolex_redis_pool')->put($slaveRedis);
                    cor_context('swoolex_slave_redis',null);
                }
            });

            cor_context('swoolex_redis_defer',1);
        }

        /**select方法处理
          */
        if('select' == $method){
            cor_context('swoolex_redis_database',$arguments[0]);
        }

        /**获取从数据库连接-读操作
         */
        else if(in_array(strtoupper($method), self::$OPERATION_TYPE['read'])){

            //获取连接
            $slaveRedis = cor_context('swoolex_slave_redis');
            //检测断开重连
            $conn = $slaveRedis?$slaveRedis['conn']:null;
            if("+PONG"!=$conn->ping()){
                $conn->connect();
            }
            //选择数据库
            $redisDatabase = cor_context('swoolex_redis_database');
            if($redisDatabase)$conn->select($redisDatabase);
            //执行操作
            return $conn -> $method(...$arguments); 

        }
        /**获取主数据库连接-写操作
         */
        else 
        {
            //获取连接
            $masterRedis = cor_context('swoolex_master_redis');
            //检测断开重连
            $conn = $masterRedis?$masterRedis['conn']:null;
            if("+PONG"!=$conn->ping()){
                $conn->connect();
            }
            //选择数据库
            $redisDatabase = cor_context('swoolex_redis_database');
            if($redisDatabase)$conn->select($redisDatabase);
            //执行操作
            return $conn -> $method(...$arguments);
        }
    }


}