<?php
/**
 * 集团推送token维护
 */
class UpdateToken 
{
    private $config = [
        'dev'=>[
            'url'=>'http://192.168.3.137:8888/login',
            'params'=>[
                'buId'=>'1301',
                'buPassword'=>'root'
            ]
        ],
        'pro'=>[

        ]
    ];
    private function getConfig($key){
        if(defined('ENV') && ENV == 'dev'){
            return $this->config['dev'][$key];
        }else{
            return $this->config['pro'][$key];
        }
    }
    public function process(){
        try{
            $redis_key = MEM_PRE_KEY."ytx_push_token";
            $url = $this->getConfig('url');
            $params = $this->getConfig('params');
            $returnData = Yii::app()->curl->post($url,$params);
            $returnData = json_decode($returnData,true);
            if($returnData['code'] == "000000"){
                Common::model()->saveLog("更新集团推送token成功:".$returnData['datas']['token'],"info","lcsUpdatePushToken");
                Yii::app()->redis_w->setex($redis_key,($returnData['datas']['effectiveTime']-1)*3600,$returnData['datas']['token']);
                return $returnData['datas']['token'];
            }else{
                Common::model()->saveLog("更新集团推送token异常:".$returnData['msg'],"error","lcsUpdatePushToken");
            }
        }catch(Exception $e){
            $error = LcsException::errorHandlerOfException($e)->toJsonString();
            Common::model()->saveLog("更新集团推送token失败:".$error,"error","lcsUpdatePushToken");
        }
    }
}
