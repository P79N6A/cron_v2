<?php
/**
 * 优惠券.
 * User: haohao
 * Date: 15-10-19
 * Time: 下午14:08
 */

class Coupon extends CActiveRecord {

	public function getDbConnection($table_key = 'lcs_w') {
		return Yii::app() -> $table_key;
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	//优惠券表
	public function tableName() {
		return TABLE_PREFIX . 'coupon';
	}

	//优惠码表
	public function tableCouponCodeName() {
		return TABLE_PREFIX . 'coupon_code';
	}

	//用户优惠券表
    public function tableUserCouponname($select = 0) {
		if($select == 0){
			return TABLE_PREFIX."user_coupon";
		}else{
			return TABLE_PREFIX."user_discount_coupon";
		}        
    }

    /**
     * 根据优惠劵ID获取详情
     * @param $coupon_ids
     * @param string $fields
     * @return array
     */
    public function getCouponInfoById($coupon_ids,$fields=''){

        $select='*';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields): $fields;
        }

        $sql = 'select '.$select.' from '.$this->tableName().' where coupon_id in ('.implode(',',$coupon_ids).');';
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $result = array();
        if(!empty($data)){
            foreach($data as $item){
                $result[$item['coupon_id']]=$item;
            }
        }
        return $result;
    }

    /**
     * 获取即将过期的优惠劵信息
     * @param $s_time
     * @param $e_time
     * @param array $type
     */
    public function getCouponUserOfExpire($s_time, $e_time, $fields=''){
        $select='id,uid,coupon_id,validity_date,status,type,channel';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields): $fields;
        }

        $sql = 'select '.$select.' from '.$this->tableUserCouponname().' where validity_date>:s_time AND validity_date<=:e_time and status=0 and type=1;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':s_time',$s_time, PDO::PARAM_STR);
        $cmd->bindParam(':e_time',$e_time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }


    /**
     * 获取即将过期的优惠劵信息
     * @param $s_time
     * @param $e_time
     * @param array $type
     */
    public function getCouponOfExpire($s_time, $e_time, $fields='', $type=array()){

        $select='coupon_id,name,img,type,coupon_type,relation_id,price';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields): $fields;
        }
        $cdn = '';
        if(!empty($type)){
            $type = (array)$type;
            $cdn = ' and type in('.implode(',',$type).')';
        }

        $sql = 'select '.$select.' from '.$this->tableName().' where validity_date>:s_time AND validity_date<=:e_time'.$cdn.';';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':s_time',$s_time, PDO::PARAM_STR);
        $cmd->bindParam(':e_time',$e_time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }


	//获取未过期的优惠券活动列表
	public function getActiveCouponList(){
		$end_date = $start_date = date('Y-m-d H:i:s');
		$sql = "select coupon_id from ".$this->tableName()." where `status`='0' AND start_time<=:start_date AND end_time>=:end_date";

		$cmd = $this->getDbConnection()->createCommand($sql);
		$cmd->bindParam(':start_date',$start_date,PDO::PARAM_STR);
		$cmd->bindParam(':end_date',$end_date,PDO::PARAM_STR);

		$tmp_list = $cmd->queryAll();
		$coupon_ids = array();
		foreach($tmp_list as $k=>$v){
			$coupon_ids[$v['coupon_id']] = $v['coupon_id'];
		}
		return $coupon_ids;
	}

	//获取优惠券剩余数量
	public function getCouponAmountLeft($coupon_id = '') {
		if (empty($coupon_id)){
			return;
        }
		//为空初始化数据
        $sql = "select amount_left from " . $this -> tableName() . " where coupon_id=:id";
        $cmd = $this -> getDbConnection() -> createCommand($sql);
        $cmd -> bindParam(':id', $coupon_id, PDO::PARAM_INT);
        $coupon_amount_left = $cmd -> queryScalar();
		return $coupon_amount_left;
	}

	/**
	 * 优惠券数量-1
	 * @param number $coupon_id
	 * @param number $num 默认-1
	 */
	public function reduceCouponNum($coupon_id, $num = -1) {
        $result = array();
		$sql = "update ".$this->tableName()." set amount_left=(amount_left+:num) where coupon_id=:coupon_id";
		$cmd = $this->getDbConnection()->createCommand($sql);
		$cmd->bindParam(':coupon_id',$coupon_id,PDO::PARAM_INT);
		$cmd->bindParam(':num',$num,PDO::PARAM_INT);
		$result['sql_result'] = $cmd->query();
        $result['redis_result'] = 1;
		return $result;
	}
	 /*
	  * 更新优惠码使用状态
	  * @param string date
	  * 	@param number status
	  */
	 public function updateCouponCodeStatus($date='',$data=array()){
	 	!empty($date) or $date=date('Y-m-d H:i:s');
	 	if(empty($data)){
	 		return false;
	 	}
		
		$res = $this->getDbConnection()->createCommand()->update(
					$this->tableCouponCodeName(),
					$data,
					'validity_date<=:date and status=0',
					array(':date'=>$date)
				);
	    return $res;
	 }
	 /*
	  * 查询失效的优惠码个数
	  * @param str date 优惠码过期时间
	  */
	 public function getValidityCouponCodeForUpdateAmountLeft($date=''){
	 	
		!empty($date) or $date=date('Y-m-d H:i:s');

		$sql = "select count(1) count,coupon_id from ".$this->tableCouponCodeName()." where `status`='0' AND is_update='0' AND validity_date<:date group by coupon_id";
		
		$cmd = $this->getDbConnection()->createCommand($sql);
		$cmd->bindParam(':date',$date,PDO::PARAM_STR);
		$result = $cmd->queryAll();
		$ids = array();
		foreach($result as $k=>$v){
			if($v['count']>0){
				$ids[$v['coupon_id']] = $v['count'];
			}
		}

		return $ids;
	 }
	//更新优惠码的失效时间为空的优惠码的失效时间为创建时间+12小时
	public function updateCodeEmptyValidityDate(){
		$sql = 'update '.$this->tableCouponCodeName().' set validity_date=(select DATE_ADD(c_time,INTERVAL 12 HOUR)) where validity_date="0000-00-00 00:00:00"';
		$this->getDbConnection()->createCommand($sql)->execute();
	}
    /**
     * 更新我的过期的优惠券
     * @param type $status
     * @return type
     */
    public function updateExpiredCoupon($status=0,$table=0){
        $nowdate = date('Y-m-d H:i:s');
        $sql = "update ".$this->tableUserCouponname($table) . " set status = -1 where validity_date <'".$nowdate."' and status=0";
        $result['sql_result'] = $this->getDbConnection()->createCommand($sql)->execute();
        return $result;
    }

	/**
	 * 添加优惠码--lcs_coupon_code
	 */
	public function addCouponCode($data = array()) {
		$res = $this -> getDbConnection('lcs_w') -> createCommand() -> insert($this -> tableCouponCodeName(), $data);
		return $res;
	}

	/**
	 * 优惠券使用数量-1
	 * @param number $coupon_id
	 * @param number $num 默认-1
	 */
	public function reduceCouponUseNum($coupon_id, $num = -1) {
		$sql = "update ".$this->tableName()." set amount_use=(amount_use+:num) where coupon_id=:coupon_id";
		$cmd = $this->getDbConnection('lcs_w')->createCommand($sql);
		$cmd->bindParam(':coupon_id',$coupon_id,PDO::PARAM_INT);
		$cmd->bindParam(':num',$num,PDO::PARAM_INT);
		$result['sql_result'] = $cmd->query();
		return $result;
	}
    /**
     * 用户添加优惠券
     */
    public function addCouponUser($data)
    {
        return $this->getDbConnection()->createCommand()->insert($this->tableUserCouponname(), $data);
    }

    /**
     * 是否获取过优惠券
     */
    public function getCouponUserById($coupon_id,$uid){
        $select='id,uid,coupon_id';
        $sql = 'select '.$select.' from '.$this->tableUserCouponname().' where coupon_id='.$coupon_id.' and uid='.$uid;
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryAll();
    }


}
