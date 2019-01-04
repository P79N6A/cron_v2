<?php

/*
 * author:shixi_lixiang3
 * time:2016.1.6
 * 理财师名片表格数据
 */

class PlannerCardChartData {

    const CRON_NO = 8013; //任务代码

    public function __construct() {
        
    }

    ///计算表格数据
    public function CalculateCardData() {
        ///获取非投顾大赛的理财师
        $p_uids = Planner::model()->getPlannerWithOutTouGu();
        if(empty($p_uids)){
            return 0;
        }
        foreach ($p_uids as $item) {
            $res = Array();
            ///获取该理财师的所有交易日,交易日会从redis中取,
            $all_trade_date = Planner::model()->getPlannerTradeDate($item);
            ///取6天交易日
            $need_date = CommonUtils::divideArray($all_trade_date, 6);
            if (count($need_date) > 0) {
                ///计算这6天的数据
                foreach ($need_date as $temp_date) {
                    ///先从redis中取该天是否计算了，如果计算了则直接取，无则需要计算并保存到redis中
                    $temp = Planner::model()->getDataByDate($item, $all_trade_date[0], $temp_date);
                    $res[] = $temp;
                }
                Planner::model()->setPlannerCardDataToRedis($item, $res);
            }
            echo date("Y-m-d H:i:s")," p_uid=".$item," data=".json_encode($res),"\n";
        }
        return count($p_uids);
    }

}
