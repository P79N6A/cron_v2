<?php
/**
 * 推送股票的问答和观点信息到newjs
 * Created by PhpStorm.
 * User: zwg
 * Date: 2015/11/25
 * Time: 17:01
 *
 *
 * 说明：
 *  1. 推送到newsJs 的数据单元结构必须是 data0#data1#data2#data3#data4$$$
 *  2. 数据单元中 data2是数据的唯一字段，如果data2有重复就会合并数据
 *  3. 中文必须是gbk字符集
 *  4. 每一个键值对必须是一行
 */

class NewJsInfoOfSymbolNew {

	const CRON_NO = 8105; //任务代码

	private $db_w = null;
	private $db_r = null;
	private $cur_time = '';
	private $space_time = 3600;
	private $limit = 8;
	private $pre_key = 'lcs2_';
	private $symbols = null;
	private $redis_key = 'lcs_cron_RsyncFinanceDataNew_new';

	//自定义数组 特使的股票代码名称
	private $symbols_special = array(
	array('symbol'=>'sh000001','name'=>'上证','type'=>'stock_cn','code'=>'000001'),
	array('symbol'=>'sh000001','name'=>'大盘','type'=>'stock_cn','code'=>'000001'),
	array('symbol'=>'sh000001','name'=>'A股','type'=>'stock_cn','code'=>'000001'),
	);

	//基金名称对应关系
	private $symbols_future_special = array(
	'CBOT-黄豆'=>'大豆','CBOT-黄豆粉'=>'豆粕','CBOT-黄豆油'=>'豆油','CBOT-小麦'=>'强麦','CBOT-玉米'=>'玉米',
	'CME瘦猪肉'=>'猪肉','COMEX白银'=>'白银','COMEX黄金'=>'黄金','COMEX铜'=>'沪铜','IMM-加元'=>'加元',
	'IMM-欧元'=>'欧元','IMM-日元'=>'日元','IMM-瑞郎'=>'瑞郎','IMM-英镑'=>'英镑','LME铝3个月'=>'沪铝',
	'LME镍3个月'=>'镍','LME铅3个月'=>'沪铅','LME铜3个月'=>'沪铜','LME锡3个月'=>'锡','LME锌3个月'=>'沪锌',
	'No11糖03'=>'白糖','No11糖05'=>'白糖','No11糖07'=>'白糖','NYBOT白糖'=>'白糖','NYMEX天然气'=>'天然气',
	'NYMEX原油'=>'原油','PVC'=>'PVC','白糖'=>'白糖','白银'=>'白银','标普期货'=>'标普','玻璃'=>'玻璃',
	'布伦特原油'=>'原油','菜粕'=>'菜粕','菜油'=>'菜籽油','菜籽'=>'菜籽','道指期货'=>'道指','动力煤'=>'动力煤',
	'豆二'=>'大豆','豆粕'=>'豆粕','豆一'=>'大豆','豆油'=>'豆油','国债期货'=>'国债期货','恒生指数期货'=>'恒指',
	'沪铝'=>'沪铝','沪铅'=>'沪铅','沪铜'=>'沪铜','沪锌'=>'沪锌','黄金'=>'黄金','鸡蛋'=>'鸡蛋','甲醇'=>'甲醇',
	'PTA'=>'PTA','胶合板'=>'胶合板','焦煤'=>'焦煤','焦炭'=>'焦炭','粳稻'=>'粳稻','PP'=>'PP','沥青'=>'沥青',
	'伦敦铂金'=>'铂金','伦敦金'=>'黄金','伦敦钯金'=>'钯金',	'伦敦银'=>'白银','螺纹钢'=>'螺纹钢','美元指数期货'=>'美元指数',
	'棉花'=>'棉花','纳指期货'=>'纳指',	'期指'=>'股指期货','强麦'=>'强麦','燃油'=>'燃料油','热轧卷板'=>'热卷',
	'铁矿石'=>'铁矿石','晚籼稻'=>'晚籼稻','纤维板'=>'纤维板','早籼稻'=>'早籼稻','线材'=>'线材','橡胶'=>'橡胶',
	'塑料'=>'塑料','玉米'=>'玉米','棕榈'=>'棕榈油','硅铁'=>'硅铁','锰硅'=>'锰硅'
	);

	public function syncDataToNewJs(){
		try{
			$start_time = CommonUtils::getMillisecond();

			$syncData = $this->createSyncData();
			if(!empty($syncData)){
				//生成数据文件
				$new_path = CommonUtils::createPath(DATA_PATH.DIRECTORY_SEPARATOR,'syncCJData');
				$new_path = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR,'newJsInfoOfSymbol');
				$dataFile = CommonUtils::saveDateFile(self::CRON_NO,$syncData,'lcs2.txt',$new_path.DIRECTORY_SEPARATOR,FILE_NO_DEFAULT_CONTEXT);
				if(file_exists($dataFile)){
					//推送到静态池
					$rsync_cmd = "rsync $dataFile 172.16.153.39::licai/";
					echo $rsync_cmd;
					exec($rsync_cmd);
				}else{
					Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "file no exits:".$dataFile);
				}
			}
			$end_time = CommonUtils::getMillisecond();
			$time = $end_time - $start_time;

			Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "生成并推送股票相关问答和观点到NEWJS消耗时间:".$time);
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}

	}

	/**
     * 创建同步到newsjs的数据
     */
	private function createSyncData(){
		$ret = array();

		$all_symbol = Symbol::model()->getSymbolList('stock_cn');
		$now_time = time();
		if($all_symbol) {
			foreach($all_symbol as $symbol_info) {
				$symbol = $symbol_info['symbol'];
				$name = $symbol_info['name'];
				if($symbol_info['symbol'] == 'sh000001'){
					continue;
				}

				$view_list = array();
				$question_list = array();
				$planner_list = array();
				$sql_qry = "(select type, r_id, c_time from lcs_symbol_relation where type=1 and symbol='$symbol' order by id desc limit 2) union (select type, r_id, c_time from lcs_symbol_relation where type=2 and symbol='$symbol' order by id desc limit 2)";
				$cmd_qry = $this->getDBR()->createCommand($sql_qry);
				$qry_result = $cmd_qry->queryAll();
				//$pln_sql = "select t.pln_id,t.deal_price,t.deal_amount,t.profit,t.c_time from lcs_plan_transactions t left join lcs_plan_info p on t.pln_id=p.pln_id where p.status=3 and t.symbol='$symbol' and t.type=2 and t.profit>0 order by id desc limit 1;";
				$pln_sql = "select pln_id,deal_price,deal_amount,profit,c_time,hold_avg_cost,transaction_cost from lcs_plan_transactions where symbol='$symbol' and type=2 and profit>0 order by id desc";
				$trans_res = $this->getDBR()->createCommand($pln_sql)->queryRow();
				//一个月以内有过交易
				if(!empty($trans_res) && ($now_time-strtotime($trans_res['c_time'])) > 2592000){
					$trans_res = array();
				}

				if(empty($qry_result) && empty($trans_res)){
					continue;
				}
				$v_ids = array();
				$a_ids = array();
				$p_uids = array();
				$pln_ids = array();
				if(!empty($qry_result)){
					foreach ($qry_result as $item){
						if($item['type']==1){
							$v_ids[] = $item['r_id'];
						}else if($item['type']==2){
							$a_ids[] = $item['r_id'];
						}
					}
				}


				//获取观点信息
				if(!empty($v_ids)){
					$view_list = View::model()->getViewById($v_ids);
					$sql = "select id as v_id,content from lcs_view where id in(".implode(',',$v_ids).")";
					$re = Yii::app()->lcs_r->CreateCommand($sql)->queryAll();
					$len = 60;
					foreach ($re as $vv){
						$vv['content'] = strip_tags($vv['content']);
						$vv['content'] = str_replace("&nbsp;",'',$vv['content']);
						$pos = mb_stripos($vv['content'],$name,0,'utf-8');
						if($pos === false){
							$pos = mb_stripos($vv['content'],$symbol_info['code'],0,'utf-8');
						}
						if($pos >30){
							$start = $pos-30;
						}else{
							$start = 0;
						}
						$view_list["$vv[v_id]"]['summary'] = mb_substr($vv['content'],$start,$len,'utf-8');
					}
					if($view_list) {
						foreach($view_list as $v_row) {
							$p_uids[] = $v_row['p_uid'];
						}
					}
				}

				//获取问答信息
				if(!empty($a_ids)){
					$question_list = Question::model()->getQuestionById($a_ids);
					if($question_list) {
						foreach($question_list as $q_row) {
							$p_uids[] = $q_row['p_uid'];
						}
					}
				}
				//获取计划信息
				if(!empty($trans_res)){
					$pln_list = Plan::model()->getPlanInfoByIds(array($trans_res['pln_id']));
					$p_uids[] = $pln_list["$trans_res[pln_id]"]['p_uid'];
				}

				if(!empty($p_uids)) {
					$p_uids = array_unique($p_uids);
					$planner_list = Planner::model()->getPlannerById($p_uids);
				}
				$item_str = '';
				if(!empty($trans_res)){
					$pln_id = $trans_res['pln_id'];
					$p_uid = $pln_list["$pln_id"]['p_uid'];
					$planner=array_key_exists($p_uid, $planner_list) ? $planner_list["$p_uid"] : null;
					$number = $pln_list["$pln_id"]['number']>9?$pln_list["$pln_id"]['number']:'0'.$pln_list["$pln_id"]['number'];
					if(empty($planner)){
						continue;
					}
					$profit = sprintf("%.2f",(($trans_res['deal_price']*$trans_res['deal_amount']-$trans_res['transaction_cost'])/($trans_res['hold_avg_cost']*$trans_res['deal_amount'])-1)*100);
					$item_str .= $trans_res['c_time'] .'###';
					$item_str .= $pln_id .'###';
					$item_str .= 'http://licaishi.sina.com.cn/plan/'.$pln_id.'?fr=stock###';
					$item_str .= $this->getSaftStr('我以'.$trans_res['deal_price'].'卖出'.$name."($symbol)".'单笔收益'.$profit."%(来自:".$pln_list["$pln_id"]['name'].$number.')###');
					$item_str .= $p_uid .'|||'. $this->getSaftStr($planner['name']) .'|||'. $planner['image'].'|||3';
					$item_str .= '$$$';
				}
				if(!empty($qry_result)){
					foreach($qry_result as $item) {
						if($item['type']==1){
							$view = array_key_exists($item['r_id'], $view_list) ? $view_list[$item['r_id']] : null;
							if(empty($view)){
								continue;
							}
							$p_uid = $view['p_uid'];
							$planner=array_key_exists($p_uid, $planner_list) ? $planner_list["$p_uid"] : null;
							if(empty($planner)){
								continue;
							}

							$item_str .= $view['p_time'] .'###';
							$item_str .= $view['id'] .'###';
							$item_str .= 'http://licaishi.sina.com.cn/web/viewInfo?v_id='. $view['id'] .'&fr=stock###';
							$item_str .= $this->getSaftStr($view['title']).'|||'.$this->getSaftStr($view['summary']).'###';
							$item_str .= $p_uid .'|||'. $this->getSaftStr($planner['name']) .'|||'. $planner['image'].'|||1';
							$item_str .= '$$$';
						}else if($item['type']==2){
							$question = array_key_exists($item['r_id'], $question_list) ? $question_list[$item['r_id']] : null;
							if(empty($question)){
								continue;
							}
							$p_uid = $question['p_uid'];
							$planner=array_key_exists($p_uid, $planner_list) ? $planner_list["$p_uid"] : null;
							if(empty($planner)){
								continue;
							}
							$item_str .= $item['c_time'] .'###';
							$item_str .= $question['id'] .'###';
							$item_str .= 'http://licaishi.sina.com.cn/ask/'.intval($question['id']).'?fr=stock###';
							$item_str .= $this->getSaftStr($question['content']) .'###';
							$item_str .= $p_uid .'|||'. $this->getSaftStr($planner['name']) .'|||'. $planner['image'].'|||2';
							$item_str .= '$$$';
						}
					}
				}
				
				$item_str .= "\n";  // 每行结尾.
				$ret[$this->pre_key. $symbol_info['symbol']] = $item_str;
			}
		}

	

		//获取交易时间 说说数据
		$this->getCommentOfTradingTime($ret);

		$syncData = '';
		if(!empty($ret)){
			foreach ($ret as $k=>$item){
				$syncData .= $k.'='.$item;
			}
		}
		return $syncData;  // 输出结果.
	}

	/**
     * 获取外汇最新观点和问答数据
     */
	private function getSyncForeignExchangeData(&$ret){
		$item_str = '';

		//外汇最新观点
		$views = $this->getDBR()->createCommand("SELECT id,id as v_id,p_uid,ind_id,pkg_id,title,p_time FROM lcs_view where ind_id=4 ORDER by p_time DESC lIMIT 4")->queryAll();
		//理财师信息
		$planner_list = array();
		$p_uids = array();
		if(!empty($views)){
			array_walk($views,function($val) use(&$p_uids){
				array_push($p_uids,$val['p_uid']);
			});

			if(!empty($p_uids)){
				$planner_list = Planner::model()->getPlannerById($p_uids);
			}

			//拼接数据
			foreach($views as $view){
				$planner = isset($planner_list[$view['p_uid']]) ? $planner_list[$view['p_uid']] : array();
				if(empty($planner)){
					continue;
				}

				$item_str .= $view['p_time'] .'###';
				$item_str .= $view['id'] .'###';
				$item_str .= 'http://licaishi.sina.com.cn/web/viewInfo?v_id='. $view['id'] .'&ind_id='.$view['ind_id'].'###';
				$item_str .= $this->getSaftStr($view['title']) .'###';
				$item_str .= $view['p_uid'] .'|||'. $this->getSaftStr($planner['name']) .'|||'. $planner['image'].'|||1';
				$item_str .= '$$$';
			}
		}


		//外汇最新问答
		$questions = $this->getDBR()->createCommand("SELECT id,uid, p_uid,ind_id,status,content,answer_time,u_time FROM lcs_ask_question where ind_id=4 ORDER by answer_time DESC lIMIT 4")->queryAll();
		//理财师信息
		$planner_list = array();
		$p_uids = array();
		if(!empty($questions)){
			array_walk($questions,function($val) use(&$p_uids){
				array_push($p_uids,$val['p_uid']);
			});

			if(!empty($p_uids)){
				$planner_list = Planner::model()->getPlannerById($p_uids);
			}

			//拼接数据
			foreach($questions as $question){
				$planner = isset($planner_list[$question['p_uid']]) ? $planner_list[$question['p_uid']] : array();
				if(empty($planner)){
					continue;
				}

				$item_str .= $question['answer_time'] .'###';
				$item_str .= $question['id'] .'###';
				$item_str .= 'http://licaishi.sina.com.cn/ask/'.intval($question['id']).'###';
				$item_str .= $this->getSaftStr($question['content']) .'###';
				$item_str .= $question['p_uid'] .'|||'. $this->getSaftStr($planner['name']) .'|||'. $planner['image'].'|||2';
				$item_str .= '$$$';
			}

		}

		!empty($item_str) && $item_str .= "\n";

		$ret[$this->pre_key."foreign_exchange"] = $item_str;

	}


	private function getCommentOfTradingTime(&$rec){
		try{
            //获取交易时间A股  "841529754"      8888
            $newsjs_trade_data = '';
            $newsjs_trade_num = '';
            $this->getCommentOfTradingTimeHandler(8888,'841529754',$newsjs_trade_data,$newsjs_trade_num);
            $rec[$this->pre_key."trade_cn"]=$newsjs_trade_data;
            $rec[$this->pre_key."trade_cn_num"]=$newsjs_trade_num;
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

        try{
            //获取交易时间金银油     "1160743180"     8889
            $newsjs_trade_data = '';
            $newsjs_trade_num = '';
            $this->getCommentOfTradingTimeHandler(8889,'1160743180',$newsjs_trade_data,$newsjs_trade_num);
            $rec[$this->pre_key."trade_jyy"]=$newsjs_trade_data;
            $rec[$this->pre_key."trade_jyy_num"]=$newsjs_trade_num;
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

        try{
            //获取交易时间美股      "635961577"      8890
            $newsjs_trade_data = '';
            $newsjs_trade_num = '';
            $this->getCommentOfTradingTimeHandler(8890,'635961577',$newsjs_trade_data,$newsjs_trade_num);
            $rec[$this->pre_key."trade_mg"]=$newsjs_trade_data;
            $rec[$this->pre_key."trade_mg_num"]=$newsjs_trade_num;
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

        try{
            //获取交易时间港股    "1391407231"       8891
            $newsjs_trade_data = '';
            $newsjs_trade_num = '';
            $this->getCommentOfTradingTimeHandler(8891,'1391407231',$newsjs_trade_data,$newsjs_trade_num);
            $rec[$this->pre_key."trade_gg"]=$newsjs_trade_data;
            $rec[$this->pre_key."trade_gg_num"]=$newsjs_trade_num;
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

        try{
            //获取交易时间期货   "3420873157"        8892
            $newsjs_trade_data = '';
            $newsjs_trade_num = '';
            $this->getCommentOfTradingTimeHandler(8892,'3420873157',$newsjs_trade_data,$newsjs_trade_num);
            $rec[$this->pre_key."trade_qh"]=$newsjs_trade_data;
            $rec[$this->pre_key."trade_qh_num"]=$newsjs_trade_num;
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


	}

	private function getRandomComment($data,$num=5){
		$res=array();
		if(empty($data)){
			return $res;
		}
		if(count($data)<=$num){
			return $data;
		}
		$random_keys = array_rand($data,$num);
		foreach($random_keys as $key){
			$res[$key]=$data[$key];
		}
		return $res;
	}


	private function doComment($data, &$rec, $errMsg){
		if(!empty($data)){
			try{
				$data = json_decode($data,true);
				if(isset($data['code']) && $data['code']==0){
					$comment_list = isset($data['data']['data'])?$data['data']['data']:null;
					if(!empty($comment_list)) {
						$count=0;
						foreach($comment_list as $cmn) {
							if(array_key_exists($cmn['cmn_id'],$rec)) {
								continue;
							}
							$item_str = $this->getSaftStr(CommonUtils::formatDate($cmn['c_time'],'web')) .'###';
							$item_str .= $cmn['up_down'].'###';
							$item_str .= $cmn['cmn_id'] .'###';
							$item_str .= $this->getSaftStr(CommonUtils::getSubStrNew($cmn['content'],85,'...')) .'###';
							$item_str .= $cmn['uid'] .'|||'. $this->getSaftStr($cmn['name']) .'|||'. $cmn['image'].'|||'. $cmn['u_type'].'|||'.($cmn['u_type']==2?$this->getSaftStr($cmn['company']):'');
							$item_str .= '$$$';

							$rec[$cmn['cmn_id']]=$item_str;
							if(++$count ==10){
								break;
							}
						}
					}
				}
			}catch (Exception $e){
				Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $errMsg.LcsException::errorHandlerOfException($e)->toJsonString());
			}
		}
	}


	private function getDBW(){
		if(empty($this->db_w)){
			$this->db_w = Yii::app()->lcs_w;
		}
		return $this->db_w;
	}

	private function getDBR(){
		if(empty($this->db_r)){
			$this->db_r = Yii::app()->lcs_r;
		}
		return $this->db_r;
	}


	/**
     * 获取安全的文本信息
     * @param unknown $str
     * @return mixed
     */
	private function getSaftStr($str){
		$str = str_replace(array('"',"\r","\n"),array('','',''), strip_tags($str));
		return iconv("UTF-8", "gb18030//IGNORE", $str);
	}
	
	
	 /**
     * 获取具体的交易时间说说
     * @param $relatiion_id
     * @param $tab_idx
     * @param $newsjs_trade_data
     * @param $newsjs_trade_num
     */
    private function getCommentOfTradingTimeHandler($relatiion_id,$tab_idx, &$newsjs_trade_data,&$newsjs_trade_num){
        $curl =Yii::app()->curl;
        $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
        $url='http://i.licaishi.sina.com.cn/api/balaCommentList';
        //获取交易时间A股
        $res_cn=array();
        //$res_cn_json = array();
        // 精华
        $params = array('cmn_type'=>51,'relation_id'=>$relatiion_id,'u_type'=>0,'is_good'=>'1');
        $search_res = $curl->get($url,$params);
        $this->doComment($search_res,$res_cn,"获取交易时间 ".$relatiion_id." 精华");
        //$this->doCommentJson($search_res,$res_cn_json,"获取交易时间A股 精华");
        // 理财师
        $params = array('cmn_type'=>51,'relation_id'=>$relatiion_id,'u_type'=>2);
        $search_res = $curl->get($url,$params);
        $this->doComment($search_res,$res_cn,"获取交易时间 ".$relatiion_id." 理财师");
        //$this->doCommentJson($search_res,$res_cn_json,"获取交易时间A股 理财师");

        //放到newjs
        $res_cn = $this->getRandomComment($res_cn,5);
        krsort($res_cn);//时间倒序
        //$rec[$this->pre_key."trade_cn"]=implode('',$res_cn)."\n";
        $newsjs_trade_data=implode('',$res_cn)."\n";
        $trade_cn_num=NewComment::model()->getCommentNumOfMaster($tab_idx,date('Y-m-d 00:00:00'));
        //$rec[$this->pre_key."trade_cn_num"]=date('Y-m-d H:i:s').'###0###0###0###'.$trade_cn_num."$$$\n";
        $newsjs_trade_num=date('Y-m-d H:i:s').'###0###0###0###'.$trade_cn_num."$$$\n";

        //自己存储到redis
        /*krsort($res_cn_json);//时间倒序
        $res_cn_json_str = json_encode(array_values($res_cn_json),JSON_UNESCAPED_UNICODE);
        $res_cn_num_json_str = json_encode(array(date('Y-m-d H:i:s'),'0','0','0','lcs_trade',''.$trade_cn_num),JSON_UNESCAPED_UNICODE);
        Yii::app()->redis_w->set('lcs_newjs_trade_cn', $res_cn_json_str);
        Yii::app()->redis_w->set('lcs_newjs_trade_cn_num', $res_cn_num_json_str);*/
    }






}
