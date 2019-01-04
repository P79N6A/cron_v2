<?php
/**
 * 
 */
class OrderHistory
{

	//任务代码
	const CRON_NO='' ;
	/**
	 * 入口
	 */
	public function option(){		
		
			
			//获取订单信息
			$orderInfo = Yii::app()->lcs_r->createCommand('select `id`,`order_no`,`uid`,`p_uid`,`relation_id`,`type`,`status`,`description`,`price`,`pay_time`,`c_time`,`u_time`,`pay_type`,`pay_number`,`fr`,`tg_id`,`tg_name` from `lcs_orders` where  pay_time>"2018-01-01 00:00:00" order by pay_time asc')->queryAll();
			if(!empty($orderInfo)){
				foreach($orderInfo as &$val){
					$order=array();
					$phone = Yii::app()->lcs_r->createCommand('select `phone` from `lcs_user_index` where id=' . $val['uid'])->queryScalar();
					$val['phone'] = CommonUtils::decodePhoneNumber($phone);
					$name = Yii::app()->lcs_r->createCommand('select `name` from `lcs_planner` where s_uid=' . $val['p_uid'])->queryScalar();
					$res=Yii::app()->curl->get("http://lcs-api.baidao.com/api/user/investInfo?phone=".$val['phone']);
					if(!$res){
						$order['sIAdata']="";
					}else{
						$res=json_decode($res,true);
			            if(!empty($res['data']['personCode'])){
			                $order['sIAdata']=$res['data']['personCode'];
			            }else{
			                $order['sIAdata']="";
			            }
					}
					$order['order_no']=$val['order_no'];
					$order['sItemAcount']=$val['price'];
					$order['sTradeTime']=$val['pay_time'];
					$order['sMibleNum']=md5($val['phone']);
					$order['sItemContent']=$val['description'];
					$order['sOrderType']=$val['type'];
					$order['sMible']=$val['phone'];
					$order['iUid']=(int)$val['uid'];
					$order['sName']=$name;
					$order['p_uid']=$val['p_uid'];
					$order['status']=$val['status'];
					$order['c_time']=$val['c_time'];
					$order['u_time']=$val['u_time'];
					$order['pay_type']=$val['pay_type'];
					$order['pay_number']=$val['pay_number'];
                    $order['tg_id']=$val['tg_id'];
                    $order['tg_name']=$val['tg_name'];
					$order['fr']=$val['fr'];
					$param_josn = json_encode($order);
					$header = array(
						'Content-Type'=>'application/json; charset=utf-8',
					);
					$sh = Yii::app()->curl->setTimeOut(10)->setHeaders($header);
					$data = $sh->post(
						 "https://beidou-api.yk5800.com/api/RestService",
						 $param_josn
					);
					$data = json_decode($data,true);
					if(!empty($data)){
						var_dump($data);
					}else{
						$e .= sprintf("数据传输失败:%s\t",$order_no);
					}
				}
			}
		if(!empty($e)){
			throw new Exception($e,-1);
		}
	}
}