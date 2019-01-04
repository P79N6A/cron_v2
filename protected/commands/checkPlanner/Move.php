<?php

/**
 * 迁移理财师身份证号码到理财师主表
 *
 * @author root
 */
class Move {

	const CRON_NO = 13202;

	public function run() {
		echo date('Y-m-d H:i:s') . "\r\n";
		$all_planner = $this->getALlPlannerIDCardNumber();
		$all_real = $this->getIDCardNumber();
		if (empty($all_planner)) {
			echo "empty \$all_planner\r\n";
			return;
		}

		$R = [];
		foreach ($all_planner as $planner_id => $idcard_number) {
			if (isset($all_real[$planner_id]) && $all_real[$planner_id] != '' && $all_real[$planner_id] != $idcard_number) {
				$R[$planner_id] = $all_real[$planner_id];
			}
		}

		if (empty($R)) {
			echo "empty data for update\r\n";
			return;
		}

		$this->updatePlannerIDCardNumber($R);

		echo "finish\r\n";
	}

	/**
	 * 获取所有绑定银行卡的理财师
	 * @return type
	 * @throws type
	 */
	public function getIDCardNumber() {
		$sql = 'SELECT uid,id_number FROM licaishi.lcs_bank_card where u_type=2';
		try {
			$list = Yii::app()->lcs_r->createCommand($sql)->queryAll() ?: [];
			$R = [];
			foreach ($list as $v) {
				$R[$v['uid']] = $v['id_number'];
			}
			return $R;
		} catch (Exception $e) {
			echo $e->getMessage();
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * 获取所有的理财师
	 * @return type
	 * @throws type
	 */
	public function getALlPlannerIDCardNumber() {
		$sql = 'SELECT s_uid,identity FROM lcs_planner';
		try {
			$list = Yii::app()->lcs_r->createCommand($sql)->queryAll() ?: [];
			$R = [];
			foreach ($list as $v) {
				$R[$v['s_uid']] = $v['identity'] ?: '';
			}
			return $R;
		} catch (Exception $e) {
			echo $e->getMessage();
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * 执行更新
	 * @param type $R
	 * @return type
	 * @throws type
	 */
	public function updatePlannerIDCardNumber($R) {
		$now = date('Y-m-d H:i:s');
		$success_num = 0;
		foreach ($R as $planner_id => $idcard_number) {
			$sql = 'update lcs_planner SET identity="' . $idcard_number . '",u_time="' . $now . '" where s_uid=' . $planner_id . ' limit 1';
			try {
				if (Yii::app()->lcs_w->createCommand($sql)->execute()) {
					$success_num++;
				}
			} catch (Exception $e) {
				echo $e->getMessage();
				throw LcsException::errorHandlerOfException($e);
			}
		}
		echo "共计" . count($R) . "条记录，成功更新${success_num}条\r\n";
	}

}
