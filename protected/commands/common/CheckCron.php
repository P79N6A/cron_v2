<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2015/6/2
 * Time: 13:39
 */

class CheckCron {
    const CRON_NO = 9902; //任务代码
    public function __construct(){
    }

    public function check(){
        $cronList = Cron::model()->getCronList();
        if(!empty($cronList)){
            foreach($cronList as $cron){
                if($cron['cron_no'] == CheckCron::CRON_NO){
                    $notice = !empty($cron['notice'])?$cron['notice']:"{}";
                    $notice = json_decode($notice,true); 
                    $notice = isset($notice['monitor']) ? $notice['monitor'] : null;
                    break;
                }
            }
            if(empty($notice)){
                $notice = array('phone'=>'13501136911','email'=>"lixiang29@ggt.sina.com.cn");
            }

            $cur_time = date('H:i:s');
            foreach($cronList as $cron){
                if($cron['space_time']<=0){
                    //持续执行的任务不在这里监控
                    continue;
                }

                if(!empty($cron['start_time']) && $cron['start_time']!='00:00:00'
                    && !empty($cron['end_time']) && $cron['end_time']!='00:00:00'
                    && ($cur_time<=$cron['start_time'] || $cur_time>=$cron['end_time'])){
                    //任务不在执行时间范围内
                    continue;
                }

                $next_time = date('Y-m-d H:i:s', strtotime($cron['u_time'])+intval($cron['space_time']));
                $now_time = date("Y-m-d H:i:s");
                if($next_time < $now_time ){
                    if((time()-strtotime($cron['notice_time']))<1800){
                        //上次发送的通知还未超过30分钟
                        continue;
                    }

                    //发送报警
                    if(isset($notice['phone']) && !empty($notice['phone'])){
                        //TODO 短信通知
                    }else if(isset($notice['email']) && !empty($notice['email'])){
                        //邮件通知
                        $sendMail = new NewSendMail($cron['cron_name'].'任务监控报警',$cron['cron_no'].'任务未在计划时间内正常执行,最近执行时间为:'.$cron['u_time'],$notice['email']);
                    }
                    //修改通知时间
                    Cron::model()->updateCron(array('notice_time'=>date('Y-m-d H:i:s')),'id=:id',array(":id"=>$cron['id']));

                }
                echo 'cron_no=>'.$cron['cron_no'],' cur_time=>',$cur_time,' next_time=>',$next_time,"\n";
            }

        }
    }
}
