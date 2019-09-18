<?php
/**
 * Created by PhpStorm.
 * User: smally
 * Date: 2019-08-01
 * Time: 19:03
 */

namespace App\aop;


class AdviceDemo
{
    //demo 前置通知
    public function before()
    {
        dump('aop-Before');
    }
    //demo 后置通知
    public function after()
    {
        dump('aop-After');
    }
    //demo 后置返回通知
    public function afterReturning()
    {
        dump('aop-afterReturning');
    }
    //demo 异常通知
    public function afterThrowing()
    {
        dump('aop-afterThrowing');
    }
    //环绕通知
    public function around()
    {
        dump('aop-around');
    }
}