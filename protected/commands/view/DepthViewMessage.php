<?php

class DepthViewMessage {


    const CRON_NO = 20180913; //任务代码

    public function __construct(){

    }
    /**
     * 深度观点小程序推送
     */
    public function pushView(){
        try {
            //Todo
            //1.获取收费观点包ID
            //2.根据观点包遍历最近1分钟内新增c_time观点记录redis
            $p = 1;
            $num = 100;
            $key = 'lcs_common_message_queue';
            $p_time = date('Y-m-d H:i:s',(time()-60));
            while (($pkg_list = Package::model()->getDepthPkgList($p, $num))) {
                foreach ($pkg_list as $pkg) {
                    $planner = Planner::model()->getPlannerById($pkg['p_uid']);
                    $ViewInfo = View::model()->getOneMinuteView($pkg['id'],$p_time);
                    if(empty($ViewInfo)){
                        continue;
                    }
                    foreach ($ViewInfo as $view) {
                        $message = [
                            "title" => $view['title'],
                            "url" => "https://licaishi.sina.com.cn/wap/newviewinfo?v_id=" . $view['id'],
                            "view_id" => $view['id'],
                            "service_name" => $planner[$pkg['p_uid']]['name']."的VIP服务",
                            "detail" => mb_substr(CommonUtils::getTextContent($view['content']),0,30,'utf-8'),
                        ];
                        $queue = [
                            "type" => "DepthView",
                            "p_uid" => $pkg['p_uid'],
                            "message" => $message
                        ];
                        Yii::app()->redis_w->rPush($key, json_encode($queue));
                    }
                }
                $p++;
            }
        }catch(Exception $e){
            Common::model()->saveLog("小程序深度观点推送错误".$e->getMessage(),"error","Depth view");
            exit;
        }
    }
}
