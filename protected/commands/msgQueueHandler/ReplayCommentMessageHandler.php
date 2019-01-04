<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/2/29
 * Time: 17:26
 */

class ReplayCommentMessageHandler {

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
            $this->commonHandler->checkRequireParam($msg, array('cmn_id','replay_id','u_type','uid','cmn_type','relation_id','parent_relation_id','content','r_cmn_type','r_uid','r_u_type','r_content'));
            $cmn_id=$msg['cmn_id'];
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();

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

            $name = '';
            $u_image='';
            $planner_info = array();//理财师信息
            if(1 == $cmn_info['u_type']){//普通用户
                $user = User::model()->getUserInfoByUid($cmn_info['uid']);
                //$user = !empty($users)&&isset($users[$cmn_info['uid']])?$users[$cmn_info['uid']]:array();
                $name = isset($user['name']) ? $user['name'] : "";//"财友".CommonUtils::encodeId($cmn_info['uid']);
                $u_image = isset($user['image']) ? $user['image'] : "";
            }elseif(2 == $cmn_info['u_type']){//理财师
                $planner_info = Planner::model()->getPlannerById(array($cmn_info['uid']));
                $planner_info = isset($planner_info[$cmn_info['uid']]) ? $planner_info[$cmn_info['uid']] : array();
                $name = isset($planner_info['name']) ? $planner_info['name'] : "";
                $u_image=isset($planner_info['image']) ? $planner_info['image'] : "";
            }elseif(3 == $cmn_info['u_type']){//理财小妹
                $name = "理财小妹";
                $u_image='http://licaishi.sina.com.cn/web_img/lcs_comment_systemuser.jpg';
            }


            $link_url = '';
            $type = 0;//提醒类型
            $from = '';//来源
            $_p_uid = '';//记录当前说说对象类型的理财师ID
            if($replay_cmn_info['cmn_type'] == 1){//计划
                $link_url = "/plan/".$cmn_info['relation_id']."?type=comment#myComment"; //计划
                $type = 6;//计划说说回复

                //计划信息
                $plan_info = Plan::model()->getPlanInfoById($cmn_info['relation_id']);
                $from = isset($plan_info['name']) ? $plan_info['name'] : '';
                $_p_uid = isset($plan_info['p_uid']) ? $plan_info['p_uid'] : '';

            }elseif($replay_cmn_info['cmn_type'] == 2){ //观点
                $type = 9;// 关注 观点包/观点 说说回复

                if($cmn_info['relation_id'] > 0){ //观点评论
                    $link_url = "/view/".$cmn_info['relation_id']."#myComment";

                    //观点信息
                    $view_info = View::model()->getViewById($cmn_info['relation_id']);
                    $view_info = isset($view_info[$cmn_info['relation_id']]) ? $view_info[$cmn_info['relation_id']] : array();
                    $from = isset($view_info['title']) ? $view_info['title'] : '';
                    $_p_uid = isset($view_info['p_uid']) ? $view_info['p_uid'] : '';
                    if(isset($view_info['subscription_price']) && $view_info['subscription_price']>0){
                        $type = 8;// 已购 观点包/观点 说说回复
                    }

                }else{//观点包评论
                    $link_url = "/web/packageInfo?pkg_id=".$cmn_info['parent_relation_id']."#myComment";
                    $cmn_info['relation_id'] = $cmn_info['parent_relation_id'];
                    $replay_cmn_info['cmn_type'] = -2;

                    //观点包信息
                    $package_info = Package::model()->getPackagesById($cmn_info['parent_relation_id']);
                    $package_info = isset($package_info[$cmn_info['parent_relation_id']]) ? $package_info[$cmn_info['parent_relation_id']] : array();
                    $from = isset($package_info['title']) ? $package_info['title'] : '';
                    $_p_uid = isset($package_info['p_uid']) ? $package_info['p_uid'] : '';
                    if(isset($package_info['subscription_price']) && $package_info['subscription_price']>0){
                        $type = 8;// 已购 观点包/观点 说说回复
                    }
                }

            }else {
                throw new Exception('无法处理的回复说说类型 cmn_type:'.$replay_cmn_info['cmn_type']);
            }

            //获取理财师
            if(empty($planner_info) || $planner_info['p_uid']!==$_p_uid) {
                $planner_info = Planner::model()->getPlannerById(array($_p_uid));
                $planner_info = isset($planner_info[$_p_uid]) ? $planner_info[$_p_uid] : array();

            }
            //获取公司名称
            if(isset($planner_info['company_id'])&& !empty($planner_info['company_id'])){
                $company_infos = Planner::model()->getCompanyById($planner_info['company_id']);
                $company_info = isset($company_infos[$planner_info['company_id']])?$company_infos[$planner_info['company_id']]:array();
                $planner_info['company']=isset($company_info['name']) ? $company_info['name'] : '';
            }

            $msg_data = array(
                'uid'=>$replay_cmn_info['uid'],
                'u_type'=>$replay_cmn_info['u_type'],  //普通用户   2理财师
                'type'=>$type,
                'relation_id'=>$cmn_info['cmn_type']==1?$cmn_info['relation_id']:$cmn_info['parent_relation_id'],
                'child_relation_id'=>$cmn_info['replay_id'],
                'content'=>json_encode(array(
                    array('value'=>$name,'class'=>'','link'=>$cmn_info['u_type']==2?"/planner/".$cmn_info['uid']."/1":""),  //当前用户为理财师的时候 显示连接
                    array('value'=>"回复了您的评论：",'class'=>'','link'=>""),
                    array('value'=>CHtml::encode($replay_cmn_info['content']),'class'=>'','link'=>$link_url),
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode(array(
                    'name' => $name,
                    'image' => $u_image,
                    'u_type'=> $cmn_info['u_type'],
                    'comment_type'=>$replay_cmn_info['cmn_type'],
                    'content' => CHtml::encode($replay_cmn_info['content']),
                    'cmn_content' => CHtml::encode($cmn_info['content']),
                    'p_uid' => isset($planner_info['p_uid']) ? $planner_info['p_uid'] : 0,
                    'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                    'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                    'company' => isset($planner_info['company']) ? $planner_info['company'] : '',
                    'from' => $from,
                    'v_id' => $replay_cmn_info['cmn_type']==2?$cmn_info['relation_id']:0,
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