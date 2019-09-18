<?php
// +----------------------------------------------------------------------
// | Swoolex - http json格式响应输出
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
namespace Swoolex\http\response;

class Json {

    /** 输出参数
     */
    protected $options = [
        // 数据编码
        'encoding'  => 'utf-8',
    ];

	/**
	 * json格式输出
	 */
	public function output($data){

		return json_encode($data);
	}

}