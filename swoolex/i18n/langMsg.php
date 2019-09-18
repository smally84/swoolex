<?php
/**
 * 多语言配置
 * 100_000 < $code < 200_000 (为框架使用)
 */
return [
    /** Server 101
     */
    101001 => ['zh'=>'连接已断开', 'en'=>'The port is disconnected'],

    /** 容器 102
     */
    102001 => ['zh'=>'无法解析依赖关系','en'=>'Unable to resolve dependencies'],
    102002 => ['zh'=>"类不能实例化", 'en'=>"Can't instantiate this."],
    102003 => ['zh'=>"容器类未配置", 'en'=>"Container class etc cant no be found!"],
    /** Aop 103
     */
    103001 => ['zh'=>"容器类AOP配置有误", 'en'=>"Container class Aop etc error!"],
    103002 => ['zh'=>"容器类AOP配置切入类不存在", 'en'=>"Container class Aop class not exists!"],
    103003 => ['zh'=>"容器类AOP配置切入方法不存在", 'en'=>"Container class Aop method not exists!"],

    /** TcpServer 104
     */
    104    => [],


    /** WebSocketServer 105
     */
    105    => [],


    /** HttpServer 106
     */
    //http服务
    106100 => ['zh'=>'http请求参数有误!', 'en'=>'Helper parameter error!'],
    106101 => ['zh'=>'协程调用结束出现异常！', 'en'=>'Coroutine abnormal end!'],
    106110 => ['zh'=>'设置session的key或value不能为空！', 'en'=>'key or value can not be null when set session!'],
    //controller 1012
    106201 => ['zh'=>'控制器方法不存在','en'=>'Controller method not exists!'],
    //view 101 300
    106301 => ['zh'=>'视图不存在','en'=>'view not exists!'],
    //文件上传 101 400
    106400 => ['zh'=>"文件不存在", 'en'=>"File does not exist"],
    106401 => ['zh'=>"上传错误", 'en'=>"File uploading failed"],
    106402 => ['zh'=>'上传文件太大', 'en'=>"File is too large"],
    106403 => ['zh'=>'文件类型不允许', 'en'=>"File extend is not allowded "],
    106404 => ['zh'=>'文件格式不允许', 'en'=>"File type is not allowed "],


    /** 数据库 107
     */
    //连接池
    107001 => ['zh'=>'请先进行数据库配置', 'en'=>'Please configure the database first!'],
    107002 => ['zh'=>'数据库连接失败!', 'en'=>'Database connect failed!'],
    107003 => ['zh'=>'获取数据库连接失败!', 'en'=>'Get Database connection failed!'],
    107004 => ['zh'=>'数据库主连接信息不能为空！', 'en'=>'Please configure the master database!'],
    107005 => ['zh'=>'数据库预处理绑定的参数必须为数组！','en'=>'PDOStatement::execute input_parameters must be array!'],
    //mysql操作
    107101 => ['zh'=>'待查询比较的字段不能为空！', 'en'=>'The filed to be queried can not be empty!'],
    107102 => ['zh'=>'查询条件格式有误！', 'en'=>'Query condition is not correct!'],
    107103 => ['zh'=>'查询比较暂时支持！', 'en'=>'Query syntax is unsupported!'],
    107104 => ['zh'=>'待操作的数据表不能为空！', 'en'=>'table is empty!'],
    107105 => ['zh'=>'没有可用的字段！', 'en'=>'There is no available fields!'],
    107106 => ['zh'=>'数据表未设置伪删除字段标识！', 'en'=>'There is no softDel field in the table!'],
    107107 => ['zh'=>'集合查询的字段不能为空！', 'en'=>'aggregate query field can not be empty!'],
    //pdo
    107201 => ['zh'=>'PDO预处理出错!', 'en'=>'PDO prepare error!'],
    107202 => ['zh'=>'事务开启失败!', 'en'=>'beginTransaction failed!'],
    107203 => ['zh'=>'事务回滚失败!', 'en'=>'rollbackTransaction failed!'],
    //redis
    107301 => ['zh'=>'redis连接失败!', 'en'=>'redis connect failed!!'],
    107302 => ['zh'=>'redis密码错误!', 'en'=>'redis password is not correct!!'],


    /** 验证器 108
     */
    108000 => ['zh'=>'验证规则设置有误','en'=>'validte rule format error'],
    108001 => ['zh'=>'验证规则不存在','en'=>'validate rule not exists'],
];