<?php

/**
 * 修复订单
 */
class  RepairOrderV2{

	const CRON_NO = 8302; //任务代码

	public function __construct() {

	}

	public function repairOrder(){
		try{
			
            $where_params = array('pay_type' => 7, 'status' => 1 );
            $order_list = Orders::model()->getOrderListsbyType($where_params , 1);
            
            if(!empty($order_list)){
                $wb = new WeiboPayV2();
                foreach ($order_list as $info) {
                    $res = $wb->queryAccount($info['order_no'], $info['pay_number']);
                    
                    if(isset($res['code']) && $res['code'] = 100000 && is_array($res['data'])){ 
                        //print_r($res['data']);
                        $notify_result = $wb->requestPayNotifyUrl($res['data']);
                        $log = date("Y-m-d H:i:s") . "--" . $notify_result . "--" . $res['data']['out_pay_id'];
                        Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, $log);
                    }
                    
                }
            }
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}
}
