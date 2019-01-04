<?php
/**
 * 定时任务:问题超时未回答处理
 * User: zwg
 * Date: 2015/8/26
 * Time: 17:33
 */

class QuestionTimeout {


    const CRON_NO = 1106; //任务代码


    public function __construct(){

    }


    /**
     * 修改未回答的问题的修改时间
     * @throws LcsException
     */
    public function updateOfTimeout(){
        try{
            $records = Ask::model()->updateQuestionTimeout();
            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
}