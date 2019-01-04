<?php

class StatWbpay extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableNameFill() {
        return TABLE_PREFIX.'wbpay_fill';
    }

    public function tableNameOrder() {
        return TABLE_PREFIX.'wbpay_order';
    }

    public function tableNameStrike() {
        return TABLE_PREFIX.'wbpay_strike';
    }

    public function tableNameProject() {
        return TABLE_PREFIX.'wbpay_project';
    }
    
    public function tableNameFix(){
        return TABLE_PREFIX.'wbpay_incrfix';
    }

    /**
     * 写入充值记录
     * @param type $data
     */
    public function saveFill(&$data) {
        if (empty($data)) {
            return FALSE;
        }
        $sql = "INSERT INTO " . $this->tableNameFill() . " (`order_no`,`wbpay_no`,`uid`,`price`,`pay_time`,`pay_type`,`status`,`original_wbpay_no`,`refund_time`,`channel`,`c_time`) VALUES ";
        $rows = array();
        $cur_time = date('Y-m-d H:i:s');
        foreach ($data as $value) {
            foreach ($value as &$val) {
                $val = "'" . $val . "'";
            }
            $value['c_time'] = "'" . $cur_time . "'";
            $rows[] = "(" . implode(',', $value) . ")";
        }
        $sql .= implode(',', $rows);
        $db_w = Yii::app()->lcs_w;
        $r_cmd = $db_w->createCommand($sql);
        $r_count = $r_cmd->execute();
        return $r_count;
    }
    /**
     * 
     * @param type $pay_type
     * @param type $type
     * @param type $start_time
     * @param type $end_time
     * @return boolean
     */
    public function deleteFill($pay_type,$type = 0,$start_time = '',$end_time = ''){
        $conditions = array();
        if(!empty($pay_type)){
            $conditions[] = "channel IN ($pay_type)";
        }
        if(!empty($type)){
            $conditions[] = "pay_type IN ($type)";
        }
        if(!empty($start_time) && !empty($end_time)){
            $conditions[] = "pay_time>='{$start_time}' AND pay_time<='{$end_time}'";
        }
        if(empty($conditions)){
            return FALSE;
        }
        $where = implode(' AND ', $conditions);
        return Yii::app()->lcs_w->createCommand()->delete($this->tableNameFill(),$where);
    }

    /**
     * 写入消费记录
     * @param type $data
     * @return boolean
     */
    public function saveOrder(&$data) {
        if (empty($data)) {
            return FALSE;
        }
        $sql = "INSERT INTO " . $this->tableNameOrder() . "(`product_type`,`product_sub_type`,`relation_id`,`uid`,`p_uid`,`p_name`,`order_no`,`order_c_time`,`wbpay_no`,`pay_time`,`price`,`refund_end_time`,`status`,`is_fencheng`, `pay_type`,`pkg_end_time`,`c_time`)
				    VALUES ";
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
    
    public function deleteOrder($pay_type,$type = 0,$start_time = '',$end_time = ''){
        $conditions = array();
        if(!empty($pay_type)){
            $conditions[] = "pay_type IN ($pay_type)";
        }
        if(!empty($type)){
            $conditions[] = "product_sub_type IN ($type)";
        }
        if(!empty($start_time) && !empty($end_time)){
            $conditions[] = "pay_time>='{$start_time}' AND pay_time<='{$end_time}'";
        }
        if(empty($conditions)){
            return FALSE;
        }
        $where = implode(' AND ', $conditions);
        return Yii::app()->lcs_w->createCommand()->delete($this->tableNameOrder(),$where);
    }
    
    public function deleteStrike($pay_type,$type = 0,$start_time = '',$end_time = ''){
        $conditions = array();
        if(!empty($start_time) && !empty($end_time)){
            $conditions[] = "pay_time>='{$start_time}' AND pay_time<='{$end_time}'";
        }
        if(empty($conditions)){
            return FALSE;
        }
        $where = implode(' AND ', $conditions);
        return Yii::app()->lcs_w->createCommand()->delete($this->tableNameOrder(),$where);
    }

    /**
     * 退款记录入库
     * @param type $data
     * @return boolean
     */
    public function saveStrike(&$data) {
        if (empty($data)) {
            return FALSE;
        }
        $sql = "INSERT INTO {$this->tableNameStrike()} (`product_type`, `product_sub_type`,`relation_id`, `uid`,`p_uid`,`p_name`,`order_no`,`refund_c_time`,
					                              `wbpay_no`,`pay_time`, `price`,`status`,`is_fencheng`, `refund_order_no`, `pay_type`, `c_time`) VALUES ";
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
     * 保存项目信息
     * @param type $data
     * @return boolean
     */
    public function saveProject(&$data) {
        if (empty($data)) {
            return FALSE;
        }
        $sql = "INSERT INTO {$this->tableNameProject()} (`relation_id`, `title`,`start_time`,`end_time`,`product_sub_type`, `real_end_time`,`c_time`) VALUES ";
        $rows = array();        
        $cur_time = date('Y-m-d H:i:s');
        foreach ($data as &$value) {
            foreach ($value as &$val) {
                $val = Yii::app()->lcs_w->getPdoInstance()->quote($val);
            }
            $value['data_c_time'] = "'" .$cur_time. "'";
            $rows[] = "(" . implode(',', $value) . ")";
        }
        $sql .= implode(',', $rows);
        $sql .= " on duplicate key update real_end_time=values(real_end_time)";        
        $db_w = Yii::app()->lcs_w;
        $r_cmd = $db_w->createCommand($sql);
        $r_count = $r_cmd->execute();
        return $r_count;
    }
    
    /**
     * 获取需要重新推送的增量需求
     * @return type
     */
    public function getFixData(){
        $sql = 'SELECT * FROM '.$this->tableNameFix().' WHERE status=-1';
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $list;
    }
    /**
     * 
     * @param type $id 任务id
     * @param type $status 运行状态
     * @return type
     */
    public function updateFixStatus($id,$status){
        return Yii::app()->lcs_r->createCommand()->update($this->tableNameFix(), array('status'=>$status), 'id=:id', $params=array(':id'=>$id));
    }

}
