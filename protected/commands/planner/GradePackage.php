<?php
/**
 * 理财师的观点包评级
 * 1. 每个月 1，16日凌晨 30分 统计所有付费观点包的评级指标
 * 2. 根据付费观点包  修改对应的理财师 (一个理财师可能有多个观点包，默认保留评级最高的观点包数据)
 * User: zwg
 * Date: 2016/3/25
 * Time: 11:08
 */

class GradePackage{
    
    const CRON_NO=1013;
    const PREV_DAYS = 15; //前15天
    public $stat_time; 
    public static $param = array(
        '5'=>array('view_num'=>2000,'collect_num'=>10000,'avg_sub_num'=>1000,'total_income'=>100000),
        '4'=>array('view_num'=>1000,'collect_num'=>6000,'avg_sub_num'=>500,'total_income'=>100000),
        '3'=>array('view_num'=>500,'collect_num'=>4000,'avg_sub_num'=>300,'total_income'=>50000),
        '2'=>array('view_num'=>200,'collect_num'=>2000,'avg_sub_num'=>100,'total_income'=>20000),
        '1'=>array('view_num'=>100,'collect_num'=>1000,'avg_sub_num'=>50,'total_income'=>10000),
    );
    
    public function __construct($stat_date=''){
        if(empty($stat_date)){
            $this->stat_time = date('Y-m-d')." 22:00:00";
        }else{
            $this->stat_time = $stat_date." 22:00:00";
        }
    }
    public function statData(){
        
        try {
            //所有收费理财包
            $sql = "select id, p_uid, view_num, collect_num, evaluate, grade, grade_auto from lcs_package where subscription_price> 0 and status = 0 and charge_time < '{$this->stat_time}'";
            
            $package_data = Yii::app()->lcs_r->createCommand($sql)->queryAll();            
            $grade_info = array();
            $count = 0;
            $sql = '';
            //遍历观点包 计算15日评级享受服务人数、累计收入
            foreach($package_data as $package){
                
                $pkg_id = $package['id'];                              
                //计算15日平均享受服务人数
                $avg_sub_num = $this->statAvgSubNum($package['id'], $this->stat_time);
                //累计收入
                $total_income = $this->statTotalIncome($package['id'],$this->stat_time);
                
                if($package['view_num']>=self::$param['5']['view_num'] && $avg_sub_num>=self::$param['5']['avg_sub_num'] && $package['collect_num']>=self::$param['5']['collect_num'] && $total_income>self::$param['5']['collect_num']){
                    $grade_info['grade_auto'] = 5;
                }elseif( $package['view_num']>=self::$param['4']['view_num'] && $avg_sub_num>=self::$param['4']['avg_sub_num'] && $package['collect_num']>=self::$param['4']['collect_num'] && self::$param['4']['collect_num']  ){
                    $grade_info['grade_auto'] = 4;
                }else if($package['view_num']>=self::$param['3']['view_num'] && $avg_sub_num>=self::$param['3']['avg_sub_num'] && $package['collect_num']>=self::$param['3']['collect_num'] && $total_income> self::$param['3']['collect_num']){
                    $grade_info['grade_auto'] = 3;
                }else if($package['view_num']>=self::$param['2']['view_num'] && $avg_sub_num>=self::$param['2']['avg_sub_num'] && $package['collect_num']>=self::$param['2']['collect_num'] && $total_income> self::$param['2']['collect_num'] ){
                    $grade_info['grade_auto'] = 2;
                }elseif($package['view_num']>=self::$param['1']['view_num'] && $avg_sub_num>=self::$param['1']['avg_sub_num'] && $package['collect_num']>=self::$param['1']['collect_num'] && $total_income> self::$param['1']['collect_num'] ){
                    $grade_info['grade_auto'] = 1;
                }else{
                    $grade_info['grade_auto'] = 0;
                }

                $grade_info['s_uid'] = $package['p_uid']; //微博id
                $grade_info['pkg_id'] = $package['id'];//观点包id
               
                //等级有变化 、并且不是运营评级
                $sql .="update lcs_package set avg_sub_num = {$avg_sub_num}, total_income = {$total_income}, grade_time = now(), grade_auto = {$grade_info['grade_auto']}, u_time = now() where id = {$grade_info['pkg_id']} and grade = 0 and grade_auto != {$grade_info['grade_auto']};";               
                $count++;
                if($count == 100){         
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                    $grade_info = array();
                    $sql = '';
                    $count = 0;
                }

            }
           
            if($count>0){
                Yii::app()->lcs_w->createCommand($sql)->execute();
            }    
            
            $this->syncGradePlannerExt();//同步理财师拓展表
            $this->syncGradeHistory();//同步历史表
            
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
       
    }
    
    
    /**
     * 计算1个观点包的 最近15日平均订阅人数
     */
    private function statAvgSubNum($pkg_id,$s_time){
        $total_sub_num = 0;
        $avg_sub_num = 0;
        $count = 0;
        $start_date = date('Y-m-d',strtotime($s_time)-60*60*24*($count+1));
        try {
    
            while($count < self::PREV_DAYS){
                $num = Package::model()->getSubNum($pkg_id,$start_date);
                $total_sub_num += $num ? $num : 0 ;  //记录
                $start_date = date('Y-m-d',strtotime($s_time)-60*60*24*($count++ +1));
            }
            $avg_sub_num = ceil($total_sub_num/self::PREV_DAYS);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
        return $avg_sub_num;
    }
     
    /**
     * 计算一个观点的累计收入
     */
    private function statTotalIncome($pkg_id,$s_time){
        try {
            $total_income = Orders::model()->getPkgTotalIncome($pkg_id,date('Y-m-d 00:00:00',strtotime($s_time)));
            return $total_income ? $total_income : 0 ;
        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
      
    /**
     * 等级同步到理财师拓展表
     */
    public function syncGradePlannerExt(){
        try {
            
            $sql = "select p_uid, count(*) as num from lcs_package group by p_uid having num >1";
            $multi_pkg_puid = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
            
            $start_grade_time = date('Y-m-d',strtotime($this->stat_time)). " 00:00:00";
            $end_grade_time = date('Y-m-d',strtotime($this->stat_time)).' 23:59:59';
            $start_p_time = date('Y-m-d',(strtotime($this->stat_time) - 60*60*24*31));
            //p_uid
            $sql = "select distinct(p_uid) from lcs_package where grade_time < '{$end_grade_time}' and grade_time >'{$start_grade_time}' and subscription_price> 0 and status = 0 and charge_time < '{$this->stat_time}' and grade = 0";
            $p_uids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
            $count = 0;
            $sql = '';
            foreach ($p_uids as $p_uid){
            
                //多个观点包取等级最高的
                $sql_pkg = "select id, p_uid, grade, grade_auto, grade_time from lcs_package where grade_time < '{$end_grade_time}' and grade_time >'{$start_grade_time}' and subscription_price> 0 and status = 0 and charge_time < '{$this->stat_time}' and p_uid = {$p_uid} and grade = 0";
                
                if(in_array($p_uid, $multi_pkg_puid)){
                    $pgk_infos = Yii::app()->lcs_r->createCommand($sql_pkg)->queryAll();//多个观点包数据
                    $pkg_info['grade_auto'] = 0;
                    $pkg_info['grade'] = 0;
                    foreach ($pgk_infos as $pkg){
                        if($pkg['grade_auto'] >= $pkg_info['grade_auto']){ 
                            $pkg_info['grade_auto'] = $pkg['grade_auto'];
                            $pkg_info['p_uid'] = $pkg['p_uid'];
                            $pkg_info['grade'] = $pkg['grade'];
                            $pkg_info['grade_time'] = $pkg['grade_time'];
                        }
                    }
                }else{
                    $pkg_info = Yii::app()->lcs_r->createCommand($sql_pkg)->queryRow();
                }
                //超过一个月未发观点星级变暗 计算近一个月发布观点数据 看是否大于 0
                $sql_view = "select count(*) as view_num_latest  from lcs_view where p_uid = {$pkg_info['p_uid']} and p_time > '{$start_p_time}'";
                $view_num_latest = Yii::app()->lcs_r->createCommand($sql_view)->queryScalar();
                $pkg_info['status'] = $view_num_latest ? 0 : 1;  //1是冻结状态 0是正常
                
                //更新sql
                $sql .="update lcs_planner_ext set grade_pkg = {$pkg_info['grade_auto']} , grade_pkg_status = {$pkg_info['status']}, grade_pkg_time='{$pkg_info['grade_time']}', u_time = now() where grade_pkg_auto = 1 and s_uid = {$pkg_info['p_uid']};";
                $count++;             
                if ($count == 100){
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                    $count = 0;
                    $sql = '';
                }
            }
            if($count>0){
                Yii::app()->lcs_w->createCommand($sql)->execute();
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
            $sql = "select s_uid from lcs_planner_ext where grade_pkg_time < '{$end_time}' and grade_pkg_time > '{$start_time}' and grade_pkg_auto = 1";
            $s_uids = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            //insert
            $fields  = 'type,p_uid,grade,reason,opt_type,c_time';
            $sql = "insert into lcs_grade_history (".$fields.") values ";
            $count = 0;
            foreach ($s_uids as $s_uid_info){
                
                $sql_info = "select s_uid, grade_pkg, grade_pkg_auto, grade_pkg_status, grade_pkg_time from lcs_planner_ext where grade_pkg_time < '{$end_time}' and grade_pkg_time > '{$start_time}'  and s_uid = {$s_uid_info['s_uid']}";
                $row = Yii::app()->lcs_r->createCommand($sql_info)->queryRow();
                 
                //判断是自动评级还是运营评级
                if($row['grade_pkg_auto'] == 1){
                    $opt_type = 1;
                }else{
                    $opt_type = 2;
                }
                $sql .= "( 2, {$row['s_uid']}, {$row['grade_pkg']}, '', {$opt_type}, '{$row['grade_pkg_time']}' ),";
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