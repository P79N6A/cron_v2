<?php

/**
 * Description of ActionVote
 *
 * @author Administrator 
 */
class ActionVote extends CActiveRecord
{

	private static $newTime = '2017-10-26 22:30:00';

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * 修改某个理财师的数据-Redis
	 * @param type $lcs_id
	 * @param type $vote_num_add
	 * @param type $fans_num_add
	 * @param type $status 审核状态 1通过 0不通过
	 * @param type $plannerStatus 理财师系统理财师状态 0正常 -1删除 -2冻结
	 * @return type
	 */
	public function updataPuserVoteRedisData($lcs_id, $vote_num_add, $fans_num_add, $status, $plannerStatus, $score_old)
	{
		//从数据库获取投票数和粉丝数
		$voteNum = $this->getPuserVoteNum($lcs_id, 'all');
		$fansNum = $this->getPuserAttentionNum($lcs_id, 'all');

		//如果审核状态为0不通过 或者 理财师系统理财师状态为-2冻结  则从zSet集合中删除该用户
		//先删除再添加
		Yii::app()->redis_w->zDelete('lcs_action_vote_p_user_score', 'lcs_' . $lcs_id);
		if ($status != 0 && $plannerStatus != -2) {
			$score = (int) $this->getScore($voteNum + $vote_num_add, $fansNum + $fans_num_add, $score_old);
			echo " \$score:$score ";
			Yii::app()->redis_w->zAdd('lcs_action_vote_p_user_score', $score, 'lcs_' . $lcs_id);
		} else {
			echo "删除排名 -> \$status:$status \$plannerStatus:$plannerStatus ";
		}

		//修改投票数
		Yii::app()->redis_w->hset('lcs_action_vote_p_user_vote_num', 'lcs_' . $lcs_id, $voteNum + $vote_num_add);
		//修改粉丝数
		Yii::app()->redis_w->hset('lcs_action_vote_p_user_fans_num', 'lcs_' . $lcs_id, $fansNum + $fans_num_add);
		echo " vote_num:" . ($voteNum + $vote_num_add);
		echo " fans_num:" . ($fansNum + $fans_num_add);
		return true;
	}

	/**
	 * 获取理财师系统中理财师的状态
	 * @data 0正常 -1删除 -2冻结
	 * @param type $p_uid
	 * @return type
	 */
	public function getPlannerStatus($p_uid)
	{
		$sql = 'select `status` from `lcs_planner` where `s_uid`=' . $p_uid . ' limit 1';
		$cmd = Yii::app()->lcs_r->createCommand($sql);
		return (int) ($cmd->queryScalar() ?: 0);
	}

	/**
	 * 获取某个理财师的人气值(包含修改的数据)
	 * @param type $lcs_id
	 */
	public function getPuserVoteScore($lcs_id)
	{
		$voteNum = $this->getPuserVoteNum($lcs_id, 'all');
		$fansNum = $this->getPuserAttentionNum($lcs_id, 'all');
		$data_ext = $this->getPuserVoteDataExtion($lcs_id);

		$score = (int) $this->getScore($voteNum + ((int) $data_ext['vote_num_add'] ?: 0), $fansNum + ((int) $data_ext['fans_num_add'] ?: 0), $data_ext['score_old']);
		return $score;
	}

	/**
	 * getPuserVoteDataExtion
	 * @param type $lcs_id
	 * @return type
	 */
	public function getPuserVoteDataExtion($lcs_id)
	{
		$sql = 'select * from `lcs_action_vote_in` where `p_uid`=' . $lcs_id . ' limit 1';
		return $cmd = Yii::app()->lcs_r->createCommand($sql)->queryRow();
	}

	/**
	 * 通过公式计算人气值
	 * @param type $voteNum
	 * @param type $fansNum
	 * @param type $score_old
	 * @return type
	 */
	public function getScore($voteNum, $fansNum, $score_old)
	{
		return (int) ($voteNum*0 + $fansNum*910 + $score_old);
	}

	/**
	 * 获取参入投票的理财师列表
	 */
	public function getVoteInList()
	{
		$sql = 'select p_uid,`status`,vote_num_add,fans_num_add,score_old from lcs_action_vote_in where 1=1';
		$cmd = Yii::app()->lcs_r->createCommand($sql);
		return $cmd->queryAll();
	}

	/**
	 * 获取投票的时间节点
	 * @return type
	 */
	public function getVoteTimes()
	{
		$param = [];
		$param['vote_in_time'] = Yii::app()->redis_r->get('lcs_action_vote_voteInTime') ?: '';
		$param['start_time'] = Yii::app()->redis_r->get('lcs_action_vote_satrTime') ?: '';
		$param['end_time'] = Yii::app()->redis_r->get('lcs_action_vote_stopTime') ?: '';
		return $param;
	}

	/**
	 * 获取该用户的投票数(当天/总的)
	 * @param type $p_uid
	 * @param type $type
	 */
	public function getPuserVoteNum($p_uid, $type = 'all')
	{
		if ($type == 'today')
			$time_start = date('Y-m-d 00:00:00');
		else {
//			$time_start = Yii::app()->redis_r->get('lcs_action_vote_satrTime');
			$time_start = self::$newTime;
		}
		$time_end = date('Y-m-d H:i:s');

		$sql = 'select count(*) as num from `lcs_action_vote_list` where `p_uid`=' . $p_uid . ' and `c_time`>="' . $time_start . '" and `c_time`<"' . $time_end . '"';
		$cmd = Yii::app()->lcs_r->createCommand($sql);
		return (int) ($cmd->queryScalar() ?: 0);
	}

	/**
	 * 获取该用户的关注数(当天/总的)
	 * @param type $p_uid
	 * @param type $type
	 */
	public function getPuserAttentionNum($p_uid, $type = 'all')
	{
		if ($type == 'today')
			$time_start = date('Y-m-d 00:00:00');
		else {
//			$time_start = Yii::app()->redis_r->get('lcs_action_vote_satrTime');
			$time_start = self::$newTime;
		}
		$time_end = date('Y-m-d H:i:s');

		$sql = 'select count(*) as num from `lcs_attention` where `p_uid`=' . $p_uid . ' and `c_time`>="' . $time_start . '" and `c_time`<"' . $time_end . '"';
		$cmd = Yii::app()->lcs_r->createCommand($sql);
		return (int) ($cmd->queryScalar() ?: 0);
	}

	//对理财师的投票数进行统计
	public function VoteCount()
	{
		$now_time = date('Y-m-d H:i:s');
		$before_time = date('Y-m-d H:i:s', strtotime('-5 minute'));
		$data = [];
		$sql = "select p_uid,count(*) as total from lcs_action_vote_list where c_time between '$before_time' and '$now_time' group by p_uid";
		try {
			echo "当前时间：" . date('Y-m-d H:i:s');
			echo "\r\n";
			$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			if (!empty($res)) {
				foreach ($res as $k => $v) {
					$data[$v['p_uid']] = $v['total'];
					echo "lcs_id: " . $v['p_uid'] . " total: " . $v['total'];
					echo "\r\n";
				}
				echo "\r\n";
			}
			return $data;
		} catch (Exception $e) {
			return $data;
		}
	}

	//对统计的投票数进行缓存记录
	public function VoteRecord($data = array())
	{
		foreach ($data as $k => $v) {
			Yii::app()->redis_w->hset('lcs_vote_error_record_count_new', $k, $v);
			$number = intval(Yii::app()->redis_r->hget('lcs_vote_error_record_count', $k));
			if ($v > $number) {
				Yii::app()->redis_w->hset('lcs_vote_error_record_count', $k, $v);
			}
		}
	}

}
