<?php
/*
 * 将错误输出到es中方便查看
 */

class SaveEsLog
{
	const CRON_NO = 7802; //任务代码

	public function __construct()
    {

    }

	/**
	 *  转存数据
	 *
	 */
    public function saveLog(){
        $start = time();
        $end = time()+60;
        while($start<$end){
            try{
                $today = date("Y-m-d");
                $index = "lcs_log_".$today;
                $redis_key = MEM_PRE_KEY."es_log_id";
                $max_id = Yii::app()->redis_w->get($redis_key);
                if(empty($max_id)){
                    $max_id = Common::model()->getMaxLogId();
                }
                $logs = Common::model()->getLogByPage($max_id,100);
                if(!empty($logs)){
                    $result = '{"index": {"_index": "'.$index.'","_type":"logs"}}';
                    ///清空文件
                    file_put_contents(DATA_PATH."/esdata.txt",'');
                    foreach($logs as $item){
                        $temp = array();
                        $temp['id'] = $item['id'];
                        $temp['level'] = $item['level'];
                        $temp['category'] = $item['category'];
                        $temp['logtime'] = $item['logtime'];
                        $temp['message'] = $item['message'];
                        $temp['@timestamp'] = date('c',time());
                        file_put_contents(DATA_PATH."/esdata.txt",$result.PHP_EOL,FILE_APPEND);
                        file_put_contents(DATA_PATH."/esdata.txt",json_encode($temp).PHP_EOL,FILE_APPEND);
                    }
                    Yii::app()->redis_w->set($redis_key,$item['id']);
                    ESstat::model()->saveES(DATA_PATH."/esdata.txt");
                }
            }catch(Exception $e){
            }
            $start = time();
        }
    }
}
