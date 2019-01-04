<?php
/**
 * 微信的服务
 * 1. 更新accessToken   发送模板消息
 * 2. 接口返回  errcode 0正常 其他错误  errmsg 错误内容
 * User: zwg
 * Date: 2015/11/12
 * Time: 13:22
 */



class WeixinApi {

    private $api_base_url='https://api.weixin.qq.com/cgi-bin';

    private $appId = "wx2c2404e4ef0bc910";
    private $appSecret = "5326e4565f0b8ccaf04d90f499c52ca7";
    private $redirect_uri = 'http://licaishi.sina.com.cn/weixin/publicPlatform/oauth2Callback';
    private $mc_access_token = 'lcs_weixin_access_token';
    private $mc_jsapi_ticket = 'lcs_weixin_jsapi_ticket';

    private $appIdGodStock = "wx599e5d89a828a279";
    private $appSecretGodStock = "0fb00cd9053ae4e1892a482f1f79a8fd";
    private $redirect_uri_god_stock = 'http://licaishi.sina.com.cn/weixinbwgs/publicPlatform/oauth2Callback';
    private $mc_access_token_god_stock = 'lcs_weixin_access_token_god_stock';
    private $mc_jsapi_ticket_god_stock = 'weixin_bwgs_jsapi_ticket';

    //测试环境
    private $appIdGodStockDev = "wx47348ac948b8b979";
    private $appSecretGodStockDev = "4210e185d4d6046396c4227d23bd8e12";
    private $appIdDev = "wxc69abb4b3cc4c857";
    private $appSecretDev = "36f4403e59f3dae543e88f9e4973d336";

    //小程序配置
    private $xcxAppId = "wx166d1d432d032c87";
    private $xcxAppSecert= "19d7a175370f1493bf556025909a8b92";
    private $xcx_access_token = 'lcs_xcx_access_token';

    /**
     * 更新微信公众平台token和js票据
     * @return array
     */
    public function updateAccessToken(){
        $result = array('errcode'=>0);
        try {
            if(defined('ENV') && ENV == 'dev'){
                $url=$this->api_base_url."/token?grant_type=client_credential&appid=".$this->appIdDev."&secret=".$this->appSecretDev;
            }else{
                $url=$this->api_base_url."/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
            }
            $json=Yii::app()->curl->get($url);
            $data= !empty($json)?json_decode($json,true):array();
            if(isset($data['access_token']) && !empty($data['access_token'])){
                Yii::app()->cache->set($this->mc_access_token,$data['access_token'],7000);
                Yii::app()->redis_w->setex($this->mc_access_token,7000,$data['access_token']);
                $result['token']=$data['access_token'];

                //获取微信jsapi_ticket
                $url=$this->api_base_url."/ticket/getticket?type=jsapi&access_token=".$data['access_token'];
                $json_js=Yii::app()->curl->get($url);
                $data_js= !empty($json_js)?json_decode($json_js,true):array();
                if(isset($data_js['ticket']) && !empty($data_js['ticket'])){
                    Yii::app()->cache->set($this->mc_jsapi_ticket,$data_js['ticket'],7000);
                    Yii::app()->redis_w->setex($this->mc_jsapi_ticket,7000,$data_js['ticket']);
                    $result['ticket']=$data_js['ticket'];
                }else{
                    $result['errcode']=-1;
                    $result['errmsg']='get weixin ticket fail, result:'.$json_js;
                }
            }else{
                $result['errcode']=-1;
                $result['errmsg']='get weixin token fail, result:'.$json;
            }
        }catch (Exception $e){
            $result['errcode']=-1;
            $result['errmsg']='get weixin fail, result:'.$e->getMessage();
        }

        return $result;

    }
    /**
     * 更新微信公众平台token和js票据(百万股神)
     * @return array
     */
    public function updateAccessTokenGodStock(){
        $result = array('errcode'=>0);
        try {
            //测试环境
            if(defined('ENV') && ENV == 'dev'){
                $url=$this->api_base_url."/token?grant_type=client_credential&appid=".$this->appIdGodStockDev."&secret=".$this->appSecretGodStockDev;
            }else{
                $url=$this->api_base_url."/token?grant_type=client_credential&appid=".$this->appIdGodStock."&secret=".$this->appSecretGodStock;
            }
            
            $json=Yii::app()->curl->get($url);
            echo $url."\r\n";
            echo "更新百万股神token:".$json."\r\n";
            $data= !empty($json)?json_decode($json,true):array();
            if(isset($data['access_token']) && !empty($data['access_token'])){
                Yii::app()->cache->set($this->mc_access_token_god_stock,$data['access_token'],7000);
                Yii::app()->redis_w->setex($this->mc_access_token_god_stock,7000,$data['access_token']);
                $result['token']=$data['access_token'];

                //获取微信jsapi_ticket
                $url=$this->api_base_url."/ticket/getticket?type=jsapi&access_token=".$data['access_token'];
                $json_js=Yii::app()->curl->get($url);
                $data_js= !empty($json_js)?json_decode($json_js,true):array();
                if(isset($data_js['ticket']) && !empty($data_js['ticket'])){
                    Yii::app()->cache->set($this->mc_jsapi_ticket_god_stock,$data_js['ticket'],7000);
                    Yii::app()->redis_w->setex($this->mc_jsapi_ticket_god_stock,7000,$data_js['ticket']);
                    $result['ticket']=$data_js['ticket'];
                }else{
                    $result['errcode']=-1;
                    $result['errmsg']='get weixin ticket fail, result:'.$json_js;
                }
            }else{
                $result['errcode']=-1;
                $result['errmsg']='get weixin token fail, result:'.$json;
            }
        }catch (Exception $e){
            $result['errcode']=-1;
            $result['errmsg']='get weixin fail, result:'.$e->getMessage();
        }

        return $result;

    }

    /**
     * 更新小程序accessToken
     * @return array
     */
    public function updateAccessTokenXcx(){
        $result = array('errcode'=>0);
        try {
            $url=$this->api_base_url."/token?grant_type=client_credential&appid=".$this->xcxAppId."&secret=".$this->xcxAppSecert;
            $json=Yii::app()->curl->get($url);
            echo $url."\r\n";
            echo "更新新浪理财师小程序token:".$json."\r\n";
            $data= !empty($json)?json_decode($json,true):array();
            if(isset($data['access_token']) && !empty($data['access_token'])){
                Yii::app()->redis_w->setex($this->xcx_access_token,7000,$data['access_token']);
                $result['token']=$data['access_token'];
            }else{
                $result['errcode']=-1;
                $result['errmsg']='get weixin token fail, result:'.$json;
            }
        }catch (Exception $e){
            $result['errcode']=-1;
            $result['errmsg']='get weixin fail, result:'.$e->getMessage();
        }

        return $result;

    }


    /**
     * 发送微信的模板消息
     * @param $message
     * @return array|mixed
     */
    public function sendTemplateMessage($message){
        $result = array('errcode'=>-1);
        //判断推送公众号
        echo "====================================\r\n";
        //判断推送公众号
//        if(isset($message['pushWechat']) && $message["pushWechat"]==15){
//            echo "type:".$message['pushWechat']."\r\n";
//            if($message["pushWechat"] != 1) {
          $this->mc_access_token = $this->mc_access_token_god_stock;
//            }
//            unset($message['pushWechat']);
//        }
        unset($message['pushWechat']);
        echo "缓存:".$this->mc_access_token."\r\n";

        $token = Yii::app()->cache->get($this->mc_access_token);
        if(empty($token)){
            $token = Yii::app()->redis_r->get($this->mc_access_token);
            if(empty($token)){
                $result['errmsg']='token is null';
                return $result;
            }
        }
        
        echo "token:".$token."\r\n";

        $url = $this->api_base_url."/message/template/send?access_token=".$token;

        if(is_array($message)){
            $message = json_encode($message,JSON_UNESCAPED_UNICODE);
        }
        try {
            $wx_res = Yii::app()->curl->setTimeOut(10)->post($url, $message);

            if(!empty($wx_res)){
                $result = json_decode($wx_res,true);
                if(isset($wx_res['errcode']) && $wx_res['errcode']=='40001'){
                    $wx_res['access_token'] = $token;
                }
            }
        }catch (Exception $e){
            $result['errmsg'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * 发送小程序模板消息
     * @param $message
     * @return array|mixed
     */
    public function sendXcxTemplateMessage($message){
        $result = array('errcode'=>-1);
        //判断推送公众号
        echo "====================================\r\n";
        //判断推送公众号
        $this->mc_access_token = $this->xcx_access_token;
        unset($message['pushWechat']);
        echo "缓存:".$this->xcx_access_token."\r\n";

        $token = Yii::app()->redis_r->get($this->xcx_access_token);
        if(empty($token)){
            $result['errmsg']='token is null';
            return $result;
        }
        echo "token:".$token."\r\n";

        $url = $this->api_base_url."/message/wxopen/template/send?access_token=".$token;

        if(is_array($message)){
            $message = json_encode($message,JSON_UNESCAPED_UNICODE);
        }
        try {
            $wx_res = Yii::app()->curl->setTimeOut(10)->post($url, $message);
            if(!empty($wx_res)){
                $result = json_decode($wx_res,true);
                if(isset($wx_res['errcode']) && $wx_res['errcode']=='40001'){
                    $wx_res['access_token'] = $token;
                }
            }
        }catch (Exception $e){
            $result['errmsg'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * 发送微信的客服消息
     * @param $message
     * @return array|mixed
     */
    public function sendCustomMessage($message){
        $result = array('errcode'=>-1);
        //判断推送公众号
        //if(isset($message['pushWechat']) && $message["pushWechat"]==15){
        echo "type:".$message['pushWechat']."\r\n";
          //  if($message["pushWechat"] != 1) {
        $this->mc_access_token = $this->mc_access_token_god_stock;
            //}
        //}
        unset($message['pushWechat']);
        echo "缓存:".$this->mc_access_token."\r\n";

        $token = Yii::app()->cache->get($this->mc_access_token);
        echo "token:".$token."\r\n";

        if(empty($token)){
            $token = Yii::app()->redis_r->get($this->mc_access_token);
            if(empty($token)){
                $result['errmsg']='token is null';
                return $result;
            }
        }


        $url = $this->api_base_url."/message/custom/send?access_token=".$token;

        if(is_array($message)){
            $message = json_encode($message,JSON_UNESCAPED_UNICODE);
        }
        try {
            echo "发送数据\r\n";
            var_dump($message);
            $wx_res = Yii::app()->curl->setTimeOut(10)->post($url, $message);

            if(!empty($wx_res)){
                $result = json_decode($wx_res,true);
                if(isset($wx_res['errcode']) && $wx_res['errcode']=='40001'){
                    $wx_res['access_token'] = $token;
                }
            }
        }catch (Exception $e){
            $result['errmsg'] = $e->getMessage();
        }
        return $result;
    }


    /**
     * 获取Oauth授权url地址
     * @param string $state  状态数据
     * @return string
     */
    public function getOauth2Url($state='',$pushWechat){
        if($pushWechat == 15){
            //测试环境
            if(defined('ENV') && ENV == 'dev'){
                $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appIdGodStockDev.'&redirect_uri='.$this->redirect_uri_god_stock.'&response_type=code&scope=snsapi_base&state='.$state.'#wechat_redirect';

            }else{
                $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appIdGodStock.'&redirect_uri='.$this->redirect_uri_god_stock.'&response_type=code&scope=snsapi_base&state='.$state.'#wechat_redirect';
            }
        }else{
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appId.'&redirect_uri='.$this->redirect_uri.'&response_type=code&scope=snsapi_base&state='.$state.'#wechat_redirect';
        }
        echo "Oauth授权链接:".$url."\r\n推送微信公众号:".$pushWechat."\r\n";
        return $url;
    }
}
