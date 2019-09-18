<?php
/**
 * Created by PhpStorm.
 * User: smally
 * Date: 2019-07-20
 * Time: 16:04
 */

namespace Swoolex\server;

use \Swoolex\http\Route;

class HttpInit
{
    /**
     * HttpInit onRequest请求
     * @param $request
     * @param $response
     */
    static function onRequest($request,$response)
    {
        /**保存rootCid
         */
        if(!config('swoolex_root_cids'))config('swoolex_root_cids',[]);
        $rootCids = config('swoolex_root_cids');
        $rootCids[] = \co::getCid();
        config('swoolex_root_cids',$rootCids);

        /**保存请求响应对象，供上下文使用
         */
        sess_context('swoolex_request',$request);   //请求对象上下文资源保存
        sess_context('swoolex_response',$response); //响应对象上下文资源保存

        /**屏蔽Chrome的favicon.ico
         */
        $uri = request()->server['request_uri'];
        if ($uri == '/favicon.ico') {
            response()->status(404);
            response()->end();
        }else{
            /**路由
             */
            $Route = new Route();
            $Route -> route();
        }
    }
}