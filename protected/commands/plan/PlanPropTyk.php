<?php
/**
 * 新浪大赛体验卡
 * 1.筛选可售卖计划  运行中 收益 >= 5% 距离目标收益 至少2点 距离结束时间 大于7天
 * 2.计划定价 （(当前收益/运行天数）/目标收益）*价格*体验卡天数*1.5，最低价格 38元，最高388元
 * 3.体验卡数量上限 300
 * @author yougang1
 * @date 2016-11-28  
 */
class PlanPropTyk{
    
    
    const CRON_NO = 5017; //任务编号
    private $experience_days = 7; //体验时间 有效期默认7天
    private $curr_ror = 0.05;  //收益
    private $sub_days = 7; //距离结束天数
    private $sub_ror = 0.02; //距离目标收益
     
     
    public function init(){
        Yii::import('application.commands.plan.*');
    }
    public function __construct(){
    
    }
    
    public function TykData(){
    
        try {
    
            $db_r = Yii::app()->lcs_r;
            $db_w = Yii::app()->lcs_w;
    
            // 1.获取已经存在的平台体验卡
            $sql = "select distinct(relation_id) from lcs_prop where use_channel = 6 and type = 2001 and status = 0";
            $exist_pln_ids = $db_r->createCommand($sql)->queryColumn();
    
            //2.获取所有符合条件的体验卡（在运行中的）
            $sql = "select pln_id from lcs_plan_info where status = 3 and (TO_DAYS(end_date)-TO_DAYS(STR_TO_DATE(now(),'%Y-%m-%d'))) >=$this->sub_days and (target_ror-curr_ror) >= $this->sub_ror";
            $total_pln_ids = $db_r->createCommand($sql)->queryColumn();
            
            //过滤更新频率
            //$total_pln_ids = $this->checkTransFrequency($total_pln_ids);
    
            //3.获取大赛的pln_ids （大赛体验卡 match_id >0）
            $sql = "select distinct(pln_id) from lcs_planner_match where status=0 and match_id > 0";
            $match_pln_ids = $db_r->createCommand($sql)->queryColumn();
            //4.平台符合条件的计划ID
            $no_match_pln_ids = array_diff($total_pln_ids,$match_pln_ids);
   
            //平台和已存在交集 更新价格浮动   存在并且符合条件的计划
            $exist_satisfy = array_intersect($no_match_pln_ids, $exist_pln_ids);
            $affect_rows_exist_satisfy = 0;
            if($exist_satisfy){
                $affect_rows_exist_satisfy = $this->updateSatisfy($exist_satisfy);
            }
    
            //平台和已存在的差集 插入新数据 新的符合条件的计划
            $new_satisfy = array_unique(array_diff($no_match_pln_ids, $exist_pln_ids));
            $affect_rows_new_satisfy = 0;
            if($new_satisfy){
                $affect_rows_new_satisfy = $this->insert($new_satisfy);
            }
            
            //已存在和平台差集  说明是不符合条件 更新数量-1  存在不符合条件的计划
            $exist_not_satisfy = array_diff($exist_pln_ids, $no_match_pln_ids);
            $affect_rows_exist_not_satisfy = 0;
            if($exist_not_satisfy){
                $affect_rows_exist_not_satisfy = $this->updateNotSatisfy($exist_not_satisfy);
            }
    
            $result =  array(
                'affect_rows_exist_satisfy'=>$affect_rows_exist_satisfy,
                'affect_rows_new_satisfy'=>$affect_rows_new_satisfy,
                'affect_rows_exist_not_satisfy'=>$affect_rows_exist_not_satisfy
            );
            return $result; 
    
        }catch (Exception $e) {
    
            throw LcsException::errorHandlerOfException($e);
    
        }
    
    }
    
    
    
    /**
     * 插入新的体验卡
     */
    private function insert($pln_ids){
        if(empty($pln_ids)){
            return 0;
        }
        $db_w = Yii::app()->lcs_w;
        $db_r = Yii::app()->lcs_r;
    
        $sql = "select pln_id, p_uid, name, number, run_days, subscription_price, curr_ror, target_ror, start_date, end_date,subscription_count,reader_count from lcs_plan_info where pln_id in (".implode(',',$pln_ids).")";
        $plan_info = $db_r->createCommand($sql)->queryAll();
    
        $affect_rows = 0;
        $count = 0;
        $str = '';
    
        $sql_start = "insert into lcs_prop (title,summary,
        type,relation_id,relation_p_uid ,
        relation_price,amount_total, amount_remainder ,
        amount_used,efficient,expire_time,
        price,use_channel,
        status , c_time ,u_time) values ";
    
        foreach ($plan_info as $row){
    
            $row['efficient']  = $this->experience_days*86400;
            $row['expire_time'] = date('Y-m-d H:i:s',strtotime($row['end_date']));
            $row['price']  = $this->getPrice($row);
            $row['title'] = $row['name'].'第'.$row['number'].'期';
            $str .= "('{$row['title']}' ,'',2001,{$row['pln_id']},{$row['p_uid']},{$row['subscription_price']},300,300,0,{$row['efficient']},'{$row['expire_time']}',{$row['price']},6,0,now(),now()),";
    
            $count++;
            if($count == 50){
                $sql = $sql_start.rtrim($str,',');
                $affect_rows+=$db_w->createCommand($sql)->execute();
                $count = 0;
                $str = '';
            }
        }
    
        if($count > 0){
            $sql = $sql_start.rtrim($str,',');
            $affect_rows+=$db_w->createCommand($sql)->execute();
    
        }
    
        return $affect_rows;
    
    }
    
    
    
    
    /**
     *
     * 更新符合条件的体验卡信息
     */
    
    private function updateSatisfy($pln_ids){
        if(empty($pln_ids)){
            return 0;
        }
        $db_w = Yii::app()->lcs_w;
        $db_r = Yii::app()->lcs_r;
    
        //获取相关计划信息
        $sql = "select pln_id, p_uid, name, number, run_days, subscription_price, curr_ror, target_ror, start_date, end_date,subscription_count,reader_count from lcs_plan_info where pln_id in (".implode(',',$pln_ids).")";
        $plan_info = $db_r->createCommand($sql)->queryAll();
    
        $sql = '';
        $affect_rows = 0;
        $count = 0;
	
        foreach ($plan_info as $row){
    
            $price = $this->getPrice($row);
            $expire_time = date('Y-m-d H:i:s',time()+$this->experience_days*86400);
            $sql .= "update lcs_prop set u_time = now(),price = {$price},expire_time='{$expire_time}' where status = 0 and type = 2001 and use_channel = 6 and staff_uid = '' and relation_id = {$row['pln_id']};";
    
            $count++;
            if($count == 50){
                $affect_rows+=$db_w->createCommand($sql)->execute();
                $count = 0;
                $sql = '';
            }
    
        }
    
        if($count>0){
            $affect_rows+=$db_w->createCommand($sql)->execute();
        }
        return $affect_rows;
    }
    
    /**
     * 更新不符合要求的体验卡
     */
    private function updateNotSatisfy($pln_ids){
        if(empty($pln_ids)){
            return 0;
        }
        $db_w = Yii::app()->lcs_w;
        $sql = "update lcs_prop set u_time = now(), amount_total = -1 where status = 0 and type = 2001 and use_channel = 6 and relation_id in (".implode(',', $pln_ids).")";
        return  $db_w->createCommand($sql)->execute();

    }
    
    
    /**
     * 计划定价 （(当前收益/运行天数）/目标收益）*价格*体验卡天数*1.5，最低价格 38元，最高388元
     * 价格阶梯
     * 38
     * 38 - 88
     * 88 - 138
     * 138 - 188
     * 188 - 238
     * 238 - 288
     * 288 - 338
     * 338 - 388
     *
     *
     * 38 58 88 118 158 188 218 258 288 318 358 388
     *
     */
    public function getPrice($row){
   
        $a = round((strtotime($row['end_date']." 23:59:59") - time())/(3600*24));
        $b = round((strtotime($row['end_date']." 23:59:59") - strtotime($row['start_date']))/(3600*24));
        if($a>0 && $b>0){
            $price = round(($a/$b)*$row['subscription_price']*1.5);
            $price = floor($price/10)*10+8;
        }
        return $price;
    }
    
    
    /**
     * 过滤交易频率
     */
    public function checkTransFrequency($pln_ids){
    
    
        $new = array();
        foreach ($pln_ids as $pln_id){
    
            //交易频率
            /*
             $plan_model = new Plan();
             $plan_info = $plan_model->getPlanInfoByIds($pln_id,'real_end_time,start_date,end_date');
             */
            $plan_info = $this->getPlanInfoById($pln_id);
    
            $e_time = strtotime($plan_info['real_end_time']) > 0 ? $plan_info['real_end_time'] : (time() > strtotime($plan_info['end_date']) ? $plan_info['end_date'] : '');
    
            $dist_date = $this->getMarketDays($plan_info['start_date'], $e_time);//交易日期
    
            $trans_frequency = $dist_date > 0 ? $this->getPlanAssess($pln_id) / $dist_date : 0;
            //频率大于1
            if ($trans_frequency >= 1) {
                $new[] = $pln_id;
            }
    
        }
    
        return $new;
    }
    
    /*
     * 获取交易日天数
     * */
    
    public function getMarketDays($start_date, $end_date = '') {
        if (strtotime($end_date) <= 0) {
            $end_date = date("Y-m-d");
        }
        $db_r = Yii::app()->lcs_r;
        $sql = "select count(cal_id) from lcs_calendar where cal_date>='" . $start_date . "' AND  cal_date<='" . $end_date . "'";
        return $db_r->createCommand($sql)->queryScalar();
    }
    
    public function getPlanAssess($pln_id){
        if(empty($pln_id)){
            return 0;
        }
        $db_r = Yii::app()->lcs_r;
        $sql = "select profit_num, loss_num, buy_num from lcs_plan_assess where pln_id = {$pln_id}";
    
        $result = $db_r->createCommand($sql)->queryRow();
        if(empty($result)){
            return 0;
        }else{
            return $result['profit_num']+$result['loss_num']+$result['buy_num'];
        }
    }
    
    public function getPlanInfoById($pln_id){
        if(empty($pln_id)){
            return null;
        }
        $db_r = Yii::app()->lcs_r;
        $sql = "select real_end_time,start_date,end_date from lcs_plan_info where pln_id = {$pln_id}";
        return $db_r->createCommand($sql)->queryRow();
    }
    
}
