<?php
// +----------------------------------------------------------------------
// | Swoolex - 默认配置
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://swoolex.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: smally<smally84@126.com>
// +----------------------------------------------------------------------
return [

    // 默认时区
    'default_timezone'       => 'PRC',

    //session
    'session'         => [
        'name'   => 'SWXSESSID',
        'expire' =>  3600,
    ],

    // +----------------------------------------------------------------------
    // | 定时任务   time=>'class:method'
    // +----------------------------------------------------------------------
    'crontab' =>[
        //'* * * * * *' => 'app\controller\Test:crontabTest',
    ],
    // +----------------------------------------------------------------------
    // | 异步任务   type=>'class:method'
    // +----------------------------------------------------------------------
    'task' =>[
//        'swoolex_console_publish'=>'Swoolex\Console:publish',
    ],

];