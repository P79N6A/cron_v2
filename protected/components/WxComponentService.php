<?php

/**
 * 微信第三方平台相关服务封装
 * @author danxian
 */
class WxComponentService
{
    const REDIS_AUTHORIZER = 'wxtpf_authorizer_'; //authorizer 授权公众号相关配置缓存hash key
    const REDIS_ACCESS_TOKEN_KEY = 'wxtpf_component_access_token'; //component_access_token Redis缓存key

    /**
     * 获取已授权公众号接口调用authorize_access_token，过期自动更新
     *
     * @param $authorizer_appid
     * @return bool|string
     */
    public static function getAuthAccessToken($authorizer_appid)
    {
        $authorizer_hash_key = MEM_PRE_KEY . self::REDIS_AUTHORIZER . $authorizer_appid;
        $redis = Yii::app()->redis_r;
        $expires_time = $redis->hget($authorizer_hash_key, 'expires_time');
        $component_api = new WeixinComponentApi();
        if (time() >= $expires_time) {

        } else {
            //未过期直接取缓存
            return $redis->hget($authorizer_hash_key, 'authorizer_access_token');
        }
    }

    /**
     * @return mixed
     */
    public static function getComponentAccessToken()
    {
        $token_key = MEM_PRE_KEY.self::REDIS_ACCESS_TOKEN_KEY;
        $token = Yii::app()->redis_r->get($token_key);

        return $token;
    }

    /**
     * 存储授权凭据
     *
     * @param $auth_app_id
     * @param $auth_access_token
     * @param $refresh_token
     * @param $expires_in
     */
    public static function setAuthAccessToken($auth_app_id, $auth_access_token, $refresh_token, $expires_in)
    {
        $authorizer_hash_key = MEM_PRE_KEY . self::REDIS_AUTHORIZER . $auth_app_id;
        //哈希结构存储授权方公众号接口调用凭据
        $redis_w = Yii::app()->redis_w;
        $redis_w->hset($authorizer_hash_key, 'authorizer_access_token', $auth_access_token);
        $redis_w->hset($authorizer_hash_key, 'authorizer_refresh_token', $refresh_token);
        $redis_w->hset($authorizer_hash_key, 'expires_time', time() + $expires_in);
    }

}