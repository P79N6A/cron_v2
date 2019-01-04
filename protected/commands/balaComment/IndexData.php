<?php
/**
 * 
 */
class IndexData
{

	//任务代码
	const CRON_NO=14001 ;
	/**
	 * 入口
	 */
	public function SaveComments(){		
		$end = time() + 60;
        $commentInfo=array();
        $ids=array();
		while(time()<$end){
			$key = "lcs_c_comment_bala";
			$data = Yii::app()->redis_w->lpop($key);
			if(!$data){
				sleep(2);
				continue;
			}
			$ids[]=$data;
			$cmn_data=array();
			if(!empty($ids)){
			    try{
                    $cmn_data= Comment::model()->getCircleCommentInfo($ids);
                }catch (Exception $e){
                    Yii::app()->redis_w->rpush($key,$ids[0]);
                }
			}
			$uids=array();
			$relation_ids=array();
			if(!empty($cmn_data)){
				foreach($cmn_data as $v){
					if(!empty($v['uid'])){
						$uids[]=$v['uid'];
					}
					if(!empty($v['relation_id'])){
						$relation_ids[]=$v['relation_id'];
					}

				}
			}else{
                Yii::app()->redis_w->rpush($key,$data);
                continue;
            }
			$username=array();
			$circletitle=array();
			if(!empty($uids)){
				$uids=array_unique($uids);
				$username=User::model()->getUserNameByUid($uids);
			}
			if(!empty($relation_ids)){
				$uids=array_unique($relation_ids);
				$circletitle=Circle::model()->getCircleInfoMapByCircleids($relation_ids);
			}
			if(!empty($cmn_data)){

				foreach($cmn_data as &$v){
					$v['name']="";
					$v['title']="";
					$v['id']=(int)$v['id'];
					$v['is_good']=(int)$v['is_good'];
					$v['is_anonymous']=(int)$v['is_anonymous'];
					$v['relation_id']=(int)$v['relation_id'];
					$v['is_essences']=(int)$v['is_essences'];
					if($v['is_good']==-1){
						$filter=Yii::app()->curl->get("http://47.104.129.89/checkfilter?message=".$v['content']);
						$filter = json_decode($filter,true);
						$v['remark']=$filter['remark'];
						$v['auditstate']=(int)$filter['result'];
					}else{
						$v['remark']='';
						$v['auditstate']=0;
					}
					
					if(!empty($username)){
						foreach ($username as $key => $vv) {
							if($v['uid']==$vv['id']){
								$v['name']=$vv['name'];
							}
						}
					}
					if(!empty($circletitle)){
						foreach ($circletitle as  $vvv) {
							if($v['relation_id']==$vvv['id']){
								$v['title']=$vvv['title'];
							}
						}
					}
					$v['c_time']=date(DATE_RFC3339,strtotime($v['c_time']));
					$commentInfo[]= json_encode(['create'=>['_index'=>Comment::INDEX_NAME,'_type'=>Comment::INDEX_NAME,'_id'=>$v['id']]])."\n".json_encode($v) ;
				}
			}
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
