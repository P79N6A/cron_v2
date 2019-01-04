<?php
/*
 * 理财师问答数据操作类
 * 
 * 
 * @author weiguang3
 */ 

class Question extends CActiveRecord
{

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


	public function __construct()
	{
		
	}

	/*
	 * 获取问题信息
	 */
	public function getQuestionById($q_ids,$is_r=true)
	{
		$q_ids = (array)$q_ids;
		$sql = "SELECT id,uid, p_uid,ind_id,price,answer_id,is_anonymous,q_add_id,status,content,c_time,u_time FROM lcs_ask_question where id in (".implode(',', $q_ids).")";
		$db = $is_r?Yii::app()->lcs_r:Yii::app()->lcs_w;
        $questions = $db->createCommand($sql)->queryAll();
		$return = array();
		if(is_array($questions) && sizeof($questions)>0){
			foreach ($questions as $vals){
				$return[$vals['id']] = $vals;
			}
		}
		return $return;
	}

    /**
     * 获取回答信息
     * @param $q_ids
     * @return array
     */
    public function getAnswerById($ids){
        $ids = (array)$ids;
        $sql = "SELECT id,q_id,p_uid,summary,c_time FROM lcs_ask_answer where id in (".implode(',', $ids).")";
        $answers = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $return = array();
        if(is_array($answers) && sizeof($answers)>0){
            foreach ($answers as $vals){
                $return[$vals['id']] = $vals;
            }
        }
        return $return;
    }

    /**
     * 获取回答信息
     * @param $q_ids
     * @return array
     */
    public function getAnswerByQid($q_ids){

        $q_ids = (array)$q_ids;
        $sql = "SELECT q_id,p_uid,summary,c_time FROM lcs_ask_answer where q_id in (".implode(',', $q_ids).")";
        $answers = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $return = array();
        if(is_array($answers) && sizeof($answers)>0){
            foreach ($answers as $vals){
                $return[$vals['q_id']] = $vals;
            }
        }
        return $return;
    }

    /**
     * 获取追问信息
     * @param $q_add_ids
     * @return array
     */
    public function getQuestionAddInfo($q_add_ids){
        $q_add_ids = (array)$q_add_ids;
        $sql = "SELECT id,content,answer,c_time FROM lcs_ask_question_add where id in (".implode(',', $q_add_ids).")";
        $q_adds = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $return = array();
        if(is_array($q_adds) && sizeof($q_adds)>0){
            foreach ($q_adds as $vals){
                $return[$vals['id']] = $vals;
            }
        }
        return $return;
    }
}

