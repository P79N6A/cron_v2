<?php
/**
 * create by zhihao6 2017/04/07
 *
 * 用户加入理财师圈子
 */

class PlannerCircleJoinMessageHandler {

    private $commonHandler = null;

    public function __construct() {
        $this->commonHandler = new CommonHandler();
    }

    public function run($msg) {
        $log_data = array('status'=>1, 'result'=>'ok');
        try {
            // 验证必填项目
            // lcs_common_message_queue
            // {"type":"plannerCircleJoin","uid":"10000015","p_uid":"5448059187"}
            $this->commonHandler->checkRequireParam($msg, array('uid','p_uid'));
            
            $circle_info = Circle::model()->getCircleInfoByPage("p_uid={$msg['p_uid']}");
            $circle_info = reset($circle_info['data']);
print_r($circle_info);
            $circle_users = Circle::model()->getCircleUser($circle_info['id'],1);
            if (!in_array($msg['uid'], $circle_users)) {
                $data = array(
                    'u_type'=>1,
                    'uid'=>$msg['uid'],
                    'circle_id'=>$circle_info['id'],
                    'is_top'=>0,
                    'c_time'=>date('Y-m-d H:i:s'),
                    'u_time'=>date('Y-m-d H:i:s')
                );
print_r($data);
                Circle::model()->addCircleUser($data);
            }
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
}
