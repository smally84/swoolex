<?php
namespace app\admin\controller;
use Swoole\Coroutine as Co;

class Home {
	static $a = 1;
	public function redis(){
		$a = [
			'a1'=>1,
			'a2'=>2,
		];
		redis()->set('key',serialize($a));
		$a = redis()->get('key');
		var_dump(unserialize($a));
	}

	public function getSession(){
        session('userId',123);
        echo session('userId');
		return session('userId');
	}
	public function setSession(){
		session('userId',123);
	}
	/**
     * @throws
	 */
	public function ceshi(){
        $fields = "name,a_name as article_name";
        $res =db('user')
            ->alias('u')
//            ->join('article a','a.user_id=u.id','left')
//            ->field($fields)
//            ->fetchSql(true)
//            ->where('u.id',1)
            ->sum('u.id');
        json($res);
	}

	/**
	 * 增加数据
	 */
	public function insert(){
		
		db()->startTrans();
			$data = [
				'_id' => time(),
				'username' => 'wangx2-'.date('Y-m-d H:i:s'),
			];
			$res = db('user')->fetchSql(false)->insert($data);
			db()->startTrans();
				$data = [
					'_id' => time()+1,
					'username' => 'wangx2-'.date('Y-m-d H:i:s'),
				];
				$res = db('user')->fetchSql(false)->insert($data);
			 db()->commit();
		db()->rollback();

	    return $res;
	}
	/**
	 * 批量增加数据
	 */
	public function insertAll(){

		$data = [
			[
				'_id' => time().'all1',
				'username' => 'wang',
			],
			[
				'_id' => time().'all2',
				'username' => 'wang',
			],
		];
		$res = db('user')->fetchSql()->insertAll($data);
		var_dump($res);

		$res = db('user')->getLastSql();
	    return $res;
	}
	/**
	 * 删除数据
	 */
	public function delete(){
		$res = db('user')
			   -> where('username','wang1')
			   -> fetchSql()
		       -> delete(true);

		var_dump($res);
	    return $res;
	}
	/**
	 * 更新数据
	 */
	public function update(){

		$data = [
			'username' => 'wang1',
		];
		$res = db('user')
			   -> where('username','wangx2')
			    -> fetchSql()
		       -> update($data);
		dump($res);


		$res = db('user')->getLastSql();
	    return $res;

	}

	/**
	 * 获取数据
	 */
	public function get(){

	    $res =db('user')
	        ->where('username','root')
		    ->field('*')
		    ->page(1)
		    ->limit(2)
		    ->order('_id')
		    ->fetchSql(false)
		    ->select();
		// var_dump($res);
		
		$res = db('user')->getLastSql();
		self::$a++;
		echo self::$a."\r\n";
	    return $res;
	}

	public function json(){
		return xml([
			'success'=>true,
			'data' =>[
				1,2,3
			],
		]);
	}

	public function exception(){

		try{
			throw new \Exception('123',1);
		}catch(\Exception $e){
			return $e->getMessage();
		}

	}
}