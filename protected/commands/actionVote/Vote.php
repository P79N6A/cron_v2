<?php

/**
 * 投票活动 - 世界中心之新浪理财师崛起
 *
 * @author Administrator
 */
class Vote
{

	const CRON_NO = 13201; //任务代码

	public function __construct()
	{

	}

	/**
	 * 定时排名及人气值计算
	 */
	public function run()
	{
		echo date('Y-m-d H:i:s') . "\r\nJob start\r\n";
		$time_start = microtime(true);
		//获取参入投票的理财师列表
		$p_users = ActionVote::model()->getVoteInList();
		//清除旧的排名
		Yii::app()->redis_w->delete('lcs_action_vote_p_user_score');
		//获取每个理财师的人气值
		foreach ($p_users ?: [] as $v) {
			echo "lcs_id:" . $v['p_uid'] . " : ";

			$plannerStatus = ActionVote::model()->getPlannerStatus($v['p_uid']);
			//写入redis的有序集合中
			ActionVote::model()->updataPuserVoteRedisData($v['p_uid'], $v['vote_num_add'], $v['fans_num_add'], $v['status'], $plannerStatus, $v['score_old']);

			echo "\r\n";
		}
		$end_start = microtime(true);
		echo "统计完成 共计耗时：" . ($end_start - $time_start) . "秒\r\n";
		echo "\r\n";
	}

	/**
	 * 把缓存中人气值存储到数据库
	 */
	public function setOldData()
	{
		echo date('Y-m-d H:i:s') . "\r\nJob start\r\n";
		$time_start = microtime(true);

		$vote_infos = Yii::app()->redis_r->zRevRange('lcs_action_vote_p_user_score', 0, -1, true);
		$index = 1;
		$R = [];
		$now = date('Y-m-d H:i:s');
		foreach ($vote_infos ?: [] as $k => $v) {
			$p_uid = substr($k, 4);
			$R[$p_uid] = ['score' => $v, 'index' => $index];

			$sql = "UPDATE lcs_action_vote_in SET score_old= $v,u_time='$now' WHERE p_uid= $p_uid LIMIT 1";
			try {
				Yii::app()->lcs_w->createCommand($sql)->execute();
			} catch (Exception $e) {

			}
			$index++;
		}
		$end_start = microtime(true);
		echo "转移完成 共计耗时：" . ($end_start - $time_start) . "秒\r\n";
		echo "\r\n";
	}

}
