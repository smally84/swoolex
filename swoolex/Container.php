<?php
// +----------------------------------------------------------------------
// | Swoolex 容器管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex;

class Container {

    /**
     * 容器内所管理的所有实例
     * @var array
     */
    protected static $instances = [];

    /**
     * 获取容器实例
     * @param   string   $class 实例类名
     * @param   bool     $isSingleton 是否为单例，默认非单例
     * @param   array    $ConstructorParameter 类的构造参数或闭包函数参数
     * @param   array    $property 要注入的属性
     * @return  mixed
     * @throws
     */
    public function get($class,bool $isSingleton = false ,array $constructorParameter = [],array $property=[]){
        /** 闭包函数
         */
        if ($class instanceof \Closure)
        {
            //直接返回执行结果
            return $class(...$constructorParameter);
        }
        /** 类
         */
        else {
            // 如果已经实例化，则返回实例
            if (isset(self::$instances[$class]))return self::$instances[$class];
            // 未实例化的
            else {
                // 使用传入的参数自动实例化，并注入属性
                if( false===$isSingleton && $constructorParameter){
                    $object = new $class(...$constructorParameter);
                    if($property){
                        foreach ($property as $propertyKey => $propertyValue)
                        {
                            $object->$propertyKey = $propertyValue;
                        }
                    }
                }
                // 未传入构造器参数的，自动构造器依赖注入，并注入属性
                else {
                    $object = $this->resolve($class,$constructorParameter,$property);
                    if($property){
                        foreach ($property as $propertyKey => $propertyValue)
                        {
                            $object->$propertyKey = $propertyValue;
                        }
                    }
                }
                if(is_object($object))
                {
                    //如果是单例模式，则保存到ioc容器中
                    if($isSingleton) self::$instances[$class] = $object;
                    return $object;
                }
            }
        }

    }
    /**
     * 解决依赖
     * @param string $class
     * @return
     * @throws
     */
    private function resolve($class,$constructorParameter,$property)
    {
        /** 创建类的反射类
         */
        $reflector = new \ReflectionClass($class);
        /** 判断类是否可以实例化
         */
        if (!$reflector->isInstantiable()) {
            throw lang()->throwException(102002);
        }
        /** 获取类的构造函数
         */
        $constructor = $reflector->getConstructor();
        /** 无构造函数的，直接返回实例化对象
         */
        if (is_null($constructor)) {
            return new $class;
        }
        /** 获取构造函数的参数
         */
        $parameters = $constructor->getParameters();
        /** 递归解析构造函数的参数
         */
        $dependencies = $this->getDependencies($parameters);
        //创建一个类的新实例，给出的参数将传递到类的构造函数。
        return $reflector->newInstanceArgs($dependencies);
    }
    /**
     * 获取构造函数的参数依赖并递归自动实例化 
     * @param  array $parameters
     * @return array
     * @throws
     */
    private function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            /** 获取参数对应的类定义
             */
            $dependency = $parameter->getClass();
            /** 不是类的，获取其默认值
             */
            if (null === $dependency) {
                // 检查是否有默认值
                if ($parameter->isDefaultValueAvailable()) {
                    // 获取参数默认值
                    $dependencies[] = $parameter->getDefaultValue();
                } else lang()->throwException(102001,"{$parameter->name}");
            }
            /** 是类的，递归解析
             */
            else
            {
                //获取类的名称，并进行实例化解析
                $dependencies[] = $this->get($dependency->name);
            }
        }
        return $dependencies;
    }


}