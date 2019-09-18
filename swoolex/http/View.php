<?php
// +----------------------------------------------------------------------
// | Swoolex 视图
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\http;

class View
{
	/**
	 * 模板变量赋值
     * @param   mixed  $field 模板变量 string||array
     * @param   string $value 变量赋值
     * @return void
     */
	public function assign($field, string $value='')
    {
        $template_assign = sess_context('swoolex_template_assign');
        if(empty($template_assign))$template_assign = [];
        if(is_string($field))
        {
            $template_assign[$field] = $value;
            sess_context('swoolex_template_assign',$template_assign);
        }
        else if(is_array($field))
        {
            foreach ($field as $index => $value)
            {
                $template_assign[$field] = $value;
                sess_context('swoolex_template_assign',$template_assign);
            }
        }
    }
    /**
     * 模板渲染
     * @param string $template 待渲染的网页模板
     */
    public function fetch(string $template)
    {
        $template_assign = sess_context('swoolex_template_assign');
        $content = file_get_contents($template);
        foreach ($template_assign as $field => $value)
        {
            $content = str_replace('{$'.$field.'}',$value,$content);
        }
        response()->header("Content-Type","text/html");
        response()->end("$content");
    }
    /**
     * 内容渲染
     * @param string $content 待渲染的内容
     */
    public function display(string $content)
    {
        $template_assign = sess_context('swoolex_template_assign');
        foreach ($template_assign as $field => $value)
        {
            $content = str_replace('{$'.$field.'}',$value,$content);
        }
        response()->header("Content-Type","text/html");
        response()->end("$content");
    }
}