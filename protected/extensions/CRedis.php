<?php

/**
 * Curl wrapper for Yii
 * @author hackerone
 */
class CRedis extends CComponent
{

	public $options;
	public $hostname;
	public $port;
	private $_redis;

	public function watch($key)
	{
		return $this->_redis->watch($key);
	}

	public function unwatch()
	{
		return $this->_redis->unwatch();
	}

	public function multi()
	{
		return $this->_redis->multi();
	}

	/**
	 * 返回指定模式的所有key
	 * @param  [type] $pattern [description]
	 * @return [type]          [description]
	 */
	public function keys($pattern)
	{
		return $this->_redis->keys($pattern);
	}

	/**
	 * 设置 redis key的有效期
	 * @param [type] $key     [description]
	 * @param [type] $seconds [description]
	 */
	public function setTimeout($key, $seconds)
	{
		return $this->_redis->setTimeout($key, $seconds);
	}

	/**
	 * 获取一个值
	 *
	 * @param unknown_type $key
	 * @return unknown
	 */
	public function get($key)
	{
		return $this->_redis->get($key);
	}

	/**
	 * 获取多个值
	 *
	 * @param array $arr_key
	 * @return unknown
	 */
	public function mget($arr_key)
	{
		return $this->_redis->mget($arr_key);
	}

	/**
	 * 设置一个值
	 *
	 * @param unknown_type $key
	 * @param unknown_type $value
	 * @return unknown
	 */
	public function set($key, $value)
	{
		return $this->_redis->set($key, $value);
	}

	/**
	 * 设置一个值 并且设置有效期 单位秒
	 * @param unknown $key
	 * @param number $seconds
	 * @param unknown $value
	 */
	public function setex($key, $seconds, $value)
	{
		return $this->_redis->setex($key, $seconds, $value);
	}

	/**
	 * 值
	 *
	 * @param unknown_type $key
	 * @return unknown
	 */
	public function delete($key)
	{
		return $this->_redis->delete($key);
	}

	/**
	 * 增加一个值
	 *
	 * @param unknown_type $key
	 * @param unknown_type $num
	 * @return unknown
	 */
	public function incrBy($key, $num = 1)
	{
		return $this->_redis->incrBy($key, $num);
	}

	/**
	 * 减少一个值
	 *
	 * @param unknown_type $key
	 * @param unknown_type $num
	 * @return unknown
	 */
	public function decrBy($key, $num = 1)
	{
		return $this->_redis->decrBy($key, $num);
	}

	public function exists($key)
	{
		return $this->_redis->exists($key);
	}

	/**
	 * 取出某个范围的数据，并删除
	 *
	 * @param string $key
	 * @param int $start
	 * @param int $end
	 * @return unknown list/nil
	 *
	 */
	public function getRange($key, $start, $end)
	{
		return $this->_redis->lRange($key, $start, $end);
	}

	public function pop($key)
	{
		return $this->_redis->rPop($key);
	}

	/**
	 * 从顶部插入一个元素
	 *
	 * @param string $key
	 * @param string $value
	 * @return unknown
	 *
	 */
	public function push($key, $value)
	{
		return $this->_redis->rpush($key, $value);
	}

	public function sizelist($key)
	{
		return $this->_redis->lSize($key);
	}

	/**
	 *  截取list，相当于删除制定区间的元素
	 *
	 * @param string $key
	 * @param int $start
	 * @param int $end
	 * @return unknown
	 *
	 */
	public function trimlist($key, $start, $end)
	{
		return $this->_redis->lTrim($key, $start, $end);
	}

	/**
	 * 向集合中添加元素
	 * @param  [type] $key [description]
	 * @param  [type] $val [description]
	 * @return [type]      [description]
	 */
	public function sAdd($key, $val)
	{
		return $this->_redis->sADD($key, $val);
	}

	/**
	 * 返回集合中的元素个数
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function sCard($key)
	{
		return $this->_redis->sCard($key);
	}

	/**
	 * 集合所有元素
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function sMembers($key)
	{
		return $this->_redis->sMembers($key);
	}

	/**
	 * 获取hash中的全部数据
	 * @param unknown $key
	 */
	public function hGetAll($key)
	{
		return $this->_redis->hGetAll($key);
	}

	/**
	 * 设置hash数据
	 * @param $key
	 * @param $hashkey
	 * @param $val
	 * @return mixed
	 */
	public function hset($key, $hashkey, $val)
	{
		return $this->_redis->hset($key, $hashkey, $val);
	}

	public function hmset($key, $field_arr)
	{
		return $this->_redis->hMset($key, $field_arr);
	}

	/**
	 * 获取hash数据
	 * @param $key
	 * @param $hashkey
	 * @return mixed
	 */
	public function hget($key, $hashkey)
	{
		return $this->_redis->hget($key, $hashkey);
	}

	/**
	 * 批量获取hash数据
	 * @param type $key
	 * @param type $field
	 * @return type
	 */
	public function hmget($key, $field)
	{
		return $this->_redis->hMget($key, $field);
	}

	/**
	 * 批量设置hash数据
	 * @param str $key
	 * @param array $field
	 */
	public function rename($key, $newkey)
	{
		return $this->_redis->rename($key, $newkey);
	}

	/**
	 * 自增hash的field的值
	 * @param unknown $key
	 * @param unknown $field
	 * @param number $value
	 */
	public function hIncrBy($key, $field, $value = 1)
	{
		return $this->_redis->hIncrBy($key, $field, intval($value));
	}

	//返回列表的头元素
	public function lPop($key)
	{
		return $this->_redis->lPop($key);
	}

	//返回列表的尾元素
	public function rPop($key)
	{
		return $this->_redis->rPop($key);
	}

	//加入到列表尾部
	public function rPush($key, $val)
	{
		return $this->_redis->rPush($key, $val);
	}

	public function lPush($key, $value)
	{
		return $this->_redis->lPush($key, $value);
	}

	public function llen($key)
	{
		return $this->_redis->lLen($key);
	}

	/**
	 * Zset 相关
	 */
	//增加一个或多个元素，如果该元素已经存在，更新它的socre值
	public function zAdd($key, $score, $member)
	{
		return $this->_redis->zAdd($key, $score, $member);
	}

	//取得特定范围内的排序元素,0代表第一个元素,1代表第二个以此类推。-1代表最后一个,-2代表倒数第二个
	public function zRange($key, $start, $end, $withscores = false)
	{
		return $this->_redis->zRange($key, $start, $end, $withscores);
	}

	//从有序集合中删除指定的成员
	public function zDelete($key, $member)
	{
		return $this->_redis->zDelete($key, $member);
	}

	//返回key对应的有序集合中指定区间的所有元素
	public function zRevRange($key, $start, $end, $withscores = false)
	{
		return $this->_redis->zRevRange($key, $start, $end, $withscores);
	}

	//返回key对应的有序集合中介于min和max间的元素的个数
	public function zCount($key, $star, $end)
	{
		return $this->_redis->zCount($key, $star, $end);
	}

	//返回存储在key对应的有序集合中的元素的个数
	public function zSize($key)
	{
		return $this->_redis->zSize($key);
	}

	//返回key对应的有序集合中member的score值。如果member在有序集合中不存在，那么将会返回nil。
	public function zScore($key, $member)
	{
		return $this->_redis->zScore($key, $member);
	}

	//返回key对应的有序集合中member元素的索引值，元素按照score从低到高进行排列
	public function zRank($key, $member)
	{
		return $this->_redis->zRank($key, $member);
	}

	//将key对应的有序集合中member元素的scroe加上value
	public function zIncrBy($key, $value, $member)
	{
		return $this->_redis->zIncrBy($key, $value, $member);
	}
	/**
	 * 设置key的超时时间
	 * @param unknown $key
	 * @param number $seconds 默认10秒
	 */
	public function expire($key, $seconds = 10) {
		return $this->_redis->expire($key, $seconds);
	}

	// initialize curl
	public function init()
	{
		try {
			$this->_redis = new Redis();
			$this->_redis->connect($this->options['hostname'], $this->options['port']);
		} catch (Exception $e) {
			throw new CException('Curl not installed');
		}
	}

}
