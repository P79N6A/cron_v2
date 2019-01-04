<?php

/**
 * Description of Globalnews
 *
 * @author Administrator
 */
class Globalnews extends CActiveRecord {

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return
	 */
	public function tableName() {
		return 'lcs_globalnews';
	}

	/**
	 * @return
	 */
	public function tableName_types() {
		return 'lcs_globalnews_type';
	}

	public function isExists($id) {
		$sql = "select id from " . $this->tableName() . " where id =" . $id;
		$res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		return $res['id'] ? true : false;
	}

	/**
	 * 获取最后的一个id
	 */
	public function getLastId($order = 'ASC') {
		if (strtoupper($order) != 'ASC')
			$order = 'DESC';
		$sql = 'select id from ' . $this->tableName() . ' order by id ' . $order . ' limit 1 ';
		$res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		return $res['id'] ?: 0;
	}

	/**
	 *
	 * @param type $id
	 * @param type $content
	 * @param type $original_pic
	 * @param type $created_time
	 */
	public function addNews($id, $content, $original_pic, $created_time, array $tags) {
		if (empty($tags)) {
			return false;
		}

		$insert_types = 'insert into ' . $this->tableName_types() . ' (`news_id`,`type_id`,`c_time`) VALUES ';
		foreach ($tags as $v) {
			$tag_id = intval($v['id']);
			$insert_types .= '(' . $id . ',' . $tag_id . ',"' . date('Y-m-d H:i:s', $created_time) . '"),';
		}
		$insert_types = rtrim($insert_types, ',');
		$sql = 'INSERT INTO ' . $this->tableName() . ' (`id`,`content`,`original_pic`,`created_time`) VALUES (' . $id . ',"' . addslashes($content) . '","' . addslashes($original_pic ?: '') . '",' . $created_time . ')';

		$connection = Yii::app()->lcs_w;
		$transaction = $connection->beginTransaction();
		try {
			$connection->createCommand($insert_types)->execute();
			$connection->createCommand($sql)->execute();
			$transaction->commit();
			return true;
		} catch (Exception $e) {
			$transaction->rollBack();
			echo $e->getMessage();
			return false;
		}
	}

	/**
	 * 添加新闻类型
	 * @param type $id
	 * @param type $types
	 * @param type $created_time
	 */
	public function addNewsType($id, $types, $created_time) {

	}

}
