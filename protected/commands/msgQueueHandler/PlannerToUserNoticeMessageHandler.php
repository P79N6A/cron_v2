<?php
/**
 * 理财师给用户(客户)的通知
 * User: weiguang3
 * Date: 2016-05-19
 * Time: 20:19
 */

class PlannerToUserNoticeMessageHandler {

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
            $this->commonHandler->checkRequireParam($msg, array('n_id'));

            //获取通知信息
            $push_msgs = PlannerPush::model()->getPlannerPushMsgByIds($msg['n_id'],'id,p_uid,grp_id,type,relation_id,content,status');
            $push_msg = isset($push_msgs[$msg['n_id']])?$push_msgs[$msg['n_id']]:array();
            if(empty($push_msg) || $push_msg['status']!=2){
                throw new Exception("push 消息为空或是状态错误：n_id=".$msg['n_id']);
            }

            //获取通知用户
            $c_uids = PlannerPush::model()->getCustomerUidByGid($push_msg['p_uid'],$push_msg['grp_id']);
            if(empty($c_uids)){
                // 更新推送消息的状态
                PlannerPush::model()->updatePlannerPushMsgInfo($push_msg['p_uid'], $push_msg['id'], array("status" => 0, "total_push" => 0, "u_time" => date("Y-m-d H:i:s")));

                throw new Exception("推送的消息用户为空");
            }

            // 获取理财师信息
            $planner_info = Planner::model()->getPlannerById(array($push_msg['p_uid']));
            $planner_info = isset($planner_info[$push_msg['p_uid']]) ? $planner_info[$push_msg['p_uid']] : array();
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
            // 理财师评级信息
            $planner_exts = Planner::model()->getPlannerExtInfo(array($push_msg['p_uid']));
            if (!empty($planner_exts) && isset($planner_exts[$push_msg['p_uid']])) {
                $px = $planner_exts[$push_msg['p_uid']];

                $grade_info=array();
                $grade_info['grade_plan'] = isset($px['grade_plan'])?($px['grade_plan_auto']==1&&$px['grade_plan']>3?3:$px['grade_plan']):0;
                $grade_info['grade_plan_status'] = isset($px['grade_plan_status'])?$px['grade_plan_status']:0;
                $grade_info['grade_pkg'] = isset($px['grade_pkg'])?(($px['grade_pkg_auto']==1&&$px['grade_pkg']>3?3:$px['grade_pkg'])):0;
                $grade_info['grade_pkg_status'] = isset($px['grade_pkg_status'])?$px['grade_pkg_status']:0;

                $planner_info['grade_info'] = $grade_info;
                unset($planner_exts);unset($px);unset($grade_info);
            }

            $content_client = array();
            $content_client['type']=$push_msg['type'];
            $content_client['n_id']=$push_msg['id'];
            // $content_client['content']=CHtml::encode(CommonUtils::getSubStrNew($push_msg['content'],30,'...'));
            $content_client['content']=CHtml::encode($push_msg['content']);
            // 添加理财师信息
            $content_client['p_uid'] = $planner_info['p_uid'];
            $content_client['p_name'] = $planner_info['name'];
            $content_client['p_image'] = $planner_info['image'];
            $content_client['p_company'] = $planner_info['company'];
            $content_client['p_position'] = $planner_info['position'];
            $content_client['p_grade_info'] = $planner_info['grade_info'];
            //根据通知类型  获取具体的内容
            $content = "";
            switch($push_msg['type']){
                case '1':
                    $content = "给您推送了一条消息";
                    break;
                case '2':
                    $content_client = array_merge($content_client,$this->getViewInfo($push_msg['relation_id']));
                    $content = "给您推送了观点《".$content_client['v_title']."》";
                    break;
                case '3':
                    $content_client = array_merge($content_client,$this->getPlanInfo($push_msg['relation_id']));
                    $content = "给您推送了计划《".$content_client['pln_name']."》";
                    break;
                case '4':
                    $content = "给大家发优惠劵了，快来抢吧";
                    $content_client = array_merge($content_client,$this->getCouponInfo($push_msg['relation_id']));
                    break;
                default:
                    throw new Exception("未知的通知类型：".$push_msg['type']);
                    break;
            }

            //记录通知
            foreach($c_uids as $uid){
                $msg_data = array(
                    'uid'=>$uid,
                    'u_type'=>1,
                    'type'=>20,
                    'relation_id'=>$msg['n_id'],
                    'content'=>json_encode(array(
                        array('value'=>"理财师",'class'=>'','link'=>''),
                        array('value'=>$planner_info['name'],'class'=>'','link'=>'/planner/'.$planner_info['p_uid'].'/1'),
                        array('value'=>$content,'class'=>'','link'=>'')
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode($content_client,JSON_UNESCAPED_UNICODE),
                    'link_url'=>'',
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 保存通知消息
                Message::model()->saveMessage($msg_data);

            }
            $log_data['ext_data']['uids']=$c_uids;


            // 加入提醒队列
            if(!empty($c_uids)){
                //将用户500个一组，分批放入队列。否则会导致数据过大。
                $uids_arr = array_chunk($c_uids,500);
                foreach($uids_arr as $_uids){
                    $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(2,3));
                }
            }

            // 更新推送消息的状态
            PlannerPush::model()->updatePlannerPushMsgInfo($push_msg['p_uid'], $push_msg['id'], array("status" => 0, "total_push" => count($c_uids), "u_time" => date("Y-m-d H:i:s")));
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
     * 获取观点的推送信息
     * @param $v_id
     * @return array
     * @throws Exception
     */
    private function getViewInfo($v_id)
    {
        if(empty($v_id)) {
            throw new Exception('观点ID为空');
        }

        $view_infos = View::model()->getViewById($v_id);
        $view_info = isset($view_infos[$v_id])? $view_infos[$v_id] : null;
        if(empty($view_info)) {
            throw new Exception('观点为空');
        }
        $view_click_info = View::model()->getViewClick($v_id);

        $data = array();
        $data['v_id']=$v_id;
        $data['v_title']=$view_info['title'];
        $data['v_summary']=$view_info['summary'];
        $data['v_image']=$view_info['image'];
        $data['v_type']=$view_info['type'];
        $data['v_p_time']=$view_info['p_time'];
        $data['v_view_num'] = isset($view_click_info[$v_id]) ? (int) $view_click_info[$v_id] : 0;
        $data['v_pkg_id']=$view_info['pkg_id'];
        $data['v_pkg_name']='';
        $package_infos = Package::model()->getPackagesById($view_info['pkg_id']);
        if(!empty($package_infos) && isset($package_infos[$view_info['pkg_id']])) {
            $data['v_pkg_name']=$package_infos[$view_info['pkg_id']]['title'];
            $data['v_pkg_image']=$package_infos[$view_info['pkg_id']]['image'];
        }

        return $data;
    }

    /**
     * 获取计划的推送信息
     * @param $pln_id
     * @return array
     * @throws Exception
     */
    private function getPlanInfo($pln_id){
        if(empty($pln_id)) {
            throw new Exception('计划ID为空');
        }

        $plan_infos = Plan::model()->getPlanInfoByIds($pln_id, '');
        $plan_info = isset($plan_infos[$pln_id])? $plan_infos[$pln_id] : null;
        if(empty($plan_info)) {
            throw new Exception('计划为空');
        }
        $data=array();
        $data['pln_id']=$pln_id;
        $data['pln_name']=($plan_info['number']>9?$plan_info['name'].$plan_info['number']:$plan_info['name'].'0'.$plan_info['number']).'期';
        $data['pln_status']=$plan_info['status'];
        $data['pln_target_ror']=$plan_info['target_ror'];
        $data['pln_curr_ror']=$plan_info['curr_ror'];
        $data['pln_history_year_ror']=$plan_info['history_year_ror'];
        $data['pln_stop_loss']=$plan_info['stop_loss'];
        $data['pln_hs300']=$plan_info['hs300'];
        $data['pln_start_date']=$plan_info['start_date'];
        $data['pln_end_date']=$plan_info['end_date'];
        $data['pln_run_days']=$plan_info['run_days'];
        $data['pln_invest_days']=$plan_info['invest_days'];


        return $data;
    }


    /**
     * 获取优惠劵的推送信息
     * @param $cpn_id
     * @return array
     * @throws Exception
     */
    private function getCouponInfo($cpn_id){
        if(empty($cpn_id)) {
            throw new Exception('优惠劵ID为空');
        }

        $coupon_infos = Coupon::model()->getCouponInfoById((array)$cpn_id, 'coupon_id,type,name,start_time,end_time,validity_date,code, amount,price,discount,full,reduction');
        $coupon_info = isset($coupon_infos[$cpn_id])? $coupon_infos[$cpn_id] : null;
        if(empty($coupon_info)) {
            throw new Exception('优惠券为空');
        }
        $data=array();
        $data['cpn_id']=$cpn_id;
        $data['cpn_type']=$coupon_info['type'];
        $data['cpn_name']=$coupon_info['name'];
        $data['cpn_start_time']=$coupon_info['start_time'];
        $data['cpn_end_time']=$coupon_info['end_time'];
        $data['cpn_validity_date']=$coupon_info['validity_date'];
        $data['cpn_code']=$coupon_info['code'];
        $data['cpn_amount']=$coupon_info['amount'];
        $data['cpn_price']=$coupon_info['price'];
        $data['cpn_discount']=$coupon_info['discount'];
        $data['cpn_full']=$coupon_info['full'];
        $data['cpn_reduction']=$coupon_info['reduction'];

        return $data;
    }


}