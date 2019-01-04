<?php

/**
 * 抢答问题被运营删除后，清理抢答表里面的数据
 * @author shixi_danxian
 * @date 2016/05/19
 */
class ClearGrabDeleted
{

    const CRON_NO = 1110; //任务代码

    public function __construct(){ }

    public function clear()
    {
        try{
            //获取已被删除的问题集合
            $q_data = Ask::model()->getDeletedQuestionIds();
            //已删问题id集合
            $q_ids  =  array();
            if (!empty($q_data))
            {
                foreach($q_data as $v)
                {
                    $q_ids[] = $v['q_id'];
                }
                //删除抢答问题
                $ret = Ask::model()->deleteGrabQuestion($q_ids);
                $qid = join(', ', $q_ids);
                if ($ret < 1)
                {
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "抢购问题删除失败：".$qid);
                }
                else
                {
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "抢购问题删除成功：".$qid);
                }
            }
            /*else
            {
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "暂无需要删除的抢购问题ID");
                return;
            }*/

        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }

    }

}