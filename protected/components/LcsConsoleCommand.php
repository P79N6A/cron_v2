<?php
/**
 * 控制台命令基础类
 * Created by PhpStorm.
 * User: zwg
 * Date: 2015/5/12
 * Time: 15:50
 */

class LcsConsoleCommand extends CConsoleCommand {

    public function init(){

    }

    /**
     * 定时任务的启动记录
     * @param $cron_no 定时任务编号
     */
    protected function monitorLog($cron_no){
        Cron::model()->updateCron(array('env'=>CommonUtils::getServerIp(),'u_time'=>date('Y-m-d H:i:s')),'cron_no=:cron_no',array(':cron_no'=>$cron_no));
    }

    /**
     * 处理通知日志信息
     * @param $cron_no
     * @param string $level
     * @param string $msg
     */
    protected function noticeLog($cron_no, $level=CLogger::LEVEL_INFO, $msg=''){

        /*$corn = Cron::model()->getCronById($cron_no);
        $notice = !empty($corn['notice'])?$corn['notice']:"{}";
        $notice = json_decode($notice,true); //{"error":{"phone":[],"email":[]}}
        $notice = isset($notice[$level]) ? $notice[$level] : null;
        if(isset($notice['phone']) && !empty($notice['phone'])){
            //TODO 短信通知
        }else if(isset($notice['email']) && !empty($notice['email'])){
            //TODO 邮件通知
            $sendMail = new NewSendMail($corn['cron_name'].'任务监控报警','this is 测试。',$notice['email']);
        }else{

        }*/
        //TODO 写到数据库统一的log表中
        Cron::model()->saveCronLog($cron_no,$level,$msg);
    }

    /**
     * 记录产生的数据文件信息
     * @param $cron_no
     * @param string $msg
     * @param string $fileName
     * @param string $filePath
     */
    protected function dataLog($cron_no, $msg='', $fileName='', $filePath=''){
        $dataFile = DATA_PATH.DIRECTORY_SEPARATOR;
        if(!empty($filePath) && !file_exists($dataFile.$filePath)){
            mkdir($dataFile.$filePath,777);
            $dataFile .= $filePath.DIRECTORY_SEPARATOR;
        }

        if(empty($fileName)){
            $fileName = $cron_no.'_'.date('Y-m-d').'.log';
        }else{
            $fileName .= '_'.date('Y-m-d').'.log';
        }

        $dataFile .= $fileName;

        file_put_contents($dataFile,$msg,FILE_APPEND);
    }


}