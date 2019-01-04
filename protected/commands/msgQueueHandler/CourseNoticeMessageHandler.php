<?php
/**
 * 课程更新提醒
 */

class CourseNoticeMessageHandler {


    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 课程更新提醒
     *
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try{
            $this->commonHandler->checkRequireParam($msg,array('title','course_id','notice_type','content','class_id','url'));
            $u_type=1;
            $channel=0; //所有渠道
            $course_info = Course::model()->getCourseById($msg['course_id']);
            if(empty($course_info) || !isset($course_info[0]['type'])){
                Common::model()->saveLog("推送课程更新失败，未取到课程信息:".json_encode($msg),"error","push_course");
                return;
            }

            $msg_data = array(
                'uid'=>'',
                'u_type'=>1,  //1普通用户   2理财师
                'type'=>64,
                'relation_id'=>$msg['course_id'],
                'title'=>"理财师课程更新",
                'course_type'=>$course_info[0]['type'],
                'subscription_price'=>$course_info[0]['subscription_price'],
                'child_relation_id'=>$msg['class_id'],
                'content'=>json_encode(array(
                    array('value'=>'您的课程《'.$msg['title'].'》刚刚更新，点击马上学习','class'=>'','link'=>$msg['url'])
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>$msg['url'],
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            $content_client['t']=$msg['notice_type'];
            $content_client['id']=$msg['course_id'];
            $content_client['url']=$msg['url'];

            $content_client['title']= "理财师课程更新";
            $content_client['image']=isset($msg['image'])?$msg['image']:'';
            $content_client['content']=CommonUtils::getSubStrNew($msg['content'],40,'...');

            $msg_data['content_client']=json_encode($content_client,JSON_UNESCAPED_UNICODE);
            $uids = Course::model()->getUidByCourseSubscription($msg['course_id']);
            if(!empty($uids)){
                $this->saveMsgAndPush($uids,$msg_data,$channel);
            }

            $log_data['ext_data']=json_encode($uids);
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }


    /**
     * 保存消息并且发送通知
     * @param $u_type
     * @param $uids
     * @param $msg
     * @param $channel
     */
    private function saveMsgAndPush($uids,$msg,$channel){
        /*if(!empty($uids)){
            foreach($uids as $uid){
                $msg['uid']=$uid;
                //TODO 可以优化批量插入
                Message::model()->saveMessage($msg);
            }
        }*/
        if(!empty($uids)){
            //将用户500个一组，分批放入队列。否则会导致数据过大。
            $uids_arr = array_chunk($uids,500);
            $msg['content'] = json_decode($msg['content'],true);
            foreach($uids_arr as $_uids){
                $this->commonHandler->addToPushQueue($msg,$_uids,$channel===0?array(1,2,3):array($channel));
            }
        }
    }
}
