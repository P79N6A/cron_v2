<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/3/15
 * Time: 9:51
 */

class NewViewMessageHandler {


    private $commonHandler = null;

    private static $pushAllNeironghao = ['6573400276'];

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 新发观点提醒
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('v_id'));
            $v_id = $msg['v_id'];
            $v_info = View::model()->getViewById($v_id);
            if(empty($v_info)){
                $v_info = View::model()->getViewById($v_id,false);
            }
            $v_info = isset($v_info[$v_id]) ? $v_info[$v_id] : array();

            if(empty($v_info)){
                throw new Exception('v_info 为空');
            }
            $pkg_id = $v_info['pkg_id'];
            $package = Package::model()->getPackagesById($pkg_id,false);
            $package = isset($package[$pkg_id]) ? $package[$pkg_id] : array();

            $msg_data = array();
            if($package['subscription_price'] > 0){
                echo "付费观点";
                $uids = Package::model()->getSubscriptionUid($pkg_id);
            }else{
                echo "免费观点";
                $data = Planner::model()->getPlannerUids(intval($v_info['p_uid']));
                $uids = array();
                foreach ($data as $key=>$value) {
                    $uids[] = $value['uid'];
                }
            }
            var_dump($uids);
            //理财师信息
            $planner_info = Planner::model()->getPlannerById(array(intval($v_info['p_uid'])));
            $planner_info = isset($planner_info[intval($v_info['p_uid'])]) ? $planner_info[intval($v_info['p_uid'])] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }
            //测试用户
            $uids[] = "171429858";
            foreach($uids as $uid){
                $msg_data = array(
                    'uid'=>$uid,
                    'u_type'=>1,
                    'type'=>2,
                    'relation_id'=>$pkg_id,
                    'child_relation_id'=>$v_id,
                    'content'=>json_encode( array(
                        array('value'=>$v_info['title'],'class'=>'','link'=>"/view/".$v_id."?ind_id=".$v_info['ind_id'])
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'title'=>$planner_info['name'],
                        'type'=>$v_info['type'],
                        'package_title' => $package['title'],
                        'view_title' => $v_info['title'],
                        'summary' => CommonUtils::getSubStrNew($v_info['summary'],40,'...'),
                        'ind_id' => $v_info['ind_id'],
                        'p_uid' => $v_info['p_uid'],
                        'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>"/view/".$v_id."?ind_id=".$v_info['ind_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                //保存通知消息
                Message::model()->saveMessage($msg_data);
                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            }

            //加入提醒队列
            if(!empty($uids)){
                //将用户500个一组，分批放入队列。否则会导致数据过大。
                foreach($uids as $_uids){
                    /**
                     * 20181019 lining添加理财师白名单(部分内容号,直接推送)
                     */
                    if(in_array($planner_info['p_uid'], self::$pushAllNeironghao)){
                        echo "推送的内容号不需要验证权限=>".$planner_info['p_uid']."\r\n";
                    }else{
                        //过滤内容号相关内容
                        $filter = self::getNeiRongHao();//需要过滤的理财师ID
                        if(in_array($planner_info['p_uid'],$filter)){
                            //如果用户关闭推送跳过
                            $redis_key = MEM_PRE_KEY."neironghao_tui_".$planner_info['p_uid'];
                            echo $redis_key.$_uids;
                            $data = Yii::app()->redis_r->hget($redis_key,$_uids);
                            $data = json_decode($data,true);
                            var_dump($data);
                            if(!empty($data)){
                                if($data['tui']){
                                    $msg_data['isRing'] = $data['volid'];
                                }else{
                                    continue;
                                }
                            }
                        }
                    }
                    var_dump($msg_data);
                    $this->commonHandler->addToPushQueue($msg_data,(array)$_uids, array(2, 3));
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
     * 获取时内容号数组
     */
    public static function getNeiRongHao(){
        $redis_key = "lcs_NeiRongHao_puids";
        $data = Yii::app()->redis_r->get($redis_key);
        $NeiRong = json_decode($data,true);
        return $NeiRong;
    }
}
