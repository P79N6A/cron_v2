<?php

/*
 * author:shixi_lixiang3
 * time:2015.12.4
 * 计划排行榜定时任务
 * 步骤：
 * 1、先获取满足基本条件的理财师信息。
 * 2、计算这些理财师的排行榜过滤参数
 * 3、更新redis中各个榜单中的前50个理财师的排行
 */

class PlannerRank {

    const CRON_NO = 8012; //任务代码

    public static $top_type_txt = array(
        '1' => '最高盈利榜',
        '2' => '风险控制榜',
        '3' => '逆势赚钱榜',
        '4' => '交易胜率榜',
        '5' => '稳定业绩榜',
        '6' => '最佳选股榜',
        '7' => '新进达人榜',
        '8' => '实战力排行榜',
    );

    public function __construct() {
        
    }

    public function updatePlannerRank() {
        $p_uids = $this->GetBasicPlanner();
        ///处理获取所有的理财师.
        $p_uids_array = Array();
        if ($p_uids && count($p_uids) > 0) {
            foreach ($p_uids as $item) {
                $p_uids_array[] = $item['p_uid'];
            }
        }else {
            return;
        }
        $all_planners = $this->CalculateRankParameter($p_uids_array);
        //处理 type 1~6的排行榜
        $res = $this->RefreshRank($all_planners, $p_uids_array);

        ///首秀前50名
        $new_p_uids = $this->GetNewPlanner();
        $new_p_uids_array = Array();
        if ($new_p_uids && count($new_p_uids) > 0) {
            foreach ($new_p_uids as $item) {
                $new_p_uids_array[] = $item['p_uid'];
            }
        }

        ///处理type 7新进达人榜
        $new_top_50_planners = $this->CalculateRankParameter($new_p_uids_array);
        $this->ProcessNewPlanner($new_top_50_planners, $new_p_uids_array);
        ///处理type 8实战力排行榜
        $this->processFightCapacity();

        if ($res) {
            Cron::model()->saveCronLog(PlannerRank::CRON_NO, CLogger::LEVEL_INFO, 'update top_50_planner');
        }
        $this->GetTop8Planner();
        $this->GetTop3Planner();

        ///设置更新时间
        Planner::model()->setTop50PlannerToRedis(date("Y-m-d H:i:s", time()), "u_time");
    }

    ///获取满足基本条件的理财师
    private function GetBasicPlanner() {
        $puids = Planner::model()->getBasicPlannerSuid(1);
        return $puids;
    }

    ///获取新进的前50名理财师
    private function GetNewPlanner() {
        $puids = Planner::model()->getBasicPlannerSuid(2);
        return $puids;
    }

    ///处理type 7新进达人榜，前50名理财师
    private function ProcessNewPlanner($new_top50_planners) {
        ///更新排行榜
        $old_rank = Planner::model()->getTop50PlannerFromRedis("7");
        ///按照年化收益排行
        $new_rank = $this->RankList($new_top50_planners, 1);
        foreach ($new_rank as &$item) {
            ///如果存在
            if (isset($old_rank[$item['p_uid']])) {
                $old = $old_rank[$item['p_uid']]['rank'];
                $new = $item['rank'];
                $old = (int) $old;
                $new = (int) $new;
                $item['rank_change'] = $new - $old;
                $item['is_new'] = 0;
            } else {
                $item['rank_change'] = 0;
                $item['is_new'] = 1;
            }
        }
        Planner::model()->setTop50PlannerToRedis($new_rank, "7");
        return true;
    }

    /**
     * 获取理财师实战力评级排行
     */
    private function processFightCapacity()
    {
        $p_uids3 = Planner::model()->getBasicPlannerSuid(3);
        $new_p_uids_array = Array();
        if ($p_uids3 && count($p_uids3) > 0) {
            foreach ($p_uids3 as $item) {
                $new_p_uids_array[] = $item['p_uid'];
            }
        }

        //按理财师的实战力等级排序，理财师的交易周期越长、历史年化收益和计划成功率越高，则理财师的等级越高
        //详见UE：http://10.210.241.119/UE/lcs_app_V2.2.9%EF%BC%88%E6%9C%8D%E5%8A%A1%E8%AF%84%E7%BA%A7%EF%BC%8C%E5%9F%BA%E9%87%91%E5%AE%9A%E6%8A%95%EF%BC%8C%E4%BA%A4%E6%98%93%E6%97%B6%E9%97%B4%E5%9B%BE%E7%89%87%EF%BC%89/#g=1&p=星级计划榜单
        $order_str = " ORDER BY grade_plan DESC,influence desc,pln_opt_days DESC,pln_year_rate DESC,pln_success_rate DESC ";
        $puid_sort = Planner::model()->getPlannerExtOrderByIds($new_p_uids_array, $order_str);
        $new_ids = array();
        if ($puid_sort && count($puid_sort) > 0) {
            foreach ($puid_sort as $item) {
                $new_ids[] = $item['s_uid'];
            }
        }

        $new_rank = $this->CalculateRankParameter($new_ids);

        ///更新排行榜
        $old_rank = Planner::model()->getTop50PlannerFromRedis("8");
        ///按照年化收益排行
        ///$new_rank = $this->RankList($new_top50_planners, 1);

        $result = array();
        if (!empty($new_rank)) {
            foreach ($new_rank as $index => &$item) {
                $item['rank'] = $index + 1;
                $result[$item['p_uid']] = $item;
            }
        }

        if (!empty($result)) {
            foreach ($result as &$item) {
                ///如果存在
                if (isset($old_rank[$item['p_uid']])) {
                    $old = array_key_exists('rank', $old_rank[$item['p_uid']]) ? $old_rank[$item['p_uid']]['rank'] : 0;
                    $new = $item['rank'];
                    $old = (int)$old;
                    $new = (int)$new;
                    $item['rank_change'] = $new - $old;
                    $item['is_new'] = 0;
                } else {
                    $item['rank_change'] = 0;
                    $item['is_new'] = 1;
                }
            }
        }

        Planner::model()->setTop50PlannerToRedis($result, "8");
        return true;
    }

    ///计算各个理财师的排行榜参数
    private function CalculateRankParameter($p_uids) {
        $company_id = Array();
        $planner_info = Planner::model()->getPlannerById($p_uids);
        foreach ($planner_info as $item) {
            $company_id[] = $item['company_id'];
        }
        $planner_plan = Planner::model()->getPlannerPlanInfos($p_uids);
        $all_company = Common::model()->getCompany($company_id);
        $planner_ext = Planner::model()->getPlannerExtInfo($p_uids);
        $planner_pln_ids = Plan::model()->getPlannerPlanIds($p_uids);
        $planner_assess = Plan::model()->getPlannerAssessInfo($planner_pln_ids);
        $planner_asset = Plan::model()->getPlannerAsset($planner_pln_ids);
        $all_plan = Plan::model()->getPlannersNewPlan($p_uids);
        $all_plan_finish_num = Plan::model()->getPlannerPlanIds($p_uids);
        $all_plan_fail_num = Plan::model()->getPlannerPlanIds($p_uids, " status>4");
        $result = Array();
        foreach ($p_uids as $item) {
            $tempNode = Array();
            $tempNode['p_uid'] = $item;

            ///交易总胜率=盈利的交易数/总的交易数.
            $total_profit_num = 0;
            $total_loss_num = 0;
            ///笔均盈利*盈利笔数 总和
            $total_avg_profit_Take_profit_num = 0;
            ///笔均亏损*亏损笔数 总和
            $total_avg_loss_Take_loss_num = 0;
            if (isset($planner_assess[$item])) {
                foreach ($planner_assess[$item] as $temp) {
                    $total_profit_num+=(int) $temp['profit_num'];
                    $total_loss_num+=(int) $temp['loss_num'];
                    $total_avg_profit_Take_profit_num+=$temp['avg_profit'] * $temp['profit_num'];
                    $total_avg_loss_Take_loss_num+=$temp['avg_loss'] * $temp['loss_num'];
                }
            }

            ///交易总胜率
            if (($total_profit_num + $total_loss_num) == 0) {
                $tempNode['total_success_rate_trade'] = (float)0;
            } else {
                $tempNode['total_success_rate_trade'] = round($total_profit_num * 1.0 / ($total_profit_num + $total_loss_num), 2);
            }
            $tempNode['total_trade_num'] = $planner_ext[$item]['pln_buy_num'] + $planner_ext[$item]['pln_sell_num'];

            ///计划总胜率=成功的计划/总的计划.
            /*if (isset($all_plan_finish_num[$item])) {
                $temp_pln_num = count($all_plan_finish_num[$item]);
            } else {
                $temp_pln_num = 0;
            }
            if (isset($all_plan_fail_num[$item])) {
                $temp_pln_loss_num = count($all_plan_fail_num[$item]);
            } else {
                $temp_pln_loss_num = 0;
            }
            if ($temp_pln_num == 0) {
                $tempNode['pln_success_rate'] = 0;
            } else {
                $tempNode['pln_success_rate'] = round(($temp_pln_num - $temp_pln_loss_num) * 1.0 / $temp_pln_num, 2);
            }*/

            $tempNode['pln_success_rate'] = $planner_ext[$item]['pln_success_rate'];
            ///计划总数
            $temp_pln_num = $tempNode['pln_num'] = $planner_ext[$item]['pln_num'];
            ///亏损控制=sum(min_profit)/计划总数,
            $total_min_profit = 0;
            $compare_market = 0;
            $avg_curr_ror = 0;
            if (isset($planner_plan[$item])) {
                foreach ($planner_plan[$item] as $temp) {
                    $total_min_profit+=$temp['min_profit'];
                    $compare_market+=($temp['curr_ror'] - $temp['hs300']);
                    $avg_curr_ror+=$temp['curr_ror'];
                }
            }

            if ($temp_pln_num == 0) {
                $tempNode['loss_control'] = 0;
                $tempNode['compare_market'] = 0;
                $tempNode['avg_curr_ror'] = 0;
            } else {
                $tempNode['loss_control'] = round($total_min_profit / $temp_pln_num, 2);
                ///跑赢大盘=sum(curr_ror-hs300)/计划总数
                $tempNode['compare_market'] = round($compare_market / $temp_pln_num, 2);
                $tempNode['avg_curr_ror'] = round($avg_curr_ror / $temp_pln_num, 2);
            }
            $px = $planner_ext[$item];
            //$tempNode['influence'] = $px['influence'];
            //评级信息
            $grade_info=array();
            $grade_info['grade_plan'] = isset($px['grade_plan'])?($px['grade_plan_auto']==1&&$px['grade_plan']>3?3:$px['grade_plan']):0;
            $grade_info['grade_plan_status'] = isset($px['grade_plan_status'])?$px['grade_plan_status']:0;
            $grade_info['grade_pkg'] = isset($px['grade_pkg'])?(($px['grade_pkg_auto']==1&&$px['grade_pkg']>3?3:$px['grade_pkg'])):0;
            $grade_info['grade_pkg_status'] = isset($px['grade_pkg_status'])?$px['grade_pkg_status']:0;
            $tempNode['grade_info'] = $grade_info;
            ///盈亏比=计划的(笔均盈利*盈利笔数 总和/总盈利笔数)/(笔均亏损*亏损笔数 总和/总亏损笔数)
            $sum_avg_profit = 0;
            $sum_avg_loss = 0;
            if ($total_profit_num != 0) {
                $sum_avg_profit = abs(ceil($total_avg_profit_Take_profit_num / $total_profit_num));
            }

            if ($total_loss_num != 0) {
                $sum_avg_loss = abs(ceil($total_avg_loss_Take_loss_num / $total_loss_num));
            }

            if ($sum_avg_loss == 0) {
                $tempNode['profit_loss_ratio'] = $sum_avg_profit;
            } else {
                $tempNode['profit_loss_ratio'] = round($sum_avg_profit / $sum_avg_loss, 2);
            }

            if ($tempNode['profit_loss_ratio'] > 100) {
                $tempNode['profit_loss_ratio'] = "100";
            } else if ($tempNode['profit_loss_ratio'] == 0) {
                $tempNode['profit_loss_ratio'] = "--";
            }

            ///最佳选股=profit>10%取已完成计划/lcs_plan_asset 已完成计划的所有股票.
            $total_planner_asset = 0;
            if (isset($planner_asset[$item])) {
                $total_planner_asset = count($planner_asset[$item]);
            }

            $total_profit_asset = 0;
            if (isset($planner_asset[$item])) {
                foreach ($planner_asset[$item] as $temp) {
                    if ($temp['profit'] >= 0.1) {
                        ///盈利大于等于10%
                        $total_profit_asset++;
                    }
                }
            } else {
                $total_profit_asset = 0;
            }

            if ($total_planner_asset == 0) {
                $tempNode['best_choice'] = (float)0;
            } else {
                $tempNode['best_choice'] = round($total_profit_asset / $total_planner_asset,2);
            }
            ///历史年化总收益
            $tempNode['pln_year_rate'] = $planner_ext[$item]['pln_year_rate'];

            ///判断是否带有最新计划
            if (isset($all_plan[$item])) {
                $tempNode['is_plan'] = 1;
                $tempNode['plan_info'] = $all_plan[$item];
            } else {
                $tempNode['is_plan'] = 0;
                $tempNode['plan_info'] = null;
            }
            ///获取姓名，图片，公司名
            $tempNode['name'] = '';
            $tempNode['image'] = '';
            $company_id = 0;
            $temp_position = 0;
            if (array_key_exists($item, $planner_info))
            {
                $tempNode['name']  = $planner_info[$item]['name'];
                $tempNode['image'] = $planner_info[$item]['image'];
                $company_id = $planner_info[$item]['company_id'];
                $temp_position = Common::model()->getPositionById($planner_info[$item]['position_id']);
            }

            $tempNode['company_name'] = '';
            if (array_key_exists($company_id, $all_company))
            {
                $tempNode['company_name'] = $all_company[$company_id]['name'];
            }

            if (isset($temp_position['name'])) {
                $tempNode['position'] = $temp_position['name'];
            } else {
                $tempNode['position'] = "";
            }
            $p_uids2 = $tempNode['p_uid'];
            $result[] = $tempNode;
        }

        return $result;
    }

    ///更新redis中的榜单
    private function RefreshRank($data) {
        for ($i = 1; $i <= 6; $i++) {
            $old_rank = Planner::model()->getTop50PlannerFromRedis($i);
            ///最高盈利榜
            $new_rank = $this->RankList($data, $i);
            foreach ($new_rank as &$item) {
                if (isset($old_rank[$item['p_uid']])) {
                    $old = $old_rank[$item['p_uid']]['rank'];
                    $new = $item['rank'];
                    ///如果存在
                    $old = (int) $old;
                    $new = (int) $new;
                    $item['rank_change'] = $old - $new;
                    $item['is_new'] = 0;
                } else {
                    $item['rank_change'] = 0;
                    $item['is_new'] = 1;
                }
            }
            Planner::model()->setTop50PlannerToRedis($new_rank, $i);
        }
        return true;
    }

    /**
     * 排序
     * @param type $data
     */
    private function RankList($data, $type, $count = 50) {
        ///根据不同排行榜获取前50个参赛
        if($type!=4){
            $data = $this->OrderRank($data, "total_success_rate_trade", 'desc');
        }
        $data = $this->OrderRank($data, "pln_year_rate", 'desc');
        switch ($type) {
            case 1:
                ///最高盈利榜
                ///$new_rank = $this->OrderRank($data, "pln_year_rate", 'desc');
                $new_rank = $data; ///先根据年后收益率排行，然后再根据其他数据拍
                break;
            case 2:
                ///风险控制榜
                $new_rank = $this->OrderRank($data, "loss_control", 'desc');
                break;
            case 3:
                ///逆势赚钱榜,跑赢大盘
                $new_rank = $this->OrderRank($data, "compare_market", 'desc');
                break;
            case 4:
                ///交易胜率榜
                $new_rank = $this->OrderRank($data, "total_success_rate_trade", 'desc');
                break;
            case 5:
                ///稳定业绩榜
                $new_rank = $this->OrderRank($data, "profit_loss_ratio", 'desc');
                break;
            case 6:
                ///最佳选股榜
                $new_rank = $this->OrderRank($data, "best_choice", 'desc');
                break;
        }
        $i = 0;
        $result = Array();
        foreach ($new_rank as $index => &$item) {
            if ($count == $i) {
                break;
            }
            $item['rank'] = $index + 1;
            $result[$item['p_uid']] = $item;
            $i++;
        }
        return $result;
    }

    /**
     * 将数组中的数据根据字段及其方向排序，冒泡排序
     * @param type $data    数组
     * @param type $field   排序字段
     * @param type $direction  排序的方向默认为desc，desc降序/asc升序
     */
    private function OrderRank($data, $field, $direction = "desc") {
        $count=count($data);
        $temp = Null;
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count-1; $j++) {
                if ($direction == "desc") {
                    if (((float)$data[$j][$field]) < ((float)$data[$j+1][$field])) {
                        $temp = $data[$j];
                        $data[$j] = $data[$j+1];
                        $data[$j+1] = $temp;
                    }
                } else {
                    if (((float)$data[$j][$field]) > ((float)$data[$j+1][$field])) {
                        $temp = $data[$j];
                        $data[$j] = $data[$j+1];
                        $data[$j+1] = $temp;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取每个排行榜的前8名
     * array('p_uid'=>array('类型'=>'','排名'=>''))
     */
    private function GetTop8Planner() {
        $result = Array();
        $result_new = Array();
        for ($i = 1; $i <= 8; $i++) {
            $tempdata = Planner::model()->getTop50PlannerFromRedis($i);
            $j = 0;
            foreach ($tempdata as $item) {
                ///只需要前8名
                if ($j >= 8) {
                    break;
                }
                if (isset($result[$item['p_uid']])) {
                    if ($result[$item['p_uid']]['rank'] > $item['rank']) {
                        $result[$item['p_uid']]['type'] = $i;
                        $result[$item['p_uid']]['name'] = self::$top_type_txt[$i];
                        $result[$item['p_uid']]['rank'] = $item['rank'];
                    }
                } else {
                    $result[$item['p_uid']]['type'] = $i;
                    $result[$item['p_uid']]['name'] = self::$top_type_txt[$i];
                    $result[$item['p_uid']]['rank'] = $item['rank'];
                }

                // add by zhihao6
                // 不做去重取最大值，保存所有榜单的信息
                $result_new[$item['p_uid']][] = array(
                    "type" => $i,
                    "name" => self::$top_type_txt[$i],
                    "rank" => $item['rank']
                );

                $j++;
            }
        }
        Planner::model()->setTop50PlannerToRedis($result, "only8");
        Planner::model()->setTop50PlannerToRedis($result_new, "only8_new");
    }

    /*
     * 获取每个排行榜的前3名并去重,保存年华收益率
     */

    private function GetTop3Planner() {
        $result = Array();
        for ($i = 1; $i <= 8; $i++) {
            $tempdata = Planner::model()->getTop50PlannerFromRedis($i);
            $j = 0;
            foreach ($tempdata as $item) {
                ///只需要前8名
                if ($j >= 3) {
                    break;
                }
                if (!isset($result[$item['p_uid']])) {
                    $result[$item['p_uid']]['name'] = $item['name'];
                    $result[$item['p_uid']]['image'] = $item['image'];
                    $result[$item['p_uid']]['position'] = $item['position'];
                    $result[$item['p_uid']]['pln_year_rate'] = $item['pln_year_rate'];
                    $result[$item['p_uid']]['company_name'] = $item['company_name'];

                    $result[$item['p_uid']]['from_top'] = array(
                        "type" => $i,
                        "rank" => $item['rank']
                    );

                    $planner_plans = Plan::model()->getPlanInfoByPlanner(
                                        $item['p_uid'], 
                                        "pln_id,target_ror,curr_ror,status", 
                                        array(2,3,4,5,6,7));
                    $result[$item['p_uid']]['new_plan_info'] = empty($planner_plans) ? null : array_shift($planner_plans);
                }
                $j++;
            }
        }
        $res = Array();
        foreach ($result as $index => $item) {
            $temp = Array();
            $temp['p_uid'] = $index;
            $temp['name'] = $item['name'];
            $temp['image'] = $item['image'];
            $temp['position'] = $item['position'];
            $temp['pln_year_rate'] = $item['pln_year_rate'];
            $temp['company_name'] = $item['company_name'];
            $temp['from_top'] = $item['from_top'];
            $temp['new_plan_info'] = $item['new_plan_info'];
            $res[] = $temp;
        }
        Planner::model()->setTop50PlannerToRedis($res, "only3");
    }

}
