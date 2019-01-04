<?php
/**
 * 获取智选股token
 */
class GetToken
{
	/**
	 * 获取token
	 * @return string
	 */
	public function GetTokenCrm(){
		$requestUrl = Config::getConfig("crmUrl")."api/login";
		$params = array(
			"buCode"=>Config::getConfig("buCode"),
			"signature"=>Config::getConfig("signature"),
		);
		$response = json_decode(Yii::app()->curl->post($requestUrl,$params),true);
		if($response['code'] == "0000"){
			return $response['data']['token'];
		}
		return ;
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
	 * Yii::app()->curl->post($requestUrl,$params);
	 */
}