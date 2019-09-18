<?php
// +----------------------------------------------------------------------
// | Swoolex - 全局配置 -  进程内有效
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
//强类型约束
declare(strict_types = 1);
namespace Swoolex\config;

use Swoolex\XLang;

class Config {

	//配置参数存储池
	static $pool = [];

	/**
	 * 获取配置 - 支持多级值获取（‘key1.key1_1.key1_1_1’）
	 * @param  $key string 配置项
	 * @return mixed
	 */
	static function get(string $key)
	{
		//转换为数组层级
		$keyArray = explode('.', $key);
		// 非多级获取值的，直接返回
		if(count($keyArray)==1){
		    if(isset(self::$pool[$key]))return self::$pool[$key];
		    else return null;
        }
		// 依次取出各级的值，如果某级的值为null，直接返回。
		else {
		    foreach($keyArray as $keyIndex => $keyValue)
            {
                //先取出一级的值
                if($keyIndex==0)
                {
                    if(isset(self::$pool[$keyValue])){
                        $getValue = self::$pool[$keyValue];
                        continue;
                    }
                    else return null;
                }
                // 在一级的基础上，陆续取出各级值
                else
                {
                    if(isset($getValue[$keyValue]))$getValue = $getValue[$keyValue];
                    else return null;
                }
            }
        }
		return $getValue;
	}
	/**
	 * 动态配置 - 支持多级配合（‘key1.key1_1.key1_1_1’）
	 * @param   $key    string   配置项
	 * @param   $value  mixed    配置值
	 * @return  bool true/false
	 */
	static function set(string $key,$value)
	{
        $config  = [];
        if($key=='')return false;
		//配置项嵌套设置
		$keyArray = explode('.', $key);
        $primaryKey = $keyArray[0];
		if(count($keyArray)==1)$config[$primaryKey] = $value;
		else{
		    $pconfig = []; //配置指针，用于指向上一次的配置
            $oconfig =  self::get($primaryKey); //首次循环，先获取原始配置
            foreach ($keyArray as $keyIndex => $keyValue)
            {
                if($keyIndex < count($keyArray)-1)
                {
                    //首次获取原始配置值
                    if($keyIndex==0){
                        /*如果该项有值，则赋给新配置项，否则新配置项直接赋值空数组*/
                        if(is_array($oconfig))$config[$keyValue] = $oconfig;
                        else $config[$keyValue] = [];
                        /*开始地址引用，后续都是在地址引用的基础上进行操作*/
                        $pconfig[$keyIndex] = &$config[$keyValue];
                    }
                    else {
                        /*如果该项有值，则赋给新配置项，否则新配置项直接赋值空数组*/
                        if(isset($oconfig[$keyValue])&&is_array($oconfig[$keyValue]))
                        {
                            $pconfig[$keyIndex-1][$keyValue] = $oconfig[$keyValue];
                            $oconfig = $oconfig[$keyValue];
                        }
                        else {
                            $pconfig[$keyIndex-1][$keyValue] = [];
                            $oconfig = [];
                        }
                        /*设置当前指针*/
                        $pconfig[$keyIndex] = &$pconfig[$keyIndex-1][$keyValue];
                    }
                }
                else if($keyIndex = count($keyArray)-1)
                {
                    $pconfig[$keyIndex-1][$keyValue] = $value;
                }
            }
        }
		//保存至存储池
		self::$pool[$primaryKey] = $config[$primaryKey];
		//取判断是否设置成功
		if($value == self::get($key))return true;
		else return false;
	}
	/**
	 * 删除配置 - 支持多级删除（‘key1.key1_1.key1_1_1’）
	 * @param  $key string 配置项
	 * @return bool true/false
     *     p0 -> config->k0
     *     p1 -> config->k0->k1 即p[0][k1]
	 */
	static function delete(string $key):bool
	{
		$delItem = null;
        $exists = self::get($key);
        //判断配置项是否存在，如果不存在直接返回删除成功
        if(!isset($exists))return true;
        //非多级删除，则直接删除
        $keyArray = explode('.', $key);
        $primaryKey = $keyArray[0];
        if(count($keyArray)==1)unset(self::$pool[$primaryKey]);
        //如果为多级删除，遍历找到对应的配置项，删除
        else{
            //配置指针，用于指向上一次的配置
            $pconfig = [];
            //获取原有配置
            $config  = self::$pool[$primaryKey];
            foreach ($keyArray as $keyIndex => $keyValue) {
                if($keyIndex < count($keyArray)-1)
                {
                    if($keyIndex==0)$pconfig[$keyIndex] = &$config;
                    else {
                        $pconfig[$keyIndex] = &$pconfig[$keyIndex-1][$keyValue];
                    }
                }
                else {
                    unset($pconfig[$keyIndex-1][$keyValue]);
                }
		    }
            self::$pool[$primaryKey] = $config;
        }
		//判断是否删除成功
		if(null == self::get($key))return true;
		else return false;
	}
}