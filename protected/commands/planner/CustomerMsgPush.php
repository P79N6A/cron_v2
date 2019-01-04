<?php


/**
* 理财师客户消息推送
* wiki:
* add by zhihao6 2016/05/24
*/

class CustomerMsgPush
{
	const CRON_NO = 1031; //任务代码

	public static $msg_type = array(
		1 => "text",
		2 => "view",
		3 => "plan",
		4 => "coupon",
	);

	function __construct()
	{}

	// 消息推送数据统计：阅读数、付费客户数，10天内
	public function pushMsgStatistics($day_time='')
	{
		if (empty($day_time)) {
			$end_time = date("Y-m-d H:i:s");
		} else {
			$end_time = date("Y-m-d 23:59:59", strtotime($day_time));
		}

		$begin_time = date("Y-m-d H:i:s", strtotime($end_time)-864000);

		// 阅读人数
		$sql = "SELECT relation_id, COUNT(*) AS total_read FROM lcs_message WHERE type=20 AND is_read=1 AND '{$begin_time}'<c_time AND c_time<='{$end_time}' GROUP BY relation_id";
		$res = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
		if (!empty($res)) {
			$sql = "";
			foreach ($res as $row) {
				$sql .= "UPDATE lcs_planner_push_msg SET total_read={$row['total_read']} WHERE id={$row['relation_id']};";
			}
			Yii::app()->lcs_w->createCommand($sql)->execute();
		}

		// 推送后产的生付费客户
		$sql = "SELECT id AS push_id,type,relation_id,u_time FROM lcs_planner_push_msg WHERE '{$begin_time}'<push_time AND push_time<='{$end_time}' AND status=0 AND type>1";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if (!empty($res)) {
			$push_ids = array();
			foreach ($res as $row) {
				$push_ids[] = $row['push_id'];
				$method = "handle".ucfirst(self::$msg_type[$row['type']])."MsgPaidUser";
				$this->$method($row['push_id'], $row['u_time'], $row['relation_id']);
			}

			// 统计付费客户数
			$sql = "SELECT push_id, COUNT(*) AS total_buy FROM lcs_planner_push_buy_user WHERE push_id IN (". implode(',', $push_ids) .") GROUP BY push_id";
			$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			if (!empty($res)) {
				$sql = "";
				foreach ($res as $row) {
					$sql .= "UPDATE lcs_planner_push_msg SET total_buy={$row['total_buy']} WHERE id={$row['push_id']};";
				}
				Yii::app()->lcs_w->createCommand($sql)->execute();
			}
		}

		Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "[OK]:理财师客户分组消息推送统计:{$begin_time}:{$end_time}");
	}
	// 订阅观点所属观点包，且收到了理财师推送信息的客户
	private function handleViewMsgPaidUser($push_id, $push_time, $v_id)
	{
		$sql = "SELECT pkg_id FROM lcs_view WHERE id={$v_id}";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
		if (empty($res)) {
			return true;
		} else {
			$pkg_id = current($res);
			unset($res);
		}

		$sql = "SELECT uid,u_time FROM lcs_package_subscription WHERE pkg_id={$pkg_id} AND u_time>'{$push_time}' AND end_time>'{$push_time}'";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if (empty($res)) {
			return true;			
		} else {
			$uids_map = array();
			foreach ($res as $row) {
				$uids_map[$row['uid']] = $row;
			}
			unset($res);
		}

		$push_users = $this->filterUnpushUser($push_id, array_keys($uids_map));
		if (empty($push_users)) {
			return true;
		}
		$push_users = $this->filterExistUser($push_id, $push_users);
		if (empty($push_users)) {
			return true;
		}

		$sql = "INSERT INTO lcs_planner_push_buy_user (push_id,uid,pay_time,c_time,u_time) VALUES ";
		foreach ($push_users as $uid) {
			$sql .= " ({$push_id},{$uid},'{$uids_map[$uid]['u_time']}','{$uids_map[$uid]['u_time']}','{$uids_map[$uid]['u_time']}'),";
		}
		$sql = rtrim($sql, ',');
		try{
			Yii::app()->lcs_w->createCommand($sql)->execute();
		} catch(exception $e) {
            // 表里已经有数据，不做操作
        }
	}
	// 订阅计划，且收到了理财师推送消息的客户
	private function handlePlanMsgPaidUser($push_id, $push_time, $pln_id)
	{
		$sql = "SELECT uid,c_time FROM lcs_plan_subscription WHERE pln_id={$pln_id} AND c_time>'{$push_time}'";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if (empty($res)) {
			return true;
		} else {
			$uids_map = array();
			foreach ($res as $row) {
				$uids_map[$row['uid']] = $row;
			}
			unset($res);
		}

		$push_users = $this->filterUnpushUser($push_id, array_keys($uids_map));
		if (empty($push_users)) {
			return true;
		}
		$push_users = $this->filterExistUser($push_id, $push_users);
		if (empty($push_users)) {
			return true;
		}
		
		$sql = "INSERT INTO lcs_planner_push_buy_user (push_id,uid,pay_time,c_time,u_time) VALUES ";
		foreach ($push_users as $uid) {
			$sql .= " ({$push_id},{$uid},'{$uids_map[$uid]['c_time']}','{$uids_map[$uid]['c_time']}','{$uids_map[$uid]['c_time']}'),";
		}
		$sql = rtrim($sql, ',');
		try{
			Yii::app()->lcs_w->createCommand($sql)->execute();
		} catch(exception $e) {
            // 表里已经有数据，不做操作
        }
	}
	// 抢了理财师推送优惠券的客户
	private function handleCouponMsgPaidUser($push_id, $push_time, $cpn_id)
	{
		$sql = "SELECT uid,c_time FROM lcs_user_coupon WHERE coupon_id={$cpn_id} AND c_time>'{$push_time}'";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if (empty($res)) {
			return true;
		} else {
			$uids_map = array();
			foreach ($res as $row) {
				$uids_map[$row['uid']] = $row;
			}
			unset($res);
		}

		$push_users = $this->filterUnpushUser($push_id, array_keys($uids_map));
		if (empty($push_users)) {
			return true;
		}

		$sql = "SELECT count(*) FROM lcs_user_coupon WHERE coupon_id={$cpn_id} AND uid IN (". implode(',', $push_users) .") AND status=1";
		$total_used_users = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
		$sql = "UPDATE lcs_planner_push_msg SET meta='". json_encode(array("total_coupon_used" => $total_used_users)) ."' WHERE id={$push_id};";
		Yii::app()->lcs_w->createCommand($sql)->execute();

		$push_users = $this->filterExistUser($push_id, $push_users);
		if (empty($push_users)) {
			return true;
		}

		$sql = "INSERT INTO lcs_planner_push_buy_user (push_id,uid,pay_time,c_time,u_time) VALUES ";
		foreach ($push_users as $uid) {
			$sql .= " ({$push_id},{$uid},'{$uids_map[$uid]['c_time']}','{$uids_map[$uid]['c_time']}','{$uids_map[$uid]['c_time']}'),";
		}
		$sql = rtrim($sql, ',');
		try{
			Yii::app()->lcs_w->createCommand($sql)->execute();
		} catch(exception $e) {
            // 表里已经有数据，不做操作
        }
	}
	// 过滤掉未推送消息的客户
	private function filterUnpushUser($push_id, $uids)
	{
		if (empty($uids)) {
			return array();
		} else {
			$sql = "SELECT uid FROM lcs_message WHERE type=20 AND relation_id={$push_id} AND uid IN (". implode(',', $uids) .")";
			$res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
			if (!empty($res)) {
				return $res;
			} else {
				return array();
			}
		}
	}
	// 过滤掉已经存在的客户
	private function filterExistUser($push_id, $uids)
	{
		$sql = "SELECT uid FROM lcs_planner_push_buy_user WHERE push_id={$push_id} AND uid IN (". implode(',', $uids) .")";
		$exist_users = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
		$uids = array_diff($uids, $exist_users);
		if (empty($uids)) {
			return array();
		} else {
			return $uids;
		}
	}


}

