<?php

/**
 * Moments交易动态生成
 * Class MomentsProducerMessageHandler
 */
class MomentsProducerMessageHandler
{
    private $commonHandler = null;

    public function __construct()
    {
        $this->commonHandler = new CommonHandler();
    }


    public function run($msg)
    {
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('discussion_type','discussion_id'));
            switch ($msg['discussion_type']) {
                //交易动态同步
                case Moments::DISCUSSION_TYPE_PLAN:
                    $res = $this->planMoments($msg['discussion_id']);
                    break;
                //观点动态同步
                case Moments::DISCUSSION_TYPE_VIEW:
                    $res = $this->viewMoments($msg['discussion_id']);
                    break;
                default :
                    throw new Exception("无法处理discussion_type:{$msg['discussion_type']}");
            }

            if (!$res) {
                throw new Exception("type:{$msg['discussion_type']}, id:{$msg['discussion_id']}同步到moments失败");
            }
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }
        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = is_array($log_data['ext_data']) ? json_encode($log_data['ext_data']) : $log_data['ext_data'];
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

    /**
     * 以理财师身份生成计划交易动态
     * @param $trans_id
     * @return mixed
     * @throws Exception
     */
    private function planMoments($trans_id)
    {
        $trans_info = Plan::model()->getPlanTransactionByIds($trans_id);
        if (empty($trans_info[$trans_id])) {
            throw new Exception("不存在tran_id:{$trans_id}计划交易动态");
        }

        $trans_info = $trans_info[$trans_id];
        //查找对应计划
        $pln_id = $trans_info['pln_id'];
        $plan_info = Plan::model()->getPlanInfoByIds($pln_id, 'pln_id,p_uid');
        if (empty($plan_info[$pln_id])) {
            throw new Exception("不存在计划交易动态tran_id:{$trans_id}中指定的pln_id:{$pln_id}计划");
        }
        $plan_info = $plan_info[$pln_id];
        //查找理财师对应的uid
        $p_uid = $plan_info['p_uid'];
        $uids = User::model()->getUidBySuids($p_uid);
        if (empty($uids[$p_uid])) {
            throw new Exception("不存在计划交易动态tran_id:{$trans_id}中指定的pln_id:{$pln_id}计划对应的p_uid:{$p_uid}");
        }
        $uid = $uids[$p_uid];
        //仅取未入库的动态，避免后期维护时候重复添加
        $is_exist = Moments::model()->isExistMoments($uid, Moments::DISCUSSION_TYPE_PLAN, $trans_id);
        //var_dump($trans_id); exit;
        //var_dump($is_exist); exit;
        if (!empty($is_exist)) {
            throw new Exception("\n计划交易动态tran_id:{$trans_id}已经同步过了");
        }

        $cur_time = date('Y-m-d H:i:s');
        $data[] = array(
            'is_fee'          => 1,
            'p_uid'           => $p_uid,
            'uid'             => $uid,
            'c_time'          => $trans_info['c_time'],
            'u_time'          => $cur_time,
            'discussion_type' => Moments::DISCUSSION_TYPE_PLAN,
            'discussion_id'   => $trans_info['id'],
            'relation_id'     => $trans_info['pln_id'],
        );


        if (empty($data)) {
            throw new Exception("没有需要同步的交易动态消息流");
        } else {
            $res = Moments::model()->batchAddMoments($data);
        }

        return $res;
    }

    /**
     * 观点动态
     * @param $v_id
     * @return mixed
     * @throws Exception
     */
    private function viewMoments($v_id)
    {
        //获取指定时间段内的观点列表
        $view_info = View::model()->getViewById($v_id);
        if (empty($view_info[$v_id])) {
            throw new Exception("观点:{$v_id}不存在");
        }
        $view_info = $view_info[$v_id];
        $uids    = User::model()->getUidBySuids($view_info['p_uid']);
        $pkg_map = Package::model()->getPackagesById($view_info['pkg_id'], false);
        $data    = array();
        $cur_time = date('Y-m-d H:i:s');
        //如果观点包收费，底下的观点也会是收费，按是否收费存储
        if (isset($pkg_map[$view_info['pkg_id']]) && ($pkg_map[$view_info['pkg_id']]['subscription_price'] > 0)) {
            $is_fee = 1;
        } else {
            $is_fee = 0;
        }
        $uid = $uids[$view_info['p_uid']];
        //仅取未入库的动态，避免后期维护时候重复添加
        $is_exist = Moments::model()->isExistMoments($uid, Moments::DISCUSSION_TYPE_VIEW, $v_id);
        if (!empty($is_exist)) {
            throw new Exception("\n观点动态id:{$v_id}已经同步过了");
        }

        $data[] = array(
            'is_fee'          => $is_fee,
            'p_uid'           => $view_info['p_uid'],
            'uid'             => $uid,
            'c_time'          => $view_info['p_time'],
            'u_time'          => $cur_time,
            'discussion_type' => Moments::DISCUSSION_TYPE_VIEW,
            'discussion_id'   => $v_id,
            'relation_id'     => $view_info['pkg_id'],
        );

        $res = Moments::model()->batchAddMoments($data);

        return $res;
    }
}