<?php
// +----------------------------------------------------------------------
// | 助手函数
// +----------------------------------------------------------------------
// | Author: smally <xl_wang@coolshow.com.cn>
// +----------------------------------------------------------------------

use \Swoolex\Container;
use \Swoolex\i18n\Lang;
use \Swoolex\config\Config;
use \Swoolex\db\mysql\Db;     //mysql处理类
use \Swoolex\db\redis\Redis;
use \Swoolex\http\request\Request;
use \Swoolex\http\request\Helper as RequestHelper;
use \Swoolex\http\response\Response;
use \Swoolex\http\response\Helper as ResponseHelper;
use \Swoolex\http\Session;
use \Swoolex\http\request\File;
use \Swoolex\http\Cookie;
use \Swoolex\context\CorContext;
use \Swoolex\context\SessContext;
use \Swoolex\asyncTask\TaskHandle;
use \Swoolex\Aop;

/**
 * 从容器中获取实例对象
 * @param string $class 类
 * @param bool   $isSingleton 是否为单例 默认非单例
 * @param array  $constructorParameter 构造函数的参数
 * @param array  $property 注入的属性
 * @return object
 */
function bean($class,$isSingleton=false,$constructorParameter=[],$property=[])
{
    $Container = new Container();
    return $Container->get($class,$isSingleton,$constructorParameter,$property);
}
/**
 * 获取切面代理
 * @param string $class 类
 * @param bool   $isSingleton 是否为单例 默认非单例
 * @param array  $constructorParameter 构造函数的参数
 * @param array  $property 注入的属性
 * @return object
 */
function aop($class,$isSingleton=false,$constructorParameter=[],$property=[])
{
   return  Aop::getInstance()->getProxy($class,$isSingleton,$constructorParameter,$property);
}
/**
 * 获取配置参数
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
function config($key,$value = ''){


    if(isset($key)&&!empty($key)){
        //删除
        if(null === $value){

            return Config::delete($key);
        }
        //获取
        else if('' === $value)
        {
            return Config::get($key);
        }
        //设置
        else
        {
            return Config::set($key,$value);
        }
    }
}
/**
 * 会话变量操作
 * @param mixed $key
 * @param mixed  $value
 * @return mixed
 */
function sess_context($key,$value = '',$root_cid=null){

    if(isset($key)&&!empty($key)){
        //删除协程上下文
        if(null === $value){
            SessContext::delete($key,$root_cid);
        }
        //获取协程上下文的值
        else if('' === $value)
        {
            return SessContext::get($key,$root_cid);
        }
        //设置协程上下文的值
        else
        {
            SessContext::set($key,$value,$root_cid);
        }
    }
    //清除全部协程上下文
    else if(null === $key){
        SessContext::delete(null,$root_cid);
    }
}
/**
 * 协程上下文操作
 * @param mixed $key
 * @param mixed $value
 * @return
 */
function cor_context($key,$value = '',$cid=null){

    if(isset($key)&&!empty($key)){
        //删除协程上下文
        if(null === $value)
        {
            CorContext::delete($key,$cid);
        }
        //获取协程上下文的值
        else if('' === $value)
        {
            return CorContext::get($key,$cid);
        }
        //设置协程上下文的值
        else
        {
            CorContext::set($key,$value,$cid);
        }
    }
    //清除全部协程上下文
    else if(null === $key){
        CorContext::delete(null,$cid);
    }
}

/**
 * 获取tcp请求信息
 */
function receive()
{
    return sess_context('swoolex_receive');
}

/**
 * 获取websocket请求信息
 */
function message()
{
    return sess_context('swoolex_message');
}

/**
 * 获取task任务投递信息
 */
function task()
{
    return sess_context('swoolex_task');
}
/**
 * 获取HTTP请求对象
 * @return
 */
function request()
{
   return new Request();
}
/**
 * 获取请求变量param、get、post、put、delete、session、cookie、request、server、env、file、route
 */
function input(string $args){
    return (new RequestHelper()) -> value($args);
}
/**
 * 获取当前请求的模块
 */
function module()
{
    return (new RequestHelper())-> module();
}

/**
 * 获取当前请求的控制器
 */
function controller()
{
    return (new RequestHelper())->controller();
}

/**
 * 获取当前请求的方法
 */
function method()
{
    return  (new RequestHelper())->method();
}
/**
 * 获取HTTP响应对象
 */
function response()
{
    return new Response();
}

/**
 * session助手函数
 * @param string $key
 * @param mixed  $value
 * @return
 */
function session(string $key,$value='')
{

	$Sess = Session::getInstance();

	if(isset($key)&&!empty($key)){
		//删除session
		if(null === $value){
			$Sess->delete($key);
		}
		//获取session的值
		else if('' === $value)
		{
			return $Sess->get($key);
		}
		//设置session的值
		else
		{
			$Sess->set($key,$value);
		}
	}
	//清除全部session
	else if(null === $key){
		$Sess->delete(null);
	}
}
/**
 * cookie助手函数
 * @return string|null
 * @throws
 */
function cookie(string $key,string $value='',int $expireTime=0,string $path='/',string $domain='',bool $secure=false,bool $httponly = true)
{
    $Cookie = new Cookie();
    //删除全部cookie
    if(null === $key){
        $Cookie->delete(null);
    }
    //删除指定cookie
    else if(null === $value)
    {
        $Cookie->delete($key);
    }
    //获取cookie的值
    else if('' === $value)
    {
        return $Cookie->get($key);
    }
    //设置cookie
    else {
        $Cookie->set($key,$value,$expireTime,$path,$domain,$secure,$httponly);
    }
}
/**
 * 数据库助手函数
 */
function db($table=''){
	$Db = new Db($table);
	return $Db;
}
/**
 * 多语言操作对象
 * @return object $lang
 */
function lang(){
    return bean(Lang::class,true);
}
/**
 * redis助手函数
 * @return object $redis
 */
function redis(){
	return bean(Redis::class,true);
}
/**
 * 添加异步任务函数
 * @param array $params 任务参数
 * @return array success/bool  msg/string
 */
function add_task($params)
{
    $TaskHandle = new TaskHandle();
    return $TaskHandle->addTask($params);
}

/**
 * 控制台打印消息
 */
function console($msgType='',$data=[])
{
    \Swoolex\Console::publish($msgType,$data);
}
/**
 * http调试输出
 */
function dump($data)
{
    if(is_string($data))$str = $data;
    else $str =  var_export($data,true);
    $res = '<pre>'.$str.'</pre>';
    (new ResponseHelper())->write($res);
}
/**
 * http text格式输出
 */
function text($data)
{
    (new ResponseHelper())->end($data);
}
/**
 * http json格式输出
 */
function json($data)
{
    (new ResponseHelper())->end($data,'json');
}
/**
 * http xml格式输出
 */
function xml($data)
{
    (new ResponseHelper())->end($data,'xml');
}
/**
 * 获取文件上传
 * e.g 需先设置安全属性，在执行上传
 */
function upload()
{
    return new File();
}