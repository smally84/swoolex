<?php
/**
 * 查询构造器
 */
namespace Swoolex\db\mysql;

use Swoolex\XLang;

class Query {

	//sql记录
	protected $_sql  = '';
	protected $_bind = [];
	public $_closure = false;
	/**
	 * 构造函数
     * @param string $sql
     * @param array $bind
     * @return void
	 */
	public function __construct($sql='',$bind=[]){

		if(empty($bind))$this->_sql = '';
		else $this->_sql = $sql;
		if(empty($bind))$this->_bind = [];
		else $this->_bind = $bind;
	}

	/**
	 * 增加WHERE关键字
	 */
	public function addWhere(){
		$sql_tmp = $this->_sql;
		$sql_tmp = trim($sql_tmp);
		$sql_tmp = preg_replace('/(\s)\1+/u','$1',$sql_tmp);
		if(0!==strpos($sql_tmp,'WHERE'))$this->_sql = ' WHERE'.$this->_sql;
		return $this->_sql;
	}

	/**
	 * 闭包条件
     * @param \Closure $function
     */
	public function closureCond(\Closure $function)
    {
        /** 如果是闭包函数，则传入mysql实例对象，执行数据库连续操作
         *  -- function($db){
         *  --    $db->where()->where()……
         *  -- }
         */
        $Db = new Db();
        $function($Db);

        /**执行连贯操作后，获取模板和模板数据参数
         */
        $sql = $Db->getSql();
        $bind = $Db->getBind();

        /** 执行一次闭包查询，会多出一个where关键字，和外部有冲突需删除
         *  注:str_irepalce 不区分大小写
         */
        $sql = str_ireplace('WHERE','',$sql);
        $sql = preg_replace('/(\s)\1+/u','$1',$sql);

        /** 合并原来的模板和模板参数
         */
        if($this->_sql)$this->_sql = $this->_sql.' AND ('.$sql.' )';
        else $this->_sql =' ('.$sql.' )';
        if($bind){
            foreach ($bind as $key => $value) {
                $this->_bind[] = $value;
            }
        }
    }
	/**
     * where查询
     * @param string $cond1
     * @param string $cond2
     * @param mixed  $cond3
     * @return array
	 */
	public function where($cond1,$cond2=null,$cond3=null){
	    /**判断$cond1是否为闭包函数
	     */
		if($cond1 instanceof \Closure){
		    $this->closureCond($cond1);
		}else {
			$this->fieldCmp($cond1,$cond2,$cond3,"AND");
			
		}
		return [$this->addWhere(),$this->_bind];
	}

	/**
     * whereor查询
	 * @param string $cond1
     * @param string $cond2
     * @param mixed  $cond3
     * @return array
	 */
	public function whereOr($cond1,$cond2=null,$cond3=null){

		if(is_callable($cond1)){
            $this->closureCond($cond1);
		}else {
			$this->fieldCmp($cond1,$cond2,$cond3,"OR");
		}
		return [$this->addWhere(),$this->_bind];
	}
	/**
	 * 比较表达式
	 * @param mixed $cond1
     * @param string $cond2
     * @param mixed  $cond3
     * @param string $AndOr
     * @throws
	 */
	public function fieldCmp($cond1,$cond2,$cond3,$AndOr){
		/*------条件预处理------*/
		//被查询字段不能为空
		if(null == $cond1)lang()->throwException(107101);
		//当第三参数为空时，默认为相等比较
		if(null == $cond3){
			$cond3 = $cond2;
			$cond2 = '=';
		}
		/*------查询条件空格及大小写处理------*/
		//处理首尾空格
		$cond1 = trim($cond1);
		//输入条件转为小写
		$cond1 = strtolower($cond1);

		//处理首尾空格
		$cond2 = trim($cond2);
		//处理中间多与的空格
		$cond2 = preg_replace('/(\s)\1+/u','$1',$cond2);
		//输入条件转为小写
		$cond2 = strtolower($cond2);
		/*------组合sql语句------*/
		//判断是否已经包含where关键字
		$sql_tmp = $this->_sql;
		//去取sql模板两边的空格
		$sql_tmp = trim($sql_tmp);
		//过滤掉重复的空格
		$sql_tmp = preg_replace('/(\s)\1+/u','$1',$sql_tmp);
		if(0==strpos($sql_tmp,'WHERE') && !empty($this->_sql))$this->_sql .=" {$AndOr}";

		switch ($cond2) {
			case '=':

				$this->_sql   .= ' '.$cond1." = ?";
				$this->_bind[] = $cond3;
				break;
			case '<>';
	
				$this->_sql   .= ' '.$cond1." <> ?";
				$this->_bind[] = $cond3;
				break;
			case '>';
	
				$this->_sql   .= ' '.$cond1." > ?";
				$this->_bind[] = $cond3;
				break;
			case '>=';
	
				$this->_sql.=' '.$cond1." >= ?";
				$this->_bind[] = $cond3;
				break;
			case '<';
	
				$this->_sql.=' '.$cond1." < ?";
				$this->_bind[] = $cond3;
				break;
			case '<=';
	
				$this->_sql.=' '.$cond1." <= ?";
				$this->_bind[] = $cond3;
				break;
			case 'like';
	
				$this->_sql.=' '.$cond1." LIKE ?";
				$this->_bind[] = $cond3;
				break;
			//查询条件支持字符串和数组
			case 'between';
			case 'not between';
				if(is_array($cond3));
				else if(is_string($cond3)){
					$cond3 = explode(',', $cond3);
				}else lang()->throwException(107102);

				if(2==count($cond3));
				else lang()->throwException(107102);
				$cond2 = strtoupper($cond2);
				$this->_sql .=' '.$cond1." {$cond2} ? AND ?";
				$this->_bind[] = $cond3[0];
				$this->_bind[] = $cond3[1];
				break;
			case 'in';
			case 'not in';
				if(is_array($cond3));
				else if(is_string($cond3)){
					$cond3 = explode(',', $cond3);
				}else lang()->throwException(107102);
				$cond2 = strtoupper($cond2);
				$this->_sql .= ' '.$cond1." {$cond2} (";
				foreach ($cond3 as $key => $value) {
			
					if($key>0)$this->_sql .= ",?";
					else $this->_sql .="?";

					$this->_bind[]=$value;
				}
				$this->_sql .=' )';
				break;
			case 'null';
			case 'not null';
				$cond2 = strtoupper($cond2);
				$this->_sql.=' '.$cond1." IS {$cond2}";
				break;
			case 'exp';
				$this->_sql    .=' '.$cond1.' ?';
				$this->_bind[]  =$cond3;
				break;
			default:
				lang()->throwException(107103);
				break;
		}
	}

}