<?php
/**
 * 理财基金推荐表
 * @author meixin@staff.sina.com.cn 
 */
class RecommendFund extends CActiveRecord {


	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return TABLE_PREFIX.'recommend_fund';
	}


	/**
	 * 获取推荐表中总数
	 */
	public function getRecommendTotalNum() {

		$sql = "select count(1) from ".$this->tableName();
		$result = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
		return $result;

	}

	/**
	 * 获取基金信息
	 */
	public function getRecommendList($start , $num = 100) {
		$result = array();
		$max_num = 100;
		$num = $num<1 ? $max_num : $num;

		$db_r = Yii::app()->lcs_r;
		$sql = 'select id , fund_code ,	show_income_column, show_income_column_2  from '.$this->tableName() .' limit :start, :limit;';
		$cmd = $db_r->createCommand($sql);
		$cmd->bindParam(':start', $start, PDO::PARAM_INT);
		$cmd->bindParam(':limit', $num, PDO::PARAM_INT);
		$result = $cmd->queryAll();
		return $result;
	}


	/**
	 * 修改基金信息
	 * @param integer $id
	 * @param data
	 *
	 * @return boolean
	 */
	public function updateRecommend($id, $data) {
		$db_w = Yii::app()->lcs_w;

		//没有需要修改的内容
		if(count($data)<=0){
			return false;
		}
		$result = $db_w->createCommand()->update($this->tableName(), $data, 'id=:id', array(':id'=>$id));

		return $result>=0?true:false;
	}

}
