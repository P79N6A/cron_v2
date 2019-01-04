<?php

/**
 * 何建彭
 * 视频推送增加
 */
class VideoList
{
    const CRON_NO = 17001; //任务代码
    public function handle(){
        try{
            $cards = [];
            $key = MEM_PRE_KEY . 'vido_list_push_time';
            $time = Yii::app()->redis_w->get($key);
            if (empty($time)){
                $time = '';
            }
            $dynamic = Dynamic::model()->getVideoList($time);
            $view = View::model()->getVideoList($time);
            if (!empty($dynamic) && !empty($view)){
                $cards = array_merge($dynamic, $view);
            }elseif(!empty($dynamic)){
                $cards = $dynamic;
            }elseif(!empty($view)){
                $cards = $view;
            }else{
                Common::model()->saveLog("无视频，可推送数据" , "info", "vido_list_push");
                echo '无视频，可推送数据';die;
            }
            $newArray = [];
            //处理视频数据
            foreach ($cards as $v){
                $videos = [];
                $videos['vid'] = $v['vid'];
                $videos['publishedAt'] = $v['publishedAt'];
                $videos['cover'] = $v['cover'];
                $videos['title'] = $v['title'];
                $author = [];
                $author['id'] = $v['id'];
                $author['nickname'] = $v['nickname'];
                $author['avatar'] = $v['avatar'];
                $author['description'] = $v['description'];
                $newArray[$v['id']]['content']['author']= $author;
                $newArray[$v['id']]['content']['videos'][] = $videos;
            }

            $i =0;
            Common::model()->saveLog("视频列表：".json_encode($newArray), "info", "vido_list_push");
            foreach ($newArray as $value){
                 $i++;
                if($i==2){
                $value['type'] = 'add';
                $value['secretKey'] = "7da76b0b-6e4e-4dd4-b18a-84f4969b0a48";
                $res = Yii::app()->curl->post('https://spero-outspace.secon.cn/api/spero-ultron-service/public/videos/sync', $value);
                Common::model()->saveLog("返回结果：".$res.'参数：'.json_encode($value), "info", "vido_list_push");
            }
             }
            $time = date('Y-m-d H:i:s');
            Yii::app()->redis_w->set($key,$time);
        }catch (Exception $e){
            Common::model()->saveLog("推送视频失败:" . $e->getMessage() , "error", "vido_list_push");
        }
    }
}
