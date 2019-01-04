<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/2/29
 * Time: 17:23
 */

class ReplayCommentNewMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 回复评论提醒  ok
     * @param $msg  type=replayComment   cmn_id
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->commonHandler->checkRequireParam($msg, array('cmn_id','replay_id','u_type','uid','cmn_type','relation_id','parent_relation_id','content','r_cmn_id','r_cmn_type','r_uid','r_u_type','r_content'));
            $cmn_id=$msg['cmn_id'];
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();
            $cmn_info['cmn_id']=$msg['cmn_id'];
            $cmn_info['replay_id']=$msg['replay_id'];
            $cmn_info['u_type']=$msg['u_type'];
            $cmn_info['uid']=$msg['uid'];
            $cmn_info['cmn_type']=$msg['cmn_type'];
            $cmn_info['relation_id']=$msg['relation_id'];
            $cmn_info['parent_relation_id']=$msg['parent_relation_id'];
            $cmn_info['content']=CommonUtils::removeEmoji($msg['content']);

            if(empty($cmn_info)){
                throw new Exception('评论的说说不存在');
            }

            //被评论的评论内容
            $replay_cmn_info = array();
            $replay_cmn_info['cmn_id']=$msg['r_cmn_id'];
            $replay_cmn_info['cmn_type']=$msg['r_cmn_type'];
            $replay_cmn_info['uid']=$msg['r_uid'];
            $replay_cmn_info['u_type']=$msg['r_u_type'];
            $replay_cmn_info['content']=$msg['r_content'];
            /*
            if(isset($cmn_info['replay_id']) && $cmn_info['replay_id']>0){
                $replay_cmn_info = Comment::model()->getCommentInfoByID($cmn_info['replay_id']);
                $replay_cmn_info = isset($replay_cmn_info[$cmn_info['replay_id']]) ? $replay_cmn_info[$cmn_info['replay_id']] : array();
            }*/

            if(empty($replay_cmn_info)) {
                throw new Exception('被评论的说说不存在');
            }

            //评论的用户信息   二级说说
            $name = '';
            $u_image='';
            $company='';
            if(1 == $cmn_info['u_type']){//普通用户
                $user = User::model()->getUserInfoByUid($cmn_info['uid']);
                //$user = !empty($users)&&isset($users[$cmn_info['uid']])?$users[$cmn_info['uid']]:array();
                $name = isset($user['name']) ? $user['name'] : "";//"财友".CommonUtils::encodeId($cmn_info['uid']);
                $u_image = isset($user['image']) ? $user['image'] : "";;
            }elseif(2 == $cmn_info['u_type']){//理财师
                $planner_info = Planner::model()->getPlannerById(array($cmn_info['uid']));
                $planner_info = isset($planner_info[$cmn_info['uid']]) ? $planner_info[$cmn_info['uid']] : array();
                $name = isset($planner_info['name']) ? $planner_info['name'] : "";
                $u_image=isset($planner_info['image']) ? $planner_info['image'] : "";
                if(isset($planner_info['company_id']) && !empty($planner_info['company_id'])){
                    $company_infos = Planner::model()->getCompanyById(array($planner_info['company_id']));
                    $company_info = isset($company_infos[$planner_info['company_id']])?$company_infos[$planner_info['company_id']]:array();
                    $company=isset($company_info['name']) ? $company_info['name'] : '';
                }


            }elseif(3 == $cmn_info['u_type']){//理财小妹
                $name = "理财小妹";
                $u_image='http://licaishi.sina.com.cn/web_img/lcs_comment_systemuser.jpg';
            }

            //被回复的评论的用户信息  一级说说
            $r_name = '';
            $r_u_image='';
            $r_company='';
            if(1 == $replay_cmn_info['u_type']){//普通用户
                $user = User::model()->getUserInfoByUid($replay_cmn_info['uid']);
                $r_name = isset($user['name']) ? $user['name'] : "";//"财友".CommonUtils::encodeId($replay_cmn_info['uid']);
                $r_u_image = isset($user['image']) ? $user['image'] : "";;
            }elseif(2 == $replay_cmn_info['u_type']){//理财师
                $r_planner_info = Planner::model()->getPlannerById(array($replay_cmn_info['uid']));
                $r_planner_info = isset($r_planner_info[$replay_cmn_info['uid']]) ? $r_planner_info[$replay_cmn_info['uid']] : array();
                $r_name = isset($r_planner_info['name']) ? $r_planner_info['name'] : "";
                $r_u_image=isset($r_planner_info['image']) ? $r_planner_info['image'] : "";
                if(isset($r_planner_info['company_id']) && !empty($r_planner_info['company_id'])){

                    $company_infos = Planner::model()->getCompanyById(array($r_planner_info['company_id']));

                    $company_info = isset($company_infos[$r_planner_info['company_id']])?$company_infos[$r_planner_info['company_id']]:array();
                    $r_company=isset($company_info['name']) ? $company_info['name'] : '';
                }
            }elseif(3 == $replay_cmn_info['u_type']){//理财小妹
                throw new Exception('理财小妹的说说不用回复');
            }

            if($replay_cmn_info['uid']==$cmn_info['uid']){
                throw new Exception('自己回复自己的说说不用通知');
            }



            $type="";
            $link_url = '';
            $from = '';//来源
            if($replay_cmn_info['cmn_type'] == 1){//计划
                $type="plan";
                $link_url = "/plan/".$cmn_info['relation_id']."?type=comment#myComment"; //计划
                //计划信息
                $plan_info = Plan::model()->getPlanInfoById($cmn_info['relation_id']);
                if(!empty($plan_info)){
                    $plan_name = isset($plan_info['name']) ? $plan_info['name'] : '';
                    $plan_name .= (isset($plan_info['number']) && $plan_info['number'] > 9 ? $plan_info['number'] : "0" . $plan_info['number']) . "期";
                    $from = $plan_name;
                }

            }else if($replay_cmn_info['cmn_type'] == 2){ //观点
                if($cmn_info['relation_id'] > 0){ //观点评论
                    $type="view";
                    $link_url = "/view/".$cmn_info['relation_id']."#myComment";

                    //观点信息
                    $view_info = View::model()->getViewById($cmn_info['relation_id']);
                    $view_info = isset($view_info[$cmn_info['relation_id']]) ? $view_info[$cmn_info['relation_id']] : array();
                    $from = isset($view_info['title']) ? $view_info['title'] : '';
                }else{//观点包评论
                    $type="package";
                    $link_url = "/web/packageInfo?pkg_id=".$cmn_info['parent_relation_id']."#myComment";
                    $cmn_info['relation_id'] = $cmn_info['parent_relation_id'];

                    //观点包信息
                    $package_info = Package::model()->getPackagesById($cmn_info['parent_relation_id']);
                    $package_info = isset($package_info[$cmn_info['parent_relation_id']]) ? $package_info[$cmn_info['parent_relation_id']] : array();
                    $from = isset($package_info['title']) ? $package_info['title'] : '';
                }

            }else if($replay_cmn_info['cmn_type'] == 3){
                $type="topic";

                $topic_infos = Topic::model()->getTopicInfoIds($cmn_info['relation_id']);
                $topic_info = isset($topic_infos[$cmn_info['relation_id']])?$topic_infos[$cmn_info['relation_id']]:array();
                $from = isset($topic_info['title']) ? $topic_info['title'] : '';

            }else if($replay_cmn_info['cmn_type'] == 4){
                $type="system";

                $from="说说广场";
            }else if($replay_cmn_info['cmn_type'] == 51){
                $type="trade_time";

                $from="交易时间";
                if($cmn_info['relation_id']=='8888'){
                    $from.='-A股';
                }else if($cmn_info['relation_id']=='8889'){
                    $from.='-金银油';
                }else if($cmn_info['relation_id']=='8890'){
                    $from.='-美股';
                }else if($cmn_info['relation_id']=='8891'){
                    $from.='-港股';
                }else if($cmn_info['relation_id']=='8892'){
                    $from.='-期货';
                }
            }else {
                throw new Exception('无法处理的回复说说类型 cmn_type:'.$replay_cmn_info['cmn_type']);
            }

            $msg_data = array(
                'uid'=>$replay_cmn_info['uid'],
                'u_type'=>$replay_cmn_info['u_type'],  //普通用户   2理财师
                'type'=>16,
                'relation_id'=>$cmn_info['cmn_id'],
                'child_relation_id'=>$cmn_info['replay_id'],
                'content'=>json_encode(array(
                    array('value'=>$name."回复了您的评论：".CHtml::encode($replay_cmn_info['content']),'class'=>'','link'=>""),  //当前用户为理财师的时候 显示连接
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode(array(
                    'cmn_type'=>$type,
                    "cmn_id"=>$cmn_info['cmn_id'],
                    'uid'=>$cmn_info['uid'],
                    'u_type'=>$cmn_info['u_type'],
                    'name' => $name,
                    'image' => $u_image,
                    'company' =>$company,
                    'content' => CHtml::encode($cmn_info['content']),
                    "r_cmn_id"=>$replay_cmn_info['cmn_id'],
                    'r_uid' => $replay_cmn_info['uid'],
                    'r_u_type' => $replay_cmn_info['u_type'],
                    'r_name' => $r_name,
                    'r_image' => $r_u_image,
                    'r_company' =>$r_company,
                    'r_content' => CHtml::encode($replay_cmn_info['content']),
                    'from' => $from,
                    'from_id' => $cmn_info['relation_id']
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>$link_url,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入提醒队列
            $msg_data['content'] = json_decode($msg_data['content'],true);
            $push_uid=array();
            if($msg_data['u_type']==2){
                $push_uid[]=User::model()->getUidBySuid($msg_data['uid']);
            }else if($msg_data['u_type']==1){
                $push_uid[]=$msg_data['uid'];
            }

            $this->commonHandler->addToPushQueue($msg_data,$push_uid ,array (2,3));

            $log_data['uid']=$msg_data['uid'];
            $log_data['relation_id']=$msg_data['relation_id'];

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