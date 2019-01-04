<?php

/**
 * 订单基本信息数据库访问类
 */
class Balance extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableNameFix() {
        return TABLE_PREFIX . 'wbpay_incrfix';
    }

    public function tableNameFill() {
        return TABLE_PREFIX . 'wbpay_fill';
    }
    
    public function tableNameOrder(){
        return TABLE_PREFIX.'wbpay_order';
    }
    
    public function tableNameStrike() {
        return TABLE_PREFIX.'wbpay_strike';
    }
    
    public function tableNameProject(){
        return TABLE_PREFIX.'wbpay_project';
    }
    
    public function tableNamePlannerContract(){
        return TABLE_PREFIX.'planner_contract';
    }
    
    public function tableNameContract(){
        return TABLE_PREFIX.'team_contract';
    }

    /**
     * 获取充值的列表
     * @param type $pay_type
     * @param type $start_time
     * @param type $end_time
     * @return string
     */
    public function getFillList($pay_type,$start_time = '',$end_time = ''){        
        $condition = array();
        if(!empty($pay_type)){
            $condition[] = "channel IN ({$pay_type})";
        }
        if(!empty($start_time)){
            $condition[] = "c_time>='{$start_time}' AND c_time<='{$end_time}'";
        }
        $where = implode(' AND ', $condition);
        if(!empty($where)){
            $where = ' WHERE '.$where;
        }
        $column = "order_no,wbpay_no,uid,price,pay_time,pay_type,status,original_wbpay_no,refund_time,channel";
        $sql = "SELECT {$column} FROM {$this->tableNameFill()} ".$where;
        $list = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $list;
    }
    
    public function getOrderList($pay_type,$start_time = '',$end_time = ''){        
        $condition = array('price>0');
        if(!empty($pay_type)){
            $condition[] = "pay_type IN ({$pay_type})";
        }
        if(!empty($start_time)){
            $condition[] = "order_c_time>='{$start_time}' AND order_c_time<='{$end_time}'";
        }
        $where = implode(' AND ', $condition);
        if(!empty($where)){
            $where = ' WHERE '.$where;
        }
        $column = "product_type,product_sub_type,relation_id,uid,p_uid,p_name,order_no";
        $sql = "SELECT {$column} FROM {$this->tableNameOrder()} ".$where;
        $list = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $list;
    }
    
    public function getStrikeList($pay_type,$start_time = '',$end_time = ''){
        $condition = array('price<0');
        if(!empty($pay_type)){
            $condition[] = "pay_type IN ({$pay_type})";
        }
        if(!empty($start_time)){
            $condition[] = "order_c_time>='{$start_time}' AND order_c_time<='{$end_time}'";
        }
        $where = implode(' AND ', $condition);
        if(!empty($where)){
            $where = ' WHERE '.$where;
        }
        $column = "";
        $sql = "SELECT {$column} FROM {$this->tableNameStrike()} ".$where;
        $list = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $list;
    }

    /**
     * 写入充值记录
     * @param type $data
     */
    public function saveFill(&$data) {
        if (empty($data)) {
            return FALSE;
        }
        $sql = "INSERT INTO " . $this->tableNameFill() . " (`order_no`,`wbpay_no`,`uid`,`price`,`pay_time`,`pay_type`,`status`,`original_wbpay_no`,`refund_time`,`channel`,`c_time`,`u_time`) VALUES ";
        $rows = array();
        $cur_time = date('Y-m-d H:i:s');
        foreach ($data as $value) {
            foreach ($value as &$val) {
                $val = "'" . $val . "'";
            }            
            $value['u_time'] = "'" . $cur_time . "'";
            $rows[] = "(" . implode(',', $value) . ")";
        }
        $sql .= implode(',', $rows);
        $db_w = Yii::app()->lcs_w;
        $r_cmd = $db_w->createCommand($sql);
        $r_count = $r_cmd->execute();
        return $r_count;
    }
    
    public function saveOrder(&$data) {
        if (empty($data)) {
            return FALSE;
        }        
        $sql = "INSERT INTO " . $this->tableNameOrder() . "(`product_type`,`product_sub_type`,`relation_id`,`uid`,`p_uid`,`p_name`,`order_no`,`order_c_time`,`wbpay_no`,`pay_time`,`price`,`refund_end_time`,`status`,`is_fencheng`, `pay_type`,`pkg_end_time`,order_month,u_time,`c_time`)
				    VALUES ";
        $rows = array();
        $cur_time = date('Y-m-d H:i:s');
        foreach ($data as $value) {            
            $value['data_c_time'] = $cur_time;           
            foreach ($value as &$val) {
                $val = "'" . $val . "'";
            }        
            $rows[] = "(" . implode(',', $value) . ")";
        }
        $sql .= implode(',', $rows);        
        $db_w = Yii::app()->lcs_w;
        $r_cmd = $db_w->createCommand($sql);
        $r_count = $r_cmd->execute();
        return $r_count;
    }
    
    public function saveStrike(&$data) {
        if (empty($data)) {
            return FALSE;
        }
        $sql = "INSERT INTO {$this->tableNameOrder()} (order_month,`product_type`, `product_sub_type`,`relation_id`, `uid`,`p_uid`,`p_name`,`order_no`,`order_c_time`,
					                              `wbpay_no`,`pay_time`, `price`,`status`,`is_fencheng`, `pay_type`,u_time, `c_time`) VALUES ";        
        $rows = array();
        $cur_time = date('Y-m-d H:i:s');
        foreach ($data as $value) {
            foreach ($value as &$val) {
                $val = "'" . $val . "'";
            }
            $value['data_c_time'] = "'" . $cur_time . "'";            
            $rows[] = "(" . implode(',', $value) . ")";
        }
        $sql .= implode(',', $rows);        
        $db_w = Yii::app()->lcs_w;
        $r_cmd = $db_w->createCommand($sql);
        $r_count = $r_cmd->execute();
        return $r_count;
    }
    /**
     * 返回没有理财师的问答
     * @return boolean
     */
    public function getAskNoPuid(){
        $sql = 'SELECT * FROM '.$this->tableNameOrder().' WHERE p_uid=0 AND product_sub_type=11';
        $list = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if(empty($list)){
            return FALSE;
        }
        return $list;
    }
    /**
     * 获取全量的计划列表
     * @return boolean
     */
    public function getPlanList($flag = FALSE){
        $where = '';
        if($flag){
            $change_time = date('Y-m-d H:i:s',  strtotime('-30 days'));
            $where = " AND (real_end_time>='{$change_time}' OR u_time>='{$change_time}')";
        }
        $sql = "SELECT concat(name,number, '期') as name,pln_id,start_date,end_date,real_end_time,status,p_uid FROM lcs_plan_info WHERE status>1".$where;       
        $list = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if(empty($list)){
            return FALSE;
        }        
        foreach ($list as &$item){
            $item['refund_end_time'] = '0000-00-00 00:00:00';
            if($item['status'] == 5){
                $item['refund_end_time'] = date('Y-m-d', strtotime('+30 days', strtotime($item['real_end_time'])));
            }
        }
        return $list;
    }
    /**
     * 其他项目列表
     * @param type $start_time
     * @param type $end_time
     * @return boolean
     */
    public function getProjectList($start_time,$end_time){
        $where = '';
        if(!empty($start_time) && !empty($end_time)){
            $where = " AND ((u_time>='{$start_time}' AND u_time<='{$end_time}') OR (pay_time>='{$start_time}' AND pay_time<='{$end_time}'))";
        }        
        $group_by = " GROUP BY relation_id,type ";
        $sql = "SELECT relation_id,description as name,type,p_uid FROM lcs_orders WHERE type IN (11,12,31,32) AND status in (-2,2,3,4) ";
        $sql .= $where.$group_by;                
        $list = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if(empty($list)){
            return FALSE;
        }
        return $list;
    }
    /**
     * 插入计划项目信息
     * @param type $data
     * @return int
     */
    public function addProjectPlan(&$data){
        if(empty($data)){
            return 0;
        }
        $sql = "INSERT INTO ".$this->tableNameProject().' (relation_id,title,start_time,end_time,product_sub_type,real_end_time,c_time,refund_end_time,pln_status,u_time) VALUES ';
        $val_str = '';
        $now = date('Y-m-d H:i:s');
        foreach ($data as $item){
            $val_str .= "('{$item['pln_id']}','{$item['name']}','{$item['start_date']}','{$item['end_date']}','21','{$item['real_end_time']}','{$now}','{$item['refund_end_time']}','{$item['status']}','{$now}'),";            
        }
        $val_str = trim($val_str,',');
        $sql .= $val_str.' on duplicate key update title=values(title),real_end_time=values(real_end_time),refund_end_time=values(refund_end_time),u_time=values(u_time)';          
        $db_w = Yii::app()->lcs_w;
        $r_cmd = $db_w->createCommand($sql);
        $r_count = $r_cmd->execute();
        return $r_count;
    }
    /**
     * 
     * @param type $data
     * @return int
     */
    public function addProjectOther(&$data){
        if(empty($data)){
            return 0;
        }
        $sql = "INSERT INTO ".$this->tableNameProject().' (relation_id,title,product_sub_type,c_time,u_time) VALUES ';
        $val_str = '';
        $now = date('Y-m-d H:i:s');
        foreach ($data as $item){
            $val_str .= "('{$item['relation_id']}',".Yii::app()->lcs_w->getPdoInstance()->quote($item['name']).",'{$item['type']}','{$now}','{$now}'),";            
        }
        $val_str = trim($val_str,',');
        $sql .= $val_str.' on duplicate key update u_time=values(u_time)';                     
        $db_w = Yii::app()->lcs_w;
        $r_cmd = $db_w->createCommand($sql);
        $r_count = $r_cmd->execute();
        return $r_count;
    }
    /**
     * 删除充值
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function deleteWbpayFill($start_time,$end_time){
        if(empty($start_time) || empty($end_time)){
            return;
        }
        $condition = "c_time>='{$start_time}' AND c_time<='{$end_time}'";        
        return Yii::app()->lcs_w->createCommand()->delete($this->tableNameFill(),$condition);
    }
    
    public function deleteWbpayOrder($start_time,$end_time){
        if(empty($start_time) || empty($end_time)){
            return;
        }
        $condition = "order_c_time>='{$start_time}' AND order_c_time<='{$end_time}' AND price>=0";
        return Yii::app()->lcs_w->createCommand()->delete($this->tableNameOrder(),$condition);
    }
    
    public function deleteWbpayStrike($start_time,$end_time){
        if(empty($start_time) || empty($end_time)){
            return;
        }
        $condition = "order_c_time>='{$start_time}' AND order_c_time<='{$end_time}' AND price<0";        
        return Yii::app()->lcs_w->createCommand()->delete($this->tableNameOrder(),$condition);
    }
    /**
     * 更新退款订单的状态
     * @param type $order_nos
     * @return type
     */
    public function updateWbpayOrderStatus($order_no,$u_time){
        if(empty($order_no)){
            return;
        }        
        $sql = "UPDATE {$this->tableNameOrder()} SET status=4,u_time='{$u_time}' WHERE order_no='{$order_no}'";
        return Yii::app()->lcs_w->createCommand($sql)->execute();
    }    

}

?>