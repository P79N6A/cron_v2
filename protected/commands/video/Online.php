<?php

/**
 * Created by PhpStorm.
 * User: haohao
 * Date: 15/12/1
 * Time: 10:38
 */
class Online
{
    const CRON_NO = 2001; //任务代码
    public function update(){
        try{
            $start_time = date('Y-m-d H:i:s',strtotime('-5 seconds'));
            $data = Video::model()->getLastMinVideoUserNumber($start_time);

            $c_time = date('YmdHis');
            if (!empty($data)) {
                foreach($data as $k=>$v){
                    $video_id = $k;
                    $count = $v;
                    if($video_id>0) {
                        $data = array(
                            'video_id' => $video_id,
                            'date_ymdhi' => substr($c_time, 0, 14),
                            'online_num' => $count,
                            'c_time' => $c_time,
                            'u_time' => $c_time,
                        );
                        Video::model()->addVideoUserStat($data);
                    }
                }
            }
        }catch (Exception $e){

        }
    }
}