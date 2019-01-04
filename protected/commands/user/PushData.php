<?php
/**
 * 
 */
class PushData
{

	//任务代码
	const CRON_NO='' ;
	/**
	 * 入口
	 */
	public function SaveUsers(){
		$obj= new Common();
		$index_name=Common::INDEX_USER_NAME;
		$type_name=Common::TYPE_USER_NAME;
		$url = $obj->url;
		$url1=$url.$index_name.'/_search';
		$json='{"from":0,"size":1,"sort":{"c_time":"desc"},"aggs":{}}';
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $data=Yii::app()->curl->post($url1,$json);
        }else{
           $data=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url1,$json);
        }
        $data=json_decode($data,true);
        $uid='';
        if(!empty($data['hits']['hits'])){
             $uid=$data['hits']['hits'][0]['_source']['uid'];
        }
        $uids=Yii::app()->lcs_r->createCommand('SELECT `id` from lcs_user_index where id>'.$uid)->queryAll();
        if(!empty($uids)){
        	foreach($uids as $v){
        		$userInfo= User::model()->getUserInfo($v['id']);
        		if($userInfo){
                    $userInfo['c_time']=date(DATE_RFC3339,strtotime($userInfo['c_time']));
                    $userInfo['u_time']=date(DATE_RFC3339,strtotime($userInfo['u_time']));
                    $userInfo['client_time']=date(DATE_RFC3339,strtotime($userInfo['client_time']));
                    $userInfo['r_time']=date(DATE_RFC3339,strtotime($userInfo['r_time']));
                    $userInfo['name_u_time']=date(DATE_RFC3339,strtotime($userInfo['name_u_time']));
                    $params= json_encode($userInfo) ;
                    CommonUtils::esdata($index_name,$type_name,$params);
                }
        	}
        }
	}	
}
