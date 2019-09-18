<?php
/**
 * Http原始请求对象.
 * User: smally
 * Date: 2019-07-23
 * Time: 17:33
 */

namespace Swoolex\http\request;

/**
 * @property array $header
 * @property array $server
 * @property array $get
 * @property array $post
 * @property array $cookie
 * @property array $files
 * @method   rawContent() string
 * @method   getData()    string
 */
class Request
{
    private $request = null;
    /**
     * Request constructor.
     */
    public function __construct()
    {
        //调用初始化方法
        $this->request = sess_context('swoolex_request');
    }

    /**
     * 获取属性值
     */
    public function __get($name)
    {
        return sess_context('swoolex_request')->$name;
    }
    /**
     * 匿名调用，可以调取原始swoole架构request所支持的方法
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return sess_context('swoolex_request')->$method(...$arguments);
    }

}