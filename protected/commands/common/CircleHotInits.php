<?php

class CircleHotInits {
    const CRON_NO = 19902; //任务代码
    public function __construct(){
    }

    public function check(){
        echo "初始化时间：".date("Y-m-d H:i:s",time())."\r\n";
        //获取圈子配置的前20条数据
        $sql = "select id from lcs_circle order by current_heat desc limit 30;";

        $CircleHotList = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        echo count($CircleHotList);
        foreach ($CircleHotList as $key=>$value) {
            //圈子热度初始化数据
            $datas = array(
                "uid" => 23393022,
                "hot" => 0,
                "circle_id" => $value['id'],
                "g_id" => 1,
            );
            echo "同步成功:circle_id".$value['id']."\r\n";
            echo CircleHot::model()->saveCircleHot($datas);
        }
    }
}
