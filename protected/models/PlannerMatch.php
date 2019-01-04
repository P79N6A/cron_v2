<?php

/**
 * Description of PlannerMatch
 *
 * @author hailin3
 */
class PlannerMatch extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return TABLE_PREFIX . 'planner_match';
    }

    //比赛表
    public function tableNameProp() {
        return TABLE_PREFIX . 'prop';
    }

    /**
     * 获取大赛的计划
     * @param type $matchid
     * @return array
     */
    public function getMatchPlan($matchid) {
        $result = array();
        if (empty($matchid)) {
            return $result;
        }
        $sql = "select distinct pln_id from {$this->tableName()} where status=0 and match_id='{$matchid}'";
        $result = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        return $result;
    }

    /**
     * 获取比赛中的相关id
     * @param int $type 比赛类型,2001计划比赛
     */
    public function getMatchRelationIds($type, $use_channel) {
        $sql = "select DISTINCT relation_id from " . $this->tableNameProp() . " where type=" . intval($type) . " and status=0 and use_channel=".intval($use_channel);
        $db = Yii::app()->lcs_r;
        $res = $db->createCommand($sql)->queryColumn();
        return $res;
    }

    /**
     * 获取理财师比赛的计划id
     */
    /*public function getPlannerMatchPlnId($match_id) {
        $sql = "select pln_id from " . $this->tableName()." where match_id=".intval($match_id)." and status=0 ";
        $db = Yii::app()->lcs_r;
        $res = $db->createCommand($sql)->queryColumn();
        return $res;
    }*/

    /**
     * 添加比赛
     */
    public function addMatch($data) {
        try {
            $sql = "insert into " . $this->tableNameProp() . " (title,summary,type,relation_id,relation_p_uid,relation_price,amount_total,amount_remainder,efficient,expire_time,price,staff_uid,use_channel,status,c_time,u_time) "
                    . "values(:title,:summary,:type,:relation_id,:relation_p_uid,:relation_price,:amount_total,:amount_remainder,:efficient,:expire_time,:price,:staff_uid,:use_channel,:status,:c_time,:u_time)";
            $db = Yii::app()->lcs_w;
            $cmd = $db->createCommand($sql);
            $cmd->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $cmd->bindParam(':summary', $data['summary'], PDO::PARAM_STR);
            $cmd->bindParam(':type', $data['type'], PDO::PARAM_STR);
            $cmd->bindParam(':relation_id', $data['relation_id'], PDO::PARAM_STR);
            $cmd->bindParam(':relation_p_uid', $data['relation_p_uid'], PDO::PARAM_STR);
            $cmd->bindParam(':relation_price', $data['relation_price'], PDO::PARAM_STR);
            $cmd->bindParam(':amount_total', $data['amount_total'], PDO::PARAM_STR);
            $cmd->bindParam(':amount_remainder', $data['amount_remainder'], PDO::PARAM_STR);
            $cmd->bindParam(':efficient', $data['efficient'], PDO::PARAM_STR);
            $cmd->bindParam(':expire_time', $data['expire_time'], PDO::PARAM_STR);
            $cmd->bindParam(':price', $data['price'], PDO::PARAM_STR);
            $cmd->bindParam(':staff_uid', $data['staff_uid'], PDO::PARAM_STR);
            $cmd->bindParam(':use_channel', $data['use_channel'], PDO::PARAM_STR);
            $cmd->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $cmd->bindParam(':c_time', date("Y-m-d H:i:s", time()), PDO::PARAM_STR);
            $cmd->bindParam(':u_time', date("Y-m-d H:i:s", time()), PDO::PARAM_STR);
            $res = $cmd->execute();
            return $res;
        } catch (Exception $ex) {
            var_dump("add Match fail:" . $ex->getMessage());
        }
    }
    
    /**
     * 获取参加大赛的pln_id
     * @param array $match_ids
     */
    public function getMatchPlnIds(){
        $db_r = Yii::app()->lcs_r;
        $sql = "select pln_id from {$this->tableName()} where match_id >0 and status = 0";
        return $db_r->createCommand($sql)->queryColumn();
    }

}
