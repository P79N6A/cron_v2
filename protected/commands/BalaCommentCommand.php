<?php
/**
 *保存说说到ES
 */

class BalaCommentCommand extends LcsConsoleCommand {
	public function init(){
		Yii::import('application.commands.balaComment.*');
	}

	/**
	 * 投教订单信息接口
	 */
	public function actionIndexData(){
		try{
			$obj = new IndexData();
            $obj->SaveComments();
            $this->monitorLog(IndexData::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(IndexData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	/**
	 * 导入历史数据
	 */
	public function actionPushData(){
		try{
			$obj = new PushData();
            $obj->SaveComments();
            $this->monitorLog(PushData::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(PushData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionCreateIndex(){
		$obj= new Comment();
		$url = $obj->url;
		$url.=Comment::INDEX_NAME;
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->put($url,'');
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->put($url,'');
        }
		var_dump($res);
	}
	public function actionDelete(){
		$obj= new Comment();
		$url = $obj->url;
		$url.=Comment::INDEX_NAME;
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->delete($url);
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->delete($url);
        }
		var_dump($res);
	}
	public function actionCreateMapping(){
		$obj= new Comment();
		$url = $obj->url;
		$url .= Comment::INDEX_NAME."/" .Comment::INDEX_NAME."/_mapping?pretty";	
		$data='{
			"'.Comment::INDEX_NAME.'": {
				"properties": {
					"id": {"type": "integer"},
					"cmn_id": {"type": "integer"},
					"cmn_type": {"type": "integer"},
					"relation_id": {"type": "integer"},
					"crc32_id": {"type": "long"},
					"u_type": {"type": "integer"},
					"uid": {"type": "long"},
					"content": {"type": "text"},
					"is_anonymous": {"type": "integer"},
					"is_good": {"type": "integer"},
					"discussion_id": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
					"c_time": {"type": "date"},
					"name": {"type": "text"},
					"title": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
					"auditstate": {"type": "integer"},
					"remark": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
					"is_essences": {"type": "integer"}
				}
			}
		}';
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->put($url, $data);
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->put($url, $data);
        }
		var_dump($res); 
	}
	public function actionDeleteIndex(){
		try{
			$obj = new DeleteIndex();
            $obj->DeleteComments();
            $this->monitorLog(DeleteIndex::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(DeleteIndex::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}

	}
	public function actionUpdateIndex(){
		try{
			$obj = new UpdateIndex();
            $obj->UpdateComments();
            $this->monitorLog(UpdateIndex::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(UpdateIndex::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionDeleteHistoryIndex(){
		try{
			$obj = new DeleteHistoryIndex();
            $obj->DeleteComment();
            $this->monitorLog(DeleteHistoryIndex::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(DeleteHistoryIndex::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}


}