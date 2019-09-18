<?php
// +----------------------------------------------------------------------
// | Swoolex session
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
declare(strict_types = 1);

namespace Swoolex\http;

/**
 * Class Session
 * @package Swoolex\server\http
 */
class Session {

    //单例对象变量
    public static $_instance;

	//sessionSaveKey
    private  $_sessionSaveKey = '';

    /**
     * 单例-防止构造函数创建对象
     */
    private function __construct(){

    }
    /**
     * 获public*/
    static function getInstance(){

         if (!self::$_instance instanceof self) {
              self::$_instance = new self();
         }
         self::$_instance->init();
         return self::$_instance;
    }

	/**
	 * 初始化函数，检测客户端是否分配了sessionId，系统会自动分配
	 */
	public function init(){
	    //获取session的名字
        $sessionName = config('session.name');
		//判断客户端是否携带sessionid
		$cookies = input('cookie');
		//获取session分配的id，然后判断redis是否已经缓存
		if(isset($cookies[$sessionName])&&!empty($cookies[$sessionName]))
		{
			$sessionId = $cookies[$sessionName];
            $sessionSaveKey = config('app.name').':'.$sessionName.':'.$sessionId;
            $this->_sessionSaveKey = $sessionSaveKey;
			if(redis()->exists($sessionSaveKey))return;
		}
        /**客户端未分配sessinId，系统自动分配
         */
		while(1)
        {
			$sessionId = md5(time().mt_rand(1000000000,9999999999));
            $sessionSaveKey = config('app.name').':'.$sessionName.':'.$sessionId;
            $this->_sessionSaveKey = $sessionSaveKey;
			if(!redis()->exists($sessionSaveKey))break;
		}

		/**设置cookie expire设置为0，在浏览器关闭时自动失效
		 */
		response()->cookie($sessionName, $sessionId, $expire = 0, $path = '/','',false, true);

		/** Redis缓存sessionID
		 */
		redis()->hSet($this->_sessionSaveKey,'create_time',time());
        redis()->expire($this->_sessionSaveKey,config('session.expire'));
	}

	/**
	 * 设置session
 	 */
	public function set(string $key,$value){
	    $res = redis()->hSet($this->_sessionSaveKey,$key,$value);
		redis()->expire($this->_sessionSaveKey,config('session.expire'));
		return $res;
	}
	/**
	 * 获取session
	 */
	public function get(string $key){
        redis()->expire($this->_sessionSaveKey,config('session.expire'));
		return redis()->hGet($this->_sessionSaveKey,$key);
	}
	/**
	 * 清空session
	 */
	public function delete($key){
		if(!empty($key)){
			return redis()->hDel($this->_sessionSaveKey,$key);
		}
		else if(null == $key){
			return redis()->del($this->_sessionSaveKey);
		}
	}




}