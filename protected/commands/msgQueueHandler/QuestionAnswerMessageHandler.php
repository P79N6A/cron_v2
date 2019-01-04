<?php
/**
 * 问题回答通知处理
 * User: weiguang3
 * Date: 2016/2/29
 * Time: 16:02
 */

class QuestionAnswerMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 问题回答通知处理 ok
     * 消息体包含如下信息：type=questionAnswer, q_id, answer_id(非必须)
     * @param $msg
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->commonHandler->checkRequireParam($msg, array('q_id'));
            $q_id = $msg['q_id'];
            $q_info = Question::model()->getQuestionById($q_id);
            if(empty($q_info)){
                //未获取到问答信息，从写库获取
                $q_info = Question::model()->getQuestionById($q_id,false);
            }
            $q_info = isset($q_info[$q_id]) ? $q_info[$q_id] : array();

            if(empty($q_info)){
                throw new Exception('问题不存在，q_id'.$q_id);
            }
            //未付款  数据同步问题
            if($q_info['status'] == -3){
                $q_info = Question::model()->getQuestionById($q_id,false);
                $q_info = isset($q_info[$q_id]) ? $q_info[$q_id] : array();
            }

            $answer_id = $q_info['answer_id'];
            if (isset($msg ['answer_id']) && !empty($msg['answer_id'])) {
                $answer_id = $msg['answer_id'];
            }

            $answer_info = Question::model()->getAnswerById($answer_id);
            $answer_info = isset($answer_info[$answer_id]) ? $answer_info[$answer_id] : array();

            //理财师信息
            if(array_key_exists("p_uid",$answer_info)) {
                //抢答问题从抢答表中获取p_uid
                $p_uid=intval($answer_info['p_uid']);
            }
            else{
                //普通问答从问题表中获取p_uid
                $p_uid=intval($q_info['p_uid']);
            }



            $planner_info = Planner::model()->getPlannerById(array($p_uid));
            $planner_info = isset($planner_info[$p_uid]) ? $planner_info[$p_uid] : array();
            if(!empty($planner_info) && isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }

            }

            //获取用户信息
            if(isset($q_info['is_anonymous'])&&$q_info['is_anonymous']==1){
                $user_info = array('name'=>null,'image'=>null);
            }
            else {
                $user_info = User::model()->getUserInfoByUid($q_info['uid']);
            }
            $msg_data = array();

            if ($q_info['status'] == 1) { //提问
                if(empty($p_uid)){
                    throw new Exception('理财师Id为空 问题ID:'.$q_id);
                }
                $msg_data = array(
                    'uid' => $q_info['p_uid'],
                    'u_type' => 2,
                    'type' => 1,
                    'relation_id' => $q_id,
                    'content' => json_encode(array(
                        array('value' => "有新的用户提问：", 'class' => '', 'link' => ''),
                        array('value' => CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id)
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => '/ask/' . $q_id,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } elseif ($q_info['status'] == 2) { //拒绝回答
                $msg_data = array(
                    'uid' => $q_info['uid'],
                    'u_type' => 1,
                    'type' => 1,
                    'relation_id' => $q_id,
                    'content' => json_encode(array(
                        array('value' => isset($planner_info['name']) ? $planner_info['name'] : '', 'class' => '', 'link' => '/planner/' . $q_info['p_uid'] . '/1'),
                        array('value' => '无法回答您的问题', 'class' => '', 'link' => ''),
                        array('value' => "：" . CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id),
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => '/ask/' . $q_id,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } elseif ($q_info['status'] == 3) { //回答
                $msg_data = array(
                    'uid' => $q_info['uid'],
                    'u_type' => 1,
                    'type' => 1,
                    'relation_id' => $q_id,
                    'content' => json_encode(array(
                        array('value' => isset($planner_info['name']) ? $planner_info['name'] : '', 'class' => '', 'link' => '/planner/' . $q_info['p_uid'] . '/1'),
                        array('value' => '回答了您的问题', 'class' => '', 'link' => ''),
                        array('value' => "：" . CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id),
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => '/ask/' . $q_id,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } elseif ($q_info['status'] == 4) { //追问中
                if(empty($p_uid)){
                    throw new Exception('理财师Id为空 问题ID:'.$q_id);
                }
                //追问回答信息
                $question_add_info = Question::model()->getQuestionAddInfo($q_info['q_add_id']);
                $question_add_info = isset($question_add_info[$q_info['q_add_id']]) ? $question_add_info[$q_info['q_add_id']] : array();
                $msg_data = array(
                    'uid' => $q_info['p_uid'],
                    'u_type' => 2,
                    'type' => 1,
                    'relation_id' => $q_id,
                    'content' => json_encode(array(
                        array('value' => "有新的用户追问：", 'class' => '', 'link' => ''),
                        array('value' => CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id)
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => '/ask/' . $q_id,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } elseif ($q_info['status'] == 5) { //追问回答
                //追问回答信息
                $question_add_info = Question::model()->getQuestionAddInfo($q_info['q_add_id']);
                $question_add_info = isset($question_add_info[$q_info['q_add_id']]) ? $question_add_info[$q_info['q_add_id']] : array();

                $msg_data = array(
                    'uid' => $q_info['uid'],
                    'u_type' => 1,
                    'type' => 1,
                    'relation_id' => $q_id,
                    'content' => json_encode(array(
                        array('value' => isset($planner_info['name']) ? $planner_info['name'] : '', 'class' => '', 'link' => '/planner/' . $q_info['p_uid'] . '/1'),
                        array('value' => '补充回答了您的问题', 'class' => '', 'link' => ''),
                        array('value' => "：" . CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id),
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => '/ask/' . $q_id,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }else{
                throw new Exception("问答状态错误：".$q_info['status']);
            }

            //保存通知消息
            $_log_id = Message::model()->saveMessage($msg_data);
            if($_log_id){
                $msg_data['id']=$_log_id;
            }
            //加入提醒队列
            $push_uid=array();
            if($msg_data['u_type']==2){
                $push_uid[]=User::model()->getUidBySuid($msg_data['uid']);
            }else if($msg_data['u_type']==1){
                $push_uid[]=$msg_data['uid'];
            }

            $this->commonHandler->addToPushQueue($msg_data,$push_uid,array (1,2,3));

            $log_data['uid']=$msg_data['uid'];
            $log_data['relation_id']=$msg_data['relation_id'];
            //}
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