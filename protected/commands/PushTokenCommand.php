<?php
/**
 * weibo 更换token
 * User: lixiang23
 * Date: 2017/03/27
 * Time: 17:34
 */

class PushTokenCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.push.*');
    }

    /**
     * 更新集团推送 token,每天晚上运行一次
     *@author lining
     */
    public function actionUpdatePushToken() {
    	try{
    		$p = new UpdateToken();
    		$p->process();
    	}catch(Exception $e) {
    		
    	}
    }
    /**
     * 测试设备推送
     */
    public function actionTestChannel(){
        $p = new Push();
        $pushData = [
            "content"=>"测试推送",
            "title"=>"推送title",
            "clientId"=>"1104a8979283b7f90c6",
            "serviceProvider"=>"203",
            "batchId"=>"123",
            "pushWay"=>2,
            "type"=>1,
            "content_client"=>"早上起来拥抱太阳",
            "child_relation_id"=>48751,
            "relation_id"=>48751,
        ];
        $p->setExtra("transmissionType",0);
        $p->pushByChannel($pushData);
    }
    /**
     * 测试账号推送
     */
    public function actionTestUids(){
        $p = new Push();
        $pushData = [
            "content"=>"测试推送",
            "title"=>"推送title",
            "users"=>"1104a8979283b7f90c6",
            "serviceProvider"=>"205",
            "batchId"=>"123",
            "pushWay"=>1,
        ];
        $p->pushByChannel($pushData);
    }
    /**
     * 绑定用户账号
     */
    public function actionBindUser(){
        try{
            $start = time();
            $end = time()+60;
            while ($start<$end) {
                // 读取队列
                $sync_user_key = 'lcs_sync_jtpush_binduser';
                $val = Yii::app()->redis_r->pop($sync_user_key);
                if(!$val){
                    sleep(2);
                }else{
                    echo $val;
                    list($uid, $channel_id, $s_id) = explode('|', $val);
            
                    $p = new Push();
                    $bind_user = [
                        "customerId" => $uid,
                        "deviceId" => $channel_id,
                        "divId" => $p->getConfig('divId'),
                        "productId" => $p->getConfig('productId'),
                        "supplierId" => $s_id
                    ];
                    
                    $p->bindUser($bind_user);
                }
                $start = time();
            }
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }
}
