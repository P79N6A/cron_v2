<?php

/**
 * PlanSubscription
 * @date 2016/12/30
 */
class PlanSubscription extends CActiveRecord
{
    //投资计划表
    public function tableName() {
        return TABLE_PREFIX .'plan_subscription';
    }

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * 获取用户的订阅计划信息
     * @param $uid
     * @param $pln_id
     * @return mixed
     */
    public function getPlanSubscriptionInfo($uid, $pln_id, $status = array()) {
        $uid = intval($uid);
        $pln_id = intval($pln_id);

        $db_r =  Yii::app()->lcs_r;
        $sql = "select id,uid,pln_id,sub_fee,init_money,sub_start_date,status,expire_time, c_time from ".$this->tableName()." where uid=$uid and pln_id=$pln_id and status>0 order by c_time desc limit 1";

        return $db_r->createCommand($sql)->queryRow();
    }


    /**
     * 检测用户是否订阅该计划
     * 退款，已付款，不退，保障失效都可以评价
     * @param str/int $pln_id 计划id
     * @param str/int $uid 用户id
     * @param array $status 计化订阅状态
     * @return bool
     */
    public function isSubPlan($pln_id, $uid, $status=array(-1,1,2,3)) {
        if (empty($pln_id) || empty($uid)) {
            return FALSE;
        }
        $where = "pln_id=".$pln_id." and uid=".$uid." and status in (".implode(',',$status).")";
        $db_r = Yii::app()->lcs_r;
        #不要被count(*)欺骗
        $sql = "SELECT count(*) as sum FROM ".$this->tableNameSubscription()." WHERE ".$where;
        $data = $db_r->createCommand($sql)->queryRow();

        if ($data['sum'] != 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $data 生成订阅数据
     * @return mixed
     */
    public function savePlanSub($data){
        $db_w = Yii::app()->lcs_w;
        $res = $db_w->createCommand()->insert($this->tableName(),$data);
        if($res==1){
            return $db_w->getLastInsertID();
        }else{
            return $res;
        }
    }
   
    /**
     * 获取计划订阅列表
     */
    public function getPlanSubList($pln_id){
        $pln_id = intval($pln_id);

        $db_r =  Yii::app()->lcs_r;
        $sql = "select id,uid,pln_id,sub_fee,init_money,sub_start_date,status,expire_time, c_time from ".$this->tableName()." where pln_id='$pln_id' and status>0 order by c_time desc";
        return $db_r->createCommand($sql)->queryAll();
    }
}
