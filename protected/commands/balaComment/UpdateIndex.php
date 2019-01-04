<?php

/**
* 
*/
class UpdateIndex
{
	
	//任务代码
	const CRON_NO=14002 ;
	/**
	 * 入口
	 */
	public function UpdateComments(){
		$end = time() + 60;
        $commentInfo=array();
        $ids=array();
		while(time()<$end){
			$key = "lcs_c_update_bala";
			$data1 = Yii::app()->redis_w->lpop($key);
			if(!$data1){
				sleep(2);
				continue;
			}
			$data=array();
			$data1=json_decode($data1,true);			
			if(isset($data1['is_good'])){
				$data['is_good']=(int)$data1['is_good'];
			}
			if(isset($data1['is_anonymous'])){
				$data['is_anonymous']=(int)$data1['is_anonymous'];
			}
			//设为精选不存es
			if(isset($data1['is_essences'])){
				$data['is_essences']=(int)$data1['is_essences'];
				$this->choicePush($data1);
				continue;
			}
			if(is_array($data1['id'])){
				foreach ($data1['id'] as $val) {
					$id = $val;
					$commentInfo[]= json_encode(['update'=>['_id'=>$id]])."\n".json_encode(['doc'=>$data]) ;
				}
			}else{
				$id = $data1['id'];
				$commentInfo[]= json_encode(['update'=>['_id'=>$id]])."\n".json_encode(['doc'=>$data]) ;
			}
			if(!empty($commentInfo)){
				$obj= new Comment();
				$url = $obj->url;
				$url.=Comment::INDEX_NAME.'/'.Comment::INDEX_NAME.'/_bulk';
				$header['content-type']="application/json; charset=UTF-8";
				$header['host']="es.licaishi.sina.com.cn";
				Yii::app()->curl->setHeaders($header);
				if(defined('ENV') && ENV == 'dev'){
		           $res=Yii::app()->curl->post($url,implode("\n",$commentInfo)."\n");
		        }else{
		           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url,implode("\n",$commentInfo)."\n");
		        }
				print_r($res);
			}
		}
		
	}

	private function choicePush($data){
		var_dump($data);
		if($data['is_essences']==1){
			$content=$data['content'];
			$circleid=$data['relation_id'];
			$circleInfo=Circle::model()->getCircleInfoMapByCircleids($circleid);
			if($circleInfo[$circleid]['is_push']==1){
				$p_uid=$circleInfo[$circleid]['p_uid'];
				$title=$circleInfo[$circleid]['title'];
				$planner_info=Planner::model()->getPlannerById($p_uid);
				$uids=Circle::model()->getCircleUser($circleid,1);
				$phone=array();
				$key=md5('lcs');
				if(!empty($uids)){
					foreach($uids as $v){
						$userInfo=User::model()->getUserInfoByUid($v);
						if(!empty($userInfo['phone'])){
							$phone[]= $userInfo['phone'];
						}
					}
					if(!empty($phone)){
						$key=md5('lcs'.$phone[0]);
					}
				}
				array_unique($phone);
				$user_info=implode(',',$phone);
				if(defined('ENV') && ENV == 'dev'){
		           $rurl="http://lcs-admin.licaishisina.com.cn/admin/messagePush/choicePush";
		        }else{
		           $rurl="http://lcs-admin.licaishisina.com/admin/messagePush/choicePush";
		        }
		        $params=array();
		        $params['planner_name']=$planner_info[$p_uid]['name'];
		        $params['url']=LCS_WEB_URL.'/wap/selectpush?id='.$circleid;
		        $params['content']=$content;
		        $params['user_phone']=$user_info;
		        $params['key']=$key;
		        $params['title']=$title;
		        $res = Yii::app()->curl->setTimeOut(10)->post($rurl,$params);
		        $res=json_decode($res,true);
		        echo '<pre>';var_dump($params);
		        if($res['code']==0){
		        	echo "精选推送成功\n";
		        }else{
		        	Common::model()->saveLog("精选推送失败:".json_encode($params),"error","push_essences");
		        	echo "精选推送失败\n";
		        }
			}
		}
	}		
}