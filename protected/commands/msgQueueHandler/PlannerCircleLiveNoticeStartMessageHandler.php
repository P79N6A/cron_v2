<?php
/**
 * create by zhihao6 2017/01/12
 *
 * 理财师圈子直播公告开始提醒，给理财师推送
 */

class PlannerCircleLiveNoticeStartMessageHandler {

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
            // {"type":"plannerCircleLiveNoticeStart","n_id":162}
            $this->commonHandler->checkRequireParam($msg, array('n_id'));
            $notice_id = $msg['n_id'];

            // 公告信息
            $notice_info = CircleCommentService::getCircleNotice($notice_id);
            if (empty($notice_info) ||
                ($notice_info['u_type'] != 2) ||
                ($notice_info['status'] != 2) ||
                (strtotime($notice_info['live_notice_info']['start_time']) < time())) {
                throw new Exception('未知的公告信息或理财师公告或公告状态');
            } else {
                $rest_min = floor((strtotime($notice_info['live_notice_info']['start_time']) - time())/60);
            }

            // 组装消息
            $msg_data = array(
                'uid' => $notice_info['uid'],
                'u_type' => 2,
                'type' => 25,
                'relation_id' => $notice_info['id'],
                'child_relation_id' => 0,
                'content' => json_encode(array(
                     array('value' => "您的视频直播{$notice_info['title']}将于{$rest_min}分钟后开始，请做好直播相关准备~", 'class' => "", 'link' => ""),
                ),JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'circle_id' => $notice_info['circle_id'],
                    'title' => "《{$notice_info['title']}》",
                    'rest_min' => "{$rest_min}分钟",
                    "content" => "您的视频直播{$notice_info['title']}将于{$rest_min}分钟后开始，请做好直播相关准备~",
                ),JSON_UNESCAPED_UNICODE),
                'link_url' => "/circle/{$notice_info['circle_id']}",
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            $msg_data['id'] = Message::model()->saveMessage($msg_data);

            // 加入提醒队列
            $push_uid = User::model()->getUidBySuid($notice_info['uid']);
            $this->commonHandler->addToPushQueue($msg_data, $push_uid, array(2, 3));

            // 更新推送状态
            $columns['status'] = 0;
            $columns['u_time'] = date("Y-m-d H:i:s");
            $conditions = 'id=:id';
            $params['id'] = $notice_info['id'];
            Circle::model()->updateCircleNotice($columns, $conditions, $params);

            $log_data['uid'] = $msg_data['uid'];
            $log_data['relation_id'] = $msg_data['relation_id'];
            $log_data['ext_data']['uids']=array($notice_info['uid']);
        }catch(Exception $e){
            // 更新推送状态
            $columns['status'] = 3;
            $columns['u_time'] = date("Y-m-d H:i:s");
            $conditions = 'id=:id';
            $params['id'] = $notice_id;
            Circle::model()->updateCircleNotice($columns, $conditions, $params);

            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])) {
            $log_data['ext_data'] = empty($log_data['ext_data']) ? array() : $log_data['ext_data'];
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
}
