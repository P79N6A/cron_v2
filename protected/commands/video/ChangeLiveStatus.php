<?php

/**
 * 更改理财师付费直播的状态
 *
 * 开始前10分钟改为即将开始
 * 结束后改为已结束
 * 
 */
class ChangeLiveStatus{

	const CRON_NO = 2002; //任务代码

	public function update(){

		$res1 = $res2 = $res3 = 0;
		//即将开始
		$live_id = PlannerLive::model()->getPlannerLiveByStatus(1);
		if(!empty($live_id)){
			$res1 = PlannerLive::model()->updPlannerLive(array('status'=>1),$live_id);
            /* 取消即将开始公众号推送 protected/commands/video/Live.php 已推送
			foreach ($live_id as $val){
				$push_data = array();
				$push_data['type'] = "createLiveNotice";
				$push_data['live_id'] = $val;
				$push_data['to_u_type'] = 1; // 1用户 2理财师
				Yii::app()->redis_w->rPush("lcs_common_message_queue",json_encode($push_data));
			}*/
		}

		//直播中
		$live_id = PlannerLive::model()->getPlannerLiveByStatus(2);
		if(!empty($live_id)){
			$res2 = PlannerLive::model()->updPlannerLive(array('status'=>2),$live_id);
		}

		//已结束 只更改视频直播状态 图文直播手动结束
		$live_id = PlannerLive::model()->getPlannerLiveByStatus(3, 1);
		if(!empty($live_id)){
			$res3 = PlannerLive::model()->updPlannerLive(array('status'=>3),$live_id);
		}

		if($res1 != 0 ||$res2 != 0 || $res3 != 0 ){
			Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '即将开始：'.$res1.'--直播中：'.$res2.'--已结束：'.$res3);
		}        
	}
    
    public function updatePlannerLastId(){
        $list = PlannerLive::model()->getVideoListList();
        if(empty($list)){
            return;
        }
        $status_map = array('1'=>'2','2'=>'1','3'=>'3');
        $result = array();
		$dict = array();
        foreach ($list as $item){
            $result[$item['s_uid']] = array('last_id'=>$item['id'],'last_live_status'=>$status_map[$item['status']],'u_time'=>$item['start_time']);
			$dict[$item['s_uid']][] = 'lcs_planner_live_hot_'.$item['id'];
        }        

		$year = date('Y');
		$week = date('W');
		$yes_week = date('W',strtotime('-7 days'));
		$week_hot_pre = "lcs_week_hot_";
		$now_week_key = $week_hot_pre.$year.'_'.$week;
		$yes_week_key = $week_hot_pre.$year.'_'.$yes_week;	
		foreach($dict as $p_uid=>$ids){
			if(empty($p_uid)){
				continue;
			}
			$total = 0;
			$clicklist = Yii::app()->redis_r->mget($ids);
			foreach($clicklist as $i){
				$total += $i;
			}
			Yii::app()->redis_w->set($now_week_key.'_'.$p_uid,$total);			
			$yes_week_hot = intval(Yii::app()->redis_r->get($yes_week_key.'_'.$p_uid));
			$week_hot = $total - $yes_week_hot;			
			$result[$p_uid]['hot'] = $total;
			$result[$p_uid]['week_hot'] = $week_hot;
		}

        foreach ($result as $s_uid=>$r){
            PlannerLive::model()->updPlannerLiveConfig(array(
				'last_id'=>$r['last_id'],
				'last_live_status'=>$r['last_live_status'],
				'hot'=>$r['hot'],
				'week_hot'=>$r['week_hot'],
				'u_time'=>$r['u_time']
			),$s_uid);
        }
    }

	public function updatePlannerHot(){
		$list = PlannerLive::model()->getVideoListList();
		$dict = array();
		foreach($list as $item){
			$dict[$item['s_uid']][] = 'lcs_planner_live_hot_'.$item['id'];
		}
		$year = date('Y');
		$week = date('W');
		$yes_week = date('W',strtotime('-7 days'));
		$week_hot_pre = "lcs_week_hot_";
		$now_week_key = $week_hot_pre.$year.'_'.$week;
		$yes_week_key = $week_hot_pre.$year.'_'.$yes_week;	
		foreach($dict as $p_uid=>$ids){
			if(empty($p_uid)){
				continue;
			}
			$total = 0;
			$clicklist = Yii::app()->redis_r->mget($ids);
			foreach($clicklist as $i){
				$total += $i;
			}
			Yii::app()->redis_w->set($now_week_key.'_'.$p_uid,$total);			
			$yes_week_hot = intval(Yii::app()->redis_r->get($yes_week_key.'_'.$p_uid));
			$week_hot = $total - $yes_week_hot;
			$u_sql = "update lcs_planner_live_config set hot='{$total}',week_hot='{$week_hot}' where s_uid='{$p_uid}'";
			// echo $u_sql."\r\n";
			Yii::app()->lcs_w->createCommand($u_sql)->execute();
		}
	}
}