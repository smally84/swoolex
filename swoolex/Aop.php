<?php
// +----------------------------------------------------------------------
// | Swoolex 切面编程
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------

namespace Swoolex;


Class Aop
{

    /**
     * 代理的类
     * @var null
     */
    private $proxyClass = null;
    /**
     * 代理的对象实例
     * @var null
     */
    private $proxyInstance = null;

    /**
     * 单例
     * @var null
     */
    static $instance = null;

    /**
     * 获取单例对象
     */
    static function getInstance()
    {

        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * 获取切面代理
     * @param string $class
     * @param bool $isSingleton 是否为单例，默认非单例
     * @param array $constructorParameter
     * @param array $property
     */
    public function getProxy(string $class, bool $isSingleton = false , array $constructorParameter = [], array $property=[])
    {
        // 获取要代理的类实例
        $this->proxyInstance = bean($class,$isSingleton,$constructorParameter,$property);

        /** 保存代理的类
         */
        if($class[0]!="\\")$class="\\".$class; //为了和aop配置统一，自动补齐首位反斜杠
        $this->proxyClass = $class;

        /** 返回代理类
         */
        return $this;
    }
    /**
     * 实例方法调用代理
     * @param string $method 类名含命名空间
     * @param mixed  $arguments 方法参数
     * @return mixed
     * @throws
     */
    function __call($method,$arguments){

        /** 连接点和通知的映射集合
         */
        $joinPointAdvice = [
            'before'         =>[],//前置通知
            'after'          =>[],//后置通知
            'afterReturning' =>[],//后置返回通知 - 必须是正常返回才能执行
            'afterThrowing'  =>[],//异常通知
            'around'         =>[],//环绕通知
        ];

        /** 获取切面配置信息
         */
        $aopConf = config('aop');

        /** 获取操作对象的切面配置
         */
        foreach ($aopConf as $aopConfKey => $aopConfValue)
        {
            //代理类的切面配置
            $objAopConf = [];
            if($aopConfKey == '*')$objAopConf =$aopConfValue;
            else if($this->proxyClass==$aopConfKey)$objAopConf =$aopConfValue;
            /** 获取  切入点<->通知集合数组
             */
            foreach ($objAopConf as $pointcuts => $adviceArray)
            {
                /** 查看切入点是否包含当前调用的方法
                 */
                $pointcutArray = explode(',',$pointcuts);
                //切入点为*，则为全部切入点(代理类的全部方法) 或者未特定的方法
                if('*' == $pointcuts || in_array($method,$pointcutArray))
                {
                    /** 遍历通知集合，归类通知类型
                     */
                    foreach($adviceArray as $joinPoint => $advice)
                    {
                        switch ($joinPoint)
                        {
                            //前置通知
                            case 'before':
                                $joinPointAdvice['before'][] = $advice;
                                break;
                            //后置通知 - 无论方法执行是否成功都会被调用
                            case 'after':
                                $joinPointAdvice['after'][] = $advice;
                                break;
                            //后置返回通知 - 必须是正常返回才能执行
                            case 'afterReturning':
                                $joinPointAdvice['afterReturning'][] = $advice;
                                break;
                            //异常通知
                            case 'afterThrowing':
                                $joinPointAdvice['afterThrowing'][] = $advice;
                                break;
                            //环绕通知
                            case 'around':
                                $joinPointAdvice['around'][] = $advice;
                                break;
                        }
                    }
                }
            }
        }
        try {
            //前置切面程序
            foreach ($joinPointAdvice['before'] as $advice)
            {
                $this -> execAdvice($advice);
            }
            //环绕切面程序
            foreach ($joinPointAdvice['around'] as $advice)
            {
                $this -> execAdvice($advice);
            }
            //当前方法
            $res = $this->proxyInstance -> $method(...$arguments);
            //环绕切面程序
            foreach ($joinPointAdvice['around'] as $advice)
            {
                $this -> execAdvice($advice);
            }
            //后置返回切面程序
            foreach ($joinPointAdvice['afterReturning'] as $advice)
            {
                $this -> execAdvice($advice);
            }
        }catch (\Throwable $e){
            //异常切面程序
            foreach ($joinPointAdvice['afterThrowing'] as $advice)
            {
                $this -> execAdvice($advice);
            }
            throw $e;
        }
        //后置切面程序
        foreach ($joinPointAdvice['after'] as $advice)
        {
            $this -> execAdvice($advice);
        }
        return $res;
    }
    /**
     * 执行切面通知
     * @param string $advice 通知方法
     * @throws
     */
    private function execAdvice($advice)
    {
        //解析各个切面程序的类和方法
        $adviceArray = explode(':',$advice);
        if(2!=count($adviceArray))lang()->throwException(103001);
        $advice_class      = trim($adviceArray[0]);
        $advice_method     = trim($adviceArray[1]);
        //判断任务执行的类或方法是否存在
        if(!class_exists($advice_class))lang()->throwException(103002);
        if(!method_exists($advice_class, $advice_method))lang()->throwException(103003);
        //执行切面程序
        $advice_class_instance = new $advice_class();
        $advice_class_instance -> $advice_method();
    }
}