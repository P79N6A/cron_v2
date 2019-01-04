<?php

/**
* 更新用户修改信息
*/
class UpdateIndex
{
	
	//任务代码
	const CRON_NO=14008 ;
	/**
	 * 入口
	 */
	public function UpdateUsers(){
		$end = time() + 50;
        $userInfo='';
		while(time()<$end){
			$uids=array();
            $end2 = time() + 3;
			while(time()<$end2){
				$key = "lcs_u_update_es";
                $uid = Yii::app()->redis_w->lpop($key);
				if($uid){
					$uids[]=$uid;
				}
			}
			echo '<pre>';var_dump($uids);
			if(empty($uids)){
				sleep(2);
				continue;
			}
			$type_name=Common::TYPE_USER_NAME;
			$index_name=Common::INDEX_USER_NAME;	
			if(!empty($uids)){
				$uids = array_unique($uids);
				foreach($uids as $uid){
					if(!is_numeric($uid)){
						continue;
					}
					$userInfo= User::model()->getUserInfo($uid);
					if(!empty($userInfo)){
						$userInfo['c_time']=date(DATE_RFC3339,strtotime($userInfo['c_time']));
						$userInfo['u_time']=date(DATE_RFC3339,strtotime($userInfo['u_time']));
						$userInfo['client_time']=date(DATE_RFC3339,strtotime($userInfo['client_time']));
						$userInfo['r_time']=date(DATE_RFC3339,strtotime($userInfo['r_time']));
						$userInfo['name_u_time']=date(DATE_RFC3339,strtotime($userInfo['name_u_time']));
						//查询主键id
						$_id=$this->searchId($uid);
						if(!empty($_id)){
							$data= json_encode(['update'=>['_id'=>$_id]])."\n".json_encode(['doc'=>$userInfo]);
							CommonUtils::updateEsData($index_name,$type_name,$data);
						}else{
							$data= json_encode($userInfo) ;
							CommonUtils::esdata($index_name,$type_name,$data);

						}

					}
		
				}
				
			}		
			
		}
		
	}
	private function searchId($id){
		$obj= new Common();
		$index_name=Common::INDEX_USER_NAME;
		$url = $obj->url;
		$url1=$url.$index_name.'/_search';
		$json='{"query":{"bool":{"must":[{"term":{"uid":"'.$id.'"}}]}},"from":0,"size":1,"sort":{"c_time":"desc"},"aggs":{}}';
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $data=Yii::app()->curl->post($url1,$json);
        }else{
           $data=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url1,$json);
        }
        $_id='';
        $data=json_decode($data,true);
        if(!empty($data['hits']['hits'])){
             $_id=$data['hits']['hits'][0]['_id'];
        }
        return $_id;
	}
}
