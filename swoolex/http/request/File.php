<?php
/**
 * swoolex 文件上传下载操作
 */
namespace Swoolex\http\request;

class File
{
    /**
     * @var int 设置允许的文件大小
     */
    public $allowSize = 2000000;
    /**
     * @var array 设置允许的后缀
     */
    public $allowExt  = [];
    /**
     * @var array 设置允许的文件类型，MIME类型
     */
    public $allowType = [];
    /**
     * @var string 设置上传文件存储的路径
     */
    public $savePath      = '';
    /**
     * @var string 设置上传文件的命名
     */
    public $saveName      = '';
	/**
	 * 上传文件
     * @param string $field 文件上传的字段名，对应form的字段
     * @return bool
     * @throws
	 */
	public function upload($field)
    {
        /** 判断文件是否存在
         */
        $file = input('file.'.$field);
        if(null == $file)\lang()->throwException(106400);

        /** 判断文件是否出错
         */
        if($file->error)\lang()->throwException(106401);

        /** 验证文件大小
         */
        if(isset($this->allowSize))
        {
            if(!$file->size || $file->size>$this->allowSize)
            {
                \lang()->throwException(106402);
            }
        }

        /** 验证文件后缀
         */
        $nameArray  = explode('.',$file->name);
        if(count($nameArray)<2)\lang()->throwException(106403);
        $fileExt = end($nameArray);
        if(isset($this->allowExt))
        {
            if(!in_array($fileExt,$this->allowExt))
            {
                \lang()->throwException(106403);
            }
        }
        /** 验证文件类型
         */
        if(isset($this->allowType))
        {
            if(!in_array($file->type,$this->allowType))
            {
                \lang()->throwException(106404);
            }
        }

        if(!isset($this->savePath) || !is_dir($this->savePath)){
            $this->savePath = './upload/';
            if(!is_dir($this->savePath))mkdir($this->savePath,0777,true);
        }
        /** 重新命名文件,默认重命名
         */
        if(isset($this->saveName) && is_string($this->saveName))
        {
            $res = move_uploaded_file($file->tmp_name,$this->savePath.DS.$file->name);
            return $res;
        }
        else
        {
            //生成文件名称
            while(true){
                $fileName = md5(microtime().rand(100000000,999999999));
                $fullFileName = $this->savePath.DS.$fileName.$fileExt;
                if(!file_exists($fullFileName))break;
            }
            $res = move_uploaded_file($file->tmp_name,$this->savePath.DS.$fileName.'.'.$fileExt);
            return $res;
        }

    }



}