<?php

class Vip extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    // 缓存开关
    private $isUseCache = true;

    //理财师
    public function tableName()
    {
        return TABLE_PREFIX . 'service';
    }

    //服务购买表
    public function tableNameBuy()
    {
        return TABLE_PREFIX . 'service_buy';
    }

    //订阅观点包历史表
    public function tableNameBuyHistory()
    {
        return TABLE_PREFIX . 'service_buy_history';
    }

    //服务详情表
    public function tableNameInfo()
    {
        return TABLE_PREFIX . 'service_info';
    }

    //服务详情表审核表
    public function tableNameInfoAudit()
    {
        return TABLE_PREFIX . 'service_info_audit';
    }

    //服务产品表
    public function tableNameProduct()
    {
        return TABLE_PREFIX . 'service_product';
    }


    /**
     * 获取服务是否开通
     * @param $p_uid
     * @return mixed
     */
    public function getIsService($p_uid,$vip=1,$service_type=1)
    {
        $where = '';
        if ($vip){
            $where = ' and service_type='.$service_type;
        }
        $res = array();
        if ($p_uid > 0) {
            $sql = "select sp_type,service_id,sp_content_id from ".$this->tableName()." s left join ".$this->tableNameProduct()." sp on s.id=sp.service_id  where s.p_uid='$p_uid' and service_status=1".$where;
            $cmd = Yii::app()->lcs_r->createCommand($sql);
            $res = $cmd->queryAll();
        }
        return $res;
    }
    /**
     * 获取理财师服务的观点包id
     */
    public function getPlannerService($p_uid){
        $sql = "select id from lcs_service where p_uid={$p_uid} and service_type=1 and service_status=1;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $service_id = $cmd->queryScalar();

        $sql = "select sp_content_id from lcs_service_product where service_id='{$service_id}' and sp_type=1;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $packageId = $cmd->queryScalar();

        return $packageId;
    }

    public function getUidsByPlannerService($p_uid){
        if(!$p_uid)
            return false;
        $time = date('Y-m-d H:i:s');
        $sql = " select uid from " . $this->tableNameBuy() . " a inner join ". $this->tableName() ." b on a.service_id=b.id where p_uid='$p_uid' and a.end_time>'$time'";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
}