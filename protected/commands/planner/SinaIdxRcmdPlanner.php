<?php
/**
 * 定时任务:向浪首推荐理财师
 * （1）推荐的计划 推荐的话题

 * User: zwg
 * Date: 2015/7/16
 * Time: 17:33
 */

class SinaIdxRcmdPlanner {


    const CRON_NO = 1003; //任务代码


    public function __construct(){
    }


    /**
     * 统计活跃数据
     * @throws LcsException
     */
    public function rsync(){
        try{
            $start_time = CommonUtils::getMillisecond();

            $res_data = $this->getData();
            $num = 0;
            if(!empty($res_data)){
                $num = count($res_data);
                //生成数据文件
                $data_content = 'var sinaIdxRcmdPlanner='.json_encode($res_data,JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES).';/*'.date("Y-m-d H:i:s").'*/';
                $new_path = CommonUtils::createPath(DATA_PATH.DIRECTORY_SEPARATOR,'licaishi');
                $new_path = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR,'recommend');
                $dataFile = CommonUtils::saveDateFile(self::CRON_NO,$data_content,'sinaIdxRcmdPlanner.js',$new_path.DIRECTORY_SEPARATOR,FILE_NO_DEFAULT_CONTEXT);
                if(file_exists($dataFile)){
                    //推送到静态池
                    $this->rsyncFile($dataFile);
                }else{
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "file no exits:".$dataFile);
                }
            }
            $end_time = CommonUtils::getMillisecond();
            $time = $end_time - $start_time;

            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "消耗时间:".$time." 记录条数:".$num);
        }catch (Exception $e){
            //超时操作不记录日志
            if(stristr($e->getMessage(),'Operation timed out')===false){
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
            }else{
                throw LcsException::errorHandlerOfException($e);
            }
        }
    }


    /**
     * 推送数据文件
     * step 1:发送文件
     * step 2:分发文件
     * @param unknown $file
     */
    private function rsyncFile($file) {
        $host = '172.16.20.214';
        $pub_url = 'http://'.$host.'/publish';  //"http://分 发主机IP： 端口 /publish";
        $module = "finance";  //模块名
        $pub_file_name = './licaishi/recommend/sinaIdxRcmdPlanner.js';
        $post_file_name = '/licaishi/recommend/sinaIdxRcmdPlanner.js';
        $rsync_cmd = "cd ".DATA_PATH."/ && rsync -avzR ".$pub_file_name." $host::finance";
        try {
            //step 1
            $rsync_result = exec($rsync_cmd);
            //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "命令：".$rsync_cmd.'  结果：'.$rsync_result);
            //step 2
            $md5 = md5_file($file);
            $postfield="module=$module&files=$post_file_name \t $md5";

            $curl = Yii::app()->curl;
            $params = array(
                CURLOPT_RETURNTRANSFER => '1',
                //CURLOPT_HTTPHEADER => 'application/x-www-form-urlencoded',
                CURLOPT_TIMEOUT => '15'
            );
            $curl->setOptions($params);
            $post_rs = $curl->post($pub_url, $postfield);
            //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $post_rs);
        }catch (Exception $e) {
            //超时操作不记录日志
            if(stristr($e->getMessage(),'Operation timed out')===false){
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
            }
            return false;
        }
        return true;
    }


    /**
     * 获取推送数据
     * @throws LcsException
     */
    private function getData(){
        try{
            $res_data=array();

            $recommend_title="今日投资机会解读";
            $recommend_url="http://licaishi.sina.com.cn/web/index";
            //获取推荐信息
            $pageCfgs = Common::model()->getPageCfgByAreaCodes(5);
            if(!empty($pageCfgs) && count($pageCfgs)>0){
                $pageCfg = current($pageCfgs);
                $recommend_title=$pageCfg['title'];
                $recommend_url=$pageCfg['url'];
            }

            //获取推荐的计划
            $redis_key = "lcs_recommend_end_plan_index_3_detail";
            $recommend_plans = Yii::app()->redis_r->get($redis_key);
            $rec_plans = array();
            if(!empty($recommend_plans)){
                $rec_plans = json_decode($recommend_plans, true);
            }
            if(!empty($rec_plans)){
                $rec_plan_info = Plan::model()->getPlanInfoByIds(array_keys($rec_plans),array('pln_id','new_pln_id'));
                if(!empty($rec_plan_info)){
                    $new_plan_ids = array();
                    foreach($rec_plan_info as $item){
                        $new_plan_ids[]=$item['new_pln_id'];
                    }

                    $rec_plans = Plan::model()->getPlanInfoByIds($new_plan_ids,array('pln_id','name','number','target_ror','invest_days','p_uid'));

                    if(!empty($rec_plans)){
                        $rec_plans = array_values($rec_plans);
                        foreach($rec_plans as $item){
                            $item['recomend_title']=$recommend_title;
                            $item['recomend_url']=$recommend_url;

                            $item['type']='plan';
                            $name = $item['name'] . ($item['number']>9 ? $item['number'] : "0".$item['number'])."期";
                            $item['name']=$name;
                            $item['invest_days_fmt'] = (int)(intval($item['invest_days'])/30).'个月';

                            unset($item['number']);
                            $item['url']='http://licaishi.sina.com.cn/plan/'.$item['pln_id'];
                            $res_data[] = $item;
                        }
                    }
                }
            }

            //获取推荐的话题
            $topics = Topic::model()->getTopicByType(array(2,3),null,3,'id,title');
            if(!empty($topics)){
                $curl =Yii::app()->curl;
                $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
                ///lixiang23 设置超时时间为14秒，该接口容易超时
                $curl->setTimeOut(14);
                $url='http://licaishi.sina.com.cn/api/searchNew';
                $params = array('t'=>3,'page'=>1,'num'=>1,'s'=>'');
                foreach($topics as $topic){
                    $topic['recomend_title']=$recommend_title;
                    $topic['recomend_url']=$recommend_url;
                    $topic['url']='http://licaishi.sina.com.cn/s/'.urlencode($topic['title']);
                    $topic['type']='topic';
                    $topic['total']=0;
                    $params['s']=$topic['title'];
                    $search_res = $curl->get($url,$params);

                    if(!empty($search_res)){
                        try{
                            $search_res = json_decode($search_res,true);
                            if(isset($search_res['code']) && $search_res['code']==0){
                                $views = isset($search_res['data']['view'])?$search_res['data']['view']:null;
                                $asks = isset($search_res['data']['ask'])?$search_res['data']['ask']:null;
                                $total = 0;
                                if(!empty($views) && isset($views['total'])){
                                    $total += intval($views['total']);
                                }
                                if(!empty($asks) && isset($asks['total'])){
                                    $total += intval($asks['total']);
                                }
                                $topic['total'] = $total;
                            }
                        }catch (Exception $e){
                            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
                        }
                    }

                    $res_data[] = $topic;
                }
            }

            return $res_data;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
}
