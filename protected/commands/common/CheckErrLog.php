<?php
/**
 * 检查错误日志的任务
 * User: zwg
 * Date: 2015/6/2
 * Time: 13:39
 */

class CheckErrLog {
    const CRON_NO = 9903; //任务代码
    public function __construct(){
    }

    public function check(){
        $cur_time = date('Y-m-d H:i:s', time()-60);
        $logList = Cron::model()->getCronLogByLevel(CLogger::LEVEL_ERROR,$cur_time);
        if(!empty($logList)){
            $cronList = Cron::model()->getCronList('cron_no,cron_name,notice');
            $cron_map = array();
            if(!empty($cronList)){
                foreach($cronList as $cron){
                    $cron_map[$cron['cron_no']] = $cron;
                }
                unset($cronList);
            }
            foreach($logList as $log){
                //发送报警
                $cron = isset($cron_map[$log['cron_no']])?$cron_map[$log['cron_no']]:null;
                if(empty($cron)){
                    continue;
                }
                $notice = !empty($cron['notice'])?$cron['notice']:"{}";
                $notice = json_decode($notice,true); //{"error":{"phone":[],"email":[]}}
                $notice = isset($notice['monitor']) ? $notice['monitor'] : null;
                if(isset($notice['phone']) && !empty($notice['phone'])){
                    //TODO 短信通知
                }else if(isset($notice['email']) && !empty($notice['email'])){
                    //邮件通知
                    $sendMail = new NewSendMail($cron['cron_name'].'任务错误日志','log_id:'.$log['id'].' log_time:'.$log['c_time'].' message:'.$log['message'],$notice['email']);
                }
            }
        }
    }
}