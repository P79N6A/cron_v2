<?php

/**
 * 修复订单
 */
class  RepairOrder{

	const CRON_NO = 8302; //任务代码

	public function __construct() {

	}

	public function repairOrder(){
		try{
			$wb = new Weibo();
        	$result = $wb->queryAccountList('','','','TRADE_SUCCESS',1,100);
        	if(!empty($result)){
            	$result = json_decode($result,true);

            	if(isset($result['code']) && $result['code']==100000 && isset($result['data']['trades'])){
                	$list = $result['data']['trades'];
                	$order_nos = array();
                	foreach($list as $l){
                    	array_push($order_nos,$l['out_trade_no']);
                	}
	
                	$orders = Orders::model()->getOrdersInfoByOrderNo($order_nos);
                	foreach($list as $l){
                    	//状态为待付款的订单请求异步回调
                    	if(isset($orders[$l['out_trade_no']]) && $orders[$l['out_trade_no']]['status']==1){
                        	$params = array(
                            	'out_trade_no' => $l['out_trade_no'],
                            	'pay_type' => 2,
                            	'subject' => $l['subject'],
                            	'body' => $l['body'],
                            	'trade_no' => $l['trade_id'],
                            	'trade_status' => $l['status'],
                            	'notify_time' => $l['gmt_payment'],
                            	'total_fee' => $l['total_fee'],
                            	'price' => $l['price'],
                            	'quantity' => $l['quantity'],
                            	'show_url' => 'http://licaishi.sina.com.cn/web/weiboPayResult',
                            	'gmt_create' => $l['gmt_create'],
                            	'gmt_payment' => $l['gmt_payment'],
                            	'gmt_refund' => $l['gmt_refund'],
                            	'from' => 'backstage'
                       	 	);
                        	$notify_result = $wb->requestPayNotifyUrl($params);
                        	$log = date("Y-m-d H:i:s")."--".$notify_result."--".$l['out_trade_no'];
                        	Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, $log);

                    }
                }
            }
        }
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}
}