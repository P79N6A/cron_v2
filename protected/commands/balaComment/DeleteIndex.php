<?php

/**
* 
*/
class DeleteIndex
{
	
	//任务代码
	const CRON_NO=14004 ;
	/**
	 * 入口
	 */
	public function DeleteComments(){
		$end = time() + 60;
        $commentInfo=array();
		while(time()<$end){
			$key = "lcs_c_delete_bala";
			$data = Yii::app()->redis_w->lpop($key);
			if(!$data){
				sleep(2);
				continue;
			}
			
			$commentInfo[]= json_encode(['delete'=>['_index'=>Comment::INDEX_NAME,'_type'=>Comment::INDEX_NAME,'_id'=>$data]])."\n" ;
			if(!empty($commentInfo)){
				$obj= new Comment();
				$url = $obj->url;
				$url.=Comment::INDEX_NAME.'/_bulk';
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
}