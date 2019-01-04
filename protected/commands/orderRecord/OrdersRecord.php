<?php
/**
 * 
 */
class OrdersRecord
{

	//任务代码
	const CRON_NO=14001 ;

	private static $course_pkg_conf = array(
		//套餐1
		'pkg_1'=>array(
			'pkg_id'=>'1',//套餐id
			'title'=>'史月波系列课程-普及版',//套餐名称
			'p_uid'=>'1451326947',//相关理财师id
			'type'=>'1',//套餐所属版本（0体验版，1普及版，2精英版，3至尊版）
			'months'=>'1',//套餐时长（1=>1月，3=>季度，6=>半年，12=>1年）
			'price'=>'1000',//套餐价格
			'hide'=>1,
			'light'=>0,
			'package'=>array(//套餐包含（可嵌套其他套餐）
				array(
					'id'=>'10',
					'title'=>'高控盘策略会',
					'type'=>'1', //所属类型（1视频直播间，2课程，3观点包，4私密圈子，5套餐）
					'bz'=>''
				),
				array(
					'id'=>'76',
					'title'=>'藏经阁初级课程',
					'type'=>'2',
					'bz'=>'6'
				)
			)
		),
		//套餐2
		'pkg_2'=>array(
			'pkg_id'=>'2',
			'title'=>'史月波系列课程-精英版',
			'p_uid'=>'1451326947',
			'type'=>'2',
			'months'=>'12',
			'price'=>'9600',
			'hide'=>0,
			'light'=>0,
			'package'=>array(
				array(
					'id'=>'1',
					'title'=>'史月波系列课程-普及版',
					'type'=>'5',
					'bz'=>''
				),
				array(
					'id'=>'77',
					'title'=>'藏经阁中高级课程',
					'type'=>'2',
					'bz'=>'6'
				),
				array(
					'id'=>'1001',
					'title'=>'高控盘指标',
					'type'=>'6',
					'bz'=>'http://licaishi.sina.com.cn/html5/gzh/adShiYueBo.html'
				)
			)
		),
		//套餐3
		'pkg_3'=>array(
			'pkg_id'=>'3',
			'title'=>'史月波系列课程-至尊版',
			'p_uid'=>'1451326947',
			'type'=>'3',
			'months'=>'12',
			'price'=>'19800',
			'hide'=>0,
			'light'=>1,
			'package'=>array(
				array(
					'id'=>'2',
					'title'=>'史月波系列课程-精英版',
					'type'=>'5',
					'bz'=>''
				),
				array(
					'id'=>'60661',
					'title'=>'高控盘俱乐部',
					'type'=>'4',
					'bz'=>''
				)
			)
		),
		//套餐4
		'pkg_4'=>array(
			'pkg_id'=>'4',
			'title'=>'边风炜系列课程-半年课',
			'p_uid'=>'6459152839',
			'type'=>'1',
			'months'=>'6',
			'price'=>'6800',
			'hide'=>0,
			'light'=>0,
			'package'=>array(
				array(
					'id'=>'78',
					'title'=>'边学边赚',
					'type'=>'2',
					'bz'=>'6'
				),
				array(
					'id'=>'79',
					'title'=>'研报点金',
					'type'=>'2',
					'bz'=>'6'
				),
				array(
					'id'=>'80',
					'title'=>'风味信箱',
					'type'=>'2',
					'bz'=>'6'
				),
				array(
					'id'=>'60662',
					'title'=>'边风炜俱乐部',
					'type'=>'4',
					'bz'=>''
				)
			)
		),
		//套餐5
		'pkg_5'=>array(
			'pkg_id'=>'5',
			'title'=>'王健系列课程-普及版',
			'p_uid'=>'6150188584',
			'type'=>'1',
			'months'=>'1',
			'price'=>'3680',
			'hide'=>1,
			'light'=>0,
			'package'=>array(
				array(
					'id'=>'81',
					'title'=>'易投经初级课程',
					'type'=>'2',
					'bz'=>'6'
				),
				array(
					'id'=>'12',
					'title'=>'易投经-实战演武堂',
					'type'=>'1',
					'bz'=>''
				)
			)
		),
		//套餐6
		'pkg_6'=>array(
			'pkg_id'=>'6',
			'title'=>'王健系列课程-精英版',
			'p_uid'=>'6150188584',
			'type'=>'2',
			'months'=>'3',
			'price'=>'19800',
			'hide'=>0,
			'light'=>1,
			'package'=>array(
				array(
					'id'=>'5',
					'title'=>'王健系列课程-普及版',
					'type'=>'5',
					'bz'=>''
				),
				array(
					'id'=>'82',
					'title'=>'易投经中高级进阶课程',
					'type'=>'2',
					'bz'=>'6'
				),
				array(
					'id'=>'60668',
					'title'=>'易投经-王牌盯市',
					'type'=>'4',
					'bz'=>''
				)
			)
		),
		//套餐7
		'pkg_7'=>array(
			'pkg_id'=>'7',
			'title'=>'王健系列课程-精英版',
			'p_uid'=>'6150188584',
			'type'=>'2',
			'months'=>'6',
			'price'=>'36800',
			'hide'=>0,
			'light'=>0,
			'package'=>array(
				array(
					'id'=>'6',
					'title'=>'王健系列课程-精英版',
					'type'=>'5',
					'bz'=>''
				)
			)
		)
	);

	/**
	 * 入口
	 */
	public function SaveOrdersRecord(){	
		$this->saveCourse();
		$this->saveCourse66();
		$this->saveSilk();
		$this->saveProp();	
	}
	//根据id获取套餐包
	private static function getCoursePackage($pkg_id)
	{
		$key = 'pkg_'.$pkg_id;
		$ret = self::$course_pkg_conf[$key];
		return $ret ? $ret : false;
	}
	private static function saveCourse(){
		$sqlhistory="select uid,relation_id,amount,order_no,pay_time from lcs_orders where type=64 and status=2 order by pay_time asc";
		$orderInfo = Yii::app()->lcs_r->createCommand($sqlhistory)->queryAll();
		if(!empty($orderInfo)){
			foreach($orderInfo as $v){
				$sqlhistory="select id,type from lcs_course where id=".$v['relation_id'];
				$subInfo = Yii::app()->lcs_r->createCommand($sqlhistory)->queryRow();
				if(!empty($subInfo)&& $subInfo['type']==6){
					$sql_history = "select uid,course_id,order_no,end_time from lcs_course_subscription_history where uid=".$v['uid']." and course_id=".$v['relation_id']." order by end_time desc limit 1";
					$sub = Yii::app()->lcs_r->createCommand($sql_history)->queryRow();
					$end_time1=$v['amount'];
					$start_time1=$v['pay_time'];
					if(!$sub){
						$end_time2=date("Y-m-d H:i:s", strtotime("+$end_time1 month",strtotime($start_time1)));
					}else{
						if ($v['pay_time'] > $sub['end_time']) {
							//说明已过期，选择当前时间之后的续约时间
							$end_time2 = date("Y-m-d H:i:s", strtotime("+$end_time1 month",strtotime($start_time1)));
						} else {
							//未过期，选择结束时间之后的续约时间
							$start_time1=$sub['end_time'];
							$end_time2 = date("Y-m-d H:i:s", strtotime("+$end_time1 month", strtotime($start_time1)));
						}
					}
					//添加订阅历史
		            $history = array(
		                "course_id" => $v['relation_id'],
		                "uid" => $v['uid'],
		                "order_no" => $v['order_no'],
		                "amount" => $end_time1,
		                "start_time" => $start_time1,
		                "end_time" => $end_time2,
		                "status" => 1,
		                "c_time" => date("Y-m-d H:i:s"),
		                "u_time" => date("Y-m-d H:i:s")
		            );
		            Yii::app()->lcs_w->createCommand()->insert("lcs_course_subscription_history",$history);
				}
				
			}
		}

	}

	private static function saveCourse66(){
		$sqlhistory="select uid,relation_id,amount,order_no,pay_time from lcs_orders where type=66 and status=2 order by pay_time asc";
		$orderInfo = Yii::app()->lcs_r->createCommand($sqlhistory)->queryAll();
		if(!empty($orderInfo)){
			foreach($orderInfo as &$v){
				self::saveCourse6($v['uid'],$v['order_no'],$v['relation_id'],$v['pay_time']);
			}
		}
		
	}

	private static function saveCourse6($uid,$order_no,$pkg_id,$pay_time,$refund=0,$time=0,$phone=''){
		//系列套餐不存在
		$course_pkg = self::getCoursePackage($pkg_id);
		if(!$course_pkg){
			return -1;
		}
		//套餐信息初始化
		$months = $course_pkg['months'];
		$time = $time ? $time : (!$refund ? "+$months" : "-$months");
		$end_time = date('Y-m-d H:i:s',strtotime("$time month"));
		$now = date('Y-m-d H:i:s');
		//套餐下的课程入各自类型的订阅表
		if($course_pkg['package'] && is_array($course_pkg['package'])){
			foreach($course_pkg['package'] as $k=>$vv){
				if($vv['type'] == 5){
					//嵌套的套餐，递归处理
					self::saveCourse6($uid,$order_no,$vv['id'],$pay_time,$refund,$time,$phone);
				}else{
					$relation_id = $vv['id'];
					if($vv['type'] == 2){
						if($vv['bz'] == 6){
							$sql_history = "select uid,course_id,order_no,end_time from lcs_course_subscription_history where uid=".$uid." and course_id=".$relation_id." order by end_time desc limit 1";
							$sub = Yii::app()->lcs_r->createCommand($sql_history)->queryRow();
							$start_time = $pay_time;
							if ($sub) {
								$e = strtotime($sub['end_time']);
								if ($pay_time > $sub['end_time']) {
									//说明已过期，选择当前时间之后的续约时间
									$end_time1 = date("Y-m-d H:i:s", strtotime("+$time month",strtotime($start_time)));
								} else {
									//未过期，选择结束时间之后的续约时间
									$start_time=$sub['end_time'];
									$end_time1 = date("Y-m-d H:i:s", strtotime("+$time month", $e));
								}
							} else {
								$end_time1=date("Y-m-d H:i:s", strtotime("+$time month",strtotime($start_time)));
							}
							//添加订阅历史
				            $history = array(
				                "course_id" => $relation_id,
				                "uid" => $uid,
				                "order_no" => $order_no,
				                "amount" => $time,
				                "start_time" => $start_time,
				                "end_time" => $end_time1,
				                "status" => 1,
				                "c_time" => date("Y-m-d H:i:s"),
				                "u_time" => date("Y-m-d H:i:s")
				            );
				            Yii::app()->lcs_w->createCommand()->insert("lcs_course_subscription_history",$history);
									   
						}
					}
				}
			}
		}

	}

	private static function saveSilk(){
		$sqlhistory="select uid,relation_id,amount,order_no,pay_time from lcs_orders where type=91 and status=2 order by pay_time asc";
		$orderInfo = Yii::app()->lcs_r->createCommand($sqlhistory)->queryAll();
		if(!empty($orderInfo)){
			foreach($orderInfo as $v){
				$sqlhistory="select id,start_time,end_time from lcs_silk where id=".$v['relation_id'];
				$subInfo = Yii::app()->lcs_r->createCommand($sqlhistory)->queryRow();
				if(!empty($subInfo)){
					//添加订阅历史
		            $history = array(
		                "silk_id" => $v['relation_id'],
		                "uid" => $v['uid'],
		                "order_no" => $v['order_no'],
		                "amount" => 1,
		                "start_time" => $subInfo['start_time'],
		                "end_time" => $subInfo['end_time'],
		                "status" => 1,
		                "c_time" => date("Y-m-d H:i:s"),
		                "u_time" => date("Y-m-d H:i:s")
		            );
		            Yii::app()->lcs_w->createCommand()->insert("lcs_silk_subscription_history",$history);
				}
			}
		}
	}

	private static function saveProp(){
		$sqlhistory="select uid,relation_id,amount,order_no,pay_time from lcs_orders where type=71 and status=2 order by pay_time asc";
        $orderInfo = Yii::app()->lcs_r->createCommand($sqlhistory)->queryAll();
        if(!empty($orderInfo)){
            foreach($orderInfo as $v){
                $sqlhistory="select id,efficient,type from lcs_prop where id=".$v['relation_id'];
                $subInfo = Yii::app()->lcs_r->createCommand($sqlhistory)->queryRow();
                if(!empty($subInfo)){
                    $sqlhistory="select id,start_time,end_time from lcs_prop_user_history where uid=".$v['uid']." and prop_id=".$v['relation_id']." order by end_time desc limit 1";
                    $sub = Yii::app()->lcs_r->createCommand($sqlhistory)->queryRow();
                    $start_time=$v['pay_time'];
                    if(!empty($sub)){
                    	if ($v['pay_time'] > $sub['end_time']) {
							//说明已过期，选择当前时间之后的续约时间
							$end_time=date("Y-m-d H:i:s",strtotime($start_time)+$subInfo['efficient']);
						} else {
							//未过期，选择结束时间之后的续约时间
							$start_time=$sub['end_time'];
							$end_time=date("Y-m-d H:i:s",strtotime($start_time)+$subInfo['efficient']);
						}
                    }else{     
                        $end_time=date("Y-m-d H:i:s",strtotime($start_time)+$subInfo['efficient']);
                    }
                    //添加订阅历史
                    $history = array(
                        "prop_id" => $v['relation_id'],
                        "uid" => $v['uid'],
                        "order_no" => $v['order_no'],
                        "amount" => 1,
                        "start_time" => $start_time,
                        "end_time" => $end_time,
                        "status" => 1,
                        "c_time" => date("Y-m-d H:i:s"),
                        "u_time" => date("Y-m-d H:i:s")
                    );
                    Yii::app()->lcs_w->createCommand()->insert("lcs_prop_user_history",$history);
                }
                
            }
        }	
	}
}