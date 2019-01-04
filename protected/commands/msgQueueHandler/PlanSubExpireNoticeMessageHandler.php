<?php

/**
 * 计划订阅到期逻辑处理+消息渠道分发
 */
class PlanSubExpireNoticeMessageHandler
{
    private $commonHandler = null;

    public function __construct()
    {
        $this->commonHandler = new CommonHandler();
    }

    public function run($msg)
    {
        $log_data = array('status' => 1, 'result' => 'ok', 'uid' =>  '', 'relation_id' => '', 'ext_data' => '');
        try {
            $this->commonHandler->checkRequireParam($msg, array('uid', 'pln_id', 'expire_time'));
            $plan_info = Plan::model()->getPlanInfoByIds($msg['pln_id']);
            $plan_info = $plan_info[$msg['pln_id']];
            if (empty($plan_info)) {
                throw new Exception("计划{$msg['pln_id']}不存在");
            }

            $msg_data = array(
                'uid'           => $msg['uid'],
                'u_type'        => 1,
                'type'          => 24,
                'relation_id'   => $msg['pln_id'],
                'content'       => '',
                'content_client'=> json_encode(
                    array(
                        'pln_id'      => $msg['pln_id'],
                        'pln_name'    => $plan_info['name'],
                        'expire_time' => $msg['expire_time']
                    ), JSON_UNESCAPED_UNICODE
                ),
                'link_url'      => '',
                'c_time'        => date("Y-m-d H:i:s"),
                'u_time'        => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            $msg_data['id'] = Message::model()->saveMessage($msg_data);
            $push_uid = (array) $msg['uid'];
            $this->commonHandler->addToPushQueue($msg_data, $push_uid, array(6));
        } catch (Exception $e) {
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
        }

        //记录队列处理结果
        if (isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])) {
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
}