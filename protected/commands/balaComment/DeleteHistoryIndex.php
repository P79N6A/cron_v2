<?php

/**
* 
*/
class DeleteHistoryIndex
{
	
	//任务代码
	const CRON_NO=14003 ;
	/**
	 * 入口
	 */
	public function DeleteComment(){
		$speechList=array();
		$commentInfo=array();
		$last_3month = date(DATE_RFC3339, strtotime("-15 day"));
		$obj= new Comment();
		$url = $obj->url;
		$url1=$url.Comment::INDEX_NAME.'/_search';
		$json='{"query":{"bool":{"must":[{"range":{"c_time":{"lt":"'.$last_3month.'"}}}]}},"from":0,"size":10000,"sort":{"c_time":"desc"},"aggs":{}}';
		// echo $json;
		$header['content-type']="application/json; charset=UTF-8";
		$header['host']="es.licaishi.sina.com.cn";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $speechLists=Yii::app()->curl->post($url1,$json);
        }else{
           $speechLists=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url1,$json);
        }
        $speechLists=json_decode($speechLists,true);
        if(!empty($speechLists['hits']['hits'])){
                foreach ($speechLists['hits']['hits'] as  &$val) {
                     $speechList[]=$val['_source']['id'];
                }
        }
		if(!empty($speechList)){
			foreach ($speechList as $v) {
				$commentInfo[]= json_encode(['delete'=>['_index'=>Comment::INDEX_NAME,'_type'=>Comment::INDEX_NAME,'_id'=>$v]])."\n" ;
			}

		}	
		if(!empty($commentInfo)){
			$url2=$url.Comment::INDEX_NAME.'/_bulk';
			if(defined('ENV') && ENV == 'dev'){
	           $res=Yii::app()->curl->post($url2,implode("\n",$commentInfo)."\n");
	        }else{
	           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url2,implode("\n",$commentInfo)."\n");
	        }
			print_r($res);
		}
	}		
}