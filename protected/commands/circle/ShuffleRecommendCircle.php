<?php
/**
 * 定时打乱推荐圈子顺序
 * @author yougang1
 *
 */
class ShuffleRecommendCircle
{
    const CRON_NO = 10001; //圈子任务代码100xx
    
    public function __construct(){}
    
    public function ShuffleCircle(){
        
        try {
            $redis_r = Yii::app()->redis_r;
            $redis_w = Yii::app()->redis_w;
            
            $key = 'lcs_recommend_circle_list_id';
            $length = $redis_r->llen($key);
            $total_circle_ids = $redis_r->getRange($key,0,$length-1);

            //打乱
            shuffle($total_circle_ids);

            foreach ($total_circle_ids as $circle_id){
                //先从后面追加
                $redis_w->rPush($key,$circle_id);
                //再lpop
                $redis_w->lPop($key);
            }
        } catch (Exception $e) {
            
            throw LcsException::errorHandlerOfException($e);
        }
       
        
    }
    
}