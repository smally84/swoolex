<?php
    //APP类声明
    use Swoolex\App;
    try{
        //开启stream流协程
        \Swoole\Runtime::enableCoroutine();
        //自动加载工具
        require_once(__DIR__.'/../vendor/autoload.php');
        //应用初始化
        App::init();
        //启动应用
        App::start();
    }catch (\Throwable $e){
       $msg = 'msg:'.$e->getMessage().',file:'.$e->getFile().',line:'.$e->getLine();
       file_put_contents(__DIR__.'/../runtime/boot.log',$msg."<br>\r\n",FILE_APPEND);
    }
