<?php

/**
  锦囊相关
 */
class Silk extends CActiveRecord {

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return TABLE_PREFIX . "silk";
	}

	public function tableNameArticle() {
		return TABLE_PREFIX . "silk_article";
	}

	public function tableNameSub() {
		return TABLE_PREFIX . "silk_subscription";
	}
	public function tableNameHistory() {
		return TABLE_PREFIX . "silk_subscription_history";
	}
	public function tableNameWhite() {
		return TABLE_PREFIX . "planner_white";
	}	
	/**
	 * 根据id获取锦囊数据
	 * @param   int     $ids锦囊ｉｄ数组
	 * @param   array   结果数组
	 */
	public function getSilkByIds($ids, $mc = true) {
		$res = array();
		$ids = (array) $ids;
		if (count($ids) > 0) {
			$left_ids = array();
			$redis_key = array();

			///使用缓存
			if ($mc) {
				foreach ($ids as $id) {
					$redis_key[] = MEM_PRE_KEY . "silkinfo_" . $id;
				}
				$redis_data = Yii::app()->redis_r->mget($redis_key);
				for ($i = 0; $i < count($ids); $i++) {
					if ($redis_data[$i]) {
						$temp = json_decode($redis_data[$i], true);
						$res[$ids[$i]] = $temp;
					} else {
						$left_ids[] = $ids[$i];
					}
				}
			} else {
				$left_ids = $ids;
			}

			if (count($left_ids) > 0) {
				$sql = "select id,p_uid,title,summary,target_user,image,expect_num,sub_num,status,subscription_price,start_time,end_time,c_time,u_time from " . $this->tableName() . " where id in (" . implode(',', $left_ids) . ") ";
				$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
				if ($data) {
					foreach ($data as $item) {
						$res[$item['id']] = $item;
					}
				}
			}
		}
		return $res;
	}

	/**
	 * 根据理财师id获取锦囊数据
	 * @param   int     $p_uid理财师ｉｄ
	 * @param   int     $page分页页码
	 * @param   int     $num分页每页数量
	 * @param   array   $status状态数组
	 * @param   string  $time_status锦囊是否结束before预售,running运行中,end已结束,notend未结束（预售和运行中）,all全部状态
	 * @return  array   分页结果数据
	 */
	public function getSilkByPuid($p_uid, $page = 1, $num = 10, $status = array(),$time_status = "all") {
		$sql = "select id,p_uid,title,summary,target_user,image,expect_num,sub_num,status,subscription_price,start_time,end_time from " . $this->tableName() . " where p_uid='$p_uid' ";
		$sql_total = "select count(*) from " . $this->tableName() . " where p_uid='$p_uid' ";

		if (count($status) > 0) {
			$sql = $sql . " and status in (" . implode(',', $status) . ")";
			$sql_total = $sql_total . " and status in (" . implode(',', $status) . ")";
		}

        if($time_status!="all"){
            $time_sql = "";
            $now = date("Y-m-d H:i:s");

            if($time_status=="before"){
                $time_sql = " and start_time>'$now' ";
            }elseif($time_status=="running"){
                $time_sql = " and start_time<'$now' and end_time>'$now' ";
            }elseif($time_status=="end"){
                $time_sql = " and end_time<'$now' ";
            }elseif($time_status=="notend"){
                $time_sql = " and end_time>'$now' ";
            }

            $sql=$sql.$time_sql;
            $sql_total=$sql_total.$time_sql;
        }

		$skip = ($page - 1) * $num;
		$sql = $sql . " order by c_time desc limit $skip,$num";
		$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		$total = Yii::app()->lcs_r->createCommand($sql_total)->queryScalar();
		if ($data) {
			return CommonUtils::getPage($data, $page, $num, $total);
		} else {
			return CommonUtils::getPage(array(), $page, $num, $total);
		}
	}

	/**
	 * 根据用户uid获取分页的锦囊购买数据
	 * @param   int $uid用户uid
	 * @param   int $page
	 * @param   int $num
	 * @param   int $status状态0只要有效的，1全部
	 */
	public function getSilkSubByUid($uid, $page, $num) {
		if (!empty($uid)) {
			$skip = ($page - 1) * $num;
			$now = date("Y-m-d H:i:s", time());
            $sql = "select silk_id from " . $this->tableNameSub() . " where uid='$uid' and end_time>'$now' limit $skip,$num";
            $sql_total = "select count(*) from " . $this->tableNameSub() . " where uid='$uid' and end_time>'$now' ";

			$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			$total = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
			if (count($data) > 0) {
				return CommonUtils::getPage($data, $page, $num, $total);
			} else {
				return CommonUtils::getPage(array(), $page, $num, $total);
			}
		}
		return CommonUtils::getPage(array(), $page, $num, 0);
	}

       /**
	 * 根据用户uid和理财师uid获取分页的锦囊购买数据
	 * @param   int $uid用户uid
	 * @param   int $p_uid理财师用户uid
	 * @param   int $page
	 * @param   int $num
	 * @param   int $status状态0只要有效的，1全部
	 */
	public function getPlannerSilkSubByUid($uid, $p_uid, $page, $num) {
		if (!empty($uid) && !empty($p_uid)) {
			$uid = intval($uid);
			//$uid = 171429864;
			$skip = ($page - 1) * $num;
			$now = date("Y-m-d H:i:s", time());
            $sql = "select a.silk_id from " . $this->tableNameSub() . " a,". $this->tableName() ." b where a.uid='$uid' and a.end_time>'$now' and a.silk_id=b.id and b.p_uid='$p_uid' limit $skip,$num";
            $sql_total = "select count(*) from " . $this->tableNameSub() . " a,". $this->tableName() ." b where a.uid='$uid' and a.end_time>'$now' and a.silk_id=b.id and b.p_uid='$p_uid'";

			$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			$total = Yii::app()->lcs_r->createCommand($sql_total)->queryScalar();
			if (count($data) > 0) {
				return CommonUtils::getPage($data, $page, $num, $total);
			} else {
				return CommonUtils::getPage(array(), $page, $num, $total);
			}
		}
		return CommonUtils::getPage(array(), $page, $num, 0);
	}

	/**
	 * 根据锦囊ｉｄ获取相应的文章列表
	 */
	public function getArticleBySilkId($silk_id, $page, $num, $column = "id", $status = array()) {
		$skip = ($page - 1) * $num;
		$sql = "select $column from " . $this->tableNameArticle() . " where silk_id='$silk_id' and status in(" . implode(',', $status) . ") order by id desc limit $skip,$num";
		$sql_total = "select count(*) from " . $this->tableNameArticle() . " where silk_id='$silk_id' and status in(" . implode(',', $status) . ")";

		$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		$total = Yii::app()->lcs_r->createCommand($sql_total)->queryScalar();
		if ($data) {
			return CommonUtils::getPage($data, $page, $num, $total);
		} else {
			return CommonUtils::getPage(array(), $page, $num, $total);
		}
	}

	/**
	 * 根据文章ｉｄ获取文章数据
	 */
	public function getArticleById($ids) {
		if (count($ids) > 0) {
			$sql = "select id,p_uid,silk_id,title,image,summary,content,status,p_time from " . $this->tableNameArticle() . " where id in(" . implode(',', $ids) . ")";
			$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			if ($data) {
				return $data;
			}
		}
		return array();
	}

	/**
	 * 根据id获取锦囊购买数量
	 */
	public function getSilkSubNumById($silk_ids) {
		$res = array();
		if (count($silk_ids) > 0) {
			$sql = "select count(distinct(uid)) as count,silk_id from " . $this->tableNameSub() . " where silk_id in (" . implode(',', $silk_ids) . ") group by silk_id";
			$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			if (count($data) > 0) {
				foreach ($data as $item) {
					$res[$item['silk_id']] = $item['count'];
				}
			}
		}
		return $res;
	}

	/**
	 * 获取锦囊统计数据
	 */
	public function getSilkStatData($silk_ids) {
		$res = array();
		if (count($silk_ids) > 0) {
			$sql = "select silk_id,count(*) as total,max(p_time) as p_time from " . $this->tableNameArticle() . " where status=0 and silk_id in (" . implode(',', $silk_ids) . ") group by silk_id";
			$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			if ($data) {
				foreach ($data as $item) {
					$res[$item['silk_id']] = $item;
				}
			}
		}
		return $res;
	}

	/**
	 * 添加锦囊订阅
	 */
	public function AddSilkSub($silk_id, $uid, $end_time,$order_no='',$start_time='') {
		$now = date("Y-m-d H:i:s");
		$sql = "select id,silk_id,uid,status,end_time from " . $this->tableNameSub() . " where status=0 and end_time>'$now' and silk_id='$silk_id' and uid='$uid'";
		$sub_info = Yii::app()->lcs_r->createCommand($sql)->queryRow();

		if ($sub_info) {
			$new_end_time = $end_time;
			$sql = "update " . $this->tableNameSub() . " set end_time=:end_time,u_time=:u_time where id=:id ";
			$cmd = Yii::app()->licaishi_w->createCommand($sql);
			$cmd->bindParam(':end_time', $new_end_time, PDO::PARAM_STR);
			$cmd->bindParam(':u_time', $now, PDO::PARAM_STR);
			$cmd->bindParam(':id', $sub_info['id'], PDO::PARAM_STR);
			$res = $cmd->execute();
			if ($res) {
				return true;
			}
		} else {
			$new_end_time = $end_time;
			$sql = "insert into " . $this->tableNameSub() . " (silk_id,uid,c_time,u_time,end_time) values(:silk_id,:uid,:c_time,:u_time,:end_time)";
			$cmd = Yii::app()->licaishi_w->createCommand($sql);
			$cmd->bindParam(':silk_id', $silk_id, PDO::PARAM_STR);
			$cmd->bindParam(':uid', $uid, PDO::PARAM_STR);
			$cmd->bindParam(':c_time', $now, PDO::PARAM_STR);
			$cmd->bindParam(':u_time', $now, PDO::PARAM_STR);
			$cmd->bindParam(':end_time', $new_end_time, PDO::PARAM_STR);
			$res = $cmd->execute();
			if ($res) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 根据锦囊id获取订阅用户列表
	 */
	public function getSilkSubListBySilkids($silk_ids,$page=0,$num=0) {
		$now = date("Y-m-d H:i:s");
        if(empty($page) || empty($num)){
		    $sql = "select silk_id,uid,status,end_time from " . $this->tableNameSub() . " where silk_id in (" . implode(',', $silk_ids) . ") and status=0 and end_time>'$now'";
        }else{
            $page = $page<=0?1:$page;
            $num = $num<=0?10:$num;
            $skip = ($page-1)*$num;
		    $sql = "select silk_id,uid,status,end_time from " . $this->tableNameSub() . " where silk_id in (" . implode(',', $silk_ids) . ") and status=0 and end_time>'$now' limit $skip,$num";
        }
		$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		$res = array();
		if ($data) {
			foreach ($data as $item) {
				if (!isset($res[$item['silk_id']])) {
					$res[$item['silk_id']] = array();
				}
				$res[$item['silk_id']][] = $item;
			}
		}
		return $res;
	}

	/**
	 * 获取理财师最新一个锦囊
	 * @param type $p_uids
	 * @return array
	 */
	public function getPlannerNewSilk($p_uids) {
		$result = [];
		if (empty($p_uids)) {
			return $result;
		}
		$now = date("Y-m-d H:i:s");
		$sql = "select id,p_uid from " . $this->tableName() . " where p_uid in (" . implode(',', $p_uids) . ") and end_time>='{$now}' and status=0 order by p_uid asc,c_time asc";
		$list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		foreach ($list as $item) {				
			$result[$item['p_uid']] = $item['id'];
		}
		if (empty($result)) {
			return $result;
		}
		$info_map = $this->getSilkByIds(array_values($result));
		foreach ($result as $p_uid => $id) {
			$result[$p_uid] = isset($info_map[$id]) ? $info_map[$id] : [];
		}
		return $result;
	}
	/**
	 * 添加订阅锦囊订阅表记录
	 * @param int $uid 订阅用户uid
	 * @param int $silk_id 锦囊id
	 * @param str $end_time 到期时间
	 * @param str $order_no 订单号
	 * @param str $end_time 开始时间
	 */
	public function saveSubscriptionHis($silk_id, $uid, $end_time,$order_no,$start_time){
		//添加订阅历史
        $history = array(
            "silk_id" => $silk_id,
            "uid" => $uid,
            "order_no" => $order_no,
            "amount" => 1,
            "start_time" => $start_time,
            "end_time" => $end_time,
            "status" => 1,
            "c_time" => date("Y-m-d H:i:s"),
            "u_time" => date("Y-m-d H:i:s")
        );
        Yii::app()->licaishi_w->createCommand()->insert($this->tableNameHistory(),$history);
	}

	/**
	 * 获取理财师是否开通锦囊
	 *
	 * @param unknown_type $p_uid
	 */
	public function getSilkOnByPuid($p_uid) {
		$sql = "select id from " . $this->tableNameWhite() . " where p_uid=:p_uid and type=1 and status=1";
		$cmd = Yii::app()->lcs_r->createCommand($sql);
		$cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
		$res = $cmd->queryRow();
		if(!$res){
			return 0;
		}
		return 1;
	}
}
