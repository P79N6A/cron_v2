<?php
/**
 * 理财师观点包 有新的评价 通知给理财师
 * User: shixi_shifeng
 * Date: 2016-05-05
 * Time: 20:02
 */

class PackageGCNewMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 理财师观点包 有新的评价 通知给理财师
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            // 评价用户uids
            $this->commonHandler->checkRequireParam($msg, array('pkg_id','uids'));

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
         
            $user_name = array();
            $count=0;
            foreach ($msg['uids'] as $uid) {
                if(intval($uid)<=0){
                    continue;
                }
                $user_info  = User::model()->getUserInfoByUid($uid);
                $user_name[] = $user_info['name'];
                if(++$count==2){
                    break;
                }
            }
            if (empty($user_name)) {
                $user_name[]="匿名用户";
            }
            //content_client: type=3  pln_id pln_name  u_num u_names[股市小能手、财神]
            $msg_data = array(
                'uid' => $pkg_info['p_uid'],
                'u_type' => 2,
                'type' =>19,
                'relation_id' => $pkg_id,
                'content' => json_encode(array(
                    array('value' => implode('，',array_slice($user_name,0,2)), 'class' => '', 'link' => ''),
                    array('value' => (count($msg['uids'])>2?"等".count($msg['uids']).'人':'').'评价了您的观点包', 'class' => '', 'link' => ''),
                    array('value'=>"《".CHtml::encode($pkg_info['title'])."》",'class'=>'','link'=>'/web/packageInfo?pkg_id='.$pkg_id)                    
                ), JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'type' => 3,
                    'pkg_id' => $pkg_id,
                    'pkg_name' => CHtml::encode($pkg_info['title']),
                    'u_num' => count($msg['uids']),
                    'u_names' => $user_name,
                ), JSON_UNESCAPED_UNICODE),
                'link_url'=>'/web/packageInfo?pkg_id='.$pkg_id.'&grade_type=1',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            Message::model()->saveMessage($msg_data);
            $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            
            //加入提醒队列
            $push_uid = array();
            $push_uid[]=User::model()->getUidBySuid($msg_data['uid']);
            $this->commonHandler->addToPushQueue($msg_data,$push_uid,array(2,3));

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
}