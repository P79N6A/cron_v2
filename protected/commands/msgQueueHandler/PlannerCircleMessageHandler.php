<?php
/**
 * create by zhihao6 2016/12/26
 *
 * 理财师圈子评论提醒
 */

class PlannerCircleMessageHandler {

    private $commonHandler = null;

    public function __construct() {
        $this->commonHandler = new CommonHandler();
    }

    /**
     * 圈子评论提醒 
     */
    public function run($msg) {
        return;
        $log_data = array('status'=>1, 'result'=>'ok');
        try {
            // 验证必填项目
            // lcs_common_message_queue
            // {"type":"plannerCircle","cmn_type":71,"relation_id":24201,"cmn_id":16}
            $this->commonHandler->checkRequireParam($msg, array('cmn_type','relation_id','cmn_id'));
            $cmn_type = $msg['cmn_type'];
            $relation_id = $circle_id = $msg['relation_id'];
            $cmn_id=$msg['cmn_id'];
            
            // 说说信息
            $cmn_info = CircleCommentService::getCircleCommentInfo($cmn_type, $relation_id, $cmn_id);
            if(empty($cmn_info)){
                throw new Exception('评论不存在');
            }

            $status1 = $this->toCircleUser($circle_id, $cmn_info, $log_data);
            $status2 = $this->toCirclePlannerUser($circle_id, $cmn_info, $log_data);
            if (!empty($status1) && !empty($status2)) {
                throw new Exception("{$status1}|{$status2}");
            }

            $log_data['relation_id'] = $circle_id;
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

    private function toCircleUser($circle_id, $cmn_info, &$log_data) {
        // 圈子当前活动用户
        $push_uids = [];
        $push_uids = Circle::model()->getCircleOnlineUser($circle_id, 1);
        if (($cmn_info['u_type'] == 1) && in_array($cmn_info['uid'], $push_uids)) {
            $push_uids = array_diff($push_uids, array($cmn_info['uid']));
        }
        if (empty($push_uids)) {
            return "'当前无在线用户'";
        } else {
            $push_uids = array_unique($push_uids);
        }

        foreach($push_uids as $uid){
            // 组装消息
            $msg_data = array(
                'uid' => $uid,
                'u_type' => 1,
                'type' => 22,
                'relation_id' => $circle_id,
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => "", 'class' => "", 'link' => ""),
                ),JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'u_type' => $cmn_info['u_type'],
                    'uid' => $cmn_info['uid'],
                    'name' => $cmn_info['name'],
                    'image' => $cmn_info['image'],
                    'msg_type' => $cmn_info['msg_type'],
                    'content' => CommonUtils::getSubStrNew(CHtml::encode($cmn_info['content']),40,'...'),
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
            $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(2,3));
        }

        return "";
    }
    private function toCirclePlannerUser($circle_id, $cmn_info, &$log_data) {
        // 圈子当前活动用户
        $push_uids = [];
        $push_uids = Circle::model()->getCircleOnlineUser($circle_id, 2);
        if (($cmn_info['u_type'] == 2) && in_array($cmn_info['uid'], $push_uids)) {
            $push_uids = array_diff($push_uids, array($cmn_info['uid']));
        }
        if (empty($push_uids)) {
            return "'当前无在线理财师用户'";
        } else {
            $push_uids = array_unique($push_uids);
        }

        foreach($push_uids as $uid){
            // 组装消息
            $msg_data = array(
                'uid' => $uid,
                'u_type' => 2,
                'type' => 22,
                'relation_id' => $circle_id,
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => "", 'class' => "", 'link' => ""),
                ),JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'u_type' => $cmn_info['u_type'],
                    'uid' => $cmn_info['uid'],
                    'name' => $cmn_info['name'],
                    'image' => $cmn_info['image'],
                    'msg_type' => $cmn_info['msg_type'],
                    'content' => CommonUtils::getSubStrNew(CHtml::encode($cmn_info['content']),40,'...'),
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
            $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(2,3));
        }

        return "";
    }
}
