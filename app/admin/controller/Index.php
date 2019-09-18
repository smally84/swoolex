<?php
namespace app\admin\controller;

use Swoolex\Validate;

class Index extends \Swoolex\http\View
{
    public function hello()
    {
        text('helloworld');
    }
    public function config(){
        config('a0',['a00'=>1,'a01'=>['a010'=>[1,2],'a011'=>3]]);
        dump(config('a0'));
        config('a0.a01.a011',4);
        dump(config('a0'));
        config('a0.a01.a011',null);
        dump(config('a0'));
    }
    public function env()
    {
        dump(config('db_mysql_options'));
    }
    /**
     * helloworld
     */
    public function index()
    {
        $this->assign('name','swoolex,Hello World!');
        $this->assign('title','Swoolex');
        $this->fetch(__DIR__ . '/hello.html');
    }
    /** 打印输出
     */
	public function dump()
    {
        $a = ['method'=>'dump'];
        json($a);
        dump('swoolex dump!');
    }
    /** Aop
     */
    public function aop()
    {
        $a = ['method'=>'aop'];
        json($a);
    }
    /** Aop Exception
     */
    public function aopException()
    {
        dump('aopException');
        throw new \Exception('aopException');
    }
    /**
     */
    static function console()
    {
        console('test');
    }
    function defer()
    {
        console(1222);
        defer(function (){
            echo 1;
            console(1);
        });
        go(function (){
            defer(function (){
                echo '2';
                console(2);
            });
        });
    }
    function mg(){
        go(function (){
            dump('go1');
            $manager = new \MongoDB\Driver\Manager("mongodb://localhost:27017",[
                'username'=>'root',
                'password'=>'dR8TtJzz0819',
                'db'=>'znji'
            ]);
            $query = new \MongoDB\Driver\Query(['name' => ['$gt' => 1]]);
            $cursor = $manager->executeQuery('znji.col1', $query);
            foreach ($cursor as $document) {
                dump($document);
            }
//            sleep(2);
            dump('go1-end');
            response()->end();
        });
        go(function (){
            dump('go2');
            $manager = new \MongoDB\Driver\Manager("mongodb://localhost:27017",[
                'username'=>'root',
                'password'=>'dR8TtJzz0819',
                'db'=>'znji'
            ]);
            $query = new \MongoDB\Driver\Query(['name' => ['$gt' => 1]]);
            $cursor = $manager->executeQuery('znji.col1', $query);
            foreach ($cursor as $document) {
                dump($document);
            }
//            sleep(1);
            dump('go2-end');
        });
    }
    public function test()
    {
        $a = ['a','b','c'];
        foreach ($a as $key=>$value)
        {
            if($key==1)unset($a[$key]);
        }
    }
}