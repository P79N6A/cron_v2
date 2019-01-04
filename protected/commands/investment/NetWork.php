<?php
/**
 * 网络请求类
 */
class NetWork
{
	/**
	 * 获取token
	 * @return string
	 */
	public function GetTokenCrm(){
		$redis_key = MEM_PRE_KEY."buyNoticeRedisKey";
		$token = Yii::app()->redis_r->get($redis_key);
		if(!empty($token)){
			return $token;
		}
		$requestUrl = Config::getConfig("crmUrl")."api/login";
		$params = array(
			"buCode"=>Config::getConfig("buCode"),
			"signature"=>Config::getConfig("signature"),
		);
		$response = json_decode(Yii::app()->curl->post($requestUrl,$params),true);
		if($response['code'] == "0000"){
			$token = $response['data']['token'];
			Yii::app()->redis_r->setex($redis_key,3600,$token);
			return $token;
		}
		return $token;
	}
	/**
	 * 验证token
	 * @param string $token
	 * @return bool
	 */
	public function checkToken($token){
		$requestUrl = Config::getConfig("crmUrl")."api/checkToken";
	}

	/**
	 * 请求接口
	 * @param array requestData 传递的订单数据
	 * @return void
	 * @exception 订单失败
	 */
	public function requestByOrder($requestData){
		$token = $this->GetTokenCrm();
		if(empty($token)){
			$token = $this->GetTokenCrm();
		}
		$requestData['token'] = $token;
		var_dump($requestData);
		$header = [
			'content-type'=>'application/json; charset=UTF-8',
			'token'=>$token,
		];
		Yii::app()->curl->setHeaders($header);
		//请求的地址
		$requestUrl = Config::getConfig('crmUrl')."order/order/commitOrder";
		echo json_encode($requestData);
		//请求
		$response = json_decode(Yii::app()->curl->post($requestUrl,json_encode($requestData)),true);
		if($response["code"] == 0000 || $response['code'] == "9998"){
			echo "\r\n同步成功\r\n";
			Common::model()->saveLog(sprintf("同步订单order_no%s成功%s",$requestData['order_no'],json_encode($response)), 'info','Lcs-Cron-Buy-Notice-V2');
		}else{
			var_dump($response);
			Common::model()->saveLog(sprintf("同步订单order_no%s失败,错误原因:%s,错误代码:%s",$requestData['order_no'],$response['message'],$response['code']), 'error','Lcs-Cron-Buy-Notice-V2');
			// $sync_user_key = 'lcs_userbuynotice';
   //          $val = Yii::app()->redis_w->lpush($sync_user_key,$requestData['order_no']);
		}
	}
	/**
	 * 更改订单接口
	 * @param array requestData 传递的订单数据
	 * @return void
	 * @exception 订单失败
	 */
	public function requestByOrderChang($requestData){
		$token = $this->GetTokenCrm();
		if(empty($token)){
			$token = $this->GetTokenCrm();
		}
		$requestData['token'] = $token;
		var_dump($requestData);
		$header = [
			'content-type'=>'application/json; charset=UTF-8',
			'token'=>$token,
		];
		Yii::app()->curl->setHeaders($header);
		//请求的地址
		$requestUrl = Config::getConfig('crmUrl')."order/order/changeOrderProperty";
		//请求
		$response = json_decode(Yii::app()->curl->post($requestUrl,json_encode($requestData)),true);
		if($response["code"] == 0000 || $response['code'] == "9998"){
			echo "\r\n同步成功\r\n";
			Common::model()->saveLog(sprintf("修改订单changInfo%s成功%s",json_encode($requestData['changInfo']),json_encode($response)), 'info','Lcs-Cron-Chang-Notice-V2');
		}else{
			Common::model()->saveLog(sprintf("修改订单changInfo%s失败,错误原因:%s,错误代码:%s,请求参数:%s",json_encode($requestData['changInfo']),$response['message'],$response['code'],json_encode($requestData)), 'error','Lcs-Cron-Chang-Notice-V2');
			// $sync_user_key = 'userchangnotice';
   //          $val = Yii::app()->redis_w->lpush($sync_user_key,$requestData['changInfo']);
		}
	}
}