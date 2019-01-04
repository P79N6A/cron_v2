<?php

/**
 * 批量更新微信公众号资料
 *
 * @author danxian
 */
class UpdateWxInfo
{
    const CRON_NO = 1904; //任务代码

    public function process()
    {
        try {
            $info_list = WeixinTS::model()->getWxInfoList();
            if (!empty($info_list)) {
                $com_access_token = WxComponentService::getComponentAccessToken();
                $com_api = new WeixinComponentApi();

                foreach ($info_list as $item) {
                    //获取公众号基本资料
                    $wx_info_res = $com_api->getAuthorInfo($item['app_id'], $com_access_token);
                    $authorizer_info = $wx_info_res['authorizer_info'];
                    $authorization_info = $wx_info_res['authorization_info'];
                    if (!empty($authorizer_info)) {

                        //获取用户授权的权限集id串
                        $func_info_arr = $authorization_info['func_info'];
                        $func_info = [];
                        if (!empty($func_info_arr)) {
                            foreach ($func_info_arr as $item) {
                                $func_info[] = $item['funcscope_category']['id'];
                            }
                            $func_info_str = join(',', $func_info);
                        } else {
                            $func_info_str = '';
                        }

                        $c_time = date(DATE_ISO8601);
                        $authorizer_appid = $authorization_info['authorizer_appid'];
                        $data = [
                            'nick_name' => $authorizer_info['nick_name'], //公众号昵称
                            'head_img' => empty($authorizer_info['head_img']) ? '' : $authorizer_info['head_img'], //公众号头像
                            'service_type_info' => $authorizer_info['service_type_info']['id'], //授权方公众号类型
                            'verify_type_info' => $authorizer_info['verify_type_info']['id'], //授权方认证类型
                            'user_name' => $authorizer_info['user_name'], //授权方公众号的原始ID
                            'alias' => empty($authorizer_info['alias']) ? '' : $authorizer_info['alias'], //授权方公众号所设置的微信号，可能为空
                            'business_info' => json_encode($authorizer_info['business_info']), //商家功能开通状态
                            'qrcode_url' => $authorizer_info['qrcode_url'], //公众号二维码图片
                            'func_info' => $func_info_str,
                            'u_time' => $c_time,
                        ];

                        $data['u_time'] = $c_time;
                        $res = WeixinTS::model()->updateWxtsInfoByAppId($authorizer_appid, $data);
                        if ($res < 1) {
                            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '更新微信公众号(app_id:'.$authorizer_appid.')资料失败：'.json_encode($wx_info_res));
                        }/* else {
                            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新微信公众号(app_id:'.$authorizer_appid.')成功：'.json_encode($wx_info_res));
                        }*/
                    } else {
                        Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '更新微信公众号资料失败：'.json_encode($wx_info_res));
                    }
                }
            }
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
    }
}