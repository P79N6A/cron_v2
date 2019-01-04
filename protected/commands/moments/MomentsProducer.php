<?php
/**
 * 用户 Moments 的生产
 * add by zhihao6 2017/02/13
 */

/**
 * 生产用户 Moments，每10分钟执行一次
 *
 * 1. 取 10 分钟内的免费观点
 * 1.2. 取观点对应理财师的关注用户
 * 1.3. 生成这些用户的观点 Momtents 至 comment 表
 *
 * 2. 取 10 分钟内的收费观点
 * 2.2. 取观点对应理财师的购买用户
 * 2.3. 生成这些用户的观点 Momtents 至 comment 表
 * 
 * 3. 取 10 分钟内的交易动态
 * 3.2 取交易动态对应计划对应的理财师的购买用户
 * 3.3 生成这些用户的观点 Momtents 至 comment 表
 */
class MomentsProducer
{
    const CRON_NO = 1502; //任务代码
    const DEFAULT_FLOW_GAP = 600; // 每次开始生产的时间间隔(秒)，默认10分钟

    private $mom_s;

    function __construct()
    {
    }

    public function process()
    {
        $this->mom_s = new MomentsService();

        $this->last_time = Yii::app()->redis_r->get(MEM_PRE_KEY."moments_producer_last_time");
        if (!empty($this->last_time)) {
            $end_time = date("Y-m-d H:i:s", strtotime($this->last_time)+self::DEFAULT_FLOW_GAP);
            $start_time = $this->last_time;
        } else {
            $end_time = date("Y-m-d H:i:s");
            $start_time = date("Y-m-d H:i:s", strtotime($end_time)-self::DEFAULT_FLOW_GAP);
        }
print_r("{$start_time}-{$end_time}:\n");
        $this->viewMoments($start_time, $end_time);
        $this->planTransMoments($start_time, $end_time);

        // Yii::app()->redis_w->set(MEM_PRE_KEY."moments_producer_last_time", $end_time);
    }

    private function viewMoments($start_time, $end_time)
    {
        $view_ids = [];
        $pkg_ids = [];
        $res = View::model()->getViewIdList($start_time, $end_time);
        foreach ($res as $row) {
            $view_ids[] = $row['id'];
            $pkg_ids[] = $row['pkg_id'];
        }
// $view_ids = [];
print_r($view_ids);
        $pkg_map = Package::model()->getPackagesById($pkg_ids, false);
        $view_map = View::model()->getViewById($view_ids);
        if (empty($view_map)) $view_map = [];
        foreach ($view_map as $row) {
            if (isset($pkg_map[$row['pkg_id']]) && ($pkg_map[$row['pkg_id']]['subscription_price'] > 0)) {
                $this->mom_s->chargeViewMoments($row['id'], $row);
            } else {
                $this->mom_s->freeViewMoments($row['id'], $row);
            }
        }
    }
    private function planTransMoments($start_time, $end_time)
    {
        $trans_ids = PlanTransactions::model()->getPlanTransIdsByTime($start_time, $end_time);
// $trans_ids = [1046,1045];
print_r($trans_ids);
        $trans_map = PlanTransactions::model()->getTransListByTransIds($trans_ids);
        if (empty($trans_map)) $trans_map = [];
        foreach ($trans_map as $row) {
            $this->mom_s->planTransMoments($row['id'], $row);
        }
    }
}

