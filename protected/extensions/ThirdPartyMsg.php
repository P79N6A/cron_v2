<?php

/**
 * 第三方消息推送
 */
class ThirdPartyMsg
{
    //测试环境
    //const THIRD_API_URL = "https://tglmcs.ghzq.com.cn:7497/finder/api";
    //国海消息API地址
    const GUOHAI_API_URL = "https://portal.ghzq.com.cn/finder/api";

    /**
     * 发送微信的模板消息
     * @param $message
     * @return array|mixed
     */
    public function sendGuoHaiTemplateMessage($message) {
        $result = array('errcode'=>-1);
        $message_data = array();
        if (is_array($message)) {
            $message_data = array(
                'cmd' => 'guitaisv.weixinPushMessage',
                'param' => array(
                    'channel'    => 'sina',
                    'messageId'  => $message['message_id'],
                    'templateId' => $message['template_id'],
                    'openid'     => $message['touser'],
                    'content'    => $message['data'],
                    'url'        => $message['url'],
                )
            );
            $message_data = json_encode($message_data, JSON_UNESCAPED_UNICODE);
        }

        try {
            $wx_res = Yii::app()->curl->post(self::GUOHAI_API_URL, $message_data);
            if (!empty($wx_res)) {
                $res = json_decode($wx_res,true);
                if (!isset($res['result'])) {
                    throw new Exception('第三方消息推送接口调用失败#1：' . $wx_res);
                }
                //guohai api result 0表失败
                elseif ($res['result'] == '0') {
                    throw new Exception('第三方消息推送接口调用失败#2：' . $wx_res);
                }
                //errocode=0表成功
                $result['errcode'] = 0;
                $result['msgid'] = $res['infoid'];
            } else {
                throw new Exception('第三方消息推送接口调用失败#3：无返回数据' . $wx_res);
            }
        } catch (Exception $e) {
            $result['errmsg'] = $e->getMessage();
        }

        return $result;
    }
}