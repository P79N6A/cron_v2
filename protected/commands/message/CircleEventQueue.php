<?php
/**
 * 圈子推荐信息消息队列
 * Created by PhpStorm.
 * User: PanChaoYi
 * Date: 2017/9/18
 * Time: 17:35
 */

class CircleEventQueue{

    const CRON_NO = 1320; //任务代码
    public function process(){
        $redis_message_key = MEM_PRE_KEY.'push_circle_action_to_event_message';
        $stop_time = time()+9*60;   //由于脚本10分钟执行一次,因此每九分钟就退出本脚本执行
        $db_w = Yii::app()->redis_w;

        while($db_w->lLen($redis_message_key)){
            if(time() > $stop_time) {
                break;
            }
            $data = $db_w->lPop($redis_message_key);
            /*try{
                if(!empty($data))  $data = json_decode($data,true);
                $this->pushCircleActionToEvent($data['uid'],$data['type'],$data['circle_id']);
            }catch (Exception $e){
                Common::model()->saveLog("推送圈子操作信息失败:{$e->getMessage()}",'error','remote_filter');
            }*/
        }
    }

    /**
     * 向上海大数据推送操作圈子的反馈(包括:加入圈子,进入圈子,退出圈子)
     * @param $uid : 用户id
     * @param $action_type :'join':加入圈子,'quit':退出圈子,'see':进入圈子看看
     * @param $circle_type : 目标类型(circle)
     * @param $circle_id : 圈子的id
     */
    private static function pushCircleActionToEvent($uid,$action_type,$circle_id){

        //$url = "http://101.95.135.102:5000/v1/user/events";
        $url = "http://api-lcs.baidao.com/v1/user/events";

        $param = array(
            array(
                'userid' => (int)$uid,
                'action' => $action_type,
                'target_type' => 'circle',
                'target_id' => (int)$circle_id,
            )
        );
        $param = json_encode($param);
        $headers = array(
            "Content-type:application/json",
            "Accept:application/json"
        );
        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_HEADER,FALSE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_TIMEOUT,2);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

        try{
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result,TRUE);
            if($result['success'] == true){
                if($result['msg'] != 'ok'){
                    Common::model()->saveLog('推送圈子操作信息接口返回异常','error','remote_filter');
                }
            }else{
                 Common::model()->saveLog('推送圈子操作信息失败','error','remote_filter');
            }
        }catch (Exception $e){
            Common::model()->saveLog("推送圈子操作信息失败:{$e->getMessage()}",'error','remote_filter');
        }
    }

}
