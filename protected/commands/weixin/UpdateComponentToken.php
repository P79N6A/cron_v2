<?php
/**
 * 更新微信第三方平台 component_access_token
 * Class WechatComponent
 *
 * @author danxian
 */

class UpdateComponentToken {

	const CRON_NO = 1902; //任务代码
	const REDIS_VERIFY_TICKET_KEY = 'wxtpf_verify_ticket'; //verify_ticket Redis缓存key
	const REDIS_ACCESS_TOKEN_KEY = 'wxtpf_component_access_token'; //component_access_token Redis缓存key

	public function process() {
		try{
			$wxapi = new WeixinComponentApi();
			$key = MEM_PRE_KEY.self::REDIS_VERIFY_TICKET_KEY;
			$ticket = Yii::app()->redis_r->get($key);
			$result = $wxapi->getComponentAccessToken($ticket);

			if (!empty($result['component_access_token'])) {
				$key = MEM_PRE_KEY.self::REDIS_ACCESS_TOKEN_KEY;
				Yii::app()->redis_w->set($key, $result['component_access_token']);
				//Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '已更新微信第三方平台component_access_token：'.json_encode($result));
			} else {
				Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '更新微信第三方平台component_access_token失败：'.json_encode($result));
			}

		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}
}
