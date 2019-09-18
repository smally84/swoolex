<?php
namespace Swoolex\i18n;
/**
 * 多语言操作类
 * User: smally
 * Date: 2019-09-05
 * Time: 13:01
 */
class Lang
{
    /**
     * 获取多语言
     * @param string|number $code 语言码
     * @param string $type 语言类型，默认为配置文件的类型
     * @return string
     */
    public function getMsg($code,$type='')
    {
        if($type==='')$type = config('default_lang');
        if(isset(config('lang')[$code][$type]))return config('lang')[$code][$type];
        else return '';
    }
    /**
     * 抛出异常语言
     * @param string|number $code  语言码
     * @param string $type 语言类型，默认为配置文件的类型
     * @return string
     * @throws
     */
    public function throwException(string $code,string $type='')
    {
        if($type==='')$type = config('default_lang');
        $msg = $this->getMsg($code);
        throw new \Exception($msg."-{$code}",$code);
    }
}