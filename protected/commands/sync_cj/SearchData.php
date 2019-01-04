<?php
/**
 * 生成观点和问答的搜索数据，并且推送到搜索部门服务器
 * User: zwg
 * Date: 2015/11/25
 * Time: 17:01
 */

class SearchData {

    const CRON_NO = 8105; //任务代码

    private $_last_dispose_time = 'lcs_search_last_dispose_time';
    private $_last_ask_time = 'lcs_search_last_ask_time';


    /**
     * 推送到搜索平台的观点数据
     *
     */
    public function actionSyncViewData(){
        //last dispose time  
        
        $last_dispose_time = Yii::app()->redis_r->get($this->_last_dispose_time);
        if(empty($last_dispose_time)) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, 'view last_dispose_time is empty !');
            return 0;
        }

        $now_time = date("Y-m-d H:i:s");
        $sql = "select id,ind_id,title,tags,content,content_pay,p_time,status from lcs_view where p_time>'$last_dispose_time' union select id,ind_id,title,tags,content,content_pay,p_time,status from lcs_view_draft where u_time>'$last_dispose_time' and status=-1";
        $rows =  Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $record=0;
        
        ///从redis中读出需要同步到搜索部门的观点数据
        $rows_from_redis=View::model()->getSyncViewListFromRedis();  
        if($rows_from_redis){
            if(is_array($rows) && sizeof($rows)>0){
                ///去重
                for($i=0;$i<count($rows_from_redis);$i++){
                    $signal=0;
                    for($j=0;$j<count($rows);$j++){
                        if($rows_from_redis[$i]['id']==$rows[$j]['id']){
                            $signal=1;
                            break;
                        }
                    }
                    if($signal==1){
                        continue;
                    }else{
                        array_push($rows,$rows_from_redis[$i]);
                    }
                }
            }else{
                $rows=$rows_from_redis;
            }
        }
        if(is_array($rows) && sizeof($rows)>0){
            
            $record=count($rows);
            $res = '';
            foreach($rows as $val){
                $val['status'] == 0?$res .="@\n@DF:I\n@id:$val[id]\n":$res .="@\n@id:$val[id]\n@DF:D\n";

                if($val['status'] == 0){
                    $res .= mb_convert_encoding("@title:".strip_tags($val['title'])."\n@type:".$val['ind_id']."\n@tags:\n@content:".$this->trimHtml($val['content'].$val['content_pay'])."\n@p_time:".strtotime($val['p_time'])."\n",'gb2312','utf-8');
                }
            }

            //生成数据文件
            $new_path = CommonUtils::createPath(DATA_PATH.DIRECTORY_SEPARATOR,'syncCJData');
            $new_path = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR,'searchData');
            $new_path = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR,date('Ymd'));
            $dataFile = CommonUtils::saveDateFile(self::CRON_NO,$res,'licaishi_'.date('Hi').'.txt',$new_path.DIRECTORY_SEPARATOR,FILE_NO_DEFAULT_CONTEXT);
            if(file_exists($dataFile)){
                //推送数据
                $rsync_cmd = "rsync $dataFile rsv4.match.sina.com.cn::MINI_SEARCH/licaishi";
                exec($rsync_cmd);
            }else{
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "file no exits:".$dataFile);
            }

            //更新操作时间
            Yii::app()->redis_w->set($this->_last_dispose_time, $now_time);
        }
        return $record;
    }



    /**
     * 推送到搜索平台的问答数据
     *
     */
    public function actionSyncAskData(){
        //last dispose time
        $last_dispose_time = Yii::app()->redis_r->get($this->_last_ask_time);
        if(empty($last_dispose_time)) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, 'ask last_dispose_time is empty !');
            return 0;
        }

        $now_time = date("Y-m-d H:i:s");
        //增加推送更新的问题答案
        $sql = "select q.id,q.p_uid,q.ind_id as type,q.content as question,q.status,CONCAT(a.content,a.content_pay) as answer,a.score,q.answer_time as answer_time from lcs_ask_answer a left join lcs_ask_question q on a.q_id=q.id where  (q.answer_time>'$last_dispose_time' and q.answer_time<'$now_time') or a.u_time>'$last_dispose_time'";
        $rows =  Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        $record = 0;
        if(is_array($rows) && sizeof($rows)>0){
            $record = count($rows);
            $res = '';
            foreach($rows as $val){
                $val['status'] > 0?$res .="@\n@DF:I\n@id:$val[id]\n":$res .="@\n@id:$val[id]\n@DF:D\n";

                if($val['status'] > 0){
                    $res .= mb_convert_encoding("@question:".strip_tags($val['question'])."\n@type:".$val['type']."\n@answer:".strip_tags($val['answer'])."\n@score:".$val['score']."\n@p_uid:".$val['p_uid']."\n@answer_time:".strtotime($val['answer_time'])."\n",'gb2312','utf-8');
                }
            }

            //生成数据文件
            $new_path = CommonUtils::createPath(DATA_PATH.DIRECTORY_SEPARATOR,'syncCJData');
            $new_path = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR,'searchData');
            $new_path = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR,date('Ymd'));
            $dataFile = CommonUtils::saveDateFile(self::CRON_NO,$res,'ask_'.date('Hi').'.txt',$new_path.DIRECTORY_SEPARATOR,FILE_NO_DEFAULT_CONTEXT);
            if(file_exists($dataFile)){
                //推送数据
                $rsync_cmd = "rsync $dataFile rsv4.match.sina.com.cn::MINI_SEARCH/licaishiask";
                exec($rsync_cmd);
            }else{
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "file no exits:".$dataFile);
            }

            //更新操作时间
            Yii::app()->redis_w->set($this->_last_ask_time, $now_time);
        }

        return $record;
    }



    private function trimHtml($content){
        $content =  strip_tags($content);
        $content = htmlspecialchars($content,ENT_QUOTES,'UTF-8');
        return $content;
    }




}
