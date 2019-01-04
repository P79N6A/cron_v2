<?php
/**
 * create by zhihao6 2017/01/06
 *
 * 理财师圈子公告提醒
 */

class PlannerCircleNoticeMessageHandler {

    private $commonHandler = null;

    public function __construct() {
        $this->commonHandler = new CommonHandler();
    }

    /**
     * 圈子公告提醒 
     */
    public function run($msg) {
        $log_data = array('status'=>1, 'result'=>'ok');
        try {
            // 验证必填项目
            // lcs_common_message_queue
            // {"type":"plannerCircleNotice","n_id":71}
            $this->commonHandler->checkRequireParam($msg, array('n_id'));
            $notice_id = $msg['n_id'];
            $pushType = 0;
            $pushType = $msg['pushType'];
            
            // 公告信息
            $notice_info = CircleCommentService::getCircleNotice($notice_id);
            if (empty($notice_info)) {
                throw new Exception('未知的公告信息');
            }

            /**
             * 验证推送时间
             */
            
            $redis_key_expire = MEM_PRE_KEY."cron2_is_push_expire_".$notice_info['uid'];
            echo $redis_key_expire."\n";
            $isPush = Yii::app()->redis_w->get($redis_key_expire);
            if(!empty($isPush)){
                echo "理财师已经推送过了,不进行推送\n";
                return;
            }else{
                echo "记录推送记录:".Yii::app()->redis_w->setex($redis_key_expire,600,"ispush");
            }
            
            $status1 = $this->toCircleUser($notice_id, $notice_info, $log_data,$pushType);
            $status2 = $this->toCirclePlannerUser($notice_id, $notice_info, $log_data,$pushType);
            if (!empty($status1) && !empty($status2)) {
                throw new Exception("{$status1}|{$status2}");
            }

            $log_data['relation_id'] = $notice_id;
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
// print_r("{$log_data['result']}\n");
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])) {
            $log_data['ext_data'] = empty($log_data['ext_data']) ? array() : $log_data['ext_data'];
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

    private function toCircleUser($notice_id, $notice_info, &$log_data, $pushType=0) {
        // 圈子用户
        $push_uids = [];
        $push_uids_planner = Planner::model()->getPlannerUids($notice_info['uid']);
        foreach ($push_uids_planner as $key => $value) {
            $push_uids[] = $value['uid'];
        }
        if (($notice_info['u_type'] == 1) && in_array($notice_info['uid'], $push_uids)) {
            $push_uids = array_diff($push_uids, array($notice_info['uid']));
        }
        if (empty($push_uids)) {
            return '当前无在线用户';
        } else {
            $push_uids = array_unique($push_uids);
        }

        $planner_info = Planner::model()->getPlannerById($notice_info['uid']);
        $planner_info = isset($planner_info[$notice_info['uid']]) ? $planner_info[$notice_info['uid']] : null;

        //获取理财师tab信息
        $redis_key = MEM_PRE_KEY."planner_tab_".$planner_info['p_uid'];
        $data = json_decode(Yii::app()->redis_r->get($redis_key),true);
        $planner_tab = [];
        if(!empty($data)){
            $planner_tab = $data;
        }

        $notice_type = Circle::$notice_type[$notice_info['type']];
        if ($notice_info['type'] == 2) {
            $notice_content = "{$planner_info['name']}：【直播预告】主题：{$notice_info['title']}";
        } else {
            $notice_content = $planner_info['name'] . "：" . $notice_info["{$notice_type}_notice_info"]['content'];
        }
        if($pushType == 1){
            $notice_content = "您关注的{$planner_info['name']}的视频直播开始了,点击前往观看";
        }

        foreach($push_uids as $uid){
            // 组装消息
            $msg_data = array(
                'uid' => $uid,
                'u_type' => 1,
                'type' => 23,
                'relation_id' => $notice_info['circle_id'],
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => $notice_content, 'class' => "", 'link' => ""),
                ),JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    "id" => $notice_info['id'],
                    'type' => $notice_info['type'],
                    'circle_id' => $notice_info['circle_id'],
                    'relation_id' => $notice_info['circle_id'],
                    'planner_tab' => $planner_tab,
                    'title' => CHtml::encode($notice_info['title']),
                    'content' => $notice_content,
                    "{$notice_type}_notice_info" => $notice_info["{$notice_type}_notice_info"],
                ),JSON_UNESCAPED_UNICODE),
                'link_url' => "",
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            $msg_data['id'] = Message::model()->saveMessage($msg_data);
        }
        
        if (!empty($log_data['ext_data']['uids'])) {
            $log_data['ext_data']['uids']=array_merge($log_data['ext_data']['uids'],$push_uids);
        } else {
            $log_data['ext_data']['uids']=$push_uids;
        }
// print_r("toUser:\t");
// print_r($push_uids);
        // 加入提醒队列
        // $msg_data['content'] = json_decode($msg_data['content'], true);
        //将用户500个一组，分批放入队列。否则会导致数据过大。
        $uids_arr = array_chunk($push_uids, 500);
        foreach($uids_arr as $_uids){
            $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(13,14));
        }
    }
    private function toCirclePlannerUser($notice_id, $notice_info, &$log_data, $pushType = 0) {
        // 圈子用户
        $push_uids = [];
        $push_uids = Circle::model()->getCircleUser($notice_info['circle_id'], 2);
        if (($notice_info['u_type'] == 2) && in_array($notice_info['uid'], $push_uids)) {
            $push_uids = array_diff($push_uids, array($notice_info['uid']));
        }
        if (empty($push_uids)) {
            return '当前无在线理财师用户';
        } else {
            $push_uids = array_unique($push_uids);
        }

        $planner_info = Planner::model()->getPlannerById($notice_info['uid']);
        $planner_info = isset($planner_info[$notice_info['uid']]) ? $planner_info[$notice_info['uid']] : null;
        $notice_type = Circle::$notice_type[$notice_info['type']];
        if ($notice_info['type'] == 2) {
            $notice_content = "{$planner_info['name']}：【直播预告】主题：{$notice_info['title']}";
        } else {
            $notice_content = $planner_info['name'] . "：" . $notice_info["{$notice_type}_notice_info"]['content'];
        }
        if($pushType == 1){
            $notice_content = "您关注的{$planner_info['name']}的视频直播开始了,点击前往观看";
        }
        
        foreach($push_uids as $uid){
            // 组装消息
            $msg_data = array(
                'uid' => $uid,
                'u_type' => 2,
                'type' => 23,
                'relation_id' => $notice_info['circle_id'],
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => $notice_content, 'class' => "", 'link' => ""),
                ),JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    "id" => $notice_info['id'],
                    'type' => $notice_info['type'],
                    'circle_id' => $notice_info['circle_id'],
                    'relation_id' => $notice_info['circle_id'],
                    'title' => CHtml::encode($notice_info['type']),
                    "{$notice_type}_notice_info" => $notice_info["{$notice_type}_notice_info"],
                ),JSON_UNESCAPED_UNICODE),
                'link_url' => "",
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            $msg_data['id'] = Message::model()->saveMessage($msg_data);
        }
        
        
        if (!empty($log_data['ext_data']['uids'])) {
            $log_data['ext_data']['uids']=array_merge($log_data['ext_data']['uids'],$push_uids);
        } else {
            $log_data['ext_data']['uids']=$push_uids;
        }
// print_r("toPlannerUser:\t");
// print_r($push_uids);
        // 加入提醒队列
        $push_uids = User::model()->getUidBySuids($push_uids);
        //将用户500个一组，分批放入队列。否则会导致数据过大。
        $uids_arr = array_chunk($push_uids, 500);
        foreach($uids_arr as $_uids){
            $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(13,14));
        }
    }
}
