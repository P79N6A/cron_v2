<?php
/**
 * 问答基本信息数据库访问类
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */

class Ask extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableNameAnswer(){
        return 'lcs_ask_answer';
    }

    public function tableNameQuestion(){
        return 'lcs_ask_question';
    }

    public function tableNameUnlock(){
        return 'lcs_unlock';
    }
    //问答表
    public function tableNamePlanner(){
        return 'lcs_ask_planner';
    }


    public function tableNameGrab(){
        return 'lcs_ask_grab';
    }

    public function tableNameGrabRecord(){
        return 'lcs_ask_grab_record';
    }
    
    public function tableNameSdata(){
        return 'lcs_ask_sdata';
    }

    /**
     * 获取指定时间前为评价的答案
     * @param $time
     */
    public function getNoScoreAnswer($time){
        $sql = "SELECT id,q_id,p_uid,c_time FROM  lcs_ask_answer  WHERE score=0 and c_time<=:time;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':time',$time,PDO::PARAM_STR);
        return $cmd->queryAll();
    }

    /**
     * 问题超时未回答处理
     * @return mixed
     */
    public function updateQuestionTimeout(){
        $update = 'UPDATE '.$this->tableNameQuestion().' SET u_time=NOW() WHERE STATUS=1 AND end_time<=NOW() and end_time>u_time;';
        $cmd = Yii::app()->lcs_w->createCommand($update);
        return $cmd->execute();
    }


    /**
     * 获取还没有答案的抢答问题
     * @param $e_time
     * @param int $is_price
     */
    public function updateAskGrabIsTop($e_time, $is_price=0){
        $cur_time = date('Y-m-d H:i:s');
        $sql = 'update '.$this->tableNameGrab().' set is_top=1,u_time=:u_time where is_top=0 and grab_num=0 and answer_num=0 and is_price=:is_price and c_time<=:c_time';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':u_time',$cur_time,PDO::PARAM_STR);
        $cmd->bindParam(':c_time',$e_time,PDO::PARAM_STR);
        $cmd->bindParam(':is_price',$is_price,PDO::PARAM_INT);
        return $cmd->execute();
    }


    /**
     * 修改问题抢答信息
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateAskGrab($id, $data){
        $db_w = Yii::app()->lcs_w;

        //没有需要修改的内容
        if(count($data)<=0){
            return false;
        }
        $result = $db_w->createCommand()->update($this->tableNameGrab(), $data, 'id=:id', array(':id'=>$id));

        return $result>=0?true:false;
    }

    /**
     * 修改问题
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateQuestion($id, $data){
        $db_w = Yii::app()->lcs_w;

        //没有需要修改的内容
        if(count($data)<=0){
            return false;
        }
        $result = $db_w->createCommand()->update($this->tableNameQuestion(), $data, 'id=:id', array(':id'=>$id));

        return $result>=0?true:false;
    }


    /**
     * 删除抢答记录
     * @param $q_id
     * @return mixed
     */
    public function deleteAskGrab($q_id){
        $sql = 'delete from '.$this->tableNameGrab().' where q_id=:q_id and answer_num>0;';

        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':q_id',$q_id,PDO::PARAM_INT);
        return $cmd->execute();
    }


    /**
     * 获取未确认
     * @param $e_time
     */
    public function getNoConfirmGrabQuestion($e_time){
        $sql='select id, answer_id from '.$this->tableNameQuestion().' where status=3 and answer_time<:e_time and is_grab=1 and is_price=1 and is_confirm=0';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':e_time',$e_time,PDO::PARAM_STR);
        return $cmd->queryAll();
    }

    /**
     * 修改记录
     * @param unknown $columns
     * @param string $conditions
     * @param unknown $params
     */
    public function updateAskGrabRecordStatusByQid($q_id, $columns){
        return Yii::app()->lcs_w->createCommand()->update($this->tableNameGrabRecord(),$columns,"q_id=:q_id and status=0",array(':q_id'=>$q_id));
    }


    /**
     * 修改记录
     * @param unknown $columns
     * @param string $conditions
     * @param unknown $params
     */
    public function updateAskGrabRecordStatus($q_id,$p_uid, $columns){
        return Yii::app()->lcs_w->createCommand()->update($this->tableNameGrabRecord(),$columns,"q_id=:q_id and p_uid=:p_uid and status=0",array(':q_id'=>$q_id,':p_uid'=>$p_uid));
    }

    /**
     * 减少抢答的用户数量
     * @param $q_id
     * @return mixed
     */
    public function reduceAskGrabGrabNum($q_id){
        $sql = 'update '.$this->tableNameGrab().' set grab_num=grab_num-1 where q_id=:q_id and answer_limit>=(grab_num+answer_num) and grab_num>0;';

        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':q_id',$q_id,PDO::PARAM_INT);
        return $cmd->execute();
    }

    /**
     * 获取理财师的抢答记录
     * @param $q_id
     * @param $p_uid
     * @param int $status
     * @return mixed
     */
    public function getAskGrabRecordsByCtime($s_time, $e_time, $is_price=0){
        $sql='select id, q_id, p_uid, status, c_time, u_time from '.$this->tableNameGrabRecord().' where c_time>:s_time and c_time<=:e_time and status=0 and is_price=:is_price;';

        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':s_time',$s_time,PDO::PARAM_STR);
        $cmd->bindParam(':e_time',$e_time,PDO::PARAM_STR);
        $cmd->bindParam(':is_price',$is_price,PDO::PARAM_INT);
        return $cmd->queryAll();
    }


    /**
     * 批量修改折扣理财师信息
     * @param $update_data
     * @return int
     */
    public function updateAskPlannerDiscountInfo($update_data){
        $num=0;
        if(!empty($update_data)){
            $sql='';
            $count=0;
            foreach($update_data as $s_uid=>$status){
                $_q_num='';
                if($status>=30){
                    $_q_num = ',discount_q_num=0';
                }
                $sql .= 'update lcs_ask_planner set discounting_status='.intval($status).$_q_num.' where s_uid='.intval($s_uid).';';
                if(++$count==50){
                    $num += Yii::app()->lcs_w->createCommand($sql)->execute();
                    $sql='';
                    $count=0;
                }

            }
            if(!empty($sql)){
                $num += Yii::app()->lcs_w->createCommand($sql)->execute();
            }
        }
        return $num;


    }

    /**
     * 理财师问答表的限时折扣提问数量清零
     */
    public function updateAskPlannerDiscountQNum(){
        $cur_time = date('H:i');
        $num = 0;
        //折扣时间在一天
        $sql = 'update '.$this->tableNamePlanner().' set discount_q_num=0 where is_discount=1 and discount_s_time<discount_e_time and discount_q_num>0 and discount_s_time>=:cur_time;';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':cur_time',$cur_time, PDO::PARAM_STR);
        $num += $cmd->execute();

        //折扣时间跨天
        $sql = 'update '.$this->tableNamePlanner().' set discount_q_num=0 where is_discount=1 and discount_s_time>discount_e_time and discount_q_num>0 and discount_e_time<=:cur_time1 and discount_s_time>=:cur_time2;';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':cur_time1',$cur_time, PDO::PARAM_STR);
        $cmd->bindParam(':cur_time2',$cur_time, PDO::PARAM_STR);
        $num += $cmd->execute();
        return $num;
    }


    /**
     * 获取开启折扣的理财师
     * @param null $fields
     * @return mixed
     */
    public function getDiscountPlanner($fields=null){
        $select = '*';
        if(!empty($fields)){
            if(is_string($fields)){
                $select = $fields;
            }else if(is_array($fields)){
                $select = implode(',',$fields);
            }
        }
        $sql = 'SELECT '.$select.' FROM '.$this->tableNamePlanner().' WHERE is_open =1 AND is_discount = 1';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryAll();
    }

    /**
     * 获取问题详情
     * @param $q_ids
     * @param $fields
     * @return array
     */
    public function getQuestionInfo($q_ids,$fields){
        $q_ids = (array)$q_ids;
        $select = 'id';
        if(is_string($fields)){
            $select = $fields;
        }else if(is_array($fields)){
            $select = implode(',',$fields);
        }

        $sql = "SELECT ".$select." FROM ".$this->tableNameQuestion()." WHERE id in (".implode(',',$q_ids).")";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
        $result=array();
        if(!empty($data)){
            foreach($data as $item){
                if(isset($item['id'])){
                    $result[$item['id']] = $item;
                }else{
                    $result[] = $item;
                }
            }
        }

        return $result;
    }


    /**
     * 获取问答信息
     * @param string $fields
     * @return mixed
     */
    public function getAskInfo($fields="s_uid"){
        $sql = "SELECT ".$fields." FROM ".$this->tableNamePlanner();
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryAll();
    }

    /**
     * 获得回答响应时间
     * @param $p_uid
     * @param $s_time
     * @param $e_time
     */
    public function getQuestionRespTime($p_uid,$s_time,$e_time){
        $sql = "SELECT a.p_uid,a.c_time as answer_time,q.c_time as question_time from ".$this->tableNameAnswer().
            " AS a LEFT JOIN ".$this->tableNameQuestion()." AS q".
            " ON a.q_id=q.id".
            " WHERE q.status>0 and q.is_grab=0 and a.c_time>=:s_time and a.c_time<=:e_time and a.p_uid=:p_uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(":s_time",$s_time,PDO::PARAM_STR);
        $cmd->bindParam(":e_time",$e_time,PDO::PARAM_STR);
        $cmd->bindParam(":p_uid",$p_uid,PDO::PARAM_INT);

        return $cmd->queryAll();

    }

    /**
     * 统计理财师回答问题数排行
     */
    public function getPlannerRankByAskNum($ind_id = NULL)
    {
        $where = '';
        if ($ind_id !== NULL & in_array($ind_id, range(1, 8))) {
            $where .= " b.ind_id={$ind_id} AND ";
        }
        $start_date = date('Y-m-d 00:00:00',  strtotime("-30 days"));
        $end_date = date('Y-m-d 00:00:00');
        $sql = 'SELECT a.`p_uid`, COUNT(*) AS total FROM '.$this->tableNameAnswer(). " AS a JOIN ".$this->tableNameQuestion()." AS b ON a.q_id= b.id WHERE {$where} a.`c_time`>=:start_time AND a.`c_time`<:end_time GROUP BY a.`p_uid` ORDER BY total DESC LIMIT 10";
        return Yii::app()->lcs_r->createCommand($sql)->bindParam(':start_time', $start_date, PDO::PARAM_STR)->bindParam(':end_time', $end_date, PDO::PARAM_STR)->queryAll();
    }


    /**
     * 获取用户用户回答的数量
     * @param string $p_uid
     * @param null $start_date
     * @param null $end_date
     * @return mixed
     */
    public function getAskCount($p_uid='', $start_time='', $end_time=''){
        $cdn = '';
        if(!empty($p_uid)){
            $cdn .= ' AND p_uid=:p_uid';
        }
        if(!empty($start_time)){
            $cdn .= ' AND c_time>=:start_time';
        }
        if(!empty($end_time)){
            $cdn .= ' AND c_time<:end_time';
        }
        $sql = 'SELECT p_uid,count(p_uid) as num FROM '.$this->tableNameAnswer().' WHERE 1=1 '.$cdn.' GROUP BY p_uid;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        if(!empty($p_uid)){
            $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
        }
        if(!empty($start_time)){
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if(!empty($end_time)){
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }


    /**
     * 获取用户向理财师提问的问题信息 参与理财师影响力计算
     * @param string $start_time
     * @param string $end_time
     */
    public function getQuestionOfInfluence($start_time='', $end_time=''){
        $sql = 'SELECT q.`id`,q.`uid`, q.`p_uid`,q.`is_price` FROM '.$this->tableNameQuestion().' q WHERE q.status>1 AND q.c_time>=:start_time AND q.c_time<:end_time;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }

    /**
     * 获取用户解锁的问题信息 参与理财师影响力计算
     * @param string $start_time
     * @param string $end_time
     * @return mixed
     */
    public function getUnlockQuestionOfInfluence($start_time='', $end_time=''){
        $sql = 'SELECT q.`id`,q.`uid`, q.`p_uid`,q.`is_price` FROM '.$this->tableNameQuestion().' q LEFT JOIN '.$this->tableNameUnlock().' u ON q.`id`=u.relation_id WHERE u.type=1 AND u.c_time>=:start_time AND u.c_time<:end_time;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }


    /**
     * 获取理财师持有免费问题的数量 不包括折扣免费的问题数量
     * @return mixed
     */
    public function getAskPlannerOfHoldQNum($s_uid){
        $sql = 'SELECT count(p_uid) as total FROM '.$this->tableNameQuestion().' where p_uid=:p_uid and status=1 AND is_discount=0 AND is_price=0 AND price=0;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':p_uid', $s_uid, PDO::PARAM_INT);
        return $cmd->queryScalar();
    }

    /**
 * 修改持有免费问题数
 * @param $s_uids
 * @return int
 */
    public function updateAskPlannerOfHoldQNum($update_data){
        $records=0;
        if(!empty($update_data)){
            $sql='';
            $count=0;
            foreach($update_data as $s_uid=>$num){
                $sql .= 'update '.$this->tableNamePlanner().' set hold_q_num='.intval($num).' where s_uid='.intval($s_uid).';';
                if(++$count==50){
                    $records += Yii::app()->lcs_w->createCommand($sql)->execute();
                    $sql='';
                    $count=0;
                }

            }
            if(!empty($sql)){
                $records += Yii::app()->lcs_w->createCommand($sql)->execute();
            }
        }
        return $records;
    }


    /**
     * 修改理财师回答问题的响应时间
     * @param $s_uids
     * @return int
     */
    public function updateAskPlannerOfRespTimeNum($update_data){
        $records=0;
        if(!empty($update_data)){
            $sql='';
            $count=0;
            foreach($update_data as $s_uid=>$num){
                $sql .= 'update '.$this->tableNamePlanner().' set resp_time_num='.intval($num).' where s_uid='.intval($s_uid).';';
                if(++$count==50){
                    $records += Yii::app()->lcs_w->createCommand($sql)->execute();
                    $sql='';
                    $count=0;
                }

            }
            if(!empty($sql)){
                $records += Yii::app()->lcs_w->createCommand($sql)->execute();
            }
        }
        return $records;
    }
    
    /**
     * //观点相关数 和 最后时间
     * @param type $begin_time 开始统计时间
     * @return type
     */
    public function getAskTagSdata($begin_time){
        $ask_count_sql = "SELECT tag_id,COUNT(tag_id) AS `count`,MAX(c_time) AS c_time FROM ".$this->tableNameSdata()." WHERE c_time>'{$begin_time}' GROUP BY tag_id";
        $ask_count_data = Yii::app()->lcs_r->createCommand($ask_count_sql)->queryAll();
        $result = array();
        if(!empty($ask_count_data)){
            foreach ($ask_count_data as $item){
                $result[$item['tag_id']] = array(
                    'count'=>$item['count'],
                    'c_time'=>$item['c_time']
                );
            }
        }
        return $result;
    }

    /*
     * 1401
     * 获取理财师30天内回答基金类观点阅读量最多的一条
     */
    public function getMostLockQuestionof30Days($p_uids=array(),$ind_id=2){
        if(empty($p_uids)){
            return;
        }
        $p_uids = (array)$p_uids;
        $where = '';
        if ($ind_id !== NULL & in_array($ind_id, range(1, 8))) {
            $where .= " b.ind_id={$ind_id} AND b.status>=1 AND ";
        }
        $start_date = date('Y-m-d 00:00:00',  strtotime("-30 days"));
        $end_date = date('Y-m-d 00:00:00');

        $sql = "select * from (SELECT a.id answer_id,b.id q_id,a.unlock_num,a.p_uid FROM `".$this->tableNameAnswer()."` a left join lcs_ask_question b on b.id=a.q_id where {$where} a.c_time>:start_time and a.c_time<:end_time and a.p_uid in (".implode(',',$p_uids).")  order by a.unlock_num desc) t group by p_uid order by unlock_num desc";
        
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_time', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_date, PDO::PARAM_STR);

        $list = $cmd->queryAll();
        return $list;
    }

    /**
     * 获取已被删除的抢答问题id
     * @return mixed
     */
    public function getDeletedQuestionIds()
    {
        $sql = "SELECT ag.q_id,ag.ind_id,aq.status FROM ".$this->tableNameGrab()." ag LEFT JOIN ".$this->tableNameQuestion()." aq ON ag.q_id=aq.id WHERE ag.answer_limit>(ag.grab_num+ag.answer_num) AND aq.status<1";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data= $cmd->queryAll();
        return $data;
    }

    /**
     * 删除抢答问题记录
     * @param $q_ids q_id数组
     * @return bool
     */
    public function deleteGrabQuestion($q_ids)
    {
        if (empty($q_ids))
        {
            return false;
        }

        $sql = "DELETE FROM ".$this->tableNameGrab()." WHERE q_id IN(".join(',', $q_ids).")";
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $ret = $cmd->execute();
        return $ret;
    }

}