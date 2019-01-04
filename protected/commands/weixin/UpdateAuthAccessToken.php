<?php
/**
 * 更新公众号授权access_token
 *
 * @author danxian
 */

class UpdateAuthAccessToken
{
	const CRON_NO = 1905; //任务代码
	const REDIS_PRE_AUTH_CODE_KEY = 'wxtpf_pre_auth_code'; //pre_auth_code Redis缓存key
	const REDIS_ACCESS_TOKEN_KEY = 'wxtpf_component_access_token'; //component_access_token Redis缓存key

    public function process()
    {
        try {
            $info_list = WeixinTS::model()->getWxInfoList();
            if (!empty($info_list)) {
                foreach ($info_list as $item) {
                    $this->authAccessTokenHandler($item['app_id']);
                }
            }/* else {
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新微信公众号：暂无授权微信公众号');
            }*/
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
    }

    /**
     * 更新单个微信公众号的授权token
     * @param $auth_app_id
     */
    public function authAccessTokenHandler($auth_app_id)
    {
        $com_access_token = WxComponentService::getComponentAccessToken();
        $com_api = new WeixinComponentApi();
        $auth_hash_key = MEM_PRE_KEY . WxComponentService::REDIS_AUTHORIZER . $auth_app_id;
        $refresh_token = Yii::app()->redis_r->hget($auth_hash_key, 'authorizer_refresh_token');
        //刷新公众号授权authorize_access_token
        $refersh_token_res = $com_api->refreshAuthAccessToken($com_access_token, $auth_app_id, $refresh_token);
        if (!empty($refersh_token_res['authorizer_access_token']) && !empty($refersh_token_res['authorizer_refresh_token'])) {
            WxComponentService::setAuthAccessToken($auth_app_id, $refersh_token_res['authorizer_access_token'], $refersh_token_res['authorizer_refresh_token'], $refersh_token_res['expires_in']);
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新微信公众号(app_id:'.$auth_app_id.')authorizer_access_token成功：'.json_encode($refersh_token_res));
        } else {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '更新微信公众号(app_id:'.$auth_app_id.')authorizer_access_token失败：'.json_encode($refersh_token_res));
        }
    }

}