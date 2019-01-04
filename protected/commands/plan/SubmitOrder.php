<?php

/**
 * 每天9：30统一提交积累的数据到撮合系统
 */
class  SubmitOrder{

	const CRON_NO = 5002; //任务代码

	public function __construct() {
	}

	public function SubmitOrder(){
		
		sleep(6);
		//拿到所有没有处理的数据
		$deal_url = array("http://".PlanOrder::model()->cuohe_user .":".PlanOrder::model()->cuohe_passwd ."@".PlanOrder::model()->cuohe_host.":".PlanOrder::model()->cuohe_port ."/order",
						   "http://".PlanOrder::model()->cuohe_user .":".PlanOrder::model()->cuohe_passwd ."@".PlanOrder::model()->cuohe_host_bak.":".PlanOrder::model()->cuohe_port ."/order");
		while (true){
			$order_list = PlanOrder::model()->getTodayNotSub();
			$res = '';
			if(is_array($order_list) && !empty($order_list)){
				$ids = array();
				$time = time();
				foreach ($order_list as $val){
					$ids[] = $val['id'];
					$res .="project=licaishi,type=cn,";
					$key =  $val['type'] == 1?"bid":"ask";
					$res .= "$key={$val['order_price']},symbol={$val['symbol']},volume={$val['order_amount']},time=$time,";
					$res .="uid={$val['pln_id']},uid2={$val['id']}\n";
				}

				foreach ($deal_url as $url){
					$upd = false;
					try {
						$rows = Yii::app()->curl->setTimeOut(5)->post($url, $res);
						if(!$upd){
							PlanOrder::model()->updateOrder($ids,array('is_sub'=>1,'u_time'=>date('Y-m-d H:i:s')));
							$upd = true;
						}
						Cron::model()->saveCronLog(self::CRON_NO,'info',$url.$rows.$res);
                    }catch(Exception $e) {
                        var_dump($e->getMessage());
						Cron::model()->saveCronLog(self::CRON_NO,'error',$e->getMessage());
					}
				}

			}else {
				break;
			}
		}


	}



}
