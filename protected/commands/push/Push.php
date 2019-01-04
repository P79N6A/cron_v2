<?php
/**
 * 调用集团推送基础类
 */

class Push
{
    private $extra = [
        "pageUrl"=>"", //通知栏图片地址
        "transmissionType"=>1, //透传类型(1为强制启动应用，客户端接收到消息后就会立即启动应用；2为等待应用启动)
        "transmissionContent"=>"",//透传内容
        "ring"=>true, //是否响铃
        "vibrate"=>false, //是否震动
        "clearable"=>false, //是否可清除
        "badge"=>0, //ios的badge
        "sound"=>"default", //ios的铃声
    ];
    private $sendjson = [
        'batchId'=>"",  //批次号
        'content'=>"", //消息内容
        'title'=>"", //推送标题
        'serviceProvider'=>"", //服务商
        'clientId'=>"" //设备号
    ];
    private $appsendinfo = [
        "buId"=>"",  //事业部签名id
        "createTime"=>"", //请求时间戳
        "msgType"=>2, //消息类型(1、调用短信2、调用推送接口)
        "pushWay"=>1, //推送方式（1、用户账号2、设备号）
        "pushType"=>5, //推送类型（3、全局广播，4、通过标签进行广播 5、指定用户）
    ];
    private $config = [
        'dev'=>[
            'secret'=>'rjhydx',
            'baseUrl'=>'http://192.168.3.137:8888/',
            'buId'=>'2001',
            'divId'=>'007',//事业部id
            'productId'=>'1701',//事业部应用app标示
        ],
        'pro'=>[

        ]
    ];
    public function getConfig($key){
        if(defined('ENV') && ENV == 'dev'){
            return $this->config['dev'][$key];
        }else{
            return $this->config['pro'][$key];
        }
    }
    /**
     * 推送
     * @param $pushData array 推送对象
     * 
     */
    public function pushByChannel($pushData){
        $this->setAppSendInfo("pushWay",$pushData['pushWay']);
        unset($pushData['pushWay']);
        if($this->extra["transmissionType"] == 0){
            $intent = "intent:#Intent;action=com.sina.licaishi.notification;launchFlags=0x4000000;package=cn.com.sina.licaishi.client;component=cn.com.sina.licaishi.client/com.sina.licaishi.ui.activity.MainTabActivity;i.type=".$pushData['type'].";S.content_client=".$pushData['content_client'].";i.child_relation_id=".$pushData['child_relation_id'].";i.relation_id=".$pushData['relation_id'].";end";
            $this->setExtra("transmissionContent",$intent);
        }
        $action = "appSend/sendAppAndSmsMsg";
        foreach ($pushData as $key => $value) {
            $this->setSendJson($key,$value);
        }
        //极光服务商设置属性
        if($pushData['serviceProvider'] == 205){
            $this->setAppSendInfo("deviceType",0);
        }
        $this->setAppSendInfo("buId",$this->getConfig("buId"));
        $this->setAppSendInfo("createTime",time());
        //初始化appsendinfo参数
        $object['appsendinfo'] = $this->appsendinfo;
        //初始化sendJson参数
        $object['appsendinfo']['sendJson'] = $this->sendjson;
        //初始化扩展参数
        $object['appsendinfo']['sendJson']['extra'] = $this->extra;
        $object = $object['appsendinfo'];
        $this->requestUrl($action,json_encode($object,JSON_UNESCAPED_UNICODE));
    }
    /**
     * 用户账号绑定
     * @param $userObject array 绑定对象
     * 
     */
    public function bindUser($user){
        $action = "binduser/bind";
        Common::model()->saveLog("用户账号绑定".json_encode($user,JSON_UNESCAPED_UNICODE),"info","lcsYtxPush_binduser");
        $this->requestUrl($action,json_encode($user,JSON_UNESCAPED_UNICODE));
    }
     /**
     * 请求数据
     */

    public function requestUrl($action,$params){
        try{
            $requestStep['params'] = $params;
            //build 系统参数
            $build = $this->build();
            $url = sprintf("%s%s?token=%s&sec=%s&rand=%s",$this->getConfig("baseUrl"),$action,$build['token'],$build['sec'],$build['rand']);
            $requestStep['url'] = $url;
            //使用json传输
            $header['content-type']="application/json; charset=UTF-8";
            $request = Yii::app()->curl->setHeaders($header);
            $response = $request->post($url,$params);
            $requestStep['response'] = $response;
            $responseArray = json_decode($response,true);
            if($responseArray['code'] != "000000"){
                //错误
                Common::model()->saveLog("接口返回错误".json_encode(['request'=>$requestStep,'response'=>$responseArray],JSON_UNESCAPED_UNICODE),"error","lcsYtxPush");
            }else{
                Common::model()->saveLog("成功".json_encode(['request'=>$requestStep,'response'=>$responseArray],JSON_UNESCAPED_UNICODE),"info","lcsYtxPush");
            }
            
        }catch(Exception $e){
            $error = LcsException::errorHandlerOfException($e)->toJsonString();
            Common::model()->saveLog("请求集团推送接口异常:".$error,"error","lcsYtxPush");
        }
    }
    /**
     * 构建系统参数
     */
    public function build(){
        $redis_key = MEM_PRE_KEY."ytx_push_token";
        $token = Yii::app()->redis_r->get($redis_key);
        $token = '07c6e47e-9532-44ca-93e6-42dd9ba62768';
        $build['token'] = $token;
        $build['rand'] = rand(1,999999);
        $md5data = $build['rand'].$this->getConfig('secret');
        $md5data = md5($md5data);
        $build['sec'] = substr($md5data,0,19);
        return $build;
    }
    /**
     * appsendinfo构建
     * @param $pushType 推送类型 （3、全局广播，4、通过标签进行广播 5、指定用户）
     * @param $pushWay 推送类型 （1、用户账号2、设备号）
     */
    /**
      * 设置appsendinfo
      */
      public function setAppSendInfo($key,$value){
        $this->appsendinfo[$key] = $value;
    }
     /**
      * 设置sendJson
      */
    public function setSendJson($key,$value){
        $this->sendjson[$key] = $value;
    }
    /**
     * 设置extra
     */
    public function setExtra($key,$value){
        $this->extra[$key] = $value;
    }
}
