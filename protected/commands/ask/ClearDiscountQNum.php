<?php
/**
 * 定时任务:清理过了折扣时间后的提问数量

 * User: zwg
 * Date: 2015/5/18
 * Time: 17:33
 */

class ClearDiscountQNum {


    const CRON_NO = 1101; //任务代码


    public function __construct(){

    }


    /**
     * 理财师问答表的限时折扣提问数量清零
     * @throws LcsException
     */
    public function clear(){
        try{
            $records = Ask::model()->updateAskPlannerDiscountQNum();
            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }



}