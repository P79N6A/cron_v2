<?php
/**
 *
 */
class PushReportInfo
{

    //任务代码
    const CRON_NO=14010;
    /**
     * 入口
     */
    public function option(){
        $end = time() + 50;
        while(time()<$end){
            $key = "lcs_push_report";
            $id = Yii::app()->redis_w->lpop($key);
            if(!$id){
                echo "暂时没有数据\n";
                continue;
            }else{
                sleep(2);
            }
            $id=json_decode($id,true);
            $json=array();
            if($id['type']==1){
                $reportInfo = Yii::app()->lcs_standby_r->createCommand('select `id`,`uid`,`reported_type`,`reported_uid`,`reported_name`,`type`,`relation_id`,`content_type`,`reason`,`content`,`c_time`,`u_time`,`imgurl`,`status`,`handle_result`,`phone`,`device`,`version`,`fr` from `lcs_report` where id='.$id['id'])->queryRow();
            }else if($id['type']==2){
                $reportInfo = Yii::app()->lcs_comment_r->createCommand('select `id`,`relation_id`,`uid`,`u_type`,`content`,`c_time`,`u_time` from `lcs_comment_master` where id='.$id['id'])->queryRow();
            }
            var_dump($reportInfo);
            if(empty($reportInfo)){
                continue;
            }
            $json['id']=(int)$reportInfo['id'];
            $json['uid']=(int)$reportInfo['uid'];
            $json['reported_type']=isset($reportInfo['reported_type'])?(int)$reportInfo['reported_type']:0;
            $json['reported_uid']=isset($reportInfo['reported_uid'])?(int)$reportInfo['reported_uid']:0;
            $json['reported_name']=isset($reportInfo['reported_name'])?$reportInfo['reported_name']:'';
            $json['type']=isset($reportInfo['type'])?(int)$reportInfo['type']:10;
            $json['relation_id']=(int)$reportInfo['relation_id'];
            $json['content_type']=isset($reportInfo['content_type'])?(int)$reportInfo['content_type']:0;
            $json['reason']=isset($reportInfo['reason'])?$reportInfo['reason']:'';
            $json['c_time']=$reportInfo['c_time'];
            $json['u_time']=$reportInfo['u_time'];
            $json['content']=$reportInfo['content'];
            $json['imgurl']=isset($reportInfo['imgurl'])?$reportInfo['imgurl']:'';
            $json['status']=isset($reportInfo['status'])?(int)$reportInfo['status']:0;
            $json['handle_result']=isset($reportInfo['handle_result'])?$reportInfo['handle_result']:'';
            $json['phone']=isset($reportInfo['phone'])?$reportInfo['phone']:'';
            $json['device']=isset($reportInfo['device'])?$reportInfo['device']:'';
            $json['version']=isset($reportInfo['version'])?$reportInfo['version']:'';
            $json['fr']=isset($reportInfo['fr'])?$reportInfo['fr']:'';
            if($id['type']==2){
                $json['id']=(int)$reportInfo['id']+500000;
                $json['reported_type']=(int)$reportInfo['u_type'];
                $json['type']=10;
            }
            $param_josn = json_encode($json);
            $header = array(
                'Content-Type'=>'application/json; charset=utf-8',
            );
            $sh = Yii::app()->curl->setTimeOut(10)->setHeaders($header);
            $data = $sh->post(
                "https://beidou-api.yk5800.com/api/RestService/report",
                $param_josn
            );
            $data = json_decode($data,true);
            var_dump($data);
            if(!empty($data)){
                Common::model()->saveLog("举报信息推送成功:".json_encode($data),"success","push_report");
            }else{
                Common::model()->saveLog("举报信息推送失败:".$param_josn,"error","push_report");
            }
        }
    }
}