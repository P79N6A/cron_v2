<?php

/**
 * 更新计划收益,市值,统计计划历史收益,清理未成交的单子
 */
class PlanIncome
{

    const CRON_NO = 5009; //任务代码

    public function __construct()
    {
    }

    public function incomeAndClear()
    {


        $day_end = date("Y-m-d");
        $is_trade_day = Yii::app()->lcs_r->createCommand("select cal_date as day from lcs_calendar where cal_date='" . $day_end . "'")->queryscalar();
        if (!$is_trade_day) {
            return;
        }
        $u_time = date("Y-m-d H:i:s");
        //清理未成交的单子
        $update_plan_info_sql = 'update lcs_plan_info set available_value=available_value+warrant_value,warrant_value=0,u_time="' . $u_time . '" where status=3 and warrant_value>0';
        $update_plan_order_sql = 'delete from lcs_plan_order  where status!=2 and is_handled=0';
        $update_plan_asset_sql = 'update lcs_plan_asset set available_sell_amount=amount,u_time="' . $u_time . '" where amount>0';

        $transaction = Yii::app()->lcs_w->beginTransaction();
        try {

            Yii::app()->lcs_w->createCommand($update_plan_info_sql)->execute();
            Yii::app()->lcs_w->createCommand($update_plan_order_sql)->execute();
            Yii::app()->lcs_w->createCommand($update_plan_asset_sql)->execute();

            $transaction->commit();

        } catch (Exception $e) {
            $transaction->rollBack();
            Cron::model()->saveCronLog(self::CRON_NO, 'error', json_encode($e->errorInfo));
        }


        //更新计划收益,市值,统计计划历史收益
        ###运行中的计划
        $sql = "SELECT pln_id,available_value,init_value,curr_ror,warrant_value,status,weight FROM  lcs_plan_info  WHERE status=3 and end_date>='" . $day_end . "'";
        $record_result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        ###当日结束的，失败的，强制平仓的
        $sql = "SELECT pln_id,available_value,init_value,curr_ror,warrant_value,status,weight FROM  lcs_plan_info  WHERE (status in(4,5) and real_end_time  BETWEEN  '" . $day_end . "' AND  '" . date("Y-m-d", strtotime("+1 day")) . "') or (status in(6,7))";
        $record_result_today_past_plan = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $record_result = array_merge($record_result, $record_result_today_past_plan);

        if (!empty($record_result)) {

            $insert_plan_history_sql = 'insert into lcs_plan_profit_stats_history(pln_id,profit_date,day_profit,total_profit,hold_percent,c_time,u_time) values';
            $insert_plan_history_sql_tp = "(%d,'%s',%s,%s,%s,'" . $u_time . "','" . $u_time . "'),";
            $insert_plan_history_sql_more = '';
            $plan_stats_sql_tp = "replace into lcs_plan_profit_stats(pln_id,profit_date,day_profit,total_profit,hold_percent,c_time,u_time) values(%d,'%s',%s,%s,%s,'" . $u_time . "','" . $u_time . "');";
            $plan_stats_sql_more = '';
            $plan_update_ids = array();
            $plan_hold_percent = array();
            $plan_curr_ror = array();
            $plan_message_ids = array();

            foreach ($record_result as $k => $v) {

                $plan_curr_ror[$v['pln_id']] = $v['curr_ror'];//市值/成本

                $plan_hold_percent[$v['pln_id']] = $v['weight'];

                $plan_update_ids[] = $v['pln_id'];

                if ($v['status'] == 3) {
                    $plan_message_ids[] = $v['pln_id'];
                }

            }
            $plan_history_last_date = Calendar::model()->getLastMarketDate($day_end);

            $plan_history = Plan::model()->getPlanHistoryProfit($plan_update_ids, $plan_history_last_date);

            foreach ($plan_history as $k => $v) {
                if ($v == 0) {
                    $day_profit = $plan_curr_ror[$k];
                } else {
                    $day_profit = $plan_curr_ror[$k] - $v;
                }
                $insert_plan_history_sql_more .= sprintf($insert_plan_history_sql_tp, $k, $day_end, $day_profit, $plan_curr_ror[$k], $plan_hold_percent[$k]);
                $plan_stats_sql_more .= sprintf($plan_stats_sql_tp, $k, $day_end, $day_profit, $plan_curr_ror[$k], $plan_hold_percent[$k]);

            }
            $insert_plan_history_sql_more = rtrim($insert_plan_history_sql_more, ",");

            $transaction = Yii::app()->lcs_w->beginTransaction();
            $plan_message_ids = array_unique($plan_message_ids);

            try {

                Yii::app()->lcs_w->createCommand($insert_plan_history_sql . $insert_plan_history_sql_more)->execute();###日收益历史表

                Yii::app()->lcs_w->createCommand($plan_stats_sql_more)->execute();###日收益表

                $transaction->commit();

                Plan::model()->addAttenMessage($plan_message_ids);

            } catch (Exception $e) {
                $transaction->rollBack();
                Cron::model()->saveCronLog(self::CRON_NO, 'error', json_encode($e->errorInfo));
            }
        }

    }

    //运行中的计划最大回撤
    public function maxBack()
    {
        $sql = "select pln_id from lcs_plan_info where status=3";
        $all_pln = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        if (!empty($all_pln)) {
            foreach ($all_pln as $pln_id) {
                $sql = "select total_profit from  lcs_plan_profit_stats_history where pln_id='$pln_id' order by profit_date asc";
                $all_profit = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
                if (!empty($all_profit)) {
                    array_unshift($all_profit, 0);
                    $i = 0;
                    $size = sizeof($all_profit);
                    $max_back = 0;
                    foreach ($all_profit as $val) {
                        $i++;
                        if (!isset($start)) {//设置计算的起点
                            $start = $val;
                            continue;
                        }

                        if ($val >= $start && !isset($min)) {//重置起点
                            $start = $val;
                            continue;
                        }

                        if ($val <= $start) {//一直回撤
                            $min = !isset($min) ? $val : ($min > $val ? $val : $min);//找到回撤这段时间的最低点
                            if ($i != $size) {
                                continue;
                            }
                        }

                        //需要结算
                        if (isset($min)) {
                            $temp = ($start - $min)/(1+$start);
                            $temp = round($temp,4);
                            $max_back = $temp > $max_back ? $temp : $max_back;
                            unset($min);
                        }
                        $start = $val;

                    }

                    $sql = "update lcs_plan_assess set max_back='$max_back' where pln_id='$pln_id'";
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                    unset($start);

                }

            }
        }
    }
}