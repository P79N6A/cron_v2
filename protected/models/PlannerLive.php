<?php

/**
 * 理财师直播
 * add by zhihao6 2016/08/12
 */

class PlannerLive extends CActiveRecord
{
	private $time = 610;//10分钟内算即将开始
    private $openTime = 300; //提前5分钟开启直播

	
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


    public function tableName()
    {
        return TABLE_PREFIX.'planner_live';
    }
    public function tableNameConfig(){
        return TABLE_PREFIX.'planner_live_config';
    }

    public function tableNameSubscription()
    {
        return TABLE_PREFIX.'live_subscription';
    }

    public function tableNameAttention()
    {
        return TABLE_PREFIX.'collect';
    }

    public function getLiveInfoByids($ids, $fields=null)
    {
        $return = array();
        $ids = (array)$ids;
        if(empty($ids)) {
            return $return;
        }

        $select = 'id';
        if(!empty($fields)) {
            if(is_array($fields)) {
                $select = implode(',',$fields);
            } else if(is_string($fields)) {
                $select = $fields;
            }
        }

        $sql = "select {$select} from ".$this->tableName()." where id in (". implode(',', $ids) .");";
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if($list) {
            foreach ($list as $vals) {
                $return[$vals['id']] = $vals;
            }
        }
        return $return;
    }

    public function getLiveSubscriptionUser($p_uid)
    {
        $sql = "select uid from ".$this->tableNameSubscription()." where s_uid=:p_uid and end_time>'".date("Y-m-d H:i:s")."'";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
        return $cmd->queryColumn();
    }

    public function getLiveAttentionUser($p_uid)
    {
        $sql = "select uid from ".$this->tableNameAttention()." where type=5 and relation_id=:p_uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
        return $cmd->queryColumn();
    }
    
    /**
     * 获取即将开始的和结束的直播但是状态还没变的
     * @param int $type 1 即将开始 2直播中 3 已结束
     * @param null $liveType 1 视频直播 2图文直播
     * @return array
     */
    public function getPlannerLiveByStatus($type = 1, $liveType = null)
    {
        if ($type < 1 || $type > 3) {
            return array();
        }

        if ($type == 1) {
            $time = date('Y-m-d H:i:s', time() + $this->time);
            $sql  = "select id from ".$this->tableName()." where video_type<>1 and status=0 and start_time<='$time'";
        } elseif ($type == 2) {
            $time = date('Y-m-d H:i:s');
            $sql  = "select id from ".$this->tableName()." where video_type<>1 and status=1 and start_time<='$time'";
        } elseif ($type == 3) {
            $time = date('Y-m-d H:i:s');
            $sql  = "select id from ".$this->tableName()." where ((status=2 and video_type<>1) or (type=1 and video_type=1 and status=1)) and end_time<='$time'";                        
        }

        if(!empty($liveType)){
            $sql .= ' and type = '.intval($liveType);
        }    

        return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }

	/**
     * 更新直播的数据
     *
     * @param unknown_type $data
     * @param unknown_type $live_id
     */
	public function updPlannerLive($data,$live_id = array()){
		
		$live_id = (array) $live_id;
		$res = 0;
        $curl = Yii::app()->curl;
        $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
        $url = LCS_WEB_INNER_URL.'/cacheApi/mcCache';
		if(!empty($live_id)){
			$res = Yii::app()->lcs_w->createCommand()->update($this->tableName(),$data,"id in (".implode(",",$live_id).")");
            foreach ($live_id as $id){
                $key = 'lcs_planner_live_'.$id;
                $params = array('key'=>$key,'opt'=>1);
                $curl->get($url,$params);
            }
		}
		return $res;
	}

    /**
     * 获取即将开始直播列表
     * mark: 严格根据开始时间 | 根据status字段 ？
     * @param null $type
     * @return mixed
     */
    public function getCommingLive($type = null)
    {
        $nowTime  = date('Y-m-d H:i:s');
        $openTime = date('Y-m-d H:i:s', time() + $this->openTime);

        $sql = 'select * from '.$this->tableName().' where start_time > "'.$nowTime.'" and start_time <= "'.$openTime.'"';
        if (!empty($type)) {
            $sql .= ' and type = '.intval($type);
        }

        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    /**
     * 根据状态获取直播
     * @param $status
     * @param null $type 视频类型
     * @return mixed
     */
    public function getLiveByStatus($status, $type = null)
    {
        $sql = 'select * from '.$this->tableName().' where status = '.intval($status);
        if (!empty($type)) {
            $sql .= ' and type = '.intval($type);
        }

        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    /**
     * 根据时间获取直播
     * @param $time array('start_time' => array('eq', '2016-08-10 12:11:11'),);
     * @param null $type
     * @param null $status
     * @return bool
     */
    public function getLiveByTime($time, $type = null, $status = null)
    {
        if (empty($time) || !is_array($time)) {
            return false;
        }

        $exp = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=',);
        $sql = 'SELECT * FROM '.$this->tableName().' WHERE 1 ';
        foreach ($time as $field => $cond) {
            if (isset($exp[$cond[0]])) {
                $sql .= ' AND '.$field.' '.$exp[$cond[0]].' "'.$cond[1].'"';
            }
        }

        if (!empty($type)) {
            $sql .= ' AND type = '.intval($type);
        }

        if (!empty($status)) {
            $sql .= is_numeric($status) ? ' AND status = '.intval($status) : ' AND status in ('.join(',', $status).')';
        }

        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    /**
     * 获取直播列表
     * @return type
     */
    public function getLiveList(){
        $sql = "select id,s_uid,status from {$this->tableName()} where status<>-1 order by id asc";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    /**
     * 获取视频直播列表
     * @return type
     */
    public function getVideoListList(){
        $sql = "select id,s_uid,status,start_time from {$this->tableName()} where status<>-1 and type=1 order by id asc";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    
    public function updPlannerLiveConfig($data,$s_uid){				
		$res = 0;     
		if(!empty($s_uid)){
			$res = Yii::app()->lcs_w->createCommand()->update($this->tableNameConfig(),$data,"s_uid=".$s_uid);            
		}
		return $res;
	}
    /**
    * 检查用户是否订阅交易师课程
    * @param $uid 用户uid
    */
    public function checkKtSub($uid){
        $sql = "select count(*) as total from {$this->tableNameSubscription()} where uid='{$uid}' and s_uid in (1300871220,1504965870)";
        $total = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        if($total > 0){
            return true;
        }else{
            return false;
        }
    }
    /**
    * 监控到达直播时间，未收到
    */
    public function monitorVideoLive(){
        $now = date('Y-m-d H:i:s');
        $sql = "select * from {$this->tableName()} where status=1 and type=1 and start_time<='{$now}' and end_time>'{$now}'";        
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $list;
    }

}
