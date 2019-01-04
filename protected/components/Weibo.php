<?php
/**
 * 微博.
 * User: bianjichao
 * Date: 15/5/28
 * Time: 上午11:09
 */

class Weibo {
    private $secret_key = "b77927faeb4bb90afa9c"; //支付私钥，申请支付时通过微博支付分配
    private $seller_id = "5178785227"; //商家id。收钱账号
    private $source = "1234567890"; // 微博开放平台的appkey

    //支付请求异步回调接口
    private $notify_url = "http://licaishi.sina.com.cn/api/weiboPayNotify";
    //对账接口地址
    private $query_url = "http://api.sc.weibo.com/v2/pay/accountquery";
    //private $query_url = "http://jichao3.sina.com.cn/test/AccountList";

    /**
     * 查询对账单
     * @param string $out_trade_no  商户业务单据编号，与out_trade_no、gmt_start_time、gmt_end_time不能同时存在
     * @param string $gmt_start_time 查询需要对账开始时间，格式为“yyyy-MM-dd HH:mm:ss”
     * @param string $gmt_end_time 查询需要对账结束时间，格式为“yyyy-MM-dd HH:mm:ss”
     * @param string $trade_status 支付状态，支付创建-WAIT_BUYER_PAY/等待支付-TRADE_PENDING/已支付-TRADE_SUCCESS/超时关闭-TRADE_CLOSED/支付结束-TRADE_FINISHED，其它TBD.为空则返回全部，否则按照支付单状态筛选.
     * @param string $pageno 查询页号
     * @param string $page_size 分页大小，默认20，最大100，且pageno*page_size最多5000
     * @return string
     */
    public function queryAccountList($out_trade_no='',$gmt_start_time='',$gmt_end_time='',$trade_status='',$pageno='',$page_size=''){
        $params = array(
            'source' => $this->secret_key,
            'uid' => $this->seller_id,
            'out_trade_no' => $out_trade_no,
            'gmt_start_time' => $gmt_start_time,
            'gmt_end_time' => $gmt_end_time,
            'trade_status' => $trade_status,
            'pageno' => $pageno,
            'page_size' => $page_size
        );

        $sign_data = $this->getSignData($params);
        $params["sign"] = md5($sign_data.$this->secret_key);
        $params["sign_type"] = "md5";

        try{
            $params = array_filter($params);
            return Yii::app()->curl->post($this->query_url,$params);
        }catch (Exception $e){
            return "";
        }

    }

    /**
     * 请求支付回调
     * @param $params
     * @return mixed
     */
    public function requestPayNotifyUrl($params){
        $sign_data = $this->getSignData($params);
        $params["sign"] = md5($sign_data.$this->secret_key);
        $params["sign_type"] = "md5";

        $params = array_filter($params);
        return Yii::app()->curl->post($this->notify_url,$params);
    }
/*
    private function getSignData($params){
        $params = array_filter($params);
        ksort($params);
        return http_build_query($params);
    }
*/

    private  function getSignData($param_arr) {
        $param_arr = array_filter($param_arr);
        $pairs = array ();
        //按照字母字典对key进行排序
        ksort($param_arr);
        foreach ( $param_arr as $k => $v )
        {
            $pairs[] = "$k=$v";
        }
        $sign_data = implode('&', $pairs);
        return $sign_data;
    }


}