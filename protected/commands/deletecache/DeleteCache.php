<?php

/**
 * Desc  :异步删缓存
 */
class DeleteCache {
    const CRON_NO = 15001; //任务代码

    public function __construct(){
    }
    /**
	 * 清除缓存
	 */
	public function removeCache(){
        $start = time();
        $end = time()+60;
        $redis_key = MEM_PRE_KEY."cache_del_queue";

        while($start<$end){
            $start = time();
            $keys=Yii::app()->redis_w->lpop($redis_key);
            if(!empty($keys)){
                var_dump($keys);
                CacheUtils::removeCache(array($keys));
            }else{
                sleep(1);
            }
        }
	}
}
