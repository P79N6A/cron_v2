<?php
/**
 * @description 理财师收费客户
 * @author yougang1
 * @date 2016-05-06
 */
class PayCustomer{
    
    public $stat_time;
    const CRON_NO = 1014;
    public function __construct($date = ''){
        $this->stat_time = empty($date) ? date('Y-m-d').' 00:00:00' : $date.' 00:00:00';
    }
    
    
    public function statData(){
        try {
            $affect_num = 0;           
            $affect_num += $this->PayPkgCustomer();
            $affect_num += $this->PayPlanCustomer();
            return $affect_num;
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
       
    }
   
    /**
     * 观点包正在订阅中的客户
     */
    public function PayPkgCustomer(){
        try {
            //所有理财师收费观点包
            $sql = "select p_uid, id from lcs_package where subscription_price> 0 and status = 0 and charge_time < '{$this->stat_time}'";
            $charge_pkg = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            $affect_num = 0;//影响行数
            foreach ($charge_pkg as $info){
                //所有购买过该理财师观点包的用户
                $sql = "select p_uid, uid, relation_id from lcs_orders where status = 2 and type=31 and relation_id = {$info['id']} and p_uid = {$info['p_uid']} and pay_time < '{$this->stat_time}'";
                $orders = Yii::app()->lcs_r->createCommand($sql)->queryAll();
                if(empty($orders)){
                    continue;
                }
                $count = 0;
                $sql = '';
                
                foreach ($orders as $order){
                    $sql_end_time = "select end_time from lcs_package_subscription where pkg_id = {$order['relation_id']} and uid = {$order['uid']}";
                    $expire_time = Yii::app()->lcs_r->createCommand($sql_end_time)->queryScalar();
                    if(!$expire_time){
                        continue;
                    }
                    //盘点是否过期
                    if(strtotime($this->stat_time) < strtotime($expire_time)){
                        $sql.= "insert into lcs_planner_customer (p_uid,uid,c_type,t_view,c_time) values ({$order['p_uid']},{$order['uid']},2,2,now()) on duplicate key update c_type=2, t_view=2, u_time=now();";
                    }else{
                        //过期未续费
                        $sql.= "insert into lcs_planner_customer (p_uid,uid,t_view,c_time) values ({$order['p_uid']},{$order['uid']},1,now()) on duplicate key update t_view=1, u_time=now();";
                    }
                    
                    $count++;
                    if($count == 100){
                        $affect_num+=Yii::app()->lcs_w->createCommand($sql)->execute();
                        $count = 0;
                        $sql = '';
    
                    }
                }
    
                if($count>0){
                    //insert or update
                    $affect_num+=Yii::app()->lcs_w->createCommand($sql)->execute();
    
                }
            }
            return $affect_num;
        } catch (Exception $e) {
    
            throw LcsException::errorHandlerOfException($e);
        }
    
    }
    
    
    /**
     * 购买最新一期计划的客户 
     */
    public function PayPlanCustomer(){
        try {
            $start_date = date('Y-m-d',strtotime($this->stat_time));
            //所有理财师当前未终止最新一期的计划
            $sql = "select a.status, a.pln_id, a.p_uid from lcs_plan_info as a inner join (select max(pln_id) as max_pln_id, p_uid from lcs_plan_info where status in (2,3,6) and c_time <'{$this->stat_time}' group by p_uid) as b on a.pln_id = b.max_pln_id and a.p_uid = b.p_uid";
            $plans = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            $affect_num = 0;
            foreach ($plans as $plan){
                //所有购买该理财师计划得客户订单所有的uid
                $sql = "select uid from lcs_orders where p_uid = {$plan['p_uid']} and relation_id = {$plan['pln_id']}  and type = 21 and status = 2 and pay_time < '{$this->stat_time}'";
                $order_uids = Yii::app()->lcs_r->createCommand($sql)->queryColumn(); 
                if(empty($order_uids)){
                    continue;//没有人购买
                }
                $sql = '';
                $count = 0;
                //所有购买最新一期正在运营计划的 客户
                foreach($order_uids as $uid){
                    $sql .= "insert into lcs_planner_customer (p_uid,uid,c_type,t_plan,c_time) values ({$plan['p_uid']},{$uid},2,2,now()) on duplicate key update c_type=2, t_plan=2, u_time=now();";
                    $count++;
                    if($count == 100){
                        $affect_num +=Yii::app()->lcs_w->createCommand($sql)->execute();
                        $sql = '';
                        $count = 0;
                    }
                    
                }
                
                if($count > 0){
                    $affect_num +=Yii::app()->lcs_w->createCommand($sql)->execute();
                }
                
            }
            return $affect_num;

        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
        
    }
    
    
  
    
    
    
       
    
}