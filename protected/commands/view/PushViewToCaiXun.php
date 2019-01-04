<?php
/**
 * 将观点推送到上海新财讯处
 *
 */
class PushViewToCaiXun{
	const CRON_NO = 1208; //任务代码
	
	public function __construct(){
	
	}

    public function process(){
        $start = time();
        $end = time()+60;
        while($start<$end){
            $this->processView();
            sleep(1);
            $start = time();
        }
    }

    /**
     * 处理观点推送
     */   
    public function processView() {
        ///获取未推送的观点
        $unpush_view_ids = View::model()->getViewNotPush();
        if(!empty($unpush_view_ids)){
            foreach($unpush_view_ids as $view){
                $v_id = $view['id'];
                $view_info = View::model()->getViewById($v_id);
                $res = ViewService::pushViewToCX($v_id);
                if($res){
                    ///设置观点已经推送
                    View::model()->updateNumber($v_id,'is_push','add',1);
                }else{
                    ///设置观点推送推送失败
                    View::model()->updateNumber($v_id,'is_push','sub',1);
                }
            }
        }
    }


}
