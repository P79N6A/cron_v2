<?php


/**
 * 理财师客户分组功能
 *     - 客户分组
 *     - 给用户的通知消息
 * edit by zhihao6 2016/06/06
 */

class PlannerPush extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


    public function tableNameInfo()
    {
        return TABLE_PREFIX.'planner_push_info';
    }
	public function tableNameMsg()
    {
		return TABLE_PREFIX.'planner_push_msg';
	}
    public function tableNameBuyUser()
    {
        return TABLE_PREFIX.'planner_push_buy_user';
    }
    public function tableNameCustomerGroup()
    {
        return TABLE_PREFIX.'planner_customer_group';
    }
    public function tableNameCustomer()
    {
        return TABLE_PREFIX.'planner_customer';
    }
    public function tableNameGroup()
    {
        return TABLE_PREFIX.'planner_group';
    }
    public function tableNameGroupStat()
    {
        return TABLE_PREFIX.'planner_group_stat';
    }


	/**
	 * 根据id 查询详情
	 * @param array $ids
     * @param array $fields string or array
	 * @return array
	 */
	public function getPlannerPushMsgByIds($ids,$fields=null)
    {
		$return = array();
		$ids = (array)$ids;
		if(empty($ids)) {
			return $return;
		}

        $select = 'id';
        if(!empty($fields)){
            if(is_array($fields)){
                $select = implode(',',$fields);
            }else if(is_string($fields)){
                $select = $fields;
            }
        }

		$sql = "select ".$select." from ".$this->tableNameMsg()." where id in (". implode(',', $ids) .");";
		$list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if($list) {
			foreach ($list as $vals) {
				$return[$vals['id']] = $vals;
			}
		}
		return $return;
	}

    /**
     * 获取理财师客户分组下的uid 列表
     * @param $p_uid
     * @param $grp_id
     * @return array  uid数组
     */
    public function getCustomerUidByGid($p_uid,$grp_id)
    {
        $sql = 'select uid from '.$this->tableNameCustomerGroup().' where p_uid=:p_uid and grp_id=:grp_id;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
        $cmd->bindParam(':grp_id', $grp_id, PDO::PARAM_INT);
        return $cmd->queryColumn();

    }

    /**
     * 更新理财师推送消息的相关信息
     * @param  [type] $p_uid       [description]
     * @param  [type] $push_id     [description]
     * @param  [type] $update_info [description]
     * @return [type]              [description]
     */
    public function updatePlannerPushMsgInfo($p_uid, $push_id, $update_info)
    {
        if (empty($update_info)) {
            return true;
        }

        $sets = "";
        foreach ($update_info as $kk => $vv) {
            if (is_int($vv)) {
                $sets .= " {$kk}={$vv},";
            } else {
                $sets .= " {$kk}='{$vv}',";
            }
        }
        $sets = rtrim($sets, ',');

        $sql = "UPDATE ". $this->tableNameMsg() ." SET {$sets} WHERE p_uid=:p_uid AND id=:push_id";
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(":p_uid", $p_uid, PDO::PARAM_INT);
        $cmd->bindParam(":push_id", $push_id, PDO::PARAM_INT);
        $res = $cmd->execute();
        return $res;
    }


}
