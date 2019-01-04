<?php
/**
 * 生成观点的搜索数据(不含tags字段)，并且推送到搜索部门服务器
 * User: liyong3 
 * Date: 2016/05/03
 * Time: 17:01
 */

class SyncView {

    const CRON_NO = 8106; //任务代码

    /**
     * 推送到搜索平台的观点数据
     *
     */
    public function process(){
        $start_time = '2016-04-01';
        $end_time = '2016-04-16';
        
        $where = "p_time>='".$start_time ."' and p_time<'".$end_time."' ";
        //$where = 'id=437772 or id=437775 ';

        $sql = "select id,ind_id,title,tags,content,content_pay,p_time,status from lcs_view where $where ";
        $rows =  Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $record = 0;
        if(is_array($rows) && sizeof($rows)>0){
            $record=count($rows);
            $res = '';
            foreach($rows as $val){
                if($val['tags'] == '') {
                    continue;
                }
                $content = $this->trimHtml($val['content'].$val['content_pay']);
                $res .="@\n@DF:U\n@id:$val[id]\n";
                $res .= mb_convert_encoding("@title:".strip_tags($val['title'])."\n@type:".$val['ind_id']."\n@tags:\n@content:".$content."\n@p_time:".strtotime($val['p_time'])."\n",'gb2312', mb_detect_encoding($content));
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

        }
        return $record;
    }

    private function trimHtml($content){
        $content =  strip_tags($content);
        $content = htmlspecialchars($content,ENT_QUOTES,'UTF-8');
        return $content;
    }

}
