# swoolex
基于swoole的轻量级web框架

# 路由
> 暂仅支持pathinfo模式
助手函数：
* module() 获取当前的请求模块
* controoler() 获取当前请求的控制器
* method() 获取当前请求的方法
* input() 获取输入参数

# 中间件
> 可以配置middleware，实现权限等逻辑的过滤拦截,配置文件/etc/conf/middleware.php
```
return
[
    'app\middleware\Demo'
];
```
只需要将需要的中间件类加入进来即可，框架会按照顺序调用

# session
> 基于redis实现
助手函数：cookie

# cookie


# context上下文管理
> 主要分为全局上下文、请求上下文、协程上下文
* config()
* sess_context()
* cor_context()

# 验证器
> 支持常规的字段验证。如require等

# i18n
> 自定义多国语言支持，实现国际化

# 异步任务调度
> 基于swoole的Task进程实现

# 容器

# aop切面编程
> 通过简单配置即可实现切面编程，实现原理是对象代理。主要用于日志，事务等的切面编程

# mysql orm
> 支持常规的mysql链式操作

# 连接池
> 支持mysql和redis连接池

# tcp
> 支持tcp响应处理，可以在/etc/conf/config.php进行开启

# websocket
> 支持websocket响应处理，可以在/etc/conf/config.php进行开启
