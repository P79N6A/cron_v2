<?php

class AutoIncrement {

    const CRON_NO = 8200;

    private $start_time = '';
    private $end_time = '';

    public function __construct($start_time = '', $end_time = '') {
        if (!empty($start_time) && !empty($end_time)) {
            $this->start_time = $start_time. ' 00:00:00';
            $this->end_time = $end_time. ' 23:59:59';
        } else {
            $incr_day = date('Y-m-d', strtotime("-2 days"));
            $this->start_time = $incr_day . ' 00:00:00';
            $this->end_time = $incr_day . ' 23:59:59';            
        }
    }
    /**
     * 导入充值
     * @return int
     */
    public function autoFill() {
        $pay_type = '1,2,4,5,8,9';
        $status = '-2,2,3,4';        
        $result = Orders::model()->getFillListNew($pay_type, $status, $this->start_time, $this->end_time);
        if (empty($result)) {
            return 0;
        }
        Balance::model()->deleteWbpayFill($this->start_time,  $this->end_time);
        foreach ($result as &$item) {
            //$item['type'] = ($item['type'] == '41') ? 1 : 2; //除了牛币 其他都是直充            
            if($item['status'] == -1){
                $item['price'] = -($item['price']);
            }
        }
        $row = Balance::model()->saveFill($result);
        return $row;
    }
    /**
     * 导入消费
     * @return int
     */
    public function autoOrder() {
        $pay_type = '1,2,3,4,5,6,8,9';
        $status = '-2,2,3,4';
        $result = Orders::model()->getOrderListNew($pay_type, $status, $this->start_time, $this->end_time);
        if (empty($result)) {
            return 0;
        }
        Balance::model()->deleteWbpayOrder($this->start_time,  $this->end_time);
        $data = array();
        foreach ($result as $value) {
            $value['refund_end_time'] = '';
            if ($value['sub_type'] == '21' && $value['pln_status'] == 5) {
                $value['refund_end_time'] = date('Y-m-d', strtotime('+30 days', strtotime($value['pln_end_time'])));
            }
            if (($value['sub_type'] == '21' && $value['price'] == '88') || empty($value['p_uid'])) {
                $value['is_fencheng'] = 2;
            }
            if ($value['sub_type'] != '31') {
                $value['pkg_end_time'] = '';
            } else {
                if ($value['pkg_end_time'] == '0000-00-00 00:00:00') {
                    $value['pkg_end_time'] = '';
                }
            }                                
            $value['order_month'] = self::getBillMonth($value['order_month'], 25);
            unset($value['pln_end_time'], $value['pln_status']);            
            $data[] = $value;
        }
        $row = Balance::model()->saveOrder($data);
        return $row;
    }

    /**
     * 导入退款
     * @return int
     */
    public function autoStrike(){
        $result = Orders::model()->getRefundListNew($this->start_time, $this->end_time);
        if(empty($result)){
            return 0;
        }
        Balance::model()->deleteWbpayStrike($this->start_time,  $this->end_time);        
        foreach ($result as &$value){           
			$value['is_fencheng'] = '1';
			if($value['sub_type']=='21' && $value['price']=='88') {
				$value['is_fencheng'] = '2';
			}	            
            if(date('j',  strtotime($value['order_month'])) > 25){
                $value['order_month'] = date('Ym',strtotime($value['order_month']) + 3600 * 24 * 10);                
            }else{
                $value['order_month'] = date('Ym',strtotime($value['order_month']));
            }
            $value['price'] = -($value['price']);
            Balance::model()->updateWbpayOrderStatus($value['order_no'],$value['u_time']);
        }            
        $row = Balance::model()->saveStrike($result);
        return $row;
    }
    
    public function autoProject($flag){
        $planlist = Balance::model()->getPlanList($flag);
        $otherlist = Balance::model()->getProjectList($this->start_time,  $this->end_time);        
        $plan_num = Balance::model()->addProjectPlan($planlist);
        $other_num = Balance::model()->addProjectOther($otherlist);
        return $plan_num + $other_num;
    }
    /**
     * 
     * @param type $time
     * @param type $day
     */
    private static function getBillMonth($time, $day) {
        if (date('j', strtotime($time)) > $day) {
            $bill_month = date('Ym', strtotime($time) + 3600 * 24 * 10);
        } else {
            $bill_month = date('Ym', strtotime($time));
        }
        return $bill_month;
    }
    
    

}
