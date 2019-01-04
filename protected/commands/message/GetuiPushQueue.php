<?php
/**
 * 向新浪博客推送消息的队列处理
 * User: zwg
 * Date: 2015/11/30
 * Time: 14:32
 */

class GetuiPushQueue {

    const CRON_NO = 1312; //任务代码
    const PUSH_CHANNEL_ANDROID=4;
    const PUSH_CHANNEL_IOS=5;
    const PUSH_CHANNEL_XINDA_ANDROID=7;
    const PUSH_CHANNEL_XINDA_IOS=8;
    const PUSH_CHANNEL_CAIDAO_ANDROID=9;
    const PUSH_CHANNEL_CAIDAO_IOS=10;
    const PUSH_CHANNEL_CAIDAO_ANDROID_TJ=11;
    const PUSH_CHANNEL_CAIDAO_IOS_TJ=12;

    const QUEUE_KEY="lcs_push_client_getui_queue";

    private $getuiApi=null;

    public function __construct(){
        Yii::import('application.commands.push.*');
        Yii::import('application.extensions.getui.*');
        if(empty($this->getuiApi)){
            $this->getuiApi = new GetuiServiceApi();
        }
    }


    /**
     * 处理推送的消息
     */
    public function processMessage(){
        //退出时间 每次随机向后推30-150秒
        $stop_time = time()+rand(3,5)*15;

        while (true){
            if(time()>$stop_time){
                break;
            }

            $msg = Yii::app()->redis_w->lPop(self::QUEUE_KEY);
            if(!empty($msg)){
                $this->sendMessage($msg);
            }else{
                sleep(1);
            }
        }
    }


    /**
     * TODO 需要处理通知用户channel_id
     * 发信消息
     * @param $item channel_type push_message   push_user
     */
    public function sendMessage($item){
        if(empty($item)){
            return;
        }
        echo date("Y-m-d H:i:s")," ",$item,"\r\n";
        $queue_log=array();
        $queue_log['queue_key']=self::QUEUE_KEY;
        $queue_log['queue_data']=$item;
        try{
            if(is_string($item)){
                $item = json_decode($item, true);
            }

            if(!isset($item['push_message']) || empty($item['push_message']) || !isset($item['push_user']) || empty($item['push_user'])){
                throw new Exception('push_message or push_user is null');
            }



            $channel_id_arr = array();
            $u_type=0;//用户类型  1用户  2理财师
            foreach($item['push_user'] as $user){
                if(!empty($user['channel_id']) && $user['channel_id']!='(null)' && strlen($user['channel_id'])>10){
                    // if($user['s_id'] != 0 && $item['channel_type']==GetuiServiceApi::CHANNEL_ANDROID_CAIDAO){
                    //     $channel_id_jt[$user['s_id']][] = $user['channel_id'];
                    // }else{
                    //     $channel_id_arr[]=$user['channel_id'];
                    //     $u_type=$user['u_type'];
                    // }
                    $channel_id_arr[]=$user['channel_id'];
                    $u_type=$user['u_type'];
                }

            }
            if(!empty($channel_id_jt)){
                //集团推送
                $push_data = $this->getPushAndroidMessage($item['push_message'],self::PUSH_CHANNEL_CAIDAO_ANDROID);
                $this->Jtpush($push_data,$channel_id_jt);
            }
            if(empty($channel_id_arr)){
                throw new Exception('channel_id_arr is null');
            }

            $push_result = null;


            $tpl = null;

            if($item['channel_type']==GetuiServiceApi::CHANNEL_ANDROID){
                $push_data = $this->getPushAndroidMessage($item['push_message'],self::PUSH_CHANNEL_ANDROID);
                $tpl=$this->getuiApi->getTransmissionTemplateOfAndroid(json_encode($push_data,JSON_UNESCAPED_UNICODE),'','',$item['channel_type'],$u_type);
                //$tpl=$this->getuiApi->getLinkTemplateOfAndroid('新浪理财师',$push_data['alert'],'http://licaishi.sina.com.cn','','','',$item['channel_type'],$u_type);
            }else if($item['channel_type']==GetuiServiceApi::CHANNEL_IOS){
                $push_data = $this->getPushIosMessage($item['push_message'],self::PUSH_CHANNEL_IOS);
                $tpl=$this->getuiApi->getTransmissionTemplateOfIos($push_data,'','',$item['channel_type'],$u_type);
            }else if($item['channel_type']==GetuiServiceApi::CHANNEL_ANDROID_XINDA){
                $push_data = $this->getPushAndroidMessage($item['push_message'],self::PUSH_CHANNEL_XINDA_ANDROID);
                $tpl=$this->getuiApi->getTransmissionTemplateOfAndroid(json_encode($push_data,JSON_UNESCAPED_UNICODE),'','',$item['channel_type'],$u_type);
            }else if($item['channel_type']==GetuiServiceApi::CHANNEL_IOS_XINDA){
                $push_data = $this->getPushIosMessage($item['push_message'],self::PUSH_CHANNEL_XINDA_IOS);
                $tpl=$this->getuiApi->getTransmissionTemplateOfIos($push_data,'','',$item['channel_type'],$u_type);
            }else if($item['channel_type']==GetuiServiceApi::CHANNEL_ANDROID_CAIDAO){
                $push_data = $this->getPushAndroidMessage($item['push_message'],self::PUSH_CHANNEL_CAIDAO_ANDROID);
                $tpl=$this->getuiApi->getTransmissionTemplateOfAndroid(json_encode($push_data,JSON_UNESCAPED_UNICODE),'','',$item['channel_type'],$u_type);
            }else if($item['channel_type']==GetuiServiceApi::CHANNEL_IOS_CAIDAO){
                $push_data = $this->getPushIosMessage($item['push_message'],self::PUSH_CHANNEL_CAIDAO_IOS);
                $tpl=$this->getuiApi->getTransmissionTemplateOfIos($push_data,'','',$item['channel_type'],$u_type);
            }else if($item['channel_type']==GetuiServiceApi::CHANNEL_ANDROID_CAIDAO_TJ){
                $push_data = $this->getPushAndroidMessage($item['push_message'],self::PUSH_CHANNEL_CAIDAO_ANDROID_TJ);
                $tpl=$this->getuiApi->getTransmissionTemplateOfAndroid(json_encode($push_data,JSON_UNESCAPED_UNICODE),'','',$item['channel_type'],$u_type);
            }else if($item['channel_type']==GetuiServiceApi::CHANNEL_IOS_CAIDAO_TJ){
                $push_data = $this->getPushIosMessage($item['push_message'],self::PUSH_CHANNEL_CAIDAO_IOS_TJ);
                $tpl=$this->getuiApi->getTransmissionTemplateOfIos($push_data,'','',$item['channel_type'],$u_type);
            }

            if(count($channel_id_arr)>1){
                $push_result = $this->getuiApi->pushMessageToList($channel_id_arr,$tpl,$item['channel_type'],$u_type);
            }else{
                $push_result = $this->getuiApi->pushMessageToSingle(current($channel_id_arr),$tpl,$item['channel_type'],$u_type);
            }


            //记录推送到客户端的数据
            $queue_log['status']=$push_result['result'];
            $queue_log['result']=$push_result['msg'];
            $queue_log['ext_data']=isset($push_result['data'])?$push_result['data']:'';
            echo "记录推送到客户端的数据:\r\n";
            var_dump($queue_log);
            echo "\r\n\r\n\r\n\r\n\r\n\r\n";
            

        }catch (Exception $e){
            $queue_log['status']=-1;
            $queue_log['result']=$e->getMessage();
            //var_dump($e);
        }
        try{
            $es_data = array(
                'logtime'=>time(),
                'uid'=>$item['push_message']['uid'],
                'relatine_id'=>$item['push_message']['relation_id'],
                'message_type'=>$item['push_message']['type'],
                'push_client'=>$item['channel_type'],
                'push_user'=>json_encode($item['push_user']),
                'push_status'=>$queue_log['status'],
                'push_body'=>$push_data,
                'push_return'=>$queue_log,
            );
            echo "es 日志\r\n";
            echo yii::app()->redis_w->rpush('lcs_push_log_es',json_encode($es_data));
            Message::model()->saveMessageQueueLog($queue_log);
        }catch (Exception $e){
            var_dump($e);
        }

    }


    /**
     * 处理android消息数据
     * @param $msg
     * @return array type  content_client  relation_id child_relation_id alert
     */
    private function getPushAndroidMessage($msg,$channel_type){
        $data['type'] = $msg['type'];
        ///lixiang23 add 个推平台来源
        $data['platform'] = "lcs";
        $data['content_client'] = $msg['content_client'];
        try{
            $content_client = json_decode($msg['content_client'],true);
            if(!isset($content_client['title']) || $content_client['title']==''){
                $content_client['title'] = "新浪理财师"; ///没有给标题的默认是新浪理财师
                $data['content_client'] = json_encode($content_client,JSON_UNESCAPED_UNICODE);
            }else{
                $content_client['title']=CommonUtils::getSubStrNew($content_client['title'],30,'...');
                $data['content_client'] = json_encode($content_client,JSON_UNESCAPED_UNICODE);
            }
        }catch(Exception $e){
        }
        $data['relation_id'] = !empty($msg['relation_id'])?intval($msg['relation_id']):0;
        $data['child_relation_id'] = isset($msg['child_relation_id'])&&!empty($msg['child_relation_id'])?$msg['child_relation_id']:'0';
        $alert = isset($msg['alert'])? $msg['alert'] : '';
        if(empty($alert)){
            $alert = $this->getAlertFromContent($msg,$channel_type);
        }

        $data['alert'] = $alert;
        if(!empty($msg['symbol'])){
            $data['symbol'] = $msg['symbol'];
        }
        if(isset($msg['isRing'])){  
            $data['isRing'] = $msg['isRing'];
        }
        return $data;

    }

    /**
     * 处理ios消息数据
     * @param $msg
     * @return array type alert ext
     */
    private function getPushIosMessage($msg,$channel_type){
        $data['type'] = $msg['type'];
        ///lixiang23 add ios判断消息来源
        $data['platform'] = "lcs";
        $alert = isset($msg['alert'])? $msg['alert'] : '';
        if(empty($alert)){
            $alert = $this->getAlertFromContent($msg,$channel_type);
        }
        $data['alert'] = $alert;
        if(isset($msg['isRing'])){  
            $data['isRing'] = $msg['isRing'];
        }

        $data['aps'] = ["alert" => $alert]; // add zhihao6 2017/01/18
        $content_client = json_decode($msg['content_client'],true);

        $relation_id = !empty($msg['relation_id'])?$msg['relation_id']:0;
        $child_relation_id = isset($msg['child_relation_id'])&&!empty($msg['child_relation_id'])?$msg['child_relation_id']:'0';

        switch($msg['type']){
            case 1:
                $data['id']=$relation_id;
                $data['u_type']=$msg['u_type'];
                break;
            case 2:
                $data['title'] = isset($content_client['title'])?$content_client['title']:"新浪理财师";
                $data['child_relation_id']=$child_relation_id;
                break;
            case 4:
                $data['id']=$relation_id;
                $data['c_time']=$msg['c_time'];
                $data['symbol']=$msg['symbol'];
                break;
            case 5:
                $data['id']=$relation_id;
                break;
            case 6:
                $data['id']=$relation_id;
                $data['child_relation_id']=$child_relation_id;
                break;
            case 7:
                $data['id']=$relation_id;
                break;
            case 11:
                $data['r_id']=$relation_id;
                $data['child_relation_id']=$child_relation_id;
                break;
            case 12:
                try{
                    $content_client = json_decode($msg['content_client'],true);
                }catch(Exception $e){
                    $content_client = array();
                }
                $data['title'] = isset($content_client['title'])?$content_client['title']:"小妹通知";
                $data['alert'] = isset($content_client['content'])?$content_client['content']:$data['alert'];
                $data['p']=$msg['content_client'];
                break;
            case 14:
                $data['r_id']=$child_relation_id;
                $cnt_client = is_array($msg['content_client'])?$msg['content_client']:json_decode($msg['content_client'],true);
                $data['cmn_t']=!empty($cnt_client)&&isset($cnt_client['cmn_type'])?$cnt_client['cmn_type']:0;
                break;
            case 15:
                $data['pln_id']=$relation_id;
                $cnt_client = is_array($msg['content_client'])?$msg['content_client']:json_decode($msg['content_client'],true);
                $data['pln_name']=!empty($cnt_client)&&isset($cnt_client['pln_name'])?$cnt_client['pln_name']:'';
                $data['status']=!empty($cnt_client)&&isset($cnt_client['status'])?$cnt_client['status']:0;
                break;
            case 22:
                $data['content_client'] = $msg['content_client'];
                $data['relation_id'] = $relation_id;
                $data['child_relation_id'] = $child_relation_id;
                break;
            case 23:
                $data['content_client'] = $msg['content_client'];
                $data['relation_id'] = $relation_id;
                $data['circle_id'] = $relation_id;
                $data['child_relation_id'] = $child_relation_id;
                break;
            case 25:
                $data['content_client'] = $msg['content_client'];
                $data['relation_id'] = $relation_id;
                $data['child_relation_id'] = $child_relation_id;
                break;
            case 64:
                $data['title'] = $msg['title'];
                $data['relation_id'] = $relation_id;
                $data['child_relation_id'] = $child_relation_id;
                break;
            case 66:
                $data['relation_id'] = $relation_id;
                $data['child_relation_id'] = $child_relation_id;
                break;
            case 68:
                $data['relation_id'] = $relation_id;
                $data['content_client'] = $msg['content_client'];
                break;
            case 69:
                $data['content_client'] = $msg['content_client'];
                break;
            case 72:
                $data['title'] = '快讯';
                $data['relation_id'] = $relation_id;
                break;
            case 74:
                $data['symbol'] = $msg['symbol'];
                $data['content_client'] = $msg['content_client'];
                break;
            case 75:
                $data['title'] = isset($content_client['title'])?$content_client['title']:"精选内容";
                $data['relation_id'] = $msg['relation_id'];
                break;
            case 76:
                $data['title'] = isset($content_client['title'])?$content_client['title']:"新浪理财师";
                $data['relation_id'] = $msg['relation_id'];
                break;
            default:
                break;
        }
        return $data;

    }



    /**
 *获取通知的文本内容
 * @param $msg
 * @param $push_channel  const PUSH_CHANNEL_ANDROID=2; const PUSH_CHANNEL_IOS=3;
 * @return string
 */
    private function getAlertFromContent($msg,$push_channel){
        $alert='';
        if(isset($msg['content']) && !empty($msg['content'])){
            $content_json = null;
            if(!is_array($msg['content'])){
                $content_json=json_decode($msg['content'],true);
            }else{
                $content_json=$msg['content'];
            }

            if(!empty($content_json) && is_array($content_json)){
                foreach($content_json as $key=>$val){
                    if(isset($val['value'])){
                        $alert .= $val['value'];
                    }
                }
            }
        }

        return CommonUtils::getSubStrNew($alert,40,'...');
    }
    private function Jtpush($push_data,$channel_ids){
        $p = new Push();
        $content_client_jt = json_decode($push_data['content_client'],true);

        foreach($channel_ids as $key=>$value){
         $channel_ids = implode(",",$value);
         $pushData = [
            "content"=>$push_data['alert'], 
            "title"=>$content_client_jt['title'],
            "clientId"=>$channel_ids,
            "serviceProvider"=>$key, 
            "batchId"=>time(),
            "pushWay"=>2,
            "type"=>$push_data["type"],
            "content_client"=>$push_data["content_client"],
            "relation_id"=>$push_data["relation_id"],
            "child_relation_id"=>$push_data["child_relation_id"],
        ];
        $p->setExtra("transmissionType",0); 
        
        $p->pushByChannel($pushData); 
       }
       var_dump("集团推送");
   }

}
