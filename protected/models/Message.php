<?php

class Message extends CActiveRecord
{
    const MSG_TYPE_ASK = 1;//提问
    const MSG_TYPE_VIEW = 2;//观点
    const MSG_TYPE_ORDERS = 3;//通知（原：支付）
    const MSG_TYPE_TRADE = 4;//交易
    const MSG_TYPE_INCOME = 5;//收益
    const MSG_TYPE_COMMENT = 6;//评论
    const MSG_TYPE_PLANNER_COMMENT = 7;//理财师说
    const msg_type_buy_pkg_comment = 8;//已购观点包说说回复
    const msg_type_attention_pkg_comment = 9;//9关注观点包说说回复
    const msg_type_attention_plan_INCOME = 10; //10观察计划收益
    const MSG_TYPE_PKG_PLANNER_COMMENT = 11; //11观点和观点包的理财师说
    const MSG_TYPE_OPERATE_NOTICE = 12; //运营通知
    const MSG_TYPE_COMMENT_PRAISE=13; //说说赞
    const MSG_TYPE_COMMENT_NEW=14; //未读新说说
    const MSG_TYPE_PLAN=15; //计划通知
    const MSG_TYPE_COMMENT_REPLY=16; //说说回复通知
    const MSG_TYPE_PLAN_GRADE=17; //计划评价通知
    const MSG_TYPE_PACKAGE=18; //观点包状态变化
    const MSG_TYPE_PACKAGE_GRADE=19; //观点包评级通知
    const MSG_TYPE_CUSTOMER_PUSH=20; //理财师客户通知
    const MSG_TYPE_PLANNER_LIVE=21; //理财师直播通知
    const MSG_TYPE_WX_PUSH_MSG=22; //微信通知消息
    const MSG_TYPE_WX_PUSH_MSG_NOTICE=23; //微信通知消息
    const MSG_TYPE_WX_PUSH_MSG_STRAGES=24; //微信通知消息
    const MSG_TYPE_ATTENTION_USER_TRADE = 28; //28用户动态收藏
    const MSG_TYPE_USER_TRADE = 29; //29已购用户动态

    const MSG_CHANNEL_WXPP = 15; //微信公众平台
    const MSG_CHANNEL_ANDROID = 2; //android平台
    const MSG_CHANNEL_IOS = 3; //ios平台
    const MSG_CHANNEL_THIRD_AUTH = 6; //第三方授权
    private $msg_type=array(-1,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,28,29);


    const U_TYPE_USER=1; //用户
    const U_TYPE_PLANNER=2;//理财师


    const MESSAGE_QUEUE_FAST = 'lcs_fast_message_queue';
    const MESSAGE_QUEUE_COMMENT = 'lcs_common_message_queue';

    private static $_model=null;

    private $remove_max_limit = 50000; //50000

    /**
     * 初始化方法
     * @param system $className
     * @return multitype:|unknown
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

	/**
	 * 消息表
	 * @return string
	 */
	public function tableName()
	{
		return "lcs_message";
	}
    /**
     * 根据用户id 获取对应的消息表
     * @param $uid 用户id
     */
    public function tableNameByUid($uid)
    {
        if ($uid) {
            return TABLE_PREFIX . 'message_' . substr($uid, -1);
        }
        return false;
    }

    /**
     * 消息队列日志表
     * @return string
     */
    public function tableNameQueueLog()
    {
        return "lcs_message_queue_log";
    }

    public function tableNameChannelUser(){
        return 'lcs_message_channel_user';
    }

    public function tableNameUserClose(){
        return 'lcs_message_user_close';
    }


    /**
     * 清理消息队列日志
     * @param $end_time
     * @return int
     */
    public function removeMessageQueueLog($end_time){
        $sql = 'delete from '.$this->tableNameQueueLog().' where c_time<:end_time limit :limit;';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':end_time',$end_time, PDO::PARAM_STR);
        $cmd->bindParam(':limit',$this->remove_max_limit, PDO::PARAM_INT);
        $total = 0;
        do{
            $records = $cmd->execute();
            $total +=$records;
        }while($records>=$this->remove_max_limit);

        return $total;
    }

    /**
     * 保存消息
     * @param $data
     * @return int
     */
    public function saveMessage($data){
        $data['c_time']=date('Y-m-d H:i:s');
        $data['u_time']=$data['c_time'];
        if(empty($data['uid'])){
            return false;
        }
        $uid = $data['uid'];
        $res = Yii::app()->lcs_w->createCommand()->insert($this->tableNameByUid($uid), $data);
        if($res==1){
            $id = Yii::app()->lcs_w->getLastInsertID();
            return empty($id) ? 1 : $id;
        }else{
            return $res;
        }
    }


    /**
     * 保存消息队列处理日志
     * @param $data
     * @return int
     */
    public function saveMessageQueueLog($data){
        $data['c_time']=date('Y-m-d H:i:s');
        $data['u_time']=$data['c_time'];
        $res = Yii::app()->lcs_w->createCommand()->insert($this->tableNameQueueLog(), $data);
        if($res==1){
            $id = Yii::app()->lcs_w->getLastInsertID();
            return empty($id) ? 1 : $id;
        }else{
            return $res;
        }
    }


	/**
	 * 修改消息队列处理日志
	 * @param array $columns
	 * @param number $id
	 * @return
	 */
	public function updateMessageQueueLog($columns, $id){
        $columns['u_time']=date('Y-m-d H:i:s');
		return Yii::app()->lcs_w->createCommand()->update($this->tableNameQueueLog(),$columns,'id=:id',array(':id'=>$id));
		
	}

    /**
     * 获取所有理财师类型的微博ID
     * @return mixed
     */
    public function getAllChannelSUid(){
        $sql = "select distinct(s_uid) from ".$this->tableNameChannelUser()." where u_type=2;";
        return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }

    /**
     * 获取所有用户类型的uid
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function getAllChannelUid($offset=0,$limit=10000,$type = 0){
        if($type == 0 || empty($type)){
            $sql = "select distinct(uid) from ".$this->tableNameChannelUser()." where u_type=1 order by uid asc limit :offset,:limit;";
        }else{
            $sql = "select distinct(uid) from ".$this->tableNameChannelUser()." where u_type=1 and channel_type in (".implode(',',$type).") order by uid asc limit :offset,:limit;";
        }
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(":offset",$offset,PDO::PARAM_INT);
        $cmd->bindParam(":limit",$limit,PDO::PARAM_INT);
        return $cmd->queryColumn();
    }

    /**
     * 获取通知渠道的用户UID  排除重复
     * @param array $s_uids
     */
    public function getChannelUidBySuids($s_uids=array()){
        $cdn='';
        if(!empty($s_uids)){
            $s_uids=(array)$s_uids;
            $cdn = " where s_uid in (".implode(',',$s_uids).")";
        }
        $sql = "select distinct(uid) from ".$this->tableNameChannelUser().$cdn.";";
        return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }


    /**
     * 获取通知用户的信息
     * @param $uids
     * @return array
     */
    public function getChannelUserInfoByUid($uids,$u_type=1){
        if(empty($uids)){
            return array();
        }
        $uids=(array)$uids;
        $sql = "select channel_type,channel_id,u_type,s_uid,uid,s_id from ".$this->tableNameChannelUser()." where uid in (".implode(',',$uids).")";
        if($u_type==1){
            $sql .=" and u_type=".intval($u_type).";";
        }else if($u_type==2){
            $sql .=" and (u_type=".intval($u_type)." or (u_type=1 and (channel_type=1 or channel_type=15)));";
        }
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    /**
     * 过滤关闭提醒的用户
     * @param $uids
     * @param $u_type
     * @param $close_type
     * @param $client_type
     * @return array
     */
    public function filterCloseUids($uids,$u_type,$close_type,$client_type){
        $uids_close = array();
        $uids = (array)$uids;
        if(!empty($uids)){
            $uids_close = $this->getCloseMessageUsers($uids,$u_type,$close_type,$client_type);
        }

        if(!empty($uids_close)){
            $uids = array_diff($uids,$uids_close);
        }

        return array_values($uids);
    }



    /**
     * 获取关闭通知的用户uid
     * @param $uids
     * @param $u_type  //用户类型 1:用户 2 理财师
     * @param $close_type  //关闭类型 1提问 2观点 3通知 4交易 5收益 6评论 7理财师说
     * @param $client_type  //客户端类型 1web和客户端 2客户端
     * @return array
     */
    public function getCloseMessageUsers($uids,$u_type,$close_type,$client_type){
        $uids = (array)$uids;
        $u_type = intval($u_type);
        $close_type = intval($close_type);
        $client_type = intval($client_type);

        $uids_str = implode(",",$uids);
        if(empty($uids_str)){
            return array();
        }

        return Yii::app()->lcs_r->createCommand("select uid from ".$this->tableNameUserClose()." where uid in (".$uids_str.") and u_type=$u_type and close_type=$close_type and client_type=$client_type")->queryColumn();
    }
    public function addMessageToQueue($queue_key,$push_data){
        if(is_array($push_data)){
            $push_data = json_encode($push_data);
        }
        Yii::app()->redis_w->rPush($queue_key,$push_data);
    }

}
