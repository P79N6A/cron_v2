<?php
/**
 * 更新微信第三方平台 pre_auth_code
 * Class UpdatePreAuthCode
 *
 * @author danxian
 */

class UpdatePreAuthCode
{
	const CRON_NO = 1903; //任务代码
	const REDIS_PRE_AUTH_CODE_KEY = 'wxtpf_pre_auth_code'; //pre_auth_code Redis缓存key
	const REDIS_ACCESS_TOKEN_KEY = 'wxtpf_component_access_token'; //component_access_token Redis缓存key

	public function process() {
		try{
			$wxapi = new WeixinComponentApi();
			$token_key = MEM_PRE_KEY.self::REDIS_ACCESS_TOKEN_KEY;
			$token = Yii::app()->redis_r->get($token_key);
			if (empty($token)) {
				Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '从redis中获取component_access_token失败');
				Yii::app()->end();
			}

			$result = $wxapi->getPreAuthCode($token);

			if (!empty($result['pre_auth_code'])) {
				$key = MEM_PRE_KEY.self::REDIS_PRE_AUTH_CODE_KEY;
				Yii::app()->redis_w->set($key, $result['pre_auth_code']);
				//Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '已更新微信第三方平台pre_auth_code：'.json_encode($result));
			} else {
				Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '更新微信第三方平台pre_auth_code失败：'.json_encode($result));
			}

		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}
}