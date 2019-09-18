<?php
class SwoolexCtl
{
    /**
     * 运行日志的路径
     * @var
     */
    private $runtime;
    /**
     * 服务状态文件
     * @var
     */
    private $statusFile;
    /**
     * 启动日志文件
     * @var
     */
    private $bootLog;
    /**
     * 运行日志文件
     * @var
     */
    private $runLog;

    /**
     * Console constructor.
     */
    public function __construct()
    {
        $this->init();
    }
    /**
     * 初始化
     */
    public function init()
    {
        $this->runtime=__DIR__.'/../runtime';
        $this->statusFile=$this->runtime.'/status';
        $this->bootLog=$this->runtime.'/boot.log';#启动的错误日志
        $this->runLog=$this->runtime.'/run.log';#swoole运行错误

        /** 检测运行日志文件夹是否存在
         */
        if(!is_dir($this->runtime))
        {
            mkdir($this->runtime);
        }
        /** 检测服务状态文件是否存在
         */
        if(!file_exists($this->statusFile) || !file_get_contents($this->statusFile))
        {
            file_put_contents($this->statusFile,'1,0,1');
        }
    }
    /**
     * 获取服务状态
     */
    public function getStatus()
    {
        $status = file_get_contents($this->statusFile);
        $status = trim($status, "\r\n");
        $statusArray = explode(',', $status);
        $swoolex_enable = is_numeric($statusArray[0]) ? (int)$statusArray[0] : 0;
        $swoolex_restart = is_numeric($statusArray[1]) ? (int)$statusArray[1] : 0;
        $swoolex_state = is_numeric($statusArray[2]) ? (int)$statusArray[2] : 0;
        $ret['code'] = 0;
        $ret['data']['swoolex_enable'] = $swoolex_enable;
        $ret['data']['swoolex_restart'] = $swoolex_restart;
        $ret['data']['swoolex_state'] = $swoolex_state;
        return $ret;
    }
    /**
     * 响应控制请求
     */
    public function response()
    {
        try{
            $params = $_REQUEST;
            $status = file_get_contents($this->statusFile);
            $status = trim($status,"\r\n");
            $statusArray = explode(',',$status);
            $swoolex_enable  = is_numeric($statusArray[0])?(int)$statusArray[0]:0;
            $swoolex_restart = is_numeric($statusArray[1])?(int)$statusArray[1]:0;
            $swoolex_state   = is_numeric($statusArray[2])?(int)$statusArray[2]:0;

            if(isset($params['swoolex_enable']))
            {
                $params['swoolex_enable'] = (bool)$params['swoolex_enable'];
                if($params['swoolex_enable'])$params['swoolex_enable'] = 1;
                else $params['swoolex_enable'] = 0;
                $swoolex_enable = $params['swoolex_enable'];
            }
            else if(isset($params['swoolex_restart']))
            {
                $params['swoolex_restart'] = (bool)$params['swoolex_restart'];
                if($params['swoolex_restart'])$params['swoolex_restart'] = 1;
                else $params['swoolex_restart'] = 0;
                $swoolex_restart = $params['swoolex_restart'];
                header('Content Type:text/json');
                $ret = [];
                $ret['code'] = 0;
                echo json_encode($ret);
            }
            else if(isset($params['status']))
            {
                header('Content Type:text/json');
                $ret = [];
                $ret['code'] = 0;
                $ret['data'] = [
                    'swoolex_enable' =>(int)$swoolex_enable,
                    'swoolex_restart' =>(int)$swoolex_restart,
                    'swoolex_state'   =>(int)$swoolex_state,
                    'php_version'     => PHP_VERSION,
                    'swoole_version'  => function_exists('swoole_version')?swoole_version():'swoole未安装',
                ];
                echo json_encode($ret);
            }
            else if(isset($params['bootlog']))
            {
                header('Content Type:text/json');
                $exists = file_exists($this->bootLog);
                if($exists){
                    $echo = file_get_contents($this->bootLog);
                    echo '<pre>'.$echo.'</pre>';
                }else{
                    echo 'file not exists';
                }

            }
            else if(isset($params['runlog']))
            {
                header('Content Type:text/json');
                $exists = file_exists($this->runLog);
                if($exists){
                    $echo =  file_get_contents($this->runLog);;
                    echo '<pre>'.$echo.'</pre>';
                }else{
                    echo 'file not exists';
                }
            }
            if(isset($params['swoolex_enable'])||isset($params['swoolex_restart']))
            {
                file_put_contents($this->statusFile,$swoolex_enable.','.$swoolex_restart.','.$swoolex_state);
            }

        }catch (\Throwable $e){
            echo $e->getMessage();
        }
    }
}

/** 实例化，响应请求
 */
$SwoolexCtl = new SwoolexCtl();
$SwoolexCtl->response();