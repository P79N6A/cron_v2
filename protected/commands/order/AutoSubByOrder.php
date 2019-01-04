<?php

/**
 * 根据已购订单自动添加订阅关系
 * @author danxian
 * @date 2016/12/29
 */
class AutoSubByOrder
{
    const CRON_NO = 8106;

    public function process() {
        //王力慧6510观点包，自动配送永久计划35476
        $this->autoSubPlanByPkgSubUser(6510, 35476);
        //$this->autoSubPlanByPkgSubUser(5325, 100003);
    }

    /**
     * @param int $pkg_id 指定监控赠送订阅计划的观点包
     * @param int $pln_id
     */
    private function autoSubPlanByPkgSubUser($pkg_id, $pln_id) {
        //指定购买了观点包半年的用户可享受此
        $amount = array(6, 12);
        //订单类型是观点包
        $type   = 31;
        //1.取具备赠送条件的订单用户
        $uids   = $this->getOrderUids($pkg_id, $type, $amount);
        if (!empty($uids)) {
            $expire_time = "0000-00-00 00:00:00";
            $status = 2;
            $res = array();
            $trans = Yii::app()->lcs_w->beginTransaction();
            //2.给涉及到的用户添加订阅关系
            try {
                foreach ($uids as $uid) {
                    //如果已订阅
                    $sub_info = PlanSubscription::model()->getPlanSubscriptionInfo($uid, $pln_id);
                    //2.1先处理已有赠送计划订阅关系的
                    $res_msg = array();
                    if (!empty($sub_info) && $sub_info['status'] > 0) {
                        //2.1.1判断订阅关系的有效期是否大于0 大于零直接 需要延长订阅时间，并设为永久有效
                        if (strtotime($sub_info['expire_time']) > 0) {
                            $upd_data['u_time'] = date("Y-m-d H:i:s");
                            $upd_data['status'] = $status;
                            $upd_data['expire_time'] = date("Y-m-d H:i:s", strtotime($expire_time));
                            Plan::model()->updatePlanSubscription($upd_data, 'id=:id', array(':id' => $sub_info['id']));
                            $res_msg[] = "由于uid:{$uid} 购买了{$pkg_id},且配送的{$pln_id}已被订阅,有效期至至{$sub_info['expire_time']}，订阅id:{$sub_info['id']} 已变更为无限期。";
                        }
                        //2.1.2已经订阅和延期的直接跳过，不用添加订阅关系
                        continue;
                    }

                    //2.2 添加订阅关系
                    $add_res = PlanService::addPlanSub($uid, $pln_id, $expire_time, $status);
                    if (!$add_res) {
                        throw new Exception("uid:{$uid} -> 计划:{$pln_id}自动订阅失败");
                    }

                    $res[] = "uid:{$uid}购买了观点包{$pkg_id},已为其自动订阅计划:{$pln_id},过期时间:{$expire_time}\n";
                }

                if (empty($res)) {
                    $res_msg[] = "没有可自动订阅计划({$pln_id})的用户#1";
                }
                $msg_json = json_encode($res_msg, JSON_UNESCAPED_UNICODE);
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, $msg_json);
                $trans->commit();
            } catch (Exception $e) {
                $trans->rollBack();
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "自动订阅失败：".$e->getMessage());
            }
        } else {
            $msg_json = "没有有效的观点包（{$pkg_id}）购买用户";
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, $msg_json);
        }
    }

    /**
     * 获取添加订阅关系的uid
     * @param $relation_id
     * @param $type
     * @param $amount
     * @return array
     */
    private function getOrderUids($relation_id, $type, $amount) {
        //获得可自动添加计划订阅关系的订单
        $orders = Orders::model()->getSubOrders($relation_id, $type, $amount);
        $uids   = array();
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $uids[] = $order['uid'];
            }
            $uids = array_unique($uids);
        }

        return $uids;
    }


}