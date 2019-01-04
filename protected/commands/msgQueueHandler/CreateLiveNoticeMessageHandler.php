<?php
/**
 * 理财师创建直播和直播即将开始提醒
 * add by zhihao6 2016/08/12
 */

class CreateLiveNoticeMessageHandler {


    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('live_id', 'to_u_type'));

            if ($msg['to_u_type'] == 1) { // 给用户提醒
                $this->toUser($log_data, $msg);
            } elseif ($msg['to_u_type'] == 2) { // 给理财师提醒
               $this->toPlanner($log_data, $msg);
            } else {
                throw new Exception("未知推送用户类别");
            }
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

    private function toUser(&$log_data, $msg){
        // 获取直播信息
        $live_info = PlannerLive::model()->getLiveInfoByids($msg['live_id'], 'id,s_uid,type,title,start_time,end_time,status,c_time');
        $live_info = isset($live_info[$msg['live_id']]) ? $live_info[$msg['live_id']] : null;
        if (empty($live_info) || !in_array($live_info['status'], array(0,1)) || (time()-strtotime($live_info['start_time'])>60)) {
            throw new Exception("未知的直播或错误的直播状态");
        }

        // 直播开始前5分钟以外，直播状态设置0，非待直播状态
        if ((strtotime($live_info['start_time']) - time()) > 300) {
            $live_info['status'] = 0;
        }
        
        // 获取通知用户
        // $sub_uids = PlannerLive::model()->getLiveSubscriptionUser($live_info['s_uid']);
        // 换为关注用户
        $sub_uids = PlannerLive::model()->getLiveAttentionUser($live_info['s_uid']);
        // $sub_uids = (array) 13115996; // 测试
        if (empty($sub_uids)) {
            throw new Exception("推送的消息用户为空");
        }

        // 理财师信息
        $planner_info = Planner::model()->getPlannerById(array($live_info['s_uid']));
        $planner_info = isset($planner_info[$live_info['s_uid']]) ? $planner_info[$live_info['s_uid']] : array();
        if (!empty($planner_info) && isset($planner_info['company_id']) && isset($planner_info['position_id'])) {
            $companys = Common::model()->getCompany($planner_info['company_id']);
            if (!empty($companys) && isset($companys[$planner_info['company_id']])){
                $planner_info['company']=$companys[$planner_info['company_id']]['name'];
            }
            $position = Common::model()->getPositionById($planner_info['position_id']);
            if (!empty($position)) {
                $planner_info['position']=$position['name'];
            }
        }
        
        $content_client = array();
        $content_client['live_id']=$live_info['id'];
        $content_client['type']=$live_info['type'];
        $content_client['status']=$live_info['status'];
        $content_client['content']="直播主题《".CHtml::encode($live_info['title'])."》";
        $content_client['start_time']=$live_info['start_time'];
        $content_client['rest_minute']=floor((strtotime($live_info['start_time'])-time())/60);
        // 添加理财师信息
        $content_client['p_uid'] = $planner_info['p_uid'];
        $content_client['p_name'] = $planner_info['name'];
        $content_client['p_image'] = $planner_info['image'];
        $content_client['p_company'] = $planner_info['company'];
        $content_client['p_position'] = $planner_info['position'];

        foreach($sub_uids as $uid){
            $msg_data = array(
                'uid'=>$uid,
                'u_type'=>1,
                'type'=>21,
                'relation_id'=>$live_info['id'],
                'content'=>json_encode(array(
                    array('value'=>"{$planner_info['name']}理财师创建了直播",'class'=>'','link'=>''),
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode($content_client,JSON_UNESCAPED_UNICODE),
                'link_url'=>'',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            $msg_data['id'] = Message::model()->saveMessage($msg_data);
        }
        $log_data['ext_data']['uids']=$sub_uids;

        // 加入提醒队列
        if(!empty($sub_uids)){
            //将用户500个一组，分批放入队列。否则会导致数据过大。
            $uids_arr = array_chunk($sub_uids,500);
            foreach($uids_arr as $_uids){
                $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(1));
            }
        }
    }

    private function toPlanner(&$log_data, $msg){
        // 获取直播信息
        $live_info = PlannerLive::model()->getLiveInfoByids($msg['live_id'], 'id,s_uid,type,title,start_time,end_time,status,c_time');
        $live_info = isset($live_info[$msg['live_id']]) ? $live_info[$msg['live_id']] : null;
        if (empty($live_info) || !in_array($live_info['status'], array(0,1)) || (time()>strtotime($live_info['start_time']))) {
            throw new Exception("未知的直播或错误的直播状态");
        }

        // 理财师信息
        $planner_info = Planner::model()->getPlannerById(array($live_info['s_uid']));
        $planner_info = isset($planner_info[$live_info['s_uid']]) ? $planner_info[$live_info['s_uid']] : array();
        if (!empty($planner_info) && isset($planner_info['company_id']) && isset($planner_info['position_id'])) {
            $companys = Common::model()->getCompany($planner_info['company_id']);
            if (!empty($companys) && isset($companys[$planner_info['company_id']])){
                $planner_info['company']=$companys[$planner_info['company_id']]['name'];
            } else {
                $planner_info['company']='';
            }
            $position = Common::model()->getPositionById($planner_info['position_id']);
            if (!empty($position)) {
                $planner_info['position']=$position['name'];
            } else {
                $planner_info['position']='';
            }
        }
        
        $content_client = array();
        $content_client['live_id']=$live_info['id'];
        $content_client['type']=$live_info['type'];
        $content_client['status']=$live_info['status'];
        $content_client['content']=CHtml::encode($live_info['title']);
        $content_client['start_time']=$live_info['start_time'];
        $content_client['rest_minute']=floor((strtotime($live_info['start_time'])-time())/60);
        // 添加理财师信息
        $content_client['p_uid'] = $planner_info['p_uid'];
        $content_client['p_name'] = $planner_info['name'];
        $content_client['p_image'] = $planner_info['image'];
        $content_client['p_company'] = $planner_info['company'];
        $content_client['p_position'] = $planner_info['position'];

        $p_uid = $live_info['s_uid'];
        // $p_uid = 3046552733; // 测试

        $msg_data = array(
            'uid'=>$p_uid,
            'u_type'=>2,
            'type'=>21,
            'relation_id'=>$live_info['id'],
            'content'=>json_encode(array(
                array('value'=>"您的预告直播",'class'=>'','link'=>''),
                array('value'=>"《".CHtml::encode($live_info['title'])."》",'class'=>'','link'=>''),
                array('value'=>"将于",'class'=>'','link'=>''),
                array('value'=>floor((strtotime($live_info['start_time'])-time())/60)."分钟",'class'=>'','link'=>''),
                array('value'=>"后开始，请做好直播相关准备~",'class'=>'','link'=>''),
            ),JSON_UNESCAPED_UNICODE),
            'content_client'=>json_encode($content_client,JSON_UNESCAPED_UNICODE),
            'link_url'=>'',
            'c_time' => date("Y-m-d H:i:s"),
            'u_time' => date("Y-m-d H:i:s")
        );
        // 保存通知消息
        $msg_data['id'] = Message::model()->saveMessage($msg_data);
        
        $log_data['ext_data']['uids']=$p_uid;

        // 加入提醒队列
        $push_uid = User::model()->getUidBySuid($msg_data['uid']);
        $this->commonHandler->addToPushQueue($msg_data, $push_uid, array(2, 3));
    }

}
