<?php
/**
 *
 */
class ReportHistory
{

    //任务代码
    const CRON_NO='' ;
    /**
     * 入口
     */
    public function option(){


        //获取订单信息
        $reportInfo = Yii::app()->lcs_standby_r->createCommand('select `id`,`uid`,`reported_type`,`reported_uid`,`reported_name`,`type`,`relation_id`,`content_type`,`reason`,`content`,`c_time`,`u_time`,`imgurl`,`status`,`handle_result`,`phone`,`device`,`version` from `lcs_report` where u_time>"2018-05-22" order by id asc')->queryAll();
        if(!empty($reportInfo)){
            foreach($reportInfo as &$val){
                $json=array();
                $json['id']=(int)$val['id'];
                $json['uid']=(int)$val['uid'];
                $json['reported_type']=(int)$val['reported_type'];
                $json['reported_uid']=(int)$val['reported_uid'];
                $json['reported_name']=$val['reported_name'];
                $json['type']=(int)$val['type'];
                $json['relation_id']=(int)$val['relation_id'];
                $json['content_type']=(int)$val['content_type'];
                $json['reason']=$val['reason'];
                $json['c_time']=$val['c_time'];
                $json['u_time']=$val['u_time'];
                $json['content']=$val['content'];
                $json['imgurl']=$val['imgurl'];
                $json['status']=(int)$val['status'];
                $json['handle_result']=$val['handle_result'];
                $json['phone']=$val['phone'];
                $json['device']=$val['device'];
                $json['version']=$val['version'];
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
}