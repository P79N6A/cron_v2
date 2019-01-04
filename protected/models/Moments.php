<?php

/**
 * 动态流
 */
class Moments extends CActiveRecord {
    const DISCUSSION_TYPE_VIEW = 2;
    const DISCUSSION_TYPE_PLAN = 7;


    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function getDB($db_type = 'r')
    {
        if ($db_type == 'r') {
            if(empty(Yii::app()->lcs_comment_r->active)){
                Yii::app()->lcs_comment_r->active = false;
                Yii::app()->lcs_comment_r->active = true;
            }
            return Yii::app()->lcs_comment_r;
        } elseif ($db_type == 'w') {
            if(empty(Yii::app()->lcs_comment_w->active)){
                Yii::app()->lcs_comment_w->active = false;
                Yii::app()->lcs_comment_w->active = true;
            }
            return Yii::app()->lcs_comment_w;
        }
    }

    public function tableName()
    {
        return "lcs_moments" ;
    }

    /**
     * 批量插入动态
     * @param $data
     * @return null
     */
    public function batchAddMoments($data) {
        if (empty($data)) {
            return null;
        }
        $sql = "insert into {$this->tableName()}(uid,p_uid,is_fee,discussion_type,discussion_id,relation_id,c_time,u_time) VALUES ";
        $value_arr = array();
        foreach ($data as $item) {
            $value_arr[] = "({$item['uid']},{$item['p_uid']},{$item['is_fee']},{$item['discussion_type']},{$item['discussion_id']},{$item['relation_id']},'{$item['c_time']}','{$item['u_time']}')";
        }
        $sql .= join(',', $value_arr);
        $res = $this->getDB('w')->createCommand($sql)->execute();

        return $res;
    }

    /**
     * 检查动态是否已经写入
     * @param $uid
     * @param $discussion_type
     * @param $discussion_id
     * @return mixed
     */
    public function isExistMoments($uid, $discussion_type, $discussion_id) {
        if (empty($discussion_id)) {
            return false;
        }
        $discussion_id = (array) $discussion_id;
        $discussion_id = join(',', $discussion_id);
        $sql = "select id from {$this->tableName()} where uid={$uid} and discussion_type={$discussion_type} and discussion_id in({$discussion_id}) ";
        $res = $this->getDB()->createCommand($sql)->queryAll();

        return $res;
    }

}
