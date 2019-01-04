<?php

class ChangeCompany {
 
	const DEBUG = true;
	private $now = '';
	private static $change = 1;

	/**
	 * run
	 * @return type
	 */
	public function run() {
		$this->now = date('Y-m-d H:i:s');

		$connection = Yii::app()->lcs_w;
		$transaction = $connection->beginTransaction();

		$allCompany = $this->getAllCompany(true);
		try {
			foreach ($allCompany as $v) {
				//full_name是否有重复
				$sql = 'select id from lcs_company where full_name="' . addslashes($v['full_name']) . '" and id<>' . intval($v['id']) . ' order by id asc limit 1';
				$old_id = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
				if ($old_id) {
					$sql2 = 'update lcs_planner set company_id=' . $old_id . ' ,u_time="' . $this->now . '" where company_id=' . $v['id'];
					self::p($sql2);
					self::$change && $connection->createCommand($sql2)->execute();
					$sql3 = 'delete from lcs_company where id=' . $v['id'];
					self::p($sql3);
					self::$change && $connection->createCommand($sql3)->execute();
					continue;
				}

				//full_name转name后是否有重复
				$name_new = str_replace(['有限责任公司', '股份有限公司', '资产管理有限公司', '投资顾问有限公司', '有限公司'], ['', '', '资管', '投顾', ''], $v['full_name']);
				$sql = 'select id from lcs_company where name="' . addslashes($name_new) . '" and id<>' . intval($v['id']);
				$old_id = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
				if ($old_id) {
					$sql2 = 'update lcs_planner set company_id=' . $old_id . ' ,u_time="' . $this->now . '" where company_id=' . $v['id'];
					self::p($sql2);
					self::$change && $connection->createCommand($sql2)->execute();
					$sql3 = 'delete from lcs_company where id=' . $v['id'];
					self::p($sql3);
					self::$change && $connection->createCommand($sql3)->execute();
				}
			}
			$transaction->commit();
		} catch (Exception $e) {
			echo $e->getFile() . " " . $e->getLine() . " " . $e->getMessage() . "\r\n";
			$transaction->rollBack();
		}
	}

	/**
	 * 获取所有公司全名
	 */
	protected function getAllCompany($isErrData = false) {
		$sql = 'SELECT `id`,`name`,`full_name` FROM `lcs_company` where 1';
		if ($isErrData) {
			$sql .= ' and id>=1079 ';
		}
		try {
			$list = Yii::app()->lcs_r->createCommand($sql)->queryAll() ?: [];
			$R = [];
			foreach ($list as $v) {
				$v['is_repeat'] = 0;
				$v['repeat_id'] = 0;
				$R[$v['id']] = $v;
			}
			return $R;
		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * p打印
	 * @param type $string
	 */
	private static function p($string = "") {
		if (self::DEBUG) {
			echo $string . "\r\n";
		}

		file_put_contents(dirname(dirname(dirname(__DIR__))) . '/log/changeCompany.log', $string . "\r\n", FILE_APPEND);
	}

}
