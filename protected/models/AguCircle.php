<?php

/**
 * Created by PhpStorm.
 * User: meixin
 * Date: 2017/7/20
 * Time: 下午12:35
 */

class AguCircle extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableTag(){
        return 'lcs_ask_tags';
    }

    public function tablePlanner(){
        return 'lcs_planner';
    }

    public function tableCircle(){
        return 'lcs_circle';
    }


    #获取股票 relation_id
    public function getCodeInfo($code_arr){

        $sql = "select id,code,symbol,name from lcs_ask_tags where type='stock_cn' and code in(".implode(",",$code_arr).")";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $code_info = [];
        foreach ($res as $v) {
            $code_info[$v['code']] = $v;

        }
        return $code_info;

    }

#获取微博ID
    public function getPuid($p_name_arr)
    {
        $sql = "select id , real_name ,s_uid from lcs_planner where real_name in('".implode("','",$p_name_arr)."') and company_id = 945";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $p_info = [];
        foreach ($res as $v) {
            $p_info[$v['real_name']] = $v;

        }
        return $p_info;
    }

    public function insertCircle($data){
        $db_w = Yii::app()->lcs_w;
        $command = $db_w->createCommand();
        $command->insert($this->tableCircle(), $data);
        $insert_id = $db_w->getLastInsertID();
        return $insert_id;
    }
}