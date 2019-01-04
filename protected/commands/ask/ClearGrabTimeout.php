<?php
/**
 * 定时任务:清理抢答超时的记录
 * 1. 免费问题  15分钟超时
 * 2. 付费问题  30分钟超时
 * User: zwg
 * Date: 2015/7/27
 * Time: 17:33
 */

class ClearGrabTimeout {


    const CRON_NO = 1102; //任务代码


    public function __construct(){

    }


    /**
     * 理财师问答表的限时折扣提问数量清零
     * @throws LcsException
     */
    public function clear(){
        try{
            $records = 0;
            //免费抢答超时记录
            $fee_list = Ask::model()->getAskGrabRecordsByCtime(date('Y-m-d H:i:s',time()-1200), date('Y-m-d H:i:s',time()-900),0);
            if(!empty($fee_list) && count($fee_list)>0){
                foreach($fee_list as $item){
                    $this->updateGrabInfo($item['p_uid'],$item['q_id']);
                    $records++;
                }
            }

            //付费抢答超时记录
            $price_list = Ask::model()->getAskGrabRecordsByCtime(date('Y-m-d H:i:s',time()-2100), date('Y-m-d H:i:s',time()-1800),1);
            if(!empty($price_list) && count($price_list)>0){
                foreach($price_list as $item){
                    $this->updateGrabInfo($item['p_uid'],$item['q_id']);
                    $records++;
                }
            }

            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }


    /**
     * 修改理财师抢答问题超时记录
     * @param $p_uid
     * @param $q_id
     */
    private function updateGrabInfo($p_uid, $q_id){
        $transaction = Yii::app()->lcs_w->beginTransaction();
        try {
            //修改抢答记录状态
            $ag_record['status']=-1;
            $ag_record['u_time']=date('Y-m-d H:i:s');
            Ask::model()->updateAskGrabRecordStatus($q_id, $p_uid, $ag_record);

            //抢答数 减1
            $res = Ask::model()->reduceAskGrabGrabNum($q_id);
            if($res!==1){
                throw new Exception('修改抢答数据错误');
            }
            $transaction->commit();
        } catch(Exception $e) { // 如果有一条查询失败，则会抛出异常
            $transaction->rollBack();
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '清理抢答超时的记录p_uid='.$p_uid.' q_id='.$q_id.','.LcsException::errorHandlerOfException($e)->toJsonString());
            return;
        }
    }



}