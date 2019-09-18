<?php
/**
 * redis分区参数设置,防止键值冲突
 * Created by PhpStorm.
 * User: xl_wang
 * Date: 2019-04-08
 * Time: 00:03
 */
return [

    'redis_partition' => [
        //默认设置
        'default' => [
            'prefix'   => config('app.name'),
            'expire'   => '',
            'database' => 0,
        ],
        //缓存设置
        'cache'      => [
            'prefix'   => config('app.name').'cache',
            'expire'   => '',
            'database' => 1,
        ],
        //业务锁设置
        'lock'       => [
            'prefix'   => config('app.name').'lock',
            'expire'   => '',
            'database' => 2,
        ]
    ],
];