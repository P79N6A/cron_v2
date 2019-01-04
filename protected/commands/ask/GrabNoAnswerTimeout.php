<?php
/**
 * 定时任务:抢答问题在规定的时间内没有回答，需要置顶
 * 1. 免费问题  1小时
 * 2. 付费问题  18小时
 * User: zwg
 * Date: 2015/7/27
 * Time: 17:33
 */

class GrabNoAnswerTimeout {


    const CRON_NO = 1104; //任务代码


    public function __construct(){

    }


    /**
     * 修改到时未回答的问题置顶
     * @throws LcsException
     */
    public function updateToTop(){
        try{
            $records = 0;
            //获取18小时前发布的还没有回答的免费问题，
            $records += Ask::model()->updateAskGrabIsTop(date('Y-m-d H:i:s',strtotime('-18hour')),0);
            //获取1小时前发布的还没有回答的付费问题，
            $records += Ask::model()->updateAskGrabIsTop(date('Y-m-d H:i:s',strtotime('-1hour')),1);
            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
}