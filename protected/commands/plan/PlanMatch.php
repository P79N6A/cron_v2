<?php

/**
 * 计划大赛导入
 */
class PlanMatch {

    const CRON_NO = 5016; //任务代码

    public function __construct() {
        
    }

    public function process($pln_ids = "") {
        $match_id=10002;
        if ($pln_ids != "") {
            $pln_id_array = explode(',', $pln_ids);
        } else {
            $pln_id_array = PlannerMatch::model()->getMatchPlan($match_id);
        }

        ///排除已经有的比赛
        $pln_id_array = $this->getValidPlnIds($pln_id_array);

        if (count($pln_id_array) > 0) {
            $plan_infos = Plan::model()->getPlanInfoByIds($pln_id_array);
            
            $rest_signal=0;
            
            foreach ($pln_id_array as $item) {
                $plan_info = isset($plan_infos[$item]) ? $plan_infos[$item] : 0;
                if (empty($plan_info)) {
                    continue;
                }
                $insert_data = $this->packageMatchData($plan_info);
                $res = PlannerMatch::model()->addMatch($insert_data);
                //var_dump($insert_data);
                echo "add prop [",$insert_data['title'],"] ok","\r\n";
                ///每插入500条记录休息下
                $rest_signal=$rest_signal+1;
                if($rest_signal/100==0){
                    sleep(1);
                }
            }
        }
    }

    /**
     * 获取有效的计划
     */
    public function getValidPlnIds($pln_id_array) {
        $exists_pln_id_array = PlannerMatch::model()->getMatchRelationIds(2001,5);
        $res = array();
        foreach ($pln_id_array as $item) {
            if (in_array($item, $exists_pln_id_array)) {
                continue;
            } else {
                $res[] = $item;
            }
        }
        $res = array_unique($res);
        return $res;
    }

    /**
     * 打包比赛数据
     */
    public function packageMatchData($plan_info) {
        $data = array();
        $data['title'] = $plan_info['name'].($plan_info['number'] > 9 ? $plan_info['number'] : "0" . $plan_info['number']) . "期";
        $data['summary'] = $plan_info['summary'];
        $data['type'] = 2001;
        $data['relation_id'] = $plan_info['pln_id'];
        $data['relation_p_uid'] = $plan_info['p_uid'];
        $data['relation_price'] = $plan_info['subscription_price'];
        $data['amount_total'] = 1000;
        $data['amount_remainder'] = 1000;
        $data['efficient'] = "604800"; ///7天的秒数
        $data['expire_time'] = "2016-12-12 00:00:00";
        $data['price'] = 168;
        $data['staff_uid'] = "";
        $data['use_channel'] = 5;
        $data['status'] = 0;
        $data["c_time"] = date("Y-m-d H:i:s");
        $data["u_time"] = date("Y-m-d H:i:s");
        return $data;
    }

}
