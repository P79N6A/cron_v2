<?php
/**
 * 理财师观点包 理财师回复用户的评价后 通知评价用户
 * User: shixi_shifeng
 * Date: 2016-05-05
 * Time: 20:32
 */

class PackageGCReplyMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 理财师计划理财师回复用户的评价后通知评价用户
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('pkg_id','cmn_id'));

            //TODO 未正式上线，屏蔽功能
            /*if(!(defined('ENV')&&ENV=='dev')&&!in_array($msg['pkg_id'],array('8'))){
                throw new Exception('未正式上线，屏蔽功能');
            }*/

            // 根据观点包ID获取观点包详情
            $pkg_id = $msg['pkg_id'];
            $pkg_info = Package::model()->getPackagesById($pkg_id);
            $pkg_info = isset($pkg_info[$pkg_id])?$pkg_info[$pkg_id]:null;
            if(empty($pkg_info)){
                throw new Exception('pkg_info 为空');
            }

            //获取评价的回复内容
            $cmn_info = GradeComment::model()->getGradeCmnById($msg['cmn_id']);
            if (empty($cmn_info)) {
                throw new Exception('cmn_info 为空');
            }
            if (empty($cmn_info['reply'])) {
                throw new Exception('回复内容为空');
            }
            if ($cmn_info['relation_id']!=$pkg_id) {
                throw new Exception('不是此观点包的评价');
            }
            if ($cmn_info['type']!=2) {
                throw new Exception('不是观点包的评价');
            }

            //理财师信息
            $planner_info = Planner::model()->getPlannerById(array($pkg_info['p_uid']));
            $planner_info = isset($planner_info[$pkg_info['p_uid']]) ? $planner_info[$pkg_info['p_uid']] : array();
            if(empty($planner_info)){
                throw new Exception('planner_info 为空');
            }


            // 组装消息结构
            $msg_data = array(
                'uid'=>$cmn_info['uid'],
                'u_type'=>1,
                'type'=>19,
                'relation_id'=>$pkg_id,
                'content'=>json_encode(array(
                    array('value'=>CHtml::encode($planner_info['name']),'class'=>'','link'=>'/planner/'.$pkg_info['p_uid'].'/1'),
                    array('value'=>'回复评价：','class'=>'', 'link'=>''),
                    array('value'=>CHtml::encode(CommonUtils::getSubStrNew($cmn_info['reply'],30,'...')),'class'=>'','link'=>''),
                    array('value'=>'~','class'=>'','link'=>'')
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode(array(
                    'cmn_id'=>$msg['cmn_id'],
                    'p_uid' => $pkg_info['p_uid'],
                    'p_name' => CHtml::encode($planner_info['name']),
                    'content' => CHtml::encode(CommonUtils::getSubStrNew($cmn_info['content'],30,'...')),
                    'reply'=> CHtml::encode(CommonUtils::getSubStrNew($cmn_info['reply'],30,'...')),
                    'pkg_name' => CHtml::encode($pkg_info['title']),
                    'pkg_id'=>$pkg_id,
                    'type'=> 2
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>'/web/packageInfo?pkg_id='.$pkg_id.'&grade_type=1',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            Message::model()->saveMessage($msg_data);

            $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            // 加入提醒队列
            $this->commonHandler->addToPushQueue($msg_data,array($cmn_info['uid']),array(2,3));


        }catch(Exception $e){
            echo $e->getMessage(),'\n',$e->getTraceAsString();
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
}