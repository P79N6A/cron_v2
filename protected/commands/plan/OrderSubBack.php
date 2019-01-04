<?php

/**
 * A股撮合系统接收程序
 */
class  OrderSubBack{

	const CRON_NO = 5001; //任务代码

	public function __construct() {
	}

	public function OrderSub(){

		if(!Calendar::model()->isTradeDay()){
			exit;
		}

		try{
			$wsclient = new WebsocketClient;
			$wsclient->connect(PlanOrder::model()->cuohe_host_bak ,PlanOrder::model()->cuohe_port ,'/sub',base64_encode(PlanOrder::model()->cuohe_user .":".PlanOrder::model()->cuohe_passwd ));
			$wsclient->setCallBack(array('OrderSubBack','ReceiveData'));

			declare(ticks=1);
			pcntl_signal(SIGTERM, 'signal_handler');
			$pid=pcntl_fork();

			if ($pid==0) {//子进程
				$redis_key = 'lcs_plan_cuohe_starttime_back';
				//撮合系统的状态
				$sys_arr = Yii::app()->curl->get("http://".PlanOrder::model()->cuohe_host_bak.":".PlanOrder::model()->cuohe_port."/status");
				$sys_arr = parse_ini_string($sys_arr);
				//自己记录的状态
				$lcs_starttime = Yii::app()->redis_w->get($redis_key);
				if ($lcs_starttime == $sys_arr['starttime']) {
					$start = $sys_arr['maxseq'];
				}else{
					$start = 0;
					Yii::app()->redis_w->set($redis_key,$sys_arr['starttime']);
				}
				$wsclient->sendData("start=$start,type=cn");
				$wsclient->run();
			}else{//父进程
				while(true){
					if (date('Hi')>'1456') {//发信号，让子进程退出
						posix_kill($pid, SIGTERM);
						exit;
					}
					sleep(20);
					$wsclient->sendData("\n");//保持心跳
				}
			}

		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}


	/**
	 * 接收到回调信息后对撮合数据处理
	 *
	 * @param unknown_type $data
	 */
	public function ReceiveData($data){
		$data = explode("\n", $data);
		var_dump($data);
		if(is_array($data) && !empty($data)){
			foreach ($data as $val){
				if(!empty($val)){
					$val = str_replace(',', '&', $val);
					parse_str($val,$arr);
					$pln_id = $arr['uid'];//计划pln_id
					$order_id = $arr['uid2'];//订单的id(lcs_plan_order的id)
					$volume = $arr['volume'];//成交量
					$price = $arr['price'];//为0表示撤单
					$ask = $arr['ask'];//大于0表示卖单
					$bid = $arr['bid'];//大于0表示买单
					$symbol = $arr['symbol'];//股票代码
					$time = date('Y-m-d H:i:s',$arr['ctime']);//成交时间
					$res = '';
					if($arr['project'] == 'licaishi' && $arr['type']=='cn'){

						if($price == 0){//撤单
							//$res = PlanService::cancelOrder($pln_id,$order_id);
							$type = 'cancel';
						}elseif($ask > 0){//卖单
							$type = 'sell';
							//$res = PlanService::dealPlanOrder($pln_id,$order_id,$symbol,$price,$volume,2,$time);
						}elseif($bid > 0){//买单
							$type = 'buy';
							//$res = PlanService::dealPlanOrder($pln_id,$order_id,$symbol,$price,$volume,1,$time);
						}
						$time = strtotime($time);
						$seqid = $arr['seqid'];
						if(defined('ENV') && ENV == 'dev'){//测试环境
							exec("php  ~/projects/cron_v2/protected/yiic.php Plan DealCuohe --type=$type --pln_id=$pln_id --order_id=$order_id --symbol=$symbol --price=$price --volume=$volume --time=$time --seqid=$seqid --is_back=1");
						}else{
							exec("php  ~/projects/cron/licaishi/protected/yiic.php Plan DealCuohe --type=$type --pln_id=$pln_id --order_id=$order_id --symbol=$symbol --price=$price --volume=$volume --time=$time --seqid=$seqid --is_back=1");
						}
						//Cron::model()->saveCronLog(self::CRON_NO,'info',$val.$res);
					}

				}

			}
		}
	}
}
function signal_handler($signo){
	switch ($signo){
		case SIGTERM:
			exit;
			break;
		default:
			break;
	}
	exit;
}
