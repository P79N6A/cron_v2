<?php

/**
 * 自选股上线前把旧版用户的自选股数据导入到新的表中
 *
 * @author Administrator
 */
class ExportUserStocksCommand
{

	public function init()
	{

	}

	public function run()
	{
		$this->actionMove();
	}

	public function actionMove()
	{
		#1.先找出所有的uid
		$sql = 'SELECT DISTINCT(uid) AS uid FROM `lcs_user_optional` WHERE `type`="stock_cn" ';
		$uid_arr = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
		if (empty($uid_arr))
			return false;

		$now = date('Y-m-d H:i:s');
		$sql_insert = 'insert into lcs_user_stock_group (`uid`,`group_name`,`version`,`create_time`) values ';
		$user_group = [];
		#为每个uid获取默认的分组id【有则获取id，无则插入默认分组并获取id】
		foreach ($uid_arr as $v) {
			$sql_find = 'select id,uid from lcs_user_stock_group where uid=' . $v . ' and group_name="我的自选" limit 1';
			$res = Yii::app()->lcs_r->createCommand($sql_find)->queryRow();
			if (!empty($res)) {
				$user_group[$res['uid']] = $res['id'];
			} else {
				$sql_insert_real = $sql_insert . '(' . $v . ',"我的自选",1,"' . $now . '")';
				Yii::app()->lcs_w->createCommand($sql_insert_real)->execute();
				$id = Yii::app()->lcs_w->getLastInsertID();
				$user_group[$v] = $id;
			}
		}

		#2.为每个用户迁移数据
		if (empty($user_group))
			return false;
		foreach ($user_group as $uid => $gid) {
			$sql_move = 'REPLACE into lcs_user_stock(`gid`,`symbol`,`sort`) select ' . $gid . ' as gid,`name` as `symbol`,`sort` from `lcs_user_optional` where uid=' . $uid . ' and `type`="stock_cn" limit 50';
			Yii::app()->lcs_w->createCommand($sql_move)->execute();
		}
	}

}
