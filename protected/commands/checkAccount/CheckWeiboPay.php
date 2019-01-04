<?php

/**
 * Desc  :与微博流水对账
 * Author: meixin
 * Date  : 2015-9-7 16:49:52
 */
class CheckWeiboPay {
    const CRON_NO = 8002; //任务代码

    public function __construct(){
    }

    
    public function check(){
        $weibo_obj = new Weibo;					
		$end_time = time() - 3600*4;
		$gmt_start_time = date("Y-m-d H:00:00" , $end_time - 6*3600) ;
		$gmt_end_time = date("Y-m-d H:00:00" , $end_time);
		$pageno = 1;
		$page_size = 100; //最大100
		while(true){
			$weibo_res = $weibo_obj->queryAccountList("", $gmt_start_time, $gmt_end_time, '', $pageno, $page_size);

			$weibo_info = json_decode($weibo_res , true);

			if(isset($weibo_info['code']) && 100000 == $weibo_info['code'] 
				&& !empty($weibo_info['data']['trades'])) {
			
				$weibo_orders = array();
				$weibo_trade_no = array();
				foreach($weibo_info['data']['trades'] as $info) {
					if( 2 != $info['pay_type'] || strlen($info['out_trade_no'])>15) continue;
					$weibo_trade_no[] = $info['trade_id'];
					$weibo_orders[$info['trade_id']] = $info;
				}
				$lcs_orders = Account::model()->getOrders($weibo_trade_no);

				$this->checkPayInfo($weibo_orders , $lcs_orders);
				$pageno++;
                $datafile = CommonUtils::saveDateFile(self::CRON_NO, $pageno);

			}else{
				$nums = $pageno*$page_size."\n";	
                $datafile = CommonUtils::saveDateFile(self::CRON_NO, $nums);
				break;	
			}
		}
		return true;
			
    }

	/**
     * 微博流水与平台订单的具体对账内容
     * @param type $weibo_orders 微博流水
     * @param type $lcs_orders  平台订单
     */
	private function checkPayInfo($weibo_orders , $lcs_orders){

		$warning_orders = array();
		$warnning = false;//报警标记

		foreach($weibo_orders as $pay_number=>$info){
			if(isset($lcs_orders[$pay_number])){
				//有些状态的订单没有refund_status字段，例如WAIT_BUYER_PAY待支付
				$refund_status = isset($info['refund_status']) ? $info['refund_status'] :''; 
				$weibo_status = $this->modifyToLcsStatus($info['status'], $refund_status);
				$check_infos = "";
				if($weibo_status != $lcs_orders[$pay_number]['status']){
					$check_infos .= "订单状态不对应,";	
				}
				if($info['total_fee'] != $lcs_orders[$pay_number]['price']){ 
					$check_infos = "订单金额不对应";	
				}
				if(!empty($check_infos)){
					//lcs 信息不对应加报警 	
					unset($info['show_url']);
					$info['infos'] = $check_infos;
					$warnning_orders[$pay_number] = array(
											'check_infos'=> $check_infos,	
											'pay_number' => $pay_number,
											'lcs_price' => $lcs_orders[$pay_number]['price'],
											'total_fee' => $info['total_fee'],
											'weibo_status' => $info['status'],
											'weibo_refund_status'=> $refund_status,
											'lcs_status' => $lcs_orders[$pay_number]['status'],
											'uid' => $info['buyer_id'],
										) ;
					$warnning = true;

				}	
			}
		
		}
		if($warnning){

			$title = "微博支付流水对账";
			$msg = "";
			foreach($warnning_orders as $l=>$info){
				foreach($info as $k=>$v){
					$msg .= $k.":".$v."\t";
				}
				$msg .= "\n";
			}
			$datafile = CommonUtils::saveDateFile(self::CRON_NO, $msg);
			$mailRes = new NewSendMail($title, $msg, Account::$toMailer); 	
		}
			
	} 

	/**
	 * 微博订单账单 modify to lcs平台status[对应]
	 * $param string $status:支付状态，支付创建-WAIT_BUYER_PAY/等待支付-TRADE_PENDING/已支付-TRADE_SUCCESS/超时关闭-TRADE_CLOSED/支付结束-TRADE_FINISHED/支付失败-TRADE_FAILED，其它TBD.
	 * $param string $refund_status:退款状态，和trade_status互斥，退款中-REFUND_PENDING/退款失败-REFUND_FAILED/已退款-REFUND_SUCCESS/退款关闭-REFUND_CLOSED，其它TBD.
	 *
	 */
	private function modifyToLcsStatus($status, $refund_status=''){
		switch($status){
			case 'TRADE_SUCCESS':
				$modify_status = 2;	
				break;
			case 'WAIT_BUYER_PAY': 
			case 'TRADE_PENDING' :
				$modify_status = 1;	
				break;
			default:	
				$modify_status = -1;	
		}
		if(!empty($refund_status)){
			switch($refund_status){
				case 'REFUND_PENDING':
					$modify_status = 3;	
					break;
				case 'REFUND_SUCCESS':
					$modify_status = 4;
					break;
				case 'REFUND_FAILED':
					$modify_status = -2;	
					break;
				default:
					$modify_status = -1;	
			}
			
		}
				
		return $modify_status;
	}
    
    
    
}
