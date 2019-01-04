<?php

/**
 * 
 * Description of SpnsapiPush
 * @datetime 2015-11-12  14:03:40
 * @author hailin3
 */
class SpnsApi {
    
    const ANDROIDAPPID = 6016;
    const IOSAPPID = 119;
    const IOS_SANDBOX_APP_ID = 153;
    const PLANNER_ANDROID_APP_ID = 6029;
    const PLANNER_IOS_SANDBOX_APP_ID = 149;
    const PLANNER_IOS_APP_ID = 152;
    const PLANNER_COMPANY_IOS_APP_ID = 158;
    const SPNSAPI = 'http://spnsapi.mp.sina.com.cn/api';
    private $result = array('status'=>0,'data'=>'','error'=>'');  //0错误  1成功

   /**
    * 推送消息方法
    * @param type $ops 推送类型
    * @param type $appid app的id
    * @param type $type  设备类型，如1为ios，2为android
    * @param type $pushid 指定下发的token，数量小于等于500个，按逗号分隔。
    * @param type $data   下发消息
    */
    private function sendMsg($ops, $appid, $type, $pushid, $data) {        
        $params = array(
            "ops" => $ops,
            "appid" => $appid,
            "type" => $type,
            "pushid" => is_array($pushid) ? implode(',', $pushid) : $pushid,
            "message" => json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $result = array('status'=>0,'data'=>'','error'=>'');
        try {
            $result_str = Yii::app()->curl->post(self::SPNSAPI, $params);
            $result_json = json_decode($result_str,true);
            if($result_json['status'] == 'success'){
                $result['status'] = 1;
                $result['data'] = $result_json['data'];
            }else{
                $result['error'] = $result_json['error'];
            }
        } catch (Exception $e) {
            //防止出现死循环，所有为直接调用本方法
            if(strpos($e->getMessage(), 'Operation timed out') === 0){
                //休眠1秒钟
                sleep(1);
                try {
                    $result_str = Yii::app()->curl->post(self::SPNSAPI, $params);
                    $result_json = json_decode($result_str,true);
                    if($result_json['status'] == 'success'){
                        $result['status'] = 1;
                        $result['data'] = $result_json['data'];
                    }else{
                        $result['error'] = $result_json['error'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 'appid:'.$appid.' type:'.$appid.' error:'.$e->getMessage();
                }
            }else{
                $result['error'] = 'appid:'.$appid.' type:'.$appid.' error:'.$e->getMessage();
            }
        }
        return $result;
    }

    /**
     * 安卓推送
     * @param int $push_u_type  1用户   2理财师
     * @param array $push_id  推送id
     * @param array $msg 消息体
     * @return type 
     */
    public function pushAndroid($push_u_type, $push_id, $msg) {
        $result = array('status'=>0,'data'=>'','error'=>'');
        if(!is_array($msg) || !isset($msg['type']) || empty($push_id)){
            $result['error'] = 'param error';
            return $result;
        }
        $app_id=0;
        $push_type=2;
        if($push_u_type==1){
            $app_id=self::ANDROIDAPPID;
        }else{
            if(defined('ENV')&&ENV=='dev'){
                $app_id=self::PLANNER_ANDROID_APP_ID;
            }else{
                $app_id=self::PLANNER_ANDROID_APP_ID;
            }

        }
        $ops = 'android_push2';
        $data = array(
            "expire" => '', 
            "message" => array(
                "app-id" => $app_id,
                "extra" => array(
                    "content" => $msg,
                    "handle_by_app" => 1
                ),
            ),
            "category" => $msg['type'],
            "offline_priorit" => 1
        );
        $result = $this->sendMsg($ops, $app_id, $push_type, $push_id, $data);
        $result['push_data']=$data;
        return $result;
    }

    /**
     * IOS推送
     * @param int $push_u_type  1用户   2理财师
     * @param array $push_id  推送id
     * @param array $msg 消息
     * @param array $ext 扩展字段
     * @return type
     */
    public function pushIos($push_u_type, $push_id, $msg,$ext = array()) {
        $result = array('status'=>0,'data'=>'','error'=>'');
        if(!is_array($msg) || !isset($msg['type']) || empty($push_id) || !isset($msg['alert'])){
            $result['error'] = 'param error';
            return $result;
        }
        $app_id=0;
        $push_type=1;
        if($push_u_type==1){
            if(defined('ENV')&&ENV=='dev'){
                $app_id=self::IOS_SANDBOX_APP_ID;
                $push_type=4; //沙箱的push类型
            }else{
                $app_id=self::IOSAPPID;
            }
        }else{
            if(defined('ENV')&&ENV=='dev'){
                $app_id=self::PLANNER_IOS_SANDBOX_APP_ID;
                $push_type=4; //沙箱的push类型
            }else{
                //$app_id=self::PLANNER_COMPANY_IOS_APP_ID;
                $app_id=self::PLANNER_IOS_APP_ID;
            }

        }

        $ops = 'ios_push2';
        $data = array(
            "payload" => array(
                "aps" => array(
                    "sound" => "default",
                    "badge" => 1,
                    "alert" => rawurlencode($msg['alert'])
                ),
                "type" => $msg['type'],
            ),
            "appid" => $app_id,
        );
        if(!empty($ext)){
            foreach ($ext as $key=>$value){
                $data['payload'][$key] = $value;
            }
        }
        $result = $this->sendMsg($ops, $app_id, $push_type, $push_id, $data);

        $result['push_data']=$data;

        return $result;
    }

}
