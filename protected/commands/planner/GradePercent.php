<?php
/**
 * 统计理财师星级百分比,每天11：30晚上执行一次
 * @author yougang1
 * @date 2016-05-17
 * @wiki http://wiki.intra.sina.com.cn/pages/viewpage.action?pageId=100401920
 */
class GradePercent {
    const CRON_NO=1016;
    
    public function __construct(){}
    
    /**
     * 计划星级百分比
     */
    public function statPlanGradePercent(){
        try {
            $db_r = Yii::app()->lcs_r;
            $redis_w = Yii::app()->redis_w;
            //发布过计划得理财师总数
            $sql = "select count(distinct(p_uid)) from lcs_plan_info where status > 3";
            $p_uids = $db_r->createCommand($sql)->queryScalar();
            //根据计划评级和影响力排序
            $sql = "select s_uid,grade_plan,grade_plan_auto from lcs_planner_ext where grade_plan > 0 order by grade_plan desc, influence desc";
            $sort_p_uids = $db_r->createCommand($sql)->queryAll();
            $count = 0;
            $percent_arr = array();
            //计算每个理财师的百分比
            foreach($sort_p_uids as $index=>$p_info){
                $sort = $index + 1;
                $percent = round(($p_uids - $sort)/$p_uids,4);
                if($p_info['grade_plan_auto'] == 1 && $p_info['grade_plan'] >3){
                    $p_info['grade_plan'] = 3;
                }
                //$percent_arr[$p_info['s_uid']] = $p_info['grade_plan'].'星计划评级,跑赢了'.($percent*100).'%的理财师';
                $percent_arr[$p_info['s_uid']] = '跑赢'.($percent*100).'%的理财师';
                $new_key = $p_info['s_uid'].'_percent';
                $percent_arr[$new_key] = (string)$percent*100; //添加一个新key
                $count++;
                if($count == 100){
                    $redis_w->hmset(MEM_PRE_KEY.'plan_grade_percent',$percent_arr);
                    $count = 0;
                    $percent_arr = array(); 
                }
            }
            if($count > 0){
                $redis_w->hmset(MEM_PRE_KEY.'plan_grade_percent',$percent_arr);
            }
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
       
        
    }
    
    /**
     * 观点包星级百分比
     */
    public function statPkgGradePercent(){
        try {
            $db_r = Yii::app()->lcs_r;
            $redis_w = Yii::app()->redis_w;
            //发布过观点包的理财师
            $sql = "select count(distinct(p_uid)) from lcs_package where status = 0";
            $p_uids = $db_r->createCommand($sql)->queryScalar();
            //根据观点包评级和影响力排序
            $sql = "select s_uid, grade_pkg, grade_pkg_auto from lcs_planner_ext where grade_pkg > 0 order by grade_pkg desc, influence desc";
            $sort_p_uids = $db_r->createCommand($sql)->queryAll();
            $count = 0;
            $percent_arr = array();
            //计算每个理财师的百分比
            foreach ($sort_p_uids as $index=>$p_info){
                $sort = $index + 1;
                $percent = round(($p_uids - $sort)/$p_uids,4);
                
                if( ($p_info['grade_pkg_auto'] == 1) && ($p_info['grade_pkg'] >3 ) ){
                    $p_info['grade_pkg'] = 3;
                }
                //$percent_arr[$p_info['s_uid']] = $p_info['grade_pkg'].'星观点评级, 跑赢了'.($percent*100).'%的理财师';
                $percent_arr[$p_info['s_uid']] = '跑赢了'.($percent*100).'%的理财师';
                $new_key = $p_info['s_uid'].'_percent';
                $percent_arr[$new_key] = (string)$percent*100;
                $count++;
                if($count == 100){
                    $redis_w->hmset(MEM_PRE_KEY.'pkg_grade_percent',$percent_arr);
                    $count = 0;
                    $percent_arr = array();
                }
            }
            if($count >0){
                $redis_w->hmset(MEM_PRE_KEY.'pkg_grade_percent',$percent_arr);
            }
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
      
        
    }
    
}