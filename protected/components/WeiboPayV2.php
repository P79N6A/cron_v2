<?php
/**
 * 微博支付V2.0接口 * 
 * author :  meixin@staff.sina.com.cn 
 * 
 */

class WeiboPayV2 {
	
	//支付创建[v2删]
	//const WEIBO_PAY_WAIT = 'WAIT_BUYER_PAY';
	//等待支付
	const TRADE_PENDING = 'PAY_STATUS_PENDIG';
	//交易成功 卖家收到钱
	const WEIBO_PAY_SUCESS = 'PAY_STATUS_SUCCES';
	//交易完成 分润都操作也已经完成[v2删]
	//const WEIBO_PAY_FINISHED = 'TRADE_FINISHED';
	//超时关闭
	const WEIBO_PAY_CLOSED = 'PAY_STATUS_CLOSD';
	
	private $secret_key = "b77927faeb4bb90afa9c"; //支付私钥，申请支付时通过微博支付分配
    private $seller_id = "5178785227"; //商家id。收钱账号
    private $appkey = "1234567890"; // 微博开放平台的appkey
    private $rsa_private_key = "-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQDWwKPdn7HavaPwJJBEmSjA4s9Wx2puk1OBqOjIF1GGp8rjdgvk
SY6XRZL/6mrMGZFMybhPNHAIkndYOjkJe32RIeuiVJkRz9ryPvUoPsO9t/FuyD+m
l92MHdrEROa/YoNQnxB2jb5Sd8+hembitvx6R74ZDJeUN3NsUmBTOBGT5wIDAQAB
AoGBAJNjRVPOzW8WJpSjU4xxHYI8aBbj6E0Zuf9MIO9q6Z4kPOAd3Y2BWmbB7mDL
zx5jEdEExQU/NQD9HQGlfA3g/kzCcZ+a5mh/iWJFoUh0td2/8z8+IheyViHI5M6/
LUnMKJx/rgvXaaSdYtMDLmIH7XEMBCmBvBUyzI6GPF3ZPb8xAkEA76bSI5C/OHCn
hAmDAh3REGXCuyWdoIBuhiOhZ9eakrH8e3diEuu3uYiIcetzkEOBVpcUPZLBSBND
r4vtRX40eQJBAOVm/Z5TwY9RGit5K8GxHfkGmNKuiLpFi9T/oaYBMDKxz5NcF5Wy
BSzMoWtqgZ5ZtpNzieOSxvR87u+0vFYIM18CQDAfZ4MMxdknhfvVjSEXq6uHQ5sg
6o4YPBljfj3D5Z4fb3u7dU4nVzVCXWPCy+nkJEym+cGDfpxigez2RCb4OMECQQC1
DU/lgZ0mi4/n9749JJjPThGXVgC7YuA1v3vJFO8BU6zMVMaYcuP6s5ZCvNCINa4P
OgT+A33awC+kKDTsgZRpAkEAv99KQwCiEMcET3muRiXvVvPbR78o09/CG+Cl1wvQ
Nnuyt+NrOAMUkLSg9FmvYpokyM/RvlnKA0hC2M1ndpEg0A==
-----END RSA PRIVATE KEY-----";
    private $rsa_public_key = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDtZIYGv5q/MTxFg7BscFFssLuj
aRHryHNYQpfz4rND1pS11fcggT9AdP8K7XkOERoG/2IG1DBt3fvrpmD4fHH0iXMx
ilIJ1gAX6msHBdlhXjmQ9iq6emxNdrg5x0wHEmoF8pUmQvtXbqlQIUDqmTcbYSZ2
gPndrlCOaFfX87qVqwIDAQAB
-----END PUBLIC KEY-----";//微博公钥(通用的)

    //支付接口
    private $pay_url = "http://pay.sc.weibo.com/api/merchant/pay/cashier";//V2.0.4
    //支付请求异步回调接口
    private $notify_url = "http://licaishi.sina.com.cn/api/weiboPayNotifyV2?debug=1";
    //退款申请接口
    private $refund_url = "http://pay.sc.weibo.com/api/merchant/pay/refund/apply";//V2.0.4
    //退款申请异步回调接口
    private $refund_notify_url = "http://licaishi.sina.com.cn/api/weiboPayRefundNotifyV2";
    //支付单查询接口
    private $query_url = "http://pay.sc.weibo.com/api/merchant/pay/query";
    
    public function __construct(){
    	//测试环境
        /*
    	if(defined('ENV_DEV') && ENV_DEV == 1){
    		$this->refund_notify_url = "http://licaishi.sina.com.cn/test/planRefundV2";
    		$this->notify_url = "http://licaishi.sina.com.cn/test/planPayV2";

    	}
         * 
         */
    }
    
    /**
     * 返回支付的相关数据
     *
     * @param unknown_type $order_id
     * @param unknown_type $orderid
     * @param unknown_type $price
     * @param unknown_type $subject
     * @param unknown_type $body
     * @param unknown_type $show_url
     */
    public function getPayUrl($order_id,$price,$subject,$body,$show_url='http://licaishi.sina.com.cn/web/weiboPayResultV2'){
    	
    	if(empty($order_id) || $price<=0 || empty($subject) || empty($body) || empty($show_url))
    	{
    		return false;
    	}
    	$body = CommonUtils::getSubStr($body,20,'...');
    	$param_arr = array (
            'seller_id'     => $this->seller_id, 
            'appkey'        => $this->appkey,
            'out_pay_id'    => $order_id, 
            'subject'       => $subject, 
            'body'          => preg_replace("/\s+/",',',$body),  
            'return_url'    => $show_url, 
            'notify_url'    => $this->notify_url, 
            'total_amount'  => $price*100,
            'expire'        => '86400',
        );
        
        $sign_data = $this->getSignData($param_arr);
        $param_arr['sign'] =$this->generate_rsa_sign($sign_data);
        $param_arr["sign_type"] = "rsa";
        $pay_url = $this->pay_url.'?'.http_build_query($param_arr);
        return $pay_url;
    	
    }
    
    public function queryAccount($order_no , $pay_number=''){
        $params_arr = array(
            'seller_id' => $this->seller_id,
            'appkey'    => $this->appkey,
            'out_pay_id' => $order_no,
            'pay_id' => $pay_number,
        );
        $sign_data = $this->getSignData($params_arr);
        $params_arr['sign'] =$this->generate_rsa_sign($sign_data);
        $params_arr["sign_type"] = "rsa";
        try{
            $params_arr = array_filter($params_arr);
            $res = Yii::app()->curl->post($this->query_url,$params_arr);
            $result = json_decode($res , true);
            return $result;
        }catch (Exception $e){
            return "";
        }
    }
    
    public function requestPayNotifyUrl($params) {
        
        $params['from'] = 'backstage';  // 定时任务请求的回调
        //$sign_data = $this->getSignData($params);
        //$params["sign"] = md5($sign_data . $this->secret_key);
        //$params["sign_type"] = "md5";
        $params = array_filter($params);
        return Yii::app()->curl->post($this->notify_url,$params);
    }
    
    /**
     * 回调校验
     *
     * @param unknown_type $arr
     * @return unknown
     */
     public function verifyResult($arr) {
        
        $sign_type = $arr['sign_type'];
        $sign = $arr['sign'];
        
        unset($arr['sign_type']);
        unset($arr['sign']);
        $sign_data = $this->getSignData($arr);
        //$verify_key = md5($sign_data.$this->secret_key);
        //return $sign == $verify_key;
        $pub_key_id = openssl_pkey_get_public($this->rsa_public_key);
        $res = openssl_verify($sign_data, $sign , $pub_key_id);
        openssl_free_key($pub_key_id);
        return 1 == $res ;
    }
    
    /**
     * 组装所有参数
     *
     * @param unknown_type $param_arr
     * @return unknown
     */
    private  function getSignData($param_arr) {
        $pairs = array ();
        //按照字母字典对key进行排序
        ksort($param_arr);
        foreach ( $param_arr as $k => $v )
        {
            if (is_null($v)) continue;
            if ('' === $v) continue;
            $pairs[] = "$k=$v";
        }
        $sign_data = implode('&', $pairs);
        return $sign_data;
        
    }
    
    private function generate_rsa_sign($sign_data){
        //rsa加密
        $priv_key_id = openssl_pkey_get_private($this->rsa_private_key);
        openssl_sign($sign_data, $signature, $priv_key_id);
        openssl_free_key($priv_key_id);
        
        return base64_encode($signature);
    }
    
}
