<?php
/**
 * Http响应辅助类.
 * User: smally
 * Date: 2019-07-23
 * Time: 17:20
 */

namespace Swoolex\http\response;

class Helper
{
    /**
     * 代理设置status
     */
    public function status(int $http_status_code)
    {
        sess_context('swoolex_response_status',$http_status_code);
        (new Response())->status($http_status_code);
    }
    /**
     * 代理发送http响应体，结束请求
     * @param mixed $data
     */
    public function end($data,$contentType='text',$finalEnd = true)
    {
        if(!(new Response())->end)
        {
            switch ($contentType)
            {
                case 'text':
                    (new Response())->header("Content-Type","text/html");
                    $finalEnd?(new Response())->end($data):self::write($data);
                    break;
                case 'json':
                    (new Response())->header("Content-Type","text/json");
                    $Json = new Json();
                    $encodeData = $Json->output($data);
                    $finalEnd?(new Response())->end($encodeData):self::write($encodeData);
                    break;
                case 'xml':
                    $Xml = new Xml();
                    $encodeData =  $Xml->output($data);
                    (new Response())->header("Content-Type","text/xml");
                    $finalEnd?(new Response())->end($encodeData):self::write($encodeData);
                    break;
                default:
                    $finalEnd?(new Response())->end($data):sess_context('swoolex_response')->write($data);
            }
        }
    }
    /**
     * 分段输出内容到浏览器客户端
     */
    public function write($data)
    {
        (new Response())->header("Transfer-Encoding","chunked");
        (new Response())->write($data);
    }
}