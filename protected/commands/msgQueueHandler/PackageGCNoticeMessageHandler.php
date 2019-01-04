<?php
/**
 * 理财师观点包 通知用户评价 
 * User: shixi_shifeng
 * Date: 2016-05-05
 * Time: 20:19
 */

class PackageGCNoticeMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 理财师观点包 通知用户评价
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('pkg_id'));

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

            //是否要有时间限制
            
            // 理财师信息
            $planner_info = Planner::model()->getPlannerById(array($pkg_info['p_uid']));
            $planner_info = isset($planner_info[$pkg_info['p_uid']]) ? $planner_info[$pkg_info['p_uid']] : array();
            if(empty($planner_info)){
                throw new Exception('planner_info 为空');
            }

            // 获取观点包的订阅用户
            //$uids = Package::model()->getSubscriptionUid($pkg_id);
            $uids = $this->getCanGradeUser($pkg_id);
            if(empty($uids)){
                throw new Exception('uids 为空');
            }

            // 组装消息结构 content_client: type=1  pln_id pln_name  p_uid p_name
            foreach($uids as $uid){
                $msg_data = array(
                    'uid'=>$uid,
                    'u_type'=>1,
                    'type'=>19,
                    'relation_id'=>$pkg_id,
                    'content'=>json_encode(array(
                        array('value'=>'您对理财师','class'=>'','link'=>''),
                        array('value'=>CHtml::encode($planner_info['name']),'class'=>'','link'=>'/planner/'.$pkg_info['p_uid'].'/1'),
                        array('value'=>'上个月的观点包','class'=>'', 'link'=>''),
                        array('value'=>"《".CHtml::encode($pkg_info['title'])."》",'class'=>'','link'=>'/web/packageInfo?pkg_id='.$pkg_id),
                        array('value'=>"感觉如何，快来评价一下吧~",'class'=>'','link'=>'')
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'p_uid' => $pkg_info['p_uid'],
                        'p_name' => CHtml::encode($planner_info['name']),
                        'pkg_name' => CHtml::encode($pkg_info['title']),
                        'pkg_id'=>$pkg_id,
                        'type'=>1
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>'/web/packageInfo?pkg_id='.$pkg_id.'&grade_type=1',
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 保存通知消息
                Message::model()->saveMessage($msg_data);

                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            }
            

            // 加入提醒队列
            if(!empty($uids)){
                //将用户500个一组，分批放入队列。否则会导致数据过大。
                $uids_arr = array_chunk($uids,500);
                foreach($uids_arr as $_uids){
                    $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(2,3));
                }
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

    /**
     * 获取可以评价的用户
     * @param $pkg_id
     */
    private function getCanGradeUser($pkg_id){
        //获取订阅用户
        $sub_uids = Package::model()->getSubscriptionUid($pkg_id);
        if(empty($sub_uids)){
            return array();
        }

        #获取当前日期的日
        $day = date('j');
        #半个月只能发表一次
        $s_time='';
        if ($day > 0 && $day < 16) {
            $s_time=date('Y-m-01 00:00:00');
        } else if ($day >= 16) {
            $s_time=date('Y-m-16 00:00:00');
        }

        //获取已经评价的用户
        $grade_users = GradeComment::model()->getGradeCommentListByCdn(2,$pkg_id,'','',$s_time,'','uid');
        if(empty($grade_users)){
            return $sub_uids;
        }else{
            $grade_uids = array();
            foreach($grade_users as $user){
                $grade_uids[]=$user['uid'];
            }

            return array_diff($sub_uids,$grade_uids);
        }
    }
}