<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/2/29
 * Time: 17:28
 */

class PkgPlannerCommentMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }


    /**
     * 理财师观点包说说提醒
     * @param $msg_json   type=pkgPlannerComment  cmn_id
     */
    public function run($msg_json){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');

        try {
            $this->commonHandler->checkRequireParam($msg_json, array('cmn_id','relation_id','parent_relation_id','content'));
            $cmn_id = $msg_json['cmn_id'];

            //说说信息
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();

            $cmn_info['relation_id']=$msg_json['relation_id'];
            $cmn_info['parent_relation_id']=$msg_json['parent_relation_id'];
            $cmn_info['content']=CommonUtils::removeEmoji($msg_json['content']);

            if(empty($cmn_info)){
                throw new Exception('cmn_info 为空');
            }

            $v_id = $cmn_info['relation_id'];
            $pkg_id = $cmn_info['parent_relation_id'];
            if($v_id == 0) {  //取观点包的信息
                $pkg_info = Package::model()->getPackagesById($pkg_id);
                $pkg_info = $pkg_info[$pkg_id];
                $p_uid = $pkg_info['p_uid'];

                //获取订阅观点包的用户id
                $uids = Package::model()->getSubscriptionUid($pkg_id);
            }else{  //取观点信息
                $v_info = View::model()->getViewById($v_id);
                $v_info = $v_info[$v_id];
                $p_uid = $v_info['p_uid'];

                //获取订阅观点包的用户id
                $uids = Package::model()->getSubscriptionUid($v_info['pkg_id']);
            }

            if(empty($p_uid)) {
                throw new Exception('p_uid 为空');
            }

            //理财师信息
            $planner_info = Planner::model()->getPlannerById($p_uid);
            $planner_info = isset($planner_info[$p_uid]) ? $planner_info[$p_uid] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }
            if(empty($uids)) {
                throw new Exception('uids 为空');
            }
            $msg_data = array();
            foreach($uids as $uid) {
                if($v_id == 0) {
                    $content = array(
                        array('value'=>$planner_info['name'],'class'=>'','link'=>'/planner/'.$p_uid.'/1'),
                        array('value'=>"在",'class'=>'','link'=>''),
                        array('value'=>"《".CHtml::encode($pkg_info['title'])."》",'class'=>'','link'=>'/web/packageInfo?pkg_id='.$pkg_id),
                        array('value'=>"中说",'class'=>'','link'=>''),
                        array('value'=>"：".CHtml::encode($cmn_info['content']),'class'=>'','link'=>'/web/packageInfo?pkg_id='.$pkg_id.'#wetalk')
                    );
                }else{
                    $content = array(
                        array('value'=>$planner_info['name'],'class'=>'','link'=>'/planner/'.$p_uid.'/1'),
                        array('value'=>"在",'class'=>'','link'=>''),
                        array('value'=>"《".CHtml::encode($v_info['title'])."》",'class'=>'','link'=>'/view/'.$v_id),
                        array('value'=>"中说",'class'=>'','link'=>''),
                        array('value'=>"：".CHtml::encode($cmn_info['content']),'class'=>'','link'=>'/view/'.$v_id)
                    );
                }

                $now_time = date("Y-m-d H:i:s");
                $msg_data = array(
                    'uid' => $uid,
                    'u_type' => 1,
                    'type' => 11,
                    'relation_id' => $pkg_id,
                    'child_relation_id'=>$cmn_id,
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'p_uid' => $p_uid,
                        'planner_name' => CHtml::encode($planner_info['name']),
                        'title' => isset($v_info['title'])?$v_info['title']:$pkg_info['title'], //
                        'comment_type'=>!empty($v_id)?2:-2, //2观点 -2观点包
                        'sub_type' => isset($v_info['title'])?'view':'package', //
                        'content' => CHtml::encode($cmn_info['content']),
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : '',
                        'v_id'=> $v_id,
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=> '',
                    'c_time' => $now_time,
                    'u_time' => $now_time
                );
                if($v_id == 0) {
                    $msg_data['link_url'] = '/web/packageInfo?pkg_id='.$pkg_id.'#wetalk';
                }else{
                    $msg_data['link_url'] = '/view/'.$v_id;
                }

                Message::model()->saveMessage($msg_data);
                $msg_data['title'] = isset($v_info['title'])?$v_info['title']:$pkg_info['title'];
            }//end foreach.

            //加入提醒队列
            if(!empty($uids)){
                //将用户500个一组，分批放入队列。否则会导致数据过大。
                $uids_arr = array_chunk($uids,500);
                foreach($uids_arr as $_uids){
                    $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(2,3));
                }
            }
            //$log_data['uid'] = $msg_data['uid'];
            $log_data['relation_id'] = $msg_data['relation_id'];
            $log_data['ext_data']=json_encode(array('uids'=>$uids));
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }


        //记录队列处理结果
        //$log_data['ext_data'] =$cmn_id;
        if(isset($msg_json['queue_log_id']) && !empty($msg_json['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg_json['queue_log_id']);
        }

    }
}