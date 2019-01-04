<?php

/**
 * 微信第三方平台相关接口封装
 *
 * @link https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&id=open1453779503&token=&lang=zh_CN
 * @author danxian
 * @date 2016/9/8
 */
class WeixinComponentApi
{

	/**
	 * @var bool 调试模式
	 */
	public $debug = true;

	/**
	 * @var array 解析后的微信消息内容
	 */
	public $msg = array();

	/**
	 * @var array 需要发送的消息体
	 */
	public $sMsg = array();

	/**
	 * @var string 公众号第三方平台 appId
	 */
	private $_component_app_id = "wx2760e0633e9db96d";

	/**
	 * @var string 公众号第三方平台 appSecret
	 */
	private $_component_app_secret = "317564ae964e35ba68ebed326fff8833";

	/**
	 * @var string 组件方调用接口路径
	 */
	private $_api_base_url = 'https://api.weixin.qq.com/cgi-bin/component/';

	/**
	 * @var string 调用公众号接口路径
	 */
	private $_public_api_base_url = 'https://api.weixin.qq.com/cgi-bin/';

	/**
	 * @var curl对象
	 */
	private $_curl;

	/**
	 * Component constructor.
	 */
	public function __construct()
	{
		$this->_curl = Yii::app()->curl;
	}

	/**
	 * 获取第三方平台授权令牌 7200s过期
	 *
	 * @param $verify_ticket 微信服务器推送的票据
	 * @return mixed
	 */
	public function getComponentAccessToken($verify_ticket)
	{
        $result['errcode'] = -1;
		$url = $this->_api_base_url . 'api_component_token';
		$data = [
			"component_appid" => $this->_component_app_id,
			"component_appsecret" => $this->_component_app_secret,
			"component_verify_ticket" => $verify_ticket
		];
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		try {
			$wx_res = $this->_curl->post($url, $data);
			if (!empty($wx_res)) {
				$res_json = json_decode($wx_res, true);
				if (isset($res_json['component_access_token']) && !empty($res_json['component_access_token'])) {
					$result = $res_json;
				} else {
				    $result['errmsg'] = 'get weixin ComponentAccessToken error,response:'.var_export($res_json, true);
                }
			} else {
				$result['errmsg'] = 'get weixin fail, no data';
			}

		} catch (Exception $e) {
			$result['errcode'] = -1;
			$result['errmsg'] = 'get weixin ComponentAccessToken Exception, result:'.$e->getMessage();
		}

		return $result;
	}

	/**
	 * 获取预授权码 1800s过期(官方文档600s)
	 *
	 * @param $component_access_token
	 * @return mixed
	 */
	public function getPreAuthCode($component_access_token)
	{
		$url = $this->_api_base_url . 'api_create_preauthcode?component_access_token=' . $component_access_token;
		$data = ["component_appid" => $this->_component_app_id];
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		try {
			$wx_res = $this->_curl->post($url, $data);
			if (!empty($wx_res)) {
				$res_json = json_decode($wx_res, true);
				if (isset($res_json['pre_auth_code']) && !empty($res_json['pre_auth_code'])) {
					$result = $res_json;
				} else {
					$result['errcode'] = -1;
					$result['errmsg'] = 'get weixin fail, no pre_auth_code '.$wx_res;
				}
			} else {
				$result['errcode'] = -1;
				$result['errmsg'] = 'get weixin fail, no data';
			}
		} catch (Exception $e) {
			$result['errcode'] = -1;
			$result['errmsg'] = 'get weixin fail, result:'.$e->getMessage();
		}

		return $result;
	}

	/**
	 * 获取授权公众号信息
	 * @param $authorizer_appid
	 * @param $component_access_token
     *
     * @return mixed
	 */
	public function getAuthorInfo($authorizer_appid, $component_access_token)
	{
		$url = $this->_api_base_url . 'api_get_authorizer_info?component_access_token=' . $component_access_token;
		$data = [
			'component_appid' => $this->_component_app_id,
			'authorizer_appid' => $authorizer_appid,
		];
        $data = json_encode($data);
        $result['errcode'] = -1;
		try {
			$wx_res = $this->_curl->post($url, $data);

            if (!empty($wx_res)) {
                $res_json = json_decode($wx_res, true);
                return $res_json;
            } else {
                $result['errmsg'] = 'get authorizer info return null';
            }

        } catch (Exception $e) {
            $result['errmsg'] = $e->getMessage();
            $result['errcode'] = -1;
            return $result;
        }

	}

	/**
	 * 保存日志
	 * @param $log
	 * @param int $level
	 */
	public function log($log, $level =  CLogger::LEVEL_INFO) {
		CommonUtils::saveLog(var_export($log, true) . "\n\r", $level, 'wxtpf');
	}

	/**
	 * 刷新authorizer_access_token
	 *
	 * @param $com_access_token
	 * @param $auth_app_id
	 * @param $refresh_token
	 * @return bool|mixed
	 */
	public function refreshAuthAccessToken($com_access_token, $auth_app_id, $refresh_token)
	{
        $result = array('errcode' => -1);
		$url = $this->_api_base_url . 'api_authorizer_token?component_access_token=' . $com_access_token;
		$data = [
            'component_appid' => $this->_component_app_id,
            'authorizer_appid' => $auth_app_id,
            'authorizer_refresh_token' => $refresh_token,
		];
		$data = json_encode($data);
		try {
			$wx_res = $this->_curl->post($url, $data);
			if (!empty($wx_res)) {
				$res_json = json_decode($wx_res, true);
				if (isset($res_json['authorizer_access_token']) && !empty($res_json['authorizer_access_token'])) {
					$result = $res_json;
				} else {
                    $result['errmsg'] = 'refresh authorizer error, response:' . var_export($res_json, true);
                }
			} else {
                $result['errmsg'] = 'refresh authorizer token response null';
            }
		} catch (Exception $e) {
            $result['errmsg'] = $e->getMessage();
		}

        return $result;
	}

	/**
     * 发送微信的模板消息
	 *
     * @param $message
     * @param $auth_app_id 授权的公众号的app_id
     * @return array|mixed
     */
    public function sendTemplateMessage($message, $auth_app_id) {
        $result = array('errcode'=>-1);
        if (empty($auth_app_id)) {
            $result['errmsg']='app_id is null';
            return $result;
        }

	    $token = WxComponentService::getAuthAccessToken($auth_app_id);
	    $url = $this->_public_api_base_url . "/message/template/send?access_token=" . $token;

        if (is_array($message)) {
            $message = json_encode($message,JSON_UNESCAPED_UNICODE);
        }

        try {
            $wx_res = Yii::app()->curl->post($url, $message);

            if (!empty($wx_res)) {
                $result = json_decode($wx_res,true);
                if (isset($result['errcode'])) {
                    $result['errmsg'] = 'wxtpf send tplMsg error response:'.var_export($result, true);
                }
            } else {
                $result['errmsg'] = 'wxtpf send tplMsg api response null';
            }
        }catch (Exception $e){
            $result['errmsg'] = $e->getMessage();
        }
        return $result;
    }


}