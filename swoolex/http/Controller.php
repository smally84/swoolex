<?php
// +----------------------------------------------------------------------
// | Swoolex 控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------

namespace Swoolex\http;

class Controller
{
    /**
     * 代理执行View assign
     *
     */
    public function assign($field,$value = '')
    {
        $View = new View();
        $View -> assign($field,$value);
    }
    /**
     * 模板渲染
     * @param string $template 模板路径
     */
    public function fetch($template)
    {
        $View = new View();
        $View -> fetch($template);
    }
    /**
     * 内容渲染
     * @param string $content 内容
     */
    public function display($content)
    {
        $View = new View();
        $View -> display($content);
    }
}