<?php
/**
 * 定时任务:生成百度搜索的xml
 * User: liyong3
 * Date: 2016/01/04
 */
class BaiduXml {
	
	const CRON_NO = 1109; //任务代码
	public $test_stocks = '601390,600795,600030,601989,601668,000667,601106,000031,002024,600886,
                           000709,600737,601918,601600,600068,600503,300024,600415,600428,601898,
                           600726,600570,300079,000768,600688,300359,300058,600690,000021,600893,
                           002066,000333,002470,002176,000698,600522,000061,000712,300219,000511,
                           000009,600502,002014,002197,300205,002215,600270,000727,000797,002496';
	private $_reg_match = '/^(sh60|sz00|sz30)\d*/';  //验证A股
	
	public function __construct() {
	
	}
	
	/**
	 * 生成xml文件，并推送到finance域下。
	 * 5分钟一次
	 */
	public function create() {
		$cn_stocks = $this->getStocks('stock_cn');
		foreach ($cn_stocks as &$stock_info) {
			if(preg_match($this->_reg_match, $stock_info['symbol'])) {
				$stock_info['data'] = $this->getAsk($stock_info['id']);
			}
		}
		
		$this->createXmlFile($cn_stocks);
	}
	
	private function getStocks($type) {
		$sql = "select id, code, symbol,name from lcs_ask_tags where type='".$type."' and code in (". $this->test_stocks .") ";
		$res =  Yii::app()->lcs_r->createCommand($sql)->queryAll();
		return $res;
	}
	
	private function getAsk($tag_id) {
		$answer_time = date('Y-m-d H:i:s', time()-5*3600*24);
		$sql = "select a.id from lcs_ask_question a  left join lcs_ask_sdata s on a.id=s.q_id
                where (a.price=0 or (a.price>0 and a.answer_time<='".$answer_time."')) and s.tag_id=".$tag_id
		      ." order by a.u_time desc limit 3";
		
		$sql_count = 'select count(q_id) from lcs_ask_sdata where tag_id='.$tag_id;
		
		$db_r = Yii::app()->lcs_r;
		$q_ids = $db_r->createCommand($sql)->queryColumn();
		$total = $db_r->createCommand($sql_count)->queryScalar();
		if($total == 0) {
			return array();
		}
		!empty($q_ids) && $question_list = Question::model()->getQuestionById($q_ids);
		if(!empty($question_list)) {
			foreach ($question_list as $q_row) {
				$p_uids[] = $q_row['p_uid'];
			}
			$planner_list = Planner::model()->getPlannerById($p_uids);
			foreach ($question_list as &$q_row) {
				$q_row['p_name'] = $planner_list[$q_row['p_uid']]['name'];
			}
		}
		$q_list = array();
		if(!empty($q_ids)) {
			foreach($q_ids as $q_id) {
				isset($question_list[$q_id]) && ($q_list[strtotime($question_list[$q_id]['u_time'])] = $question_list[$q_id]);
			}
		}
		$result = array(
			'question_list' => $q_list,
			'total' => $total
		);
		return $result;
	}
	
	/**
	 * 暂时不用。
	 * @param unknown $tag_id
	 * @return array
	 */
	private function getView($tag_id) {
		$sql = 'select v_id from lcs_view_sdata where tag_id='.$tag_id.' order by u_time desc limit 3';
		$sql_count = 'select count(v_id) from lcs_view_sdata where tag_id='.$tag_id;
		$db_r = Yii::app()->lcs_r;
		$v_ids = $db_r->createCommand($sql)->queryColumn();
		$total = $db_r->createCommand($sql_count)->queryScalar();
		if($total == 0) {
			return array();
		}
		!empty($v_ids) && $view_list = View::model()->getViewById($v_ids);
		if(!empty($view_list)) {
			foreach ($view_list as $v_row) {
				$p_uids[] = $v_row['p_uid'];
			}
			$planner_list = Planner::model()->getPlannerById($p_uids);
			foreach ($view_list as &$v_row) {
				$v_row['p_name'] = $planner_list[$v_row['p_uid']]['name'];
			}
		}
		$v_list = array();
		if(!empty($v_ids)) {
			foreach($v_ids as $v_id) {
				isset($view_list[$v_id]) && ($v_list[strtotime($view_list[$v_id]['p_time'])] = $view_list[$v_id]);
			}
		}
		$result = array(
			'view_list' => $v_list,
			'total' => $total
		);
		return $result;
	}
	
	private function createXmlFile($data) {
		$total_content = '';
		$line_tail = "\n";
		if($data) {
			foreach ($data as $stock_row) {
				$content = '';
				$code_content = '';
				$name_content = '';
				if(empty($stock_row['data'])) {
					continue;
				}
				
				$title = $stock_row['name'].'('. $stock_row['code'] .')_理财师在线解读'.$stock_row['name'].'_新浪理财师';
				$brief = '共有'.($stock_row['data']['total']).'条关于'.$stock_row['name'].'的理财师解读';
				$url = 'licaishi.sina.com.cn/s/'.$stock_row['code'].'?sina-fr=bd.ala.lcs';
				$show_url = 'licaishi.sina.com.cn/s/'.$stock_row['code'];
				
				$code_content .= '<item>'.$line_tail;
				$name_content .= '<item>'.$line_tail;
				$code_content .= '<key>'. $stock_row['code'] .'</key>'.$line_tail;
				$name_content .= '<key>'. $stock_row['name'] .'</key>'.$line_tail;
				
				$content .= '<display>'.$line_tail;
				$content .= '<title>'. $title .'</title>'.$line_tail;
				$content .= '<url>http://'. $url .'</url>'.$line_tail;
				$content .= '<brief>'. $brief .'</brief>'.$line_tail;
				$content .= '<hot-bbs>'. $stock_row['name'] .'相关：</hot-bbs>'.$line_tail;
				$content .= '<hot-bbs-link txt="个股问答" url="'.CHtml::encode('http://licaishi.sina.com.cn/s/'. $stock_row['code'] .'?ind_id=1&t=2&all=0&sina-fr=bd.ala.lcs').'" />'.$line_tail;
				$content .= '<hot-bbs-link txt="观点分析" url="'. CHtml::encode('http://licaishi.sina.com.cn/s/'. $stock_row['code'] .'?ind_id=1&t=1&all=0&sina-fr=bd.ala.lcs') .'" />'.$line_tail;
				$content .= '<hot-bbs-link txt="理财师计划" url="'. CHtml::encode('http://licaishi.sina.com.cn/s/'. $stock_row['code'] .'?t=3&all=1&sina-fr=bd.ala.lcs') .'" />'.$line_tail;
				$content .= '<hot-bbs-link txt="擅长理财师" url="'. CHtml::encode('http://licaishi.sina.com.cn/s/'. $stock_row['code'] .'?sina-fr=bd.ala.lcs&abc=1').'" />'.$line_tail;
				
				$question_str = '';
				$cur_date = '';  //该股票问答的更新时间
				
				$data_merge = array();
				!empty($stock_row['data']['question_list']) && $data_merge = $stock_row['data']['question_list'];
				
				if(!empty($data_merge)) {
					$i = 0;
					foreach ($data_merge as $row) {
						if($i>=3) {
							break;
						}
						if(isset($row['content']) && $row['content']) {  //问答
							$question_str .= '<link txt="'.$row['p_name'].'解答：'
									      .CHtml::encode(mb_substr(trim($row['content']), 0,30,'utf-8')).'" url="http://licaishi.sina.com.cn/ask/'.$row['id'].'?sina-fr=bd.ala.lcs"/>'.$line_tail;
							if($cur_date =='' || strtotime($cur_date) < strtotime($row['u_time'])) {
								$cur_date = $row['u_time'];
							}
						}elseif (isset($row['title']) && $row['title']) {  //观点
							$question_str .= '<link txt="'.$row['p_name'].'：'
									      .CHtml::encode(mb_substr(trim($row['title']), 0,30,'utf-8')).'" url="http://licaishi.sina.com.cn/view/'.$row['id'].'?sina-fr=bd.ala.lcs"/>'.$line_tail;
							if($cur_date =='' || strtotime($cur_date) < strtotime($row['p_time'])) {
								$cur_date = $row['p_time'];
							}
						}
						
						$i++;
					}//end foreach.
					$question_str .= '<morelink txt="查看更多解读>>" url="http://licaishi.sina.com.cn/s/'. $stock_row['code'] .'?sina-fr=bd.ala.lcs"/>'.$line_tail;
				}
				$content .= '<date>'.date('Y-m-d', strtotime($cur_date)).'</date>'.$line_tail;
				$content .= '<showurl>'.$show_url.'</showurl>'.$line_tail;
				$content .= $question_str;
				$content .= '</display>'.$line_tail;
				$content .= '</item>'.$line_tail;
				
				$total_content .= $code_content. $content. $name_content . $content;
			}//end foreach.
		}//end if.
		
		$file_content = '<?xml version="1.0" encoding="utf-8"?>'.$line_tail;
		$file_content .= '<DOCUMENT>'. $total_content. '</DOCUMENT>';
		
		$new_path = CommonUtils::createPath(DATA_PATH.DIRECTORY_SEPARATOR, 'licaishi');
		$prod_dir = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR, 'seo');
		$save_dir = CommonUtils::createPath($prod_dir.DIRECTORY_SEPARATOR, date('Ymd'));

		$txt_name = 'licaishi_'.date('ymdHi').'_50.xml';
		$prod_name = 'licaishi_baidu_50.xml';
		file_put_contents($save_dir."/".$txt_name, $file_content);
		file_put_contents($prod_dir."/".$prod_name, $file_content);
		
		//file to s3
		$rs = $this->pushToS3($prod_dir."/".$prod_name);
	}
	
	/**
	 * upload file to http://s3.licaishi.sina.com.cn
	 */
	private function pushToS3($local_file) {
		$file_content = file_get_contents($local_file);
		$file_length = filesize($local_file);
		$dest_file = 'licaishi/seo/licaishi_baidu_50.xml';
		$s3 = Yii::app()->sinaStorageService;
		$s3->setCURLOPTs(array(CURLOPT_VERBOSE=>1));
		$s3->setAuth(true);
		$upload_res = false;
		try{
			$upload_res = $s3->uploadFile($dest_file, $file_content, $file_length, 'text/xml', $result);
			echo json_encode($upload_res)."\n";
		}catch (SinaServiceException $e){
			echo $e->getCode().':'.$e->getMessage();
		}
		return $upload_res;
	}
	
}