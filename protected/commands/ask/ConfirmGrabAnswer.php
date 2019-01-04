<?php
/**
 * 定时任务:问题有答案 3天后自动确认默认最佳答案
 * 1. 免费问题  15分钟超时
 * 2. 付费问题  30分钟超时
 * User: zwg
 * Date: 2015/7/27
 * Time: 17:33
 */

class ConfirmGrabAnswer {


    const CRON_NO = 1103; //任务代码


    public function __construct(){

    }


    /**
     * 确认问题最优答案
     * @throws LcsException
     */
    public function confirm(){
        try{
            $records = 0;
            //获取三天内还没有确认最优答案的问题ID
            $q_list = Ask::model()->getNoConfirmGrabQuestion(date('Y-m-d H:i:s',strtotime('-3day')));
            if(!empty($q_list) && count($q_list)>0){
                foreach($q_list as $item){
                    $this->confirmGrabAnswer($item['answer_id']);
                    $records++;
                }
            }
            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }


    /**
     * 修改理财师抢答问题超时记录 保证逻辑一致性，直接调用前端接口
     * @param $p_uid
     * @param $q_id
     */
    private function confirmGrabAnswer($answer_id){
        try{
            $curl =Yii::app()->curl;
            $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
            $url=LCS_WEB_INNER_URL.'/api/askConfirmGrabAnswer';
            $params = array('ans_id'=>$answer_id,'internal'=>1,'signature'=>CommonUtils::getCRC32('lcs_cron_confirmGrabAnswer_'.$answer_id));
            $res = $curl->get($url,$params);
            if(!empty($res)){
                $res_json = json_decode($res,true);
                if(!isset($res_json['code']) || $res_json['code']!=0){
                    throw new LcsException('返回错误：'.$res);
                }
            }else{
                throw new LcsException('返回数据为空');
            }
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "answer_id:".$answer_id." ".LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }



}