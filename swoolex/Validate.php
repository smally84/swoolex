<?php
// +----------------------------------------------------------------------
// | Swoolex 参数验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex;

class Validate 
{

    /**
     * @var array
     */
    public $rules = [];

    /**
     * @var string
     */
    public $error = '';

	/**
	 * 构造函数
     */
	function __construct(array $rule){
        $this->rules = $rule;
    }
    /**
     * 获取验证错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
	/**
	 * 参数验证
     * @param array $data
     * @return bool
     * @throws
	 */
	public function check($data)
	{
	    $rules = $this->rules;
	    /** 遍历处理每个规则
	     */
        foreach ($rules as $rule_index=>$rule_vlaue)
        {
            if( isset($rule_vlaue[0]) && isset($rule_vlaue[1]) && isset($rule_vlaue[2]))
            {
                /** 取出每条规则要验证的字段，验证规则，错误信息
                 */
                $field = $rule_vlaue['0'];
                $conds =  explode('|',$rule_vlaue[1]);
                $msgs  =  explode('|',$rule_vlaue[2]);
                foreach ($conds as $key => $cond)
                {
                    if(method_exists(__CLASS__,$cond))
                    {
                        /** 规则验证通过，继续验证下一项
                         */
                        $validateData = isset($data[$field])?$data[$field]:null;
                        $res = $this->$cond($validateData);
                        if($res == true)continue;
                        /** 规则验证不通过，设置错误信息，返回false
                         */
                        else {
                            if(isset($msgs[$key]))
                            {
                                /** 如果错误信息为整数，则取语言码定义
                                 */
                                if((int)($msgs[$key])>0)
                                {
                                    $langMsg = lang()->getMsg($msgs[$key]);
                                    if($langMsg){
                                        $this->error = $langMsg;
                                    }
                                    else $this->error = $msgs[$key];
                                }
                                /** 非整数的去字面错误信息
                                 */
                                else $this->error = $msgs[$key];
                            }
                            else {
                                $this->error = $field.' must '.$cond;
                            }
                            return false;
                        }
                    }
                    /** 规则不存在抛出异常
                     */
                    else lang()->throwException(105001);
                }
            }
            /** 规则设置不规范抛出异常
             */
            else lang()->throwException(105000);
        }
        /** 全部校验通过，返回true
         */
        return true;
	}
    // +----------------------------------------------------------------------
    // | 格式验证
    // +----------------------------------------------------------------------
	/**
	 * required 必须
     * @param string $key
     * @return bool
	 */
	public function required($data)
	{
		return !empty($data) || $data == '0';
	}
    /**
     * boolear
     * @param string $key
     * @return bool
     */
    public function bool($data){
        return is_bool($data);
    }
    /**
     * boolear
     * @param string $key
     * @return bool
     */
    public function boolean($data){
        return is_bool($data);
    }
	/**
	 * 整数
	 */
	public function int($data){
		return is_integer($data);
	}
	/**
	 * 整数
	 */
	public function integer($data){
		return is_integer($data);
	}
	/**
	 * 数字
	 */
	public function number($data){
		return is_numeric($data);
	}
	/**
	 * 浮点
	 */
	public function float($data){
		return is_float($data);
	}
	/**
	 * string 字符串
	 */
	public function string($data)
	{
		return is_string($data);
	}
	/**
	 * 数组
	 */
	public function array($data)
	{
		return is_array($data);
	}
	/**
	 * url
	 */
	public function url($data)
	{
		return filter_var($data, FILTER_VALIDATE_URL);
	}
	/**
	 *  是否为有效的网址
	 */
	public function activeUrl($data){
		return checkdnsrr($data);
	}
	/**
	 * ip
	 */
	public function ip($data)
	{
		return filter_var($data,[FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6]);
	}
	/**
	 * email
	 */
	public function email($data){
		return filter_var($data,FILTER_VALIDATE_EMAIL);
	}
	/**
	 * 日期
	 */
	public function date($data){
		return false !== strtotime($data);
	}
	/**
	 * 只允许字母
	 */
	public function alpha($data){
		return preg_match($data, '/^[A-Za-z]+$/');
	}
	/**
	 * 只允许字母和数字
	 */
	public function alphaNum($data){
		return preg_match('/^[A-Za-z0-9]+$/',$data);
	}
	/**
	 * 只允许字母、数字和下划线 破折号
	 */
	public function alphaDash($data){
		return preg_match('/^[A-Za-z0-9]+$/',$data);
	}
	/**
	 *  只允许汉字
	 */
	public function chs($data){
		return preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$data);
	}
	/**
	 *  只允许汉字、字母
	 */
	public function chsAlpha($data){
		return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u',$data);
	}
	/**
	 *  只允许汉字、字母和数字
	 */
	public function chsAlphaNum($data){
		return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u',$data);
	}
	/**
	 *  只允许汉字、字母、数字和下划线_及破折号-
	 */
	public function chsDash($data){
		return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u',$data);
	}
    // +----------------------------------------------------------------------
    // | 长度和区间
    // +----------------------------------------------------------------------

    public function in($data,$params)
    {

    }
    public function notIn($data,$params)
    {

    }
    public function between($data,$params)
    {

    }
    public function notBetween($data,$params)
    {

    }
    public function length($data,$params)
    {

    }
    public function max($data,$params)
    {

    }
    public function min($data,$params)
    {

    }
}