<?php
/**
 * 理财师的计划评级
 * 1. 每天晚上 11点获取当天结束计划
 * 2. 根据当天结束的计划 更新对应理财师的计划评级
 * User: zwg
 * Date: 2016/3/25
 * Time: 11:08
 */

class GradePlan {
    const CRON_NO=1012;
    public $stat_time;
    public static $param = array(
        '4'=>array('pln_opt_days'=>360,'success_num'=>8,'pln_year_rate'=>0.8,'pln_success_rate'=>0.5,'pln_is_win'=>1),
        '3'=>array('pln_opt_days'=>300,'success_num'=>5,'pln_year_rate'=>0.5,'pln_success_rate'=>0.3,'pln_is_win'=>1),
        '2'=>array('pln_opt_days'=>210,'success_num'=>3,'pln_year_rate'=>0.3,'pln_success_rate'=>0.3),
        '1'=>array('pln_opt_days'=>150,'success_num'=>2,'pln_year_rate'=>0.2)        
    );
    public function __construct($stat_date = ''){
        if($stat_date == ''){
            $this->stat_time = date('Y-m-d') . " 15:00:00";
        }else{
            $this->stat_time = $stat_date . " 15:00:00";
        }
    }
    
    /**
     * 统计理财师计划:交易周期、是否跑赢大盘
     */
    public function statData(){
        try {
            //所有完全计划理财id
            $sql = "select distinct(p_uid) as p_uid from lcs_plan_info where status in (4,5) and pln_id>28340 and c_time < '{$this->stat_time}'";
            $p_uids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
            $count = 0;
            $grade = array();
            $update_sql = '';
            foreach ($p_uids as $p_uid){ 
                //获取每个理财师的指标数据        
                $sql = "select pln_num, pln_loss_num, pln_year_rate, pln_success_rate, lcs_planner.status from lcs_planner_ext left join lcs_planner on lcs_planner_ext.s_uid = lcs_planner.s_uid where lcs_planner_ext.s_uid = {$p_uid} ";
                $row = Yii::app()->lcs_r->createCommand($sql)->queryRow();
                if(!$row){
                    continue 1;
                }
                //交易周期
                $days = $this->plnOptDays($p_uid,$this->stat_time);
                if(empty($days)){
                    $row['pln_opt_days'] = 0;
                    $row['pln_is_win'] = 0;
                }else{
                    $row['pln_opt_days'] = $days['pln_opt_days'];
                    //是否跑赢大盘
                    $row['pln_is_win'] = $this->plnIsWin($days,$p_uid);
                }
                //成功数
                $sql = "select count(*) from lcs_plan_info where c_time < '{$this->stat_time}' and p_uid = {$p_uid} and pln_id>28340 and status = 4";
                $success_num = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
                $success_num = $success_num ? $success_num : 0 ;
                
                //状态是否是冻结 1冻结 、0正常   两个月未发计划或者账号冻结评级为冻结
                $start_time = date('Y-m-d H:i:s',(strtotime($this->stat_time)-60*60*24*60));
                $sql = "select count(*) from lcs_plan_info where c_time < '{$start_time}' and p_uid = {$p_uid} ";
                $plan_num_latest = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
                $plan_num_latest = $plan_num_latest ? $plan_num_latest : 0;
                
                if( ($plan_num_latest == 0) && ($row['status'] == -2) ){
                    $row['plan_status'] = 1;  //冻结
                }else{
                    $row['plan_status'] = 0;  //正常
                }
                
                //评级
                if($row['pln_opt_days'] > self::$param['4']['pln_opt_days'] && $success_num >=self::$param['4']['success_num'] && $row['pln_year_rate']>=self::$param['4']['pln_year_rate'] && $row['pln_success_rate']>=self::$param['4']['pln_success_rate'] && ($row['pln_is_win'] == self::$param['4']['pln_is_win']) ){
                    $row['grade_auto'] = 4;
                }elseif($row['pln_opt_days'] > self::$param['3']['pln_opt_days'] && $success_num >=self::$param['3']['success_num'] && $row['pln_year_rate']>=self::$param['3']['pln_year_rate'] && $row['pln_success_rate']>=self::$param['3']['pln_success_rate'] && ($row['pln_is_win'] == self::$param['3']['pln_is_win']) ){
                    $row['grade_auto'] = 3;
                }elseif($row['pln_opt_days'] > self::$param['2']['pln_opt_days'] && $success_num >=self::$param['2']['success_num'] && $row['pln_year_rate']>=self::$param['2']['pln_year_rate'] && $row['pln_success_rate']>=self::$param['2']['pln_success_rate']){
                    $row['grade_auto'] = 2;
                } elseif($row['pln_opt_days'] > self::$param['1']['pln_opt_days'] && $success_num >=self::$param['1']['success_num'] && $row['pln_year_rate']>=self::$param['1']['pln_year_rate']){
                    $row['grade_auto'] = 1;
                }else{
                    $row['grade_auto'] = 0;
                }
                
                //更新条件 1、自动评级 2、等级有变化
                $update_sql .= "update lcs_planner_ext set pln_opt_days = {$row['pln_opt_days']}, pln_is_win = {$row['pln_is_win']} , grade_plan = {$row['grade_auto']}, grade_plan_time = now(), grade_plan_status = {$row['plan_status']} where grade_plan_auto = 1 and grade_plan != {$row['grade_auto']}  and s_uid = {$p_uid};";
                
                $count++;
                if($count == 100){
                    //更新planner_ext
                    Yii::app()->lcs_w->createCommand($update_sql)->execute();
                    $count = 0;
                    $update_sql = '';
                }
              
            }
            if($count > 0){
                //更新planner_ext
                Yii::app()->lcs_w->createCommand($update_sql)->execute();
            }
            $this->syncGradeHistory();
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
    }
    
    /**
     * 交易周期
     * @param integer $p_uid    理财师ID
     * @param string $stat_time 计算日期
     */
    public function plnOptDays($p_uid,$stat_time){
        $days = array();
        $s_date = array();
        $e_date = array();
        try {
            $sql = "select pln_id, p_uid, start_date, end_date, real_end_time, status, freeze_time from lcs_plan_info where status in (4,5) and p_uid = {$p_uid} and pln_id>28340 and c_time < '{$stat_time}'";
            $plns = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if (empty($plns)){
                return $days;
            }
            
            $sql = "select cal_date from lcs_calendar";
            $calendars = Yii::app()->lcs_r->createCommand($sql)->queryRow();
            
            foreach ($plns as $pln){
                if($pln['start_date'] != '0000-00-00'){
                    $s_date[] = $pln['start_date'];
                }
                $e_date[] = date('Y-m-d',strtotime($pln['real_end_time']));
            }
            sort($s_date);
            rsort($e_date);
            if(empty($e_date) || empty($s_date)){
                return $days;
            }
            if(!in_array($s_date[0],$calendars)){
                $sql = "select cal_date from lcs_calendar where cal_date < '{$s_date[0]}' ORDER BY cal_date desc limit 1";
                $s_date[0] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
            }
            if(!in_array($e_date[0],$calendars)){
            
                $sql ="select cal_date from lcs_calendar where cal_date < '{$e_date[0]}' ORDER BY cal_date desc limit 1";
                $e_date[0] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
            }
            $timestamp = strtotime($e_date[0]) - strtotime($s_date[0]);
            $pln_opt_days = floor($timestamp/(60*60*24));
            return array(
                'start_date'=>$s_date[0],
                'end_date'=>$e_date[0],
                'pln_opt_days'=>$pln_opt_days
            );
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
    }
    
    /**
     * 是否跑赢大盘
     * @param array $op_days
     * @param integer $s_uid
     */
    public function plnIsWin($op_days,$s_uid){
        $start_k = 0;
        $end_k = 0;
        $pln_is_win = 0;
        $op_start_date = $op_days['start_date'];
        $op_end_date = $op_days['end_date'];
        $op_run_days = $op_days['pln_opt_days'];
        try {
            $sql = "select open,close from lcs_daily_k where symbol = 'sh000001' and day in ('{$op_start_date}','{$op_end_date}') order by day asc";
            $daily_k = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if(empty($daily_k)){
                return 0;
            }
            if(count($daily_k) != 2){
                return 0;
            }
            $start_k = $daily_k[0]['open'];
            $end_k = $daily_k[1]['close'];
            if($op_run_days == 0){return 0;}  //除数不能为0
            $rate = sprintf('%.3f',($end_k-$start_k)*365/($start_k*$op_run_days));
            $sql = "select pln_success_rate from lcs_planner_ext where s_uid = {$s_uid}";
            $pln_success_rate = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
            if($pln_success_rate >$rate ){
                return 1;
            }else{
                return 0;
            }
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
    }
    
    
    /**
     * 等级同步到等级历史表
     */
    public function syncGradeHistory(){
        try {
            
            $start_time = date('Y-m-d',strtotime($this->stat_time)). " 00:00:00";
            $end_time = date('Y-m-d',strtotime($this->stat_time)).' 23:59:59';
    
            //等级变动的理财师
            $sql = "select s_uid from lcs_planner_ext where grade_plan_time < '{$end_time}' and grade_plan_time > '{$start_time}' and grade_plan_auto = 1";
            $s_uids = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            //insert
            $fields  = 'type, p_uid, grade, reason, opt_type, c_time';
            $sql = "insert into lcs_grade_history (".$fields.") values ";
            $count = 0;
            foreach ($s_uids as $s_uid_info){
    
                $sql_info = "select s_uid, grade_plan, grade_plan_auto, grade_plan_status, grade_plan_time from lcs_planner_ext where grade_plan_time < '{$end_time}' and grade_plan_time > '{$start_time}'  and s_uid = {$s_uid_info['s_uid']}";
                $row = Yii::app()->lcs_r->createCommand($sql_info)->queryRow();
                 
                //判断是自动评级还是运营评级
                if($row['grade_plan_auto'] == 1){
                    $opt_type = 1; //自动评级
                    $sql .= "( 1, {$row['s_uid']}, {$row['grade_plan']}, '', {$opt_type}, '{$row['grade_plan_time']}' ),";
                }
                if($row['grade_plan_auto'] == 0){
                    $opt_type = 2; //运营评级
                    $sql .= "( 1, {$row['s_uid']}, {$row['grade_plan']}, '', {$opt_type}, '{$row['grade_plan_time']}' ),";
                }
                $count++;
                if($count == 100) {
                    $sql = rtrim($sql,',');
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                    $count = 0;
                    $sql = "insert into lcs_grade_history (".$fields.") values ";
                }
            }
            if($count>0){
                $sql = rtrim($sql,',');
                Yii::app()->lcs_w->createCommand($sql)->execute();
            }
    
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
         
    }
    
    
    
   
}