<?php
/**
 * 定时任务:对过期没有评价的问答自动评价(超过3天)
 * User: zwg
 * Date: 2015/8/26
 * Time: 17:33
 */

class QuestionAutoScore {


    const CRON_NO = 1107; //任务代码
    public $default_score = 5;###默认5星评级
    public $default_time = 259200; //默认三天


    public function __construct(){

    }


    /**
     * 自动评级问题得分
     * @throws LcsException
     */
    public function autoScore(){
        $_time = date("Y-m-d H:i:s",time()-$this->default_time);
        $no_score_answers = Ask::model()->getNoScoreAnswer($_time);
        /*$no_score_answers = array(
            array('id'=>1981,'q_id'=>68873,'p_uid'=>2804062791)
        );*/
        $records = 0;
        if(!empty($no_score_answers))
        {
            $update_score_answer_id = array(); //评分的答案ID
            $update_no_score_answer_id = array(); //不得分的答案ID  score=-1 为了下次不再获取此条数据
            $update_question_id = array(); //评分的问题
            $update_p_uid = array(); //评分的理财师

            $q_ids = array(); //先获取所有的问题ID
            foreach($no_score_answers as $item){
                $q_ids[] = $item['q_id'];
            }

            $questions = Ask::model()->getQuestionInfo($q_ids,array('id','answer_id','is_score','is_grab','is_confirm'));

            foreach($no_score_answers as $item){
                if(!isset($questions[$item['q_id']])){
                    continue;
                }
                $q = $questions[$item['q_id']];
                if($q['is_grab']==1){
                    //抢答处理
                    if($q['is_confirm']==1){
                        if($q['is_score']==0 && $q['answer_id'] == $item['id']){
                            $update_score_answer_id[] = $item['id'];
                            $update_question_id[] = $item['q_id'];
                            $update_p_uid[] = $item['p_uid'];
                        }else{
                            $update_no_score_answer_id[] = $item['id'];
                        }
                    }
                }else{
                    $update_score_answer_id[] = $item['id'];
                    $update_question_id[] = $item['q_id'];
                    $update_p_uid[] = $item['p_uid'];
                }
            }

            $u_time = date("Y-m-d H:i:s");
            $update_planner_sql = '';
            if(!empty($update_p_uid)){
                $update_planner_sql_tp = 'update lcs_ask_planner set satisfaction_num=satisfaction_num+%d,q_score_num=q_score_num+1,u_time="'.$u_time.'" where s_uid=%d;';
                foreach ($update_p_uid as $p_uid){
                    $update_planner_sql.=sprintf($update_planner_sql_tp,$this->default_score,$p_uid);
                }
            }

            $transaction=Yii::app()->lcs_w->beginTransaction();
            try{
                //被采纳的答案更新为满分
                if(!empty($update_score_answer_id)){
                    $answer_sql = "UPDATE lcs_ask_answer SET score=".$this->default_score." where id in(".implode(",", $update_score_answer_id).")";
                    $records += Yii::app()->lcs_w->createCommand($answer_sql)->execute();
                }

                //未被采纳的答案更新为-1
                if(!empty($update_no_score_answer_id)){
                    $answer_sql = "UPDATE lcs_ask_answer SET score=-1 where id in(".implode(",", $update_no_score_answer_id).")";
                    $records += Yii::app()->lcs_w->createCommand($answer_sql)->execute();
                }

                //更新理财师信息
                if(!empty($update_planner_sql)){
                    Yii::app()->lcs_w->createCommand($update_planner_sql)->execute();
                }

                //更新问题信息
                if(!empty($update_question_id)){
                    $question_sql = "UPDATE lcs_ask_question SET is_score=1 where id in(".implode(",", $update_question_id).")";
                    Yii::app()->lcs_w->createCommand($question_sql)->execute();
                }
                $transaction->commit();
                $isClearCache = true;
            }catch(Exception $e)
            {
                $transaction->rollBack();
                $isClearCache = false;
                throw LcsException::errorHandlerOfException($e);
            }
            //清除问题缓存
            if($isClearCache)
            {
                $this->clearQuestionCache($update_question_id);
                $this->clearPlannerCache($update_p_uid);
            }
        }

        return $records;
    }


    /**
 * 清除问题缓存
 * @param $q_ids
 */
    private function clearQuestionCache($q_ids){
        if(empty($q_ids)){
            return;
        }
        try{
            $curl =Yii::app()->curl;
            $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
            $url=LCS_WEB_INNER_URL.'/cacheApi/question';

            foreach($q_ids as $q_id){
                $params = array('q_id'=>$q_id);
                $res = $curl->get($url,$params);
                if(!empty($res)){
                    $res_json = json_decode($res,true);
                    if(!isset($res_json['code']) || $res_json['code']!=0){
                        Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "清除缓存失败 q_id:".$q_id." 返回错误：".$res);
                    }
                }else{
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "清除缓存失败 q_id:".$q_id." 返回数据为空");
                }
            }
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "清除缓存失败 q_ids:".json_encode($q_ids)." error:".LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    /**
     * 清除理财师缓存
     * @param $q_ids
     */
    private function clearPlannerCache($p_uids){
        if(empty($p_uids)){
            return;
        }
        try{
            $curl =Yii::app()->curl;
            $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
            $url=LCS_WEB_INNER_URL.'/cacheApi/planner';

            foreach($p_uids as $p_id){
                $params = array('p_uid'=>$p_id);
                $res = $curl->get($url,$params);
                if(!empty($res)){
                    $res_json = json_decode($res,true);
                    if(!isset($res_json['code']) || $res_json['code']!=0){
                        Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "清除缓存失败 p_uid:".$p_id." 返回错误：".$res);
                    }
                }else{
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "清除缓存失败 p_uid:".$p_id." 返回数据为空");
                }
            }
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "清除缓存失败 p_uids:".json_encode($p_uids)." error:".LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}