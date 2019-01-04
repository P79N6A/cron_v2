<?php

class IncrementOrder {

    const CRON_NO = 10001;

    //增量时间
    private $start_time = '';
    private $end_time = '';
    private $incr_day = false;
    //理财师订单状态与财务订单状态的映射
	public static $status_text = array(
		-2 => '1', 2 => '1', 3 => '1', 4 => '-1'
	);
	
	public static $o_status_text = array(
		-2 => '退款失败', 2 => '已付款', 3 => '退款中', 4 => '已退款'
	);
	
	//订单类型
	public static $type_text = array(
		11 => '问答提问',  12 => '问答解锁',  21 => '理财计划',  31 => '观点包',  32 => '单条观点', 41 => '牛币', 51 => '财学会',
	);
	//充值类型
	public static $use_text = array(
		11 => '直充',  12 => '直充',  21 => '直充',  31 => '直充',  32 => '直充', 41 => '牛币', 51 => '直充',
	);
	//支付类型
	public static $pay_type_text = array(
		1 => '直充',  2 => '直充',  3 => '牛币',
	);
	//渠道
	public static $pay_text = array(
		1 => '支付宝',  2 => '支付宝',  3 => '牛币',
	);

    public function __construct($start_time = '', $end_time = '') {
        if (!empty($start_time) && !empty($end_time)) {
            $this->start_time = $start_time;
            $this->end_time = $end_time;
        } else {
            $this->incr_day = date('Y-m-d', strtotime("-2 days"));
        }
    }

    /**
     * 充值数据增量
     */
    public function doRechargeIncr() {
        $columns = array(
            'order_no',
            'pay_number',
            'uid',
            'price',
            'pay_time',
            'type',
            'status',
            'pay_number AS weibo_order_no',
            '\'refund_result_time\'',
            'pay_type'
        );        
        $f_columns = 'order_no,refund_result_time';
        $f_conditions = array(
            'refund_result=4'
        );
        //曾经正常的订单（含-2,2,3,4）
        $conditions = array(
            'pay_type in (1,2)',
            'status in (-2,2,3,4)',
        );
        if ($this->incr_day) {
            $conditions[] = "DATE_FORMAT(c_time,'%Y-%m-%d')='" . $this->incr_day . "'";
            $f_conditions[] = "DATE_FORMAT(c_time,'%Y-%m-%d')='" . $this->incr_day . "'";
        } else {
            $conditions[] = "c_time>='" . $this->start_time . "'";
            $conditions[] = "c_time<='" . $this->end_time . "'";
            $f_conditions[] = "c_time>='" . $this->start_time . "'";
            $f_conditions[] = "c_time<='" . $this->end_time . "'";
        }
        $data = array();
        $recharge_list = Orders::model()->getOrderIncr($columns, $conditions, 'c_time ASC');
        //业务订单号    微博订单号   用户UID 充值金额   充值时间   充值类型(牛币/直充/…)  订单状态(1:正常；-1：退款)  原微博订单号   退款时间    渠道
        if ($recharge_list) {
            foreach ($recharge_list as $value) {
                $value['status'] = 1;
                $value['weibo_order_no'] = '';
                $value['refund_result_time'] = '';
                $data[] = $value;
            }
            unset($recharge_list);
        }        
        $refund_list = Orders::model()->getRefundIncr($f_columns, $f_conditions, 'c_time ASC');
        if ($refund_list) {
            $r_order_nos = array();
            $fund = array();
            foreach ($refund_list as $item) {
                $r_order_nos[] = $item['order_no'];
                $fund[$item['order_no']] = $item['refund_result_time'];
            }
            $conditions = array(                
                'pay_type IN (1,2)',
                'status=4',
                'order_no IN ('.implode(',', $r_order_nos) . ')'
            );
            $refund_list = Orders::model()->getOrderIncr($columns, $conditions, 'c_time ASC');
            foreach ($refund_list as &$refund) {
                $refund['refund_result_time'] = $fund[$refund['order_no']];
                $refund['status'] = -1;
                $data[] = $refund;
            }
        }
        foreach ($data as $item){
            print_r($item);
        }
    }

    /**
     * 消费增量
     */
    public function doConsumerIncr() {
        $columns = array(            
            'type','type as sub_type','relation_id', 'uid','p_uid','\'name\'','order_no','c_time',
            'pay_number','pay_time','price','u_time as refund_end_time', 'status as o_status','\'is_fencheng\'',
            'pay_type','\'pkg_end_time\'','\'pln_end_time\'','\'pln_status\''
        );
        $conditions = array(
            'status IN (-2,2,3,4)',
            'pay_type in (1,2,3)',
            'type<>41',            
        );
        if ($this->incr_day) {
            $conditions[] = "DATE_FORMAT(c_time,'%Y-%m-%d')='" . $this->incr_day . "'";            
        } else {
            $conditions[] = "c_time>='" . $this->start_time . "'";
            $conditions[] = "c_time<='" . $this->end_time . "'";
        }
        $data = array();
        $planner_ids = array();
        $plan_ids = array();  //计划id
        $plan_map = array();
        $package_map = array();
        $planner_map = array();
        $package_orders = array(); //观点包id        
        $order_list = Orders::model()->getOrderIncr($columns,$conditions,'c_time ASC');     
        foreach ($order_list as &$item){
            if(!in_array($item['p_uid'], $planner_ids)){
                $planner_ids[] = $item['p_uid'];
            }
            if($item['type'] == 21 && !in_array($item['relation_id'], $plan_ids)){
                $plan_ids[] = $item['relation_id'];
            }
            if($item['type'] == 31 && !in_array($item['relation_id'], $package_orders)){
                $package_orders[] = $item['order_no'];
            }
        }		
        //计划状态、结束时间
        if(!empty($plan_ids)){
            $plan_list = Plan::model()->getPlanInfoByIds($plan_ids,'pln_id,status,real_end_time');
            foreach ($plan_list as $plan){                
                    $plan_map[$plan['pln_id']] = $plan;                           
            }
        }
        //观点包结束时间
        if(!empty($package_orders)){
            $package_list = Package::model()->getSubHistoryByOrderNo($package_orders);
            foreach ($package_list as $package){
                $package_map[$package['order_no']] = $package['end_time'];
            }
        }
        //理财师名称
        if(!empty($planner_ids)){
            $planner_list = Planner::model()->getPlannerById($planner_ids);
            foreach ($planner_list as $planner){
                $planner_map[$planner['p_uid']] = $planner['name'];
            }
        }
        foreach ($order_list as $value){
            $value['name'] = isset($planner_map[$value['p_uid']]) ? $planner_map[$value['p_uid']] : '';
            $sub_type = $value['sub_type'];
			$value['refund_end_time'] = '';
            $value['pln_status'] = '';
            $value['pln_end_time'] = '';
            if($value['sub_type'] == '21'){
                $value['pln_status'] = isset($plan_map[$value['relation_id']]) ? $plan_map[$value['relation_id']]['status'] : '';
                $value['pln_end_time'] = isset($plan_map[$value['relation_id']]) ? $plan_map[$value['relation_id']]['real_end_time'] : '';
                $value['refund_end_time'] = ($value['pln_status'] == 5) ? date('Y-m-d',  strtotime('+30 days', strtotime($value['pln_end_time']))) : '';
            }
			$value['type'] = '理财师';
			$tmp_type = $value['sub_type'];
			$value['sub_type'] = self::$type_text[$tmp_type];
			$tmp_status = $value['o_status'];
			$value['o_status'] = self::$o_status_text[$tmp_status];
				
			$value['is_fencheng'] = '是';
			if($tmp_type=='21' && $value['price']=='88') {
				$value['is_fencheng'] = '否';
			}elseif($tmp_type=='51') {
				$value['is_fencheng'] = '否';
			}
			
			$tmp_pay = $value['pay_type'];
			$value['pay_type'] = self::$pay_type_text[$tmp_pay];
			
			if($sub_type != '31') {
				$value['pkg_end_time'] = '';
			}else{
				$value['pkg_end_time'] = isset($package_map[$value['order_no']]) ? $package_map[$value['order_no']] : '';
			}
			unset($value['pln_end_time'], $value['pln_status']);				
			$data[] = $value;
        }
		print_r($data);
    }

    /**
     * 退款数据增量
     */
    public function doRefundIncr() {        
		//一级产品类型    二级产品类型    项目ID  用户UID  理财师ID  理财师姓名    退款业务订单号    退款订单提交时间    退款微博订单号    退款付款时间    退款订单金额
		//订单状态    是否分成    被退业务订单号    支付类型
        $refund_columns = 'order_no,c_time,refund_number';        
        $nibi_columns = 'order_no,c_time';
        if ($this->incr_day) {
            $refund_conditions[] = "DATE_FORMAT(c_time,'%Y-%m-%d')='" . $this->incr_day . "'";            
        } else {
            $refund_conditions[] = "c_time>='" . $this->start_time . "'";
            $refund_conditions[] = "c_time<='" . $this->end_time . "'";
        }
        $refund_list = Orders::model()->getRefundIncr($refund_columns,$refund_conditions);
        $refund_conditions[] = 'type=1';
        $account_list = Orders::model()->getAccountIncr($nibi_columns,$refund_conditions);
        $refund_order_nos = array();
        $account_order_nos = array();
        $refund_map = array();
        $account_map = array();
        if(!empty($refund_list)){
            foreach ($refund_list as $refund){
                $refund_order_nos[] = $refund['order_no'];
                $refund_map[$refund['order_no']] = $refund;
            }
        }
        if(!empty($account_list)){
            foreach ($account_list as $account){
                $account_order_nos[] = $account['order_no'];
                $account_map[$account['order_no']] = $account['c_time'];
            }
        }
        $columns = array(
            'type','type as sub_type','relation_id', 'uid', 'p_uid','\'name\'','order_no','\'c_time\'', '\'refund_number\'','pay_time','price','status as o_status',
				           'refund_lock as is_fencheng','order_no as refund_order_no','pay_type' 
        );
        $conditions = '';
        if($refund_order_nos){
            $conditions = 'status=4 and pay_type in (1,2) AND order_no IN ('.  implode(',', $refund_order_nos).')';
        }
        if($account_order_nos){            
            if($conditions){
                $conditions = '('.$conditions.') OR ('.'status=4 and pay_type in (3) AND order_no IN ('.  implode(',', $account_order_nos).'))';
            }else{
                $conditions = 'status=4 and pay_type in (3) AND order_no IN ('.  implode(',', $account_order_nos).')';
            }
        }                
		$result = Orders::model()->getOrderIncr($columns,$conditions);                 
		foreach ($result as &$value) {
            if($value['pay_type'] == 1 || $value['pay_type'] == 2){
                $value['c_time'] = $refund_map[$value['order_no']]['c_time'];
                $value['refund_number'] = $refund_map[$value['order_no']]['refund_number'];
            }else{
                $value['c_time'] = $account_map[$value['order_no']];
                $value['refund_number'] = '';
            }
			$value['type'] = '理财师';				
			$tmp_type = $value['sub_type'];
			$value['sub_type'] = self::$type_text[$tmp_type];				
			$tmp_status = $value['o_status'];
			$value['o_status'] = self::$o_status_text[$tmp_status];
            
			$value['is_fencheng'] = '是';
			if($value['type']=='21' && $value['price']=='88') {
				$value['is_fencheng'] = '否';
			}
		
			$tmp_pay = $value['pay_type'];
			$value['pay_type'] = self::$pay_type_text[$tmp_pay];
		}
		print_r($result);
    }

}
