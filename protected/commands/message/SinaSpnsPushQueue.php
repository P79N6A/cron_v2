<?php
/**
 * 向新浪博客推送消息的队列处理
 * User: zwg
 * Date: 2015/11/30
 * Time: 14:32
 */

class SinaSpnsPushQueue {

    const CRON_NO = 1307; //任务代码
    const PUSH_CHANNEL_ANDROID=2;
    const PUSH_CHANNEL_IOS=3;

    const QUEUE_KEY="lcs_push_client_spns_queue";
    private $spns = null;

    public function __construct(){
        if(empty($this->spns)){
            $this->spns = new SpnsApi();
        }
    }


    /**
     * 处理推送的消息
     */
    public function processMessage(){
        //退出时间 每次随机向后推30-150秒
        $stop_time = time()+rand(2,10)*15;

        while (true){
            if(time()>$stop_time){
                //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "processMessage 到达时间退出");
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
    private function sendMessage($item){
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
                    $channel_id_arr[]=$user['channel_id'];
                    $u_type=$user['u_type'];
                }

            }
            if(empty($channel_id_arr)){
                throw new Exception('channel_id_arr is null');
            }

            $push_result = null;
            if($item['channel_type']==self::PUSH_CHANNEL_ANDROID){
                $push_data = $this->getPushAndroidMessage($item['push_message']);
                $push_result=$this->spns->pushAndroid($u_type,$channel_id_arr,$push_data);
            }else if($item['channel_type']==self::PUSH_CHANNEL_IOS){
                $push_data = $this->getPushIosMessage($item['push_message']);
                $push_result=$this->spns->pushIos($u_type,$channel_id_arr, $push_data,isset($push_data['ext'])?$push_data['ext']:null);
            }

            //记录推送到客户端的数据
            $queue_log['ext_data']=json_encode(isset($push_result['push_data'])?$push_result['push_data']:array());

            if(!empty($push_result) && $push_result['status']==1){
                $queue_log['status']=1;
                $queue_log['result']=$push_result['data'];
            }else{
                $queue_log['status']=-1;
                $queue_log['result']= json_encode($push_result);
            }
        }catch (Exception $e){
            $queue_log['status']=-1;
            $queue_log['result']=$e->getMessage();
        }
        try{
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
    private function getPushAndroidMessage($msg){
        $data['type'] = $msg['type'];
        $data['content_client'] = $msg['content_client'];
        $data['relation_id'] = !empty($msg['relation_id'])?$msg['relation_id']:0;
        $data['child_relation_id'] = isset($msg['child_relation_id'])&&!empty($msg['child_relation_id'])?$msg['child_relation_id']:'0';
        $alert = isset($msg['alert'])? $msg['alert'] : '';
        if(empty($alert)){
            $alert = $this->getAlertFromContent($msg,self::PUSH_CHANNEL_ANDROID);
        }

        $data['alert'] = $alert;
        return $data;

    }

    /**
     * 处理ios消息数据
     * @param $msg
     * @return array type alert ext
     */
    private function getPushIosMessage($msg){
        $data['type'] = $msg['type'];
        $alert = isset($msg['alert'])? $msg['alert'] : '';
        if(empty($alert)){
            $alert = $this->getAlertFromContent($msg,self::PUSH_CHANNEL_IOS);
        }
        $data['alert'] = $alert;

        $relation_id = !empty($msg['relation_id'])?$msg['relation_id']:0;
        $child_relation_id = isset($msg['child_relation_id'])&&!empty($msg['child_relation_id'])?$msg['child_relation_id']:'0';

        $ext = array();
        switch($msg['type']){
            case 1:
                $ext['id']=$relation_id;
                $ext['u_type']=$msg['u_type'];
                break;
            case 2:
                $ext['child_relation_id']=$child_relation_id;
                break;
            case 4:
                $ext['id']=$relation_id;
                $ext['c_time']=$msg['c_time'];
                $ext['symbol']=$msg['symbol'];
                break;
            case 5:
                $ext['id']=$relation_id;
                break;
            case 6:
                $ext['id']=$relation_id;
                $ext['child_relation_id']=$child_relation_id;
                break;
            case 7:
                $ext['id']=$relation_id;
                break;
            case 11:
                $ext['r_id']=$relation_id;
                $ext['child_relation_id']=$child_relation_id;
                break;
            case 12:
                $ext['p']=$msg['content_client'];
                break;
            case 14:
                $ext['r_id']=$child_relation_id;
                $cnt_client = is_array($msg['content_client'])?$msg['content_client']:json_decode($msg['content_client'],true);
                $ext['cmn_t']=!empty($cnt_client)&&isset($cnt_client['cmn_type'])?$cnt_client['cmn_type']:0;
                break;
            case 15:
                $ext['pln_id']=$relation_id;
                $cnt_client = is_array($msg['content_client'])?$msg['content_client']:json_decode($msg['content_client'],true);
                $ext['pln_name']=!empty($cnt_client)&&isset($cnt_client['pln_name'])?$cnt_client['pln_name']:'';
                break;
            default:
                break;
        }
        $data['ext'] = $ext;
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
        return $alert;
    }
}