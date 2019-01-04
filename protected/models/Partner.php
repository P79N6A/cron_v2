<?php

/**
 * Class 微信第三方系统model
 */
class Partner extends CActiveRecord
{



    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }


    public function tableName() 
	{
		return TABLE_PREFIX.'partner';
	}

	public function tableNamePartnerGroup() 
	{
		return TABLE_PREFIX.'partner_group';
	}


    /**
     * 获取合作信息
     * @param $app_keys
     * @return mixed
     */
    public function getPartnerByAppKey($app_keys){
        $app_keys = (array)$app_keys;
        $sql = "select id, partner_type,status,app_key,app_secret, notice_app_type, notice_app_key, notice_app_secret from "
            .$this->tableName()." where app_key in ('".implode("','",$app_keys)."') and status=0;";

        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $result=array();
        if(!empty($list)){
            foreach ($list as $item){
                $result[$item['app_key']]=$item;
            }
        }

        return $result;
    }

    /**
     * 获取合作信息
     * @param $partner_ids
     * @return mixed
     */
    public function getPartnerByAppId($partner_ids){
        $partner_ids = (array)$partner_ids;
        $sql = "select id, partner_type,status,app_key,app_secret, notice_app_type, notice_app_key, notice_app_secret from "
            .$this->tableName()." where id in ('".implode("','",$partner_ids)."') and status=0;";

        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $result=array();
        if(!empty($list)){
            foreach ($list as $item){
                $result[$item['id']]=$item;
            }
        }

        return $result;
    }

    public function getPartnerGroupInfo($and_where_arr)
	{
		if (empty($and_where_arr) || !is_array($and_where_arr)) {
            return [];
        }

		$_where = '1';
        foreach ($and_where_arr as $field => $value) {
            if (is_array($value)) {
                $_where .= " AND {$field} IN ('" . implode("','", $value) . "'')";
            } else {
                $tmp_field = explode(' ', trim($field));
                if (isset($tmp_field["1"])) {
                    $f = $tmp_field["0"];
                    $c = $tmp_field["1"];
                } else {
                    $f = $tmp_field["0"];
                    $c = "=";
                }
                $_where .= " AND {$f} {$c} '{$value}'";
            }
        }

		$cmd =  Yii::app()->lcs_r->createCommand();
		$group_info = $cmd->select('id')->from($this->tableNamePartnerGroup())->where($_where)->queryRow();
		return $group_info ? $group_info : false;
	}



}
