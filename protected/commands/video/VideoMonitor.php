<?php


class VideoMonitor{

    public function monitor(){
        $list = PlannerLive::model()->monitorVideoLive();
        if(empty($list)){
            return;
        }
        $ids = array();
        foreach($list as $item){
            $ids[] = $item['id'];
        }
        $idstr = implode(',',$ids);
        $msg = "直播未正常开始，直播id：".$idstr;
        echo $msg;
        $tomailer = array(
            'hailin3@staff.sina.com.cn'
        );
        $title = "异常直播";
        new NewSendMail($title, $msg, $tomailer);
    }
}
?>