<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of InitGoimRedis
 *
 * @author hailin5
 */
class InitGoimRedis {
	//put your code here
	public function run($domain){
		$this->handle($domain);
	}
	public function handle($domain){
		$ids = $this->getCircleList();
		$init_url = "http://licaishi.sina.com.cn/api/circleCommentList?page_size=15&u_type=0&circle_id=";
		$goim_url = "http://".$domain."/2/circle/Init?circle_id=%s&sign=%s";
		foreach ($ids as $id){
			$url = $init_url.$id."&fr=lcs_client_caidao_android&wb_actoken=LUHsIO949XP%2BQi7WEttkJMKg7F9kCtnCHiw8n9g2br8%3D&token_fr=phone";
			$res = Yii::app()->curl->setTimeOut(5)->get($url);
			if(empty($res)){
				continue;
			}
			$data = @json_decode($res,true);
			if(!isset($data['data']['comment_page']['data']) || empty($data['data']['comment_page']['data'])){
				continue;
			}
			$list = $data['data']['comment_page']['data'];
			$post_data = [];
			foreach ($list as $v){
				$v['is_good'] = intval($v['is_good']);
				$v['is_anonymous'] = intval($v['is_anonymous']);				
				$item = array('info'=> json_encode($v));
				$post_data[] = $item;
			}
			try{
				echo Yii::app()->curl->setTimeOut(5)->post(sprintf($goim_url,$id,md5("circle_id=".$id."5ab4bdacb920a8c1e90f37ad2920013a")), json_encode($post_data));
				echo $id."\n";
			} catch (Exception $ex) {
				echo $ex->getMessage()."\n";
			}
			
		}
	}
	
	public function getCircleList(){
		$sql = "select id from lcs_circle where comment_num>0 order by comment_num desc";
		$ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
		return $ids;
	}
	
	
}
