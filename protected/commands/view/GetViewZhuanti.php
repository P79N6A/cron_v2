<?php
/**
 * 将观点专题从上海财讯处同步到本地
 *
 */
class GetViewZhuanti{
	const CRON_NO = 1210; //任务代码
	
	public function __construct(){
	
	}

    public function process(){
        $start = time();
        $end = time()+60;
        while($start<$end){
            $this->processMsg();
            sleep(1);
            $start = time();
        }
    }    

    /**
     * 具体处理消息
     */
    public function processMsg() {
        $redis_key = MEM_PRE_KEY."view_zhuanti_lasttime";
        $lastTime = Yii::app()->redis_r->get($redis_key);
        if(empty($lastTime)){
            $lastTime = date('Y-m-d H:i:s',strtotime("-5 minute"));
            ///毫秒级别
            $lastTime = strtotime($lastTime)."000";
        }
        $zhuanti_list = ViewService::getViewZhuanti($lastTime);

        $lastTime = date('Y-m-d H:i:s',strtotime("-1 minute"));
        ///毫秒级别
        $lastTime = strtotime($lastTime)."000";

        if(isset($zhuanti_list['data'])){
            foreach($zhuanti_list['data'] as $item){
                $temp = ViewCaixun::model()->getThemeById($item['id']);
                if($temp){
                    var_dump("update theme",$item);
                    ViewCaixun::model()->saveTheme($item);
                }else{
                    var_dump("insert theme",$item);
                    ViewCaixun::model()->addTheme($item);
                }
            }
        }
        Yii::app()->redis_w->set($redis_key,$lastTime);
    }
}
