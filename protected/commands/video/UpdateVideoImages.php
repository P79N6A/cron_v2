<?php

/**
 * 何建彭
 * 修复点播图片问题
 */
class UpdateVideoImages
{
    const CRON_NO = 18001; //任务代码
    public function handle(){
        try{
            $cards = [];
            $dynamic = Dynamic::model()->getVideoImagesAliyun();
            if (!empty($dynamic)){
                foreach ($dynamic as $v){
                    $video_info = $this->getVideoInfo($v['video_id']);
                    if (!empty($video_info['data']['coverURL'])){
                        $arr = parse_url($video_info['data']['coverURL']);
                        $data = [];
                        $data['imgurl'] = 'https://'.$arr['host'].$arr['path'];
                        $data['id'] = $v['id'];
                        Dynamic::model()->updateImgurl($data);
                    }
                }

            }
            $view = View::model()->getVideoImagesAliyun();
            if (!empty($view)){
                foreach ($view as $v){
                    $video_info = $this->getVideoInfo($v['video_id']);
                    if (!empty($video_info['data']['coverURL'])){
                        $arr = parse_url($video_info['data']['coverURL']);
                        $data = [];
                        $data['imgurl'] = 'https://'.$arr['host'].$arr['path'];
                        $data['id'] = $v['id'];
                        View::model()->updateImgurl($data);
                    }
                }
            }

        }catch (Exception $e){
            Common::model()->saveLog("修复点播图片报错:" . $e->getMessage() , "error", "UpdateVideoImages");
        }
    }
    public function getVideoInfo($videoId){
        $value['type'] = 2;
        $value['videoId'] = $videoId;
        $res = Yii::app()->curl->post('http://video.sinalicaishi.com.cn/video.php', $value);
        return json_decode($res,true);
    }

}
