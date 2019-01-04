<?php
/**
 *
 * User: weiguang3
 * Date: 2016/2/29
 * Time: 14:24
 */

class BuyPackageMessageHandler {


    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 购买观点包通知处理
     *
     * 消息体包含如下信息：type, pkg_id, uid, start_time,end_time
     *
     * @param $msg
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->commonHandler->checkRequireParam($msg, array('uid','pkg_id','start_time','end_time'));
            $pkg_id = $msg['pkg_id'];

            //添加通知
            $pkgs = Package::model()->getPackagesById($pkg_id);
            $pkg = !empty($pkgs) && isset($pkgs[$pkg_id]) ? $pkgs[$pkg_id] : null;
            $pkg_name = !empty($pkg) && isset($pkg['title']) ? $pkg['title'] : '';
            $p_uid = !empty($pkg) && isset($pkg['p_uid']) ? $pkg['p_uid'] : '';
            $planner = array();

            if (!empty($p_uid)) {
                $planners = Planner::model()->getPlannerById($p_uid);
                $planner = !empty($planners) && isset($planners[$p_uid]) ? $planners[$p_uid] : null;
            }
            $msg_data = array(
                'uid' => $msg['uid'],
                'u_type' => 1,  //1普通用户   2理财师
                'type' => 3,
                'relation_id' => $pkg_id,
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => "您已成功购买", 'class' => '', 'link' => ""),
                    array('value' => '《' . $pkg_name . '》', 'class' => '', 'link' => "/web/packageInfo?pkg_id=" . $pkg_id),
                    array('value' => "观点包，我们将实时提醒你观点包的更新动态，请及时查看新消息", 'class' => '', 'link' => ""),
                ), JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'type' => 7,
                    'pkg_id' => $pkg_id,
                    'pkg_name' => $pkg_name,
                    "start_time" => isset($msg['start_time']) ? $msg['start_time'] : '',
                    "end_time" => isset($msg['end_time']) ? $msg['end_time'] : '',
                    "p_uid" => $p_uid,
                    "planner_name" => !empty($planner) && isset($planner['name']) ? $planner['name'] : '',
                    "planner_image" => !empty($planner) && isset($planner['image']) ? $planner['image'] : '',
                    "company" => !empty($planner) && isset($planner['company']) ? $planner['company'] : '',
                ), JSON_UNESCAPED_UNICODE),
                'link_url' => "/web/packageInfo?pkg_id=" . $pkg_id,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            //保存通知消息
            Message::model()->saveMessage($msg_data);
            //加入提醒队列
            $push_uid = array();
            $push_uid[] = $msg_data['uid'];
            $this->commonHandler->addToPushQueue($msg_data, $push_uid, array(2, 3));

            $log_data['uid'] = $msg_data['uid'];
            $log_data['relation_id'] = $msg_data['relation_id'];
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

}