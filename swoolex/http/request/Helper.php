<?php
/**
 * Http原始请求助手函数
 * User: smally
 * Date: 2019-07-23
 * Time: 17:33
 */
namespace Swoolex\http\request;


class Helper {


    /**
     * 请求的uri路径
     * @var string|string[]|null
     */
    protected $_uri = '';

    /**
     * 构造函数
     */
    public function __construct(){
        $uri = request()->server['request_uri'];
        $uri = preg_replace('/(\/)\1+/u','$1',$uri);
        $this->_uri = $uri;
    }
 	/**
 	 * 获取对应类型的值
     * e.g: 'get'-获取get的全部数值,'get.id'-获取get中id的值,'?get'-判断是否get有值,'?get.id'
 	 */
 	public function value($requestType = ''){
        $requestValue = [];
        /*要获取的变量类型type和变量key*/
        $requestType = preg_replace('/(\.)\1+/u','$1',$requestType);
		$arr = explode('.', $requestType);
		$req_type = isset($arr[0])?$arr[0]:'';
        $req_type = preg_replace('/(\?)\1+/u','$1',$req_type); //去除多余的问号，在检测变量是否存在有用(?get.name)
        $req_real_type = str_replace('?', '', $req_type);
        $req_key  = isset($arr[1])?$arr[1]:'';
        //获取类型和请求类型均转为小写处理
        $req_real_type = strtolower($req_real_type);
        $requestMethod = strtolower(request()->server['request_method']);
        //默认获取请求类型的全部变量
        if('' === $requestType ){
            if('get'==$requestMethod || 'post'==$requestMethod){
                $requestValue = request()->$requestMethod;
            }
        }
        else if('get' == $req_real_type)$requestValue = request()->get;
        else if('post' == $req_real_type)$requestValue = request()->post;
        else if('put'  == $req_real_type){
            if('put'==$requestMethod){
                $requestValue = request()->rawContent();
            }
        }
        else if('delete' == $req_real_type){
            if('delete'== $requestMethod){
                $requestValue = request()->rawContent();
            }
        }
        else if('patch' == $req_real_type){
            if('patch'== $requestMethod){
                $requestValue = request()->rawContent();
            }
        }
        else if('cookie' == $req_real_type){
                $requestValue = request()->cookie;
        }
        else if('server' == $req_real_type){
            $requestValue = request()->server;
        }
        else if('header' == $req_real_type){
            $requestValue = request()->header;
        }
        else if('file' == $req_real_type){
            $files = request()->files;
            foreach ($files as $key => &$file)
            {
                $requestValue[$key] = new File($file);
            }
        }
        else if('raw' == $req_real_type){
            $requestValue = request()->rawContent();
        }
        /*检测变量是否存在*/
        if(isset($req_type[0]) && '?' == $req_type[0]){
            if(!empty($req_key))return isset($requestValue[$req_key])?true:false;
            else return isset($requestValue)?true:false;

        }else{
            if(!empty($req_key))return isset($requestValue[$req_key])?$requestValue[$req_key]:null;
            else return isset($requestValue)?$requestValue:null;
        }
 	}
    /**
     * 获取模块
     * @return string|null
     */
    public  function module(){
        if(!config('app.app_multi_module'))return null;
        $arr = explode('/', $this->_uri);
        return isset($arr[1])?$arr[1]:null;
    }
    /**
     * 获取控制器
     * @return string|null
     */
    public  function controller(){
        $arr = explode('/', $this->_uri);
        if(!config('app.app_multi_module'))return isset($arr[1])?$arr[1]:null;
        return isset($arr[2])?$arr[2]:null;
    }
    /**
     * 获取操作名称
     * @return string|null
     */
    public  function method(){
        $arr = explode('/', $this->_uri);
        if(!config('app.app_multi_module'))return isset($arr[2])?$arr[2]:null;
        return isset($arr[3])?$arr[3]:null;
    }


}