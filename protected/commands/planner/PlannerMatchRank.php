<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PlannerMatchRank
 *
 * @author hailin3
 */
class PlannerMatchRank {
    const CRON_NO = 8401; //任务代码

    public function __construct() {
        
    }
    private $start_date = '2017-01-05';//大赛开始时间
    private $start_date_dict = array(
        '10004'=>'2017-01-05',
        '10005'=>'2017-02-24',
        '10006'=>'2017-04-26',
    );
    private $end_date = array(
        10001 => '2016-12-20',//大赛结束时间
        10002 => '2016-12-30',
        10004 => '2017-03-31',
        10005 => '2017-04-21',
        10006 => '2017-04-21',
    );
    private $top_100 = null;
    private $max = 100;
    private $profit_history = null;
    private $weeks_date = array();
    
    public function handle($matchid){
        $pln_ids = PlannerMatch::model()->getMatchPlan($matchid);        
        if(empty($pln_ids)){
            return;
        }

        $plan_list = Plan::model()->getPlanInfoByIds($pln_ids);
        $plan_assess = PlanAssess::model()->getAssessInfos($pln_ids);    
        $day = date('d',strtotime($this->start_date_dict[$matchid]));
        $month_day = date("Y-m-$day");
        $last_month_day = '';
        if(time() > strtotime($month_day) && $month_day != $this->start_date_dict[$matchid]){
            $last_month_day = $month_day;           
        }elseif(strtotime("$month_day -1 month") > strtotime($this->start_date_dict[$matchid])){
            $last_month_day = date('Y-m-d',strtotime("$month_day -1 month"));           
        }
        if($last_month_day){
            $trade_month_day = Calendar::model()->getLastMarketDate($last_month_day);            
            $this->profit_history = Plan::model()->getPlanProfitHistory($pln_ids,$trade_month_day);
        }
                      
        $data = $this->matctData($pln_ids,$plan_list,$plan_assess, $matchid);

        //按总收益排序
        $this->sortByRor($matchid, $data);
        //按月收益
        $this->sortByMonthRor($matchid, $data);
        //最稳健投顾
        $this->sortByAvgWeight($matchid, $data);
        //高胜率
        $this->sortByWinning($matchid, $data);
        //最牛选股
        $this->getMaxProfitTrans($pln_ids, $plan_list, $plan_assess,$matchid);        
        //营业部
        $this->departmentRank($data, $matchid);
        //按周收益
        $this->sortByWeekRor($matchid, $data);
        
    }
    /**
     * 'department'=>'中关村营业部',//营业部名称
            'company'=>'信达证券',//公司名称
            'avg_profit'=>'10%',//平均收益
            'avg_month_profit'=>'10%',//平均月收益
            'nums'=>'15',//参赛人数
            'top_planner'=>array(
                '0'=>array(
                's_uid'=>'3046552733',
                'name'=>'张睿恒'
                )
            ),//领军投顾
            'top100_nums'=>'2',//前100人数
     * @param type $data
     */
    public function departmentRank($data,$matchid){
        $p_uids = array();
        $dict = array();
        
        foreach ($data as $item){
            $p_uids[] = $item['p_uid'];
            $dict[$item['p_uid']] = $item;
        }        
        $planner_list = Planner::model()->getPlannerById($p_uids);
        $department_group = array();
        foreach ($planner_list as $item){
            if(empty($item['department'])){
                continue;
            }
            $department_group[$item['department']][] = $dict[$item['p_uid']];
            
        }
        $department_data = array();        
        foreach ($department_group as $key=>$departmen){            
            $i = array();
            $i['department'] = $key;
            $i['nums'] = count($departmen);
            $sum_ror = 0;
            $sum_month_ror = 0;
            $d_puids = array();
            foreach ($departmen as $planner){
                $sum_ror += $planner['curr_ror'];
                $sum_month_ror += $planner['month_ror'];
                $d_puids[] = $planner['p_uid'];
            }
            $i['avg_profit'] = round($sum_ror / $i['nums'],4);
            $i['avg_month_profit'] = round($sum_month_ror / $i['nums'],4);
            $sort_department = $this->multi_sort($departmen, 'curr_ror');            
            $i['top_planner'] = $sort_department[0]['p_uid'];
            $i['pln_id'] = $dict[$sort_department[0]['p_uid']]['pln_id'];
            if (empty($this->top_100)) {
                $top = null;
            } else {
                $top = array_intersect($d_puids,  $this->top_100);
            }

            $i['top100_nums'] = count($top);
            $department_data[] = $i;
        }        
        //按总平均收益排序
        $this->sortDepartmentProfit($department_data, $matchid);
        //按月平均收益排序
        $this->sortDepartmentProfitMonth($department_data, $matchid);
        //按top100人数排序
        $this->sortDepartmentTop($department_data, $matchid);
        
    }
    
    public function sortDepartmentProfit($data,$matchid){
        $list = $this->multi_sort($data,'avg_profit');
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_6_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_6';
        Yii::app()->redis_w->delete($tmp_key);        
        foreach ($list as $item){        
            if($item['nums'] < 2){
                continue;
            }
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));            
        }
        Yii::app()->redis_w->rename($tmp_key,$redis_key);       
    }
    
    public function sortDepartmentProfitMonth($data,$matchid){
        $list = $this->multi_sort($data,'avg_month_profit');
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_7_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_7';
        Yii::app()->redis_w->delete($tmp_key);        
        foreach ($list as $item){        
            if($item['nums'] < 2){
                continue;
            }
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));            
        }
        Yii::app()->redis_w->rename($tmp_key,$redis_key);        
    }
    
    public function sortDepartmentTop($data,$matchid){        
        $list = $this->multi_sort_mix($data, 'top100_nums', SORT_DESC, 'avg_profit', SORT_DESC);
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_8_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_8';
        Yii::app()->redis_w->delete($tmp_key);        
        foreach ($list as $item){   
            if($item['nums'] < 2){
                continue;
            }
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));            
        }
        Yii::app()->redis_w->rename($tmp_key,$redis_key);
    }

    //1、投顾总收益 2、投顾月收益  3、投顾稳健派 4 投顾高胜率 5、投顾最牛选股 6、营业部平均收益 7、营业部平均月收益 8、营业部最佳团队 -1、周收益
    /*
     *  
        
        
     */
    public function matctData($pln_ids,&$plan_list,&$plan_assess, $match_id){
        
        //大赛交易天数
        $trade_days = Calendar::model()->getTotalTradeDay($this->start_date_dict[$match_id],date('Y-m-d',time()));
        $end_date = $this->end_date[$match_id];
        $data = array();        
        foreach ($pln_ids as $pln_id){
            $plan_info = isset($plan_list[$pln_id]) ? $plan_list[$pln_id] : '';
            $plan_assess_info = isset($plan_assess[$pln_id]) ? $plan_assess[$pln_id] : '';
            if(empty($plan_info)){
                continue;
            }
            $item = array();
            $item['pln_id'] = $pln_id;
            $item['p_uid'] = $plan_info['p_uid'];
            $item['curr_ror'] = $plan_info['curr_ror'];
            $item['weight'] = $plan_info['weight'];
            $item['hs300'] = $plan_info['hs300'];
            $item['trade_days'] = $trade_days;
            $item['month_ror'] = $this->getPlanMonthRor($plan_info);
            $item['weeks_ror'] = $this->getPlanWeeksRor($plan_info['pln_id'], $end_date);
            $item['week_date'] = '';
            $item['week_ror'] = 0;
            $item['max_back'] = 0;
            $item['avg_weight'] = 0;
            $item['sell_num'] = 0;
            $item['trans_winning'] = 0;
            if(!empty($plan_assess_info)){
                //最大回撤
                $item['max_back'] = $plan_assess_info['max_back'];
                //日均仓位
                $item['avg_weight'] = $plan_assess_info['hold_days'] > 0 ? sprintf("%.4f",$plan_assess_info['hold_total_weight'] / $plan_assess_info['hold_days']) : 0;
                //平仓笔数
                $item['sell_num'] = $plan_assess_info['profit_num'] + $plan_assess_info['loss_num'];
                //交易胜率
                $item['trans_winning'] = $plan_assess_info['profit_num'] + $plan_assess_info['loss_num'] > 0 ? sprintf("%.4f", $plan_assess_info['profit_num'] / ($plan_assess_info['profit_num'] + $plan_assess_info['loss_num'])) : 0;
            }
            $data[] = $item;            
        }
        return $data;
    }
    /**
     * 
     * @param type $arrays
     * @param type $sort_key
     * @param type $sort_order
     * @param type $sort_type
     */
    public function multi_sort($arrays,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC){
        if(!is_array($arrays)){
            return FALSE;
        }
        foreach ($arrays as $array){
            if(!is_array($array)){
                return FALSE;
            }
            $key_arrays[] = floatval($array[$sort_key]);
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }
    
    /**
     * 
     * @param type $arrays
     * @param type $sort_key
     * @param type $sort_order
     * @param type $sort_type
     */
    public function multi_sort_mix($arrays,$sort_key,$sort_order=SORT_DESC,$sort_key2,$sort_order2=SORT_DESC){
        if(!is_array($arrays)){
            return FALSE;
        }
        foreach ($arrays as $array){
            if(!is_array($array)){
                return FALSE;
            }
            $key_arrays[] = floatval($array[$sort_key]);
            $key_arrays2[] = floatval($array[$sort_key2]);
        }
        array_multisort($key_arrays,$sort_order,$key_arrays2,$sort_order2,$arrays);
        return $arrays;
    }

    /**
     * 按总收益排序
     * @param type $data
     */
    public function sortByRor($matchid,$data){
        $list = $this->multi_sort($data,'curr_ror');
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_1_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_1';
        $tmp_rank_key = MEM_PRE_KEY.'match_plan_rank_tmp_'.$matchid;
        $rank_key = MEM_PRE_KEY.'match_plan_rank_'.$matchid;
        Yii::app()->redis_w->delete($tmp_key);
        $rank_list = array();
        $i = 1;
        $r = 1;

        foreach ($list as $item){              
            $rank_list[$item['pln_id']] = $r;
            $r++;
            if($i <= 100 && $item['curr_ror'] > 0){
                $this->top_100[] = $item['p_uid'];//记录top100的理财师
            }                     
            if($i > $this->max){
                continue;
            }
            $i++;
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));            
        }
        Yii::app()->redis_w->hmset($tmp_rank_key,$rank_list);
        Yii::app()->redis_w->rename($tmp_rank_key,$rank_key);        
        Yii::app()->redis_w->rename($tmp_key,$redis_key);        
    }
    /**
     * 按月收益排序
     * @param type $matchid
     * @param type $data
     */
    public function sortByMonthRor($matchid,$data){
        $list = $this->multi_sort($data,'month_ror');
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_2_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_2';
        Yii::app()->redis_w->delete($tmp_key);
        $i = 1;
        foreach ($list as $item){            
            if($i > $this->max){
                break;
            }
            $i++;
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));
            
        }
        Yii::app()->redis_w->rename($tmp_key,$redis_key);        
    }


    /**
     * 按最大回撤正序
     * @param type $matchid
     * @param type $data
     */
    public function sortByAvgWeight($matchid,$data){                
        $list = $this->multi_sort_mix($data, 'max_back', SORT_ASC, 'curr_ror', SORT_DESC);
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_3_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_3';
        $tmp_rank_key = MEM_PRE_KEY.'match_plan_rank_max_back_tmp_'.$matchid;
        $rank_key = MEM_PRE_KEY.'match_plan_rank_max_back_'.$matchid;
        Yii::app()->redis_w->delete($tmp_key);
        $i = 1;
        $r = 1;
        foreach ($list as $item){
            $max_back_rank[$item['pln_id']] = $r;
            $r++;
            if($item['curr_ror'] <= 0 || $item['curr_ror'] < $item['hs300']){ //日均仓位使用最少，总收益为正
                continue;
            }
            if($i > $this->max){
                continue;
            }
            $i++;
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));
        }
        if($i == 1){
            Yii::app()->redis_w->delete($redis_key);
        }
        Yii::app()->redis_w->hmset($tmp_rank_key,$max_back_rank);
        Yii::app()->redis_w->rename($tmp_rank_key,$rank_key);
        Yii::app()->redis_w->rename($tmp_key,$redis_key);        
    }
    /**
     * 按交易胜率排序
     * @param type $matchid
     * @param type $data
     */
    public function sortByWinning($matchid,$data){        
        $list = $this->multi_sort_mix($data, 'trans_winning', SORT_DESC, 'curr_ror', SORT_DESC);        
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_4_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_4';
        $tmp_rank_key = MEM_PRE_KEY.'match_plan_rank_winning_tmp_'.$matchid;
        $rank_key = MEM_PRE_KEY.'match_plan_rank_winning_'.$matchid;
        Yii::app()->redis_w->delete($tmp_key);
        $i = 1;
        $r = 1;
        foreach ($list as $item){
            $winning_rank[$item['pln_id']] = $r;
            $r++;
            if($item['sell_num'] <= 3 || $item['curr_ror'] <= 0){ //平仓笔数不少于3次，总收益为正
                continue;
            }
            if($i > $this->max){
                continue;
            }
            $i++;
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));
        }
        if($i == 1){
            Yii::app()->redis_w->delete($redis_key);
        }
        Yii::app()->redis_w->hmset($tmp_rank_key,$winning_rank);
        Yii::app()->redis_w->rename($tmp_rank_key,$rank_key);
        Yii::app()->redis_w->rename($tmp_key,$redis_key);        
    }

    /**
     * 获取计划的月收益
     * @param type $pln_id
     */
    public function getPlanMonthRor($plan_info){
        if(!isset($this->profit_history[$plan_info['pln_id']])){
            return $plan_info['curr_ror'];    
        }
        $history_ror = $this->profit_history[$plan_info['pln_id']]['total_profit'];
        return floatval($plan_info['curr_ror']) - floatval($history_ror);        
    }

    /**
     * 获取周收益信息
     * @param $pln_id
     * @param $end_date
     * @return array
     */
    public function getPlanWeeksRor($pln_id, $end_date) {
        $start_time = strtotime($this->start_date);
        $end_time = strtotime($end_date);
        $next_week_gap = 86400 * 7;
        $weeks_ror = array();
        //获取每周的周收益
        for ($monday_time = strtotime("next Monday", $start_time); $monday_time < $end_time; $monday_time += $next_week_gap) {
            $friday_time = $monday_time + 86400 * 4;
            if ($friday_time > $end_time) {
                break;
            }

            $monday_date = date('Y-m-d', $monday_time);
            $friday_date = date('Y-m-d', $friday_time);
            $week_profit = PlanService::getWeekProfit($pln_id, $monday_date, $friday_date);
            $this->weeks_date[] = $monday_date;
            $weeks_ror[$monday_date] = $week_profit;
        }

        return $weeks_ror;
    }

    /**
     * $symbol_data = array(
            'pln_id'=>'100836',//计划id
            'single_ratio'=>'3%',//个股贡献
            'profit_ratio'=>'10%',//个股收益
            'symbol'=>'sh600001',//股票代码
            'symbol_name'=>'中航资本',//股票名称          
            'curr_ror'=>'10%',//当前收益
            'hold_total_weight'=>'200%',//累计使用仓位
            'planner_info'=>array(
                's_uid'=>'3046552733',
                'name'=>'张睿恒'
            )
        );
     * @param type $pln_ids
     * @return type
     */
    public function getMaxProfitTrans($pln_ids,&$plan_list,&$plan_assess,$matchid){
        $sql = "select pln_id from lcs_plan_info where curr_ror>0 and pln_id in (".  implode(',', $pln_ids).")";
        $pln_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_5_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_5';
        $tmp_rank_key = MEM_PRE_KEY.'match_plan_rank_trans_tmp_'.$matchid;
        $rank_key = MEM_PRE_KEY.'match_plan_rank_trans_'.$matchid;
        if(empty($pln_ids)){            
            Yii::app()->redis_w->delete($redis_key);
            return;
        }        
        $trans_list = PlanAsset::model()->getAssetProfit($pln_ids);
        if(empty($trans_list)){
            return;
        }
        $data = array();
        $symbols = array();
        //pln_id,symbol,profit,profit_weight
        foreach ($trans_list as $trans){            
            $item = array();
            $pln_id = $trans['pln_id'];
            $item['id'] = $trans['id'];
            $item['pln_id'] = $pln_id;
            $item['p_uid'] = $plan_list[$pln_id]['p_uid'];
            $item['curr_ror'] = $plan_list[$pln_id]['curr_ror'];
            $item['hold_total_weight'] = sprintf("%.4f",$trans['profit_weight']/$trans['profit']);
            $item['single_ratio'] = sprintf("%.4f",$trans['profit']);
            $item['profit_ratio'] = sprintf("%.4f",$trans['profit_weight']);
            $item['symbol'] = $trans['symbol'];  
            $data[] = $item;
            $symbols[] = $trans['symbol'];
        }                
        $symbols = array_unique($symbols);
        $stocks = Symbol::model()->getTagsBySymbol('stock_cn', $symbols);
        foreach ($data as &$item){
            $item['symbol_name'] = isset($stocks[$item['symbol']]['name']) ? $stocks[$item['symbol']]['name'] : '';
        }
        
        $list = $this->multi_sort($data,'profit_ratio');                
        Yii::app()->redis_w->delete($tmp_key);
        $i = 1;
        $r = 1;
        foreach ($list as $item){    
            $trans_rank[$item['id']] = $r;
            $r++;
            if($i > $this->max){
                continue;
            }
            $i++;
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));
        }
        Yii::app()->redis_w->hmset($tmp_rank_key,$trans_rank);
        Yii::app()->redis_w->rename($tmp_rank_key,$rank_key);
        Yii::app()->redis_w->rename($tmp_key,$redis_key);              
    }


    /**
     * -1、周收益
     * @param $matchid
     * @param $data
     */
    public function sortByWeekRor($matchid,$data){
        $list = $this->getWeekProfitRank($data);

        $tmp_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_-1_tmp';
        $redis_key = MEM_PRE_KEY.'match_rank_'.$matchid.'_-1';
        Yii::app()->redis_w->delete($tmp_key);
        $i = 1;
        foreach ($list as $item){
            if($i > $this->max){
                break;
            }
            $i++;
            Yii::app()->redis_w->rPush($tmp_key,  json_encode($item));

        }
        Yii::app()->redis_w->rename($tmp_key,$redis_key);
    }


    /**
     * 获取单周内的最大周收益计划
     * @param array $data 计划数据（包含weeks_ror）
     */

    /**
     * 获取单周内的最大周收益计划
     * @param array $data matchData处理后的计划数据
     * @param string $cur_week_monday 需要排序的周次
     * @return array|bool|type
     */
    public function getMaxWeekProfitPlan($data, $cur_week_monday) {
        $week_data = array();
        if (!empty($data)) {
            foreach ($data as $item) {
                //取单周内的所有计划，并附上周收益
                $item['week_ror']  = $item['weeks_ror'][$cur_week_monday];
                $item['week_date'] = $cur_week_monday;
                $week_data[] = $item;
            }
        }
        //对单周的计划进行排序
        $week_data = $this->multi_sort($week_data, 'week_ror');

        return $week_data;
    }

    /**
     * 获取最终的周收益排名(每周取最高收益，再按最高收益排序)
     * @param $data
     * @return array
     */
    public function getWeekProfitRank($data) {
        if (empty($data)) {
            return;
        }
        //获取需要计算的周
        $this->weeks_date = array_keys($data[0]['weeks_ror']);

        $week_profit_rank = array();
        if (!empty($this->weeks_date)) {
            foreach ($this->weeks_date as $week_monday) {
                $week_data = $this->getMaxWeekProfitPlan($data, $week_monday);
                //取这一周 周收益排名第一的计划
                $week_data = $week_data[0];

                if (!empty($week_data)) {
                    $week_profit_rank[] = $week_data;
                }
            }
        }
        $week_profit_rank = $this->multi_sort($week_profit_rank, 'week_ror');

        return $week_profit_rank;
    }

}
