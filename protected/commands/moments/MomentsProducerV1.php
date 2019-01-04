<?php

/**
 *
 */
class MomentsProducerV1
{
    const CRON_NO = 1504;
    const DEFAULT_FLOW_GAP = 600; // 每次开始生产的时间间隔(秒)，默认10分钟
    private $last_time; //上次执行时间
    private $mom_s; //momentService
    //上次处理时间key
    private $last_time_key = "moments_producerv1_last_time";

    public function __construct()
    {

    }

    /**
     * 生成观点包（包含付费+免费）+计划动态
     * @param string $start_time
     * @param string $end_time
     */
    public function process($start_time = "", $end_time = "")
    {
        $this->mom_s = new MomentsService();

        //导入数据默认截止到当前时间
        if (empty($end_time)) {
            $end_time = date("Y-m-d H:i:s");
        }

        //导入数据开始时间默认为上次执行时间
        if (empty($start_time)) {
            $last_time = strtotime(Yii::app()->redis_r->get(MEM_PRE_KEY . $this->last_time_key));
            if (empty($last_time)) {
                $last_time = date('Y-m-d H:i:s', (strtotime($end_time) - self::DEFAULT_FLOW_GAP - 120));
            }
            $start_time = date("Y-m-d H:i:s", $last_time);
            echo "last_time:" . $start_time."\n";
        }

        Yii::app()->redis_w->set(MEM_PRE_KEY.$this->last_time_key, $end_time);
        $view_res = $this->viewMoments($start_time, $end_time);
        echo '观点：'.$this->getResult($view_res);
        echo "\n";
        $plan_res = $this->planMoments($start_time, $end_time);
        echo '交易动态：'.$this->getResult($plan_res);
        echo "\n";
        echo "本次起止：$start_time -- $end_time";
        echo "\n";
    }

    private function getResult($result) {
        if($result > 0) {
            $res = '更新成功，已同步' . $result . '条';
        } elseif ($result == -1) {
            $res = '没有需要同步的数据';
        } else {
            $res = "同步失败";
        }

        return $res;
    }

    /**
     * 以理财师身份生成观点动态
     * @param $start_time
     * @param $end_time
     * @return int|void
     */
    private function viewMoments($start_time, $end_time)
    {
        $view_ids = [];
        $pkg_ids  = [];
        $s_uids   = [];
        //获取指定时间段内的观点列表
        $res = View::model()->getViewIdList($start_time, $end_time);
        if (empty($res)) {
            return -1;
        }

        foreach ($res as $row) {
            $view_ids[] = $row['id'];
            $pkg_ids[]  = $row['pkg_id'];
            $s_uids[]   = $row['p_uid'];
        }

        $uids      = User::model()->getUidBySuids($s_uids);
        $pkg_map   = Package::model()->getPackagesById($pkg_ids, false);
        $view_map  = View::model()->getViewById($view_ids);
        $data = array();
        if (empty($view_map)) $view_map = [];
        $cur_time = date('Y-m-d H:i:s');
        foreach ($view_map as $row) {
            //如果观点包收费，底下的观点也会是收费，按是否收费存储
            if (isset($pkg_map[$row['pkg_id']]) && ($pkg_map[$row['pkg_id']]['subscription_price'] > 0)) {
                $is_fee = 1;
            } else {
                $is_fee = 0;
            }
            $uid = $uids[$row['p_uid']];
            //仅取未入库的动态，避免后期维护时候重复添加
            $is_exist = Moments::model()->isExistMoments($uid, Moments::DISCUSSION_TYPE_VIEW, $row['id']);
            if (!empty($is_exist)) {
                continue;
            }

            $data[] = array(
                'is_fee'          => $is_fee,
                'p_uid'           => $row['p_uid'],
                'uid'             => $uid,
                'c_time'          => $row['p_time'],
                'u_time'          => $cur_time,
                'discussion_type' => Moments::DISCUSSION_TYPE_VIEW,
                'discussion_id'   => $row['id'],
                'relation_id'     => $row['pkg_id'],
            );
        }

        if (empty($data)) {
            //没有需要同步的观点动态消息流
            $res = -1;
        } else {
            $res = Moments::model()->batchAddMoments($data);
        }

        return $res;
    }

    /**
     * 以理财师身份生成计划交易动态
     * @param $start_time
     * @param $end_time
     * @return int|void
     */
    private function planMoments($start_time, $end_time)
    {
        $trans_list = Plan::model()->getPlanTransactionList($start_time, $end_time);
        if (empty($trans_list)) {
            return -1;
        }

        $pln_ids = [];
        $trans_ids = [];
        foreach ($trans_list as $item) {
            $pln_ids[] = $item['pln_id'];
            $trans_ids[] = $item['id'];
        }
        $pln_ids = array_unique($pln_ids);
        $plan_map = Plan::model()->getPlanInfoByIds($pln_ids, 'pln_id,p_uid');
        $s_uids = array();
        foreach ($plan_map as $item) {
            $s_uids[] = $item['p_uid'];
        }
        $uids = User::model()->getUidBySuids($s_uids);
        $data = array();
        $cur_time = date('Y-m-d H:i:s');
        foreach ($trans_list as $item) {
            if (empty($plan_map[$item['pln_id']])) {
                continue;
            }
            $p_uid  = $plan_map[$item['pln_id']]['p_uid'];
            if (empty($uids[$p_uid])) {
                continue;
            }
            $uid = $uids[$p_uid];
            //仅取未入库的动态，避免后期维护时候重复添加
            $is_exist = Moments::model()->isExistMoments($uid, Moments::DISCUSSION_TYPE_PLAN, $item['id']);
            if (!empty($is_exist)) {
                continue;
            }

            $data[] = array(
                'is_fee'          => 1,
                'p_uid'           => $p_uid,
                'uid'             => $uid,
                'c_time'          => $item['c_time'],
                'u_time'          => $cur_time,
                'discussion_type' => Moments::DISCUSSION_TYPE_PLAN,
                'discussion_id'   => $item['id'],
                'relation_id'     => $item['pln_id'],
            );
        }

        if (empty($data)) {
            //没有需要同步的交易动态消息流
            $res = -1;
        } else {
            $res = Moments::model()->batchAddMoments($data);
        }

        return $res;
    }

}