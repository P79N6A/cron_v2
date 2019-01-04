<?php
/**
 * 理财师评价
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */

class GradeComment extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return 'lcs_grade_comment';
    }

    /**
     * 获取评价列表
     * @param int $type  类型，1：计划，2：观点包
     * @param int $relation_id  相关id
     * @param int $p_uid 理财师id
     * @param int $uid  用户id
     * @param string $s_time 开始时间
     * @param string $e_time 结束时间
     * @param array $fields 需要获取额字段
     */
    public function getGradeCommentListByCdn($type,$relation_id,$p_uid,$uid,$s_time,$e_time,$fields){
        $cdn='1=1';
        if(!empty($type)){
            $cdn .= ' and type='.intval($type);
        }
        if(!empty($relation_id)){
            $cdn .= ' and relation_id=:relation_id';
        }
        if(!empty($s_time)){
            $cdn .= ' and c_time>=:s_time';
        }
        if(!empty($e_time)){
            $cdn .= ' and c_time<:e_time';
        }
        if(!empty($p_uid)){
            $cdn .= ' and p_uid='.intval($p_uid);
        }
        if(!empty($uid)){
            $cdn .= ' and uid='.intval($uid);
        }

        $cdn .=' and status=0;';

        $select = 'id';
        if(!empty($fields)){
            if(is_array($fields)){
                $select = implode(',',$fields);
            }else if(is_string($fields)){
                $select = $fields;
            }
        }

        $sql = 'select '.$select.' from '.$this->tableName().' where '.$cdn;

        $cmd = Yii::app()->lcs_r->createCommand($sql);

        if(!empty($relation_id)){
            $cmd->bindParam(':relation_id', $relation_id, PDO::PARAM_STR);
        }
        if(!empty($s_time)){
            $cmd->bindParam(':s_time', $s_time, PDO::PARAM_STR);
        }
        if(!empty($e_time)){
            $cmd->bindParam(':e_time', $e_time, PDO::PARAM_STR);
        }

        return $cmd->queryAll();



    }

    /**
     * 根据评价id,获取该条评价的详细信息
     * @param int $cmn_id 评价id
     * @return array 该评价的详细信息
     */
    public function getGradeCmnById($cmn_id) {
        
        $sql = 'select type,relation_id,p_uid,uid,content,reply,score_1,score_2,score_3 from '.$this->tableName(). ' where id=:cmn_id and status=0;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':cmn_id', $cmn_id, PDO::PARAM_INT);
        return $cmd->queryRow();
    }

}