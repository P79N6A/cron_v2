<?php

/**
 * 圈子用户相关
 * @author yougang1
 *
 */
class CircleUser extends CActiveRecord {

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	private $default_select_fields = "u_type,uid,circle_id,is_top,end_time,c_time,is_online,u_time,service_status,is_option";

	public function tableName() {
		return TABLE_PREFIX . 'circle_user';
	}

	/**
	 * 组装where查询条件
	 * @param  array $filters 过滤字段列表
	 * @return string          组装好的where条件
	 */
	public function buildAndWhere($filters) {
		$where = ' where 1 ';

		if (!empty($filters)) {
			foreach ($filters as $f => $v) {
				if (is_int($v)) {
					$where .= " and {$f}={$v}";
				} elseif (is_array($v)) {
					if (is_int($v['0'])) {
						$where .= " and {$f} in (" . implode(",", $v) . ")";
					} else {
						$where .= " and {$f} in ('" . implode("','", $v) . "')";
					}
				} else {
					$where .= " and {$f}='{$v}'";
				}
			}
		}

		return $where;
	}

	/**
	 * 获取单条用户圈子信息
	 * @param  array $where_arr 过滤字段列表
	 * @param  array  $order_arr 排序规则
	 * @return array             用户圈子信息
	 */
	public function getUserCircleInfo($where_arr, $order_arr = array()) {
		$fields = $this->default_select_fields;
		$where = $this->buildAndWhere($where_arr);
		if (!empty($order_arr)) {
			$order = ' order by ' . implode(',', $order_arr);
		} else {
			$order = '';
		}

		$sql = "select {$fields}" .
			" from {$this->tableName()} " .
			" {$where} " .
			" {$order} ";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		if (empty($res)) {
			return null;
		} else {
			return $res;
		}
	}

	/**
	 * 获取用户多个圈子信息map
	 * @param   int $u_type用户类型
	 * @param   int $uid用户id
	 * @param   array   $circle_ids圈子id数组
	 * @param   array   排序规则
	 */
	public function getUserCircleInfoMap2($u_type, $uid, $circle_ids, $order_arr = array()) {
		$where_arr = array();
		$where_arr['u_type'] = $u_type;
		$where_arr['uid'] = $uid;
		$where_arr['circle_id'] = $circld_ids;

		$fields = $this->default_select_fields;
		$where = $this->buildAndWhere($where_arr);
		if (!empty($order_arr)) {
			$order = ' order by ' . implode(',', $order_arr);
		} else {
			$order = '';
		}

		$sql = "select {$fields}" .
			" from {$this->tableName()} " .
			" {$where} " .
			" {$order} ";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		if (empty($res)) {
			return null;
		} else {
			$result = array();
			foreach ($res as $item) {
				$result[$item['circle_id']] = $item;
			}
			return $result;
		}
	}

	/**
	 * 获取用户圈子列表分页对象
	 * @param  array  $where_arr 过滤字段列表
	 * @param  array   $order_arr 排序规则
	 * @param  integer $page      页码
	 * @param  integer $page_num  页容量
	 * @return array             用户圈子列表分页对象
	 */
	public function getUserCircleList($where_arr, $order_arr = array(), $page = 1, $page_num = 40) {
		$fields = $this->default_select_fields;
		$where = $this->buildAndWhere($where_arr);
		//add by lining in 2018-11-05 (判断用户是否有权限)
		// $where .= " and end_time > '2000-01-01 00:00:00' and end_time>NOW()";

		$sqlcount = "select count(*) as total " .
			" from {$this->tableName()} " .
			" {$where};";
		$total = Yii::app()->lcs_r->createCommand($sqlcount)->queryScalar();
		$page_num = $page_num > 0 ? $page_num : $total;
		$offset = CommonUtils::fomatPageParam($page, $page_num);
		if (!empty($order_arr)) {
			$order = ' order by ' . implode(',', $order_arr);
		} else {
			$order = '';
		}

		$list = null;
		if ($offset < $total) {
			$sql = "select {$fields}" .
				" from {$this->tableName()} " .
				" {$where} " .
				" {$order} " .
				" limit {$offset},{$page_num}";
			$list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		}

		return CommonUtils::getPage($list, $page, $page_num, $total);
	}

	/**
	 * 添加圈子用户
	 */
	public function addCircleUser($data) {
		$db_w = Yii::app()->licaishi_w;
		$command = $db_w->createCommand();
		return $command->insert($this->tableName(), $data);
	}

	/**
	 * 删除用户圈子信息
	 * @param unknown $type 用户类型
	 * @param unknown $uid 用户UID
	 * @param unknown $circle_id 圈子ID
	 */
	public function deleteCircleUser($u_type, $uid, $circle_id) {
		if (empty($u_type) || empty($uid) || empty($circle_id)) {
			return false;
		}
		$db_w = Yii::app()->licaishi_w;
		$sql = "delete from {$this->tableName()} where u_type=:u_type and uid=:uid and circle_id=:circle_id";
		$command = $db_w->createCommand($sql);
		$command->bindParam(':u_type', $u_type, PDO::PARAM_INT);
		$command->bindParam(':uid', $uid, PDO::PARAM_INT);
		$command->bindParam(':circle_id', $circle_id, PDO::PARAM_INT);
		return $command->execute();
	}

	/**
	 * 修改用户圈子信息
	 * @param array $columns
	 * @param string $conditions
	 * @param array $params
	 */
	public function updateCircleUser($columns, $conditions = '', $params = array()) {
		return Yii::app()->licaishi_w->createCommand()->update($this->tableName(), $columns, $conditions, $params);
	}

	/**
	 * 更新圈子用户表增长字段
	 * @param  int  $circle_id 圈子id
	 * @param  string  $column    数据库表待增长字段
	 * @param  int $val       增长值
	 * @return boolean             true
	 */
	public function updateCircleUserInc($where_arr, $column, $val = 1) {
		$where = $this->buildAndWhere($where_arr);

		$val = intval($val);
		$sql = "update {$this->tableName()} set {$column}={$column}+{$val} {$where}";
		$res = Yii::app()->licaishi_w->createCommand($sql)->execute();

		return true;
	}

	/**
	 * 获取圈子列表信息
	 * @param unknown $page
	 * @param unknown $num
	 * @param unknown $type
	 */
	public function getCircleList($page, $num, $type = 1, $uid) {

		$page = intval($page);
		$page = $page > 1 ? $page : 1;
		$num = intval($num);
		if ($num < 1) {
			$num = 10;
		}
		$offset = ($page - 1) * $num;
		$db_r = Yii::app()->lcs_r;

		//未过期的
		if ($type == 1) {
			$where = " uid=:uid and ((end_time > now() and end_time <> '1970-01-01 00:00:00') or(end_time = '1970-01-01 00:00:00')) ";
		}
		//过期的
		if ($type == 0) {
			$where = " uid=:uid and end_time < now() and end_time <> '1970-01-01 00:00:00' ";
		}
		//总数量
		$sql_total = "select count(1) from {$this->tableName()} where " . $where;
		$total_command = $db_r->createCommand($sql_total);
		$total_command->bindParam(':uid', $uid, PDO::PARAM_INT);
		$total = $total_command->queryScalar();

		//$sql = "select circle_id,is_top,end_time,c_time,u_time from {$this->tableName()} where ".$where." order by is_top desc,c_time desc limit :offset,:limit";
		$sql = "select circle_id,end_time,is_top from {$this->tableName()} where " . $where . " order by is_top desc,c_time desc limit :offset,:limit";
		$command = $db_r->createCommand($sql);
		$command->bindParam(':uid', $uid, PDO::PARAM_INT);
		$command->bindParam(':offset', $offset, PDO::PARAM_INT);
		$command->bindParam(':limit', $num, PDO::PARAM_INT);
		$list = $command->queryAll();

		return CommonUtils::getPage($list, $page, $num, $total);
	}

	/**
	 * 获取圈子用户信息
	 * @param unknown $id
	 */
	public function getCircleUserInfo($id) {
		$id = intval($id);
		if (!$id) {
			return null;
		}
		$db_r = Yii::app()->lcs_r;
		$sql = "select id, u_type, uid, circle_id, is_top, end_time, c_time, u_time from {$this->tableName()} where id = :id";
		$command = $db_r->createCommand($sql);
		$command->bindParam(':id', $id, PDO::PARAM_INT);
		return $command->queryRow();
	}

	/**
	 * 查询用户加入的圈子ID
	 */
	public function getUserCircleId($uid, $u_type) {
		$db_r = Yii::app()->lcs_r;
		$sql = "select distinct circle_id from {$this->tableName()} where uid = :uid and u_type = :u_type and end_time >= :end_time";
		$command = $db_r->createCommand($sql);
		$end_time = date("Y-m-d H:i:s");
		$command->bindParam(':uid', $uid, PDO::PARAM_INT);
		$command->bindParam(':u_type', $u_type, PDO::PARAM_INT);
		$command->bindParam(':end_time', $end_time, PDO::PARAM_STR);
		return $command->queryColumn();
	}

	/**
	 * 获取信息
	 */
	public function getUserCircleMap($where, $key, $value_key) {
		//Common::model()->saveLog(json_encode($where).json_encode($key),json_encode($value_key),"info","circle_map");
		$fields = $this->default_select_fields;
		$where = $this->buildAndWhere($where);
		$result = array();
		if (empty($where)) {
			return $result;
		}
		$sql = sprintf("select %s from %s %s", $fields, $this->tableName(), $where);
		$list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if (empty($list)) {
			return $result;
		}
		foreach ($list as $item) {
			$result[$item[$key]] = $item[$value_key];
		}
		return $result;
	}

	public function updateCircleUserBatchInfo($columns, $where = '') {
		if (empty($columns)) {
			return false;
		}
		$columns_str = '';
		foreach ($columns as $k => $v) {
			$columns_str .= $k . '=' . $v . ",";
		}
		$columns_str = substr($columns_str, 0, -1);
		$sql = "update " . $this->tableName() . " set " . $columns_str . " where " . $where;

		Yii::app()->licaishi_w->createCommand($sql)->execute();
		return true;
	}

	/**
	 * 批量删除用户关注圈子记录
	 * @param $u_type
	 * @param $uid
	 * @param $circle_id
	 * @return bool
	 */
	public function batchDeleteCircleUser($u_type, $uid, $circle_ids) {
		$circle_ids = (array) $circle_ids;
		if (empty($u_type) || empty($uid) || empty($circle_ids)) {
			return false;
		}
		$db_w = Yii::app()->licaishi_w;
		$sql = "delete from {$this->tableName()} where u_type=:u_type and uid=:uid and circle_id in (" . implode(",", $circle_ids) . ")";
		$command = $db_w->createCommand($sql);
		$command->bindParam(':u_type', $u_type, PDO::PARAM_INT);
		$command->bindParam(':uid', $uid, PDO::PARAM_INT);
		return $command->execute();
	}

	public function getUserCircleIdbyCircleId($circle_ids, $uid) {

		if (empty($circle_ids))
			return;
		$sql = "select distinct circle_id from " . $this->tableName() . " where uid=:uid and circle_id in (" . implode(',', $circle_ids) . ")";
		$command = Yii::app()->lcs_r->createCommand($sql);

		$command->bindParam(':uid', $uid, PDO::PARAM_INT);
		$result = $command->queryColumn();
		return $result;
	}

	/**
	 * 根据条件获取用户关注的圈子
	 * @param $where
	 * @param $key
	 * @return array
	 */
	public function getUserCircleInfoMap($where, $key) {
		$fields = $this->default_select_fields;
		$where = $this->buildAndWhere($where);
		$result = array();
		if (empty($where)) {
			return $result;
		}
		$sql = sprintf("select %s from %s %s", $fields, $this->tableName(), $where);
		$list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if (empty($list)) {
			return $result;
		}
		foreach ($list as $item) {
			$result[$item[$key]] = $item;
		}
		return $result;
	}

}
