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

class NewJsInfoOfSymbol {

    const CRON_NO = 8104; //任务代码

    private $db_w = null;
    private $db_r = null;
    private $cur_time = '';
    private $space_time = 3600;
    private $limit = 8;
    private $pre_key = 'lcs2_';
    private $symbols = null;
    private $redis_key = 'lcs_cron_RsyncFinanceDataNew';

    /**
     * 处理相关的问答和观点数据
     */
    public function createRelationData(){
        $this->cur_time = date('Y-m-d H:i:s');

        $s_time = Yii::app()->redis_r->hget($this->redis_key,'s_time');
        $e_time = Yii::app()->redis_r->hget($this->redis_key,'e_time');

        if(empty($s_time) || empty($e_time) || $s_time>$e_time){
            $s_time = date("Y-m-d H:i:s",strtotime("-3 minute"));
            $e_time = date("Y-m-d H:i:s");
        }

        try{
            var_dump($s_time,$e_time);
            //生成相关的观点数据,暂时停止，从新财讯处获取相关股票数据
            $view_num = $this->createRelationViewData($s_time,$e_time);
            //生成相关的问答数据
            $ask_num = $this->createRelationAskData($s_time,$e_time);
            //生成相关的问答数据
            $silk_num = $this->createRelationSilkData($s_time,$e_time);

            Yii::app()->redis_w->hset($this->redis_key,'s_time',$e_time);
            Yii::app()->redis_w->hset($this->redis_key,'e_time',date("Y-m-d H:i:s"));

        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $e->getMessage());
        }
    }


    public function syncDataToNewJs(){
        try{
            $start_time = CommonUtils::getMillisecond();

            $syncData = $this->createSyncData(); //'test time:'.date('Y-m-d H:i:s');//
            if(!empty($syncData)){
                //生成数据文件
                $new_path = CommonUtils::createPath(DATA_PATH.DIRECTORY_SEPARATOR,'syncCJData');
                $new_path = CommonUtils::createPath($new_path.DIRECTORY_SEPARATOR,'newJsInfoOfSymbol');
                $dataFile = CommonUtils::saveDateFile(self::CRON_NO,$syncData,'lcs2.txt',$new_path.DIRECTORY_SEPARATOR,FILE_NO_DEFAULT_CONTEXT);
                if(file_exists($dataFile)){
                    //推送到静态池
                    //$rsync_cmd = "cd ".DATA_PATH."/ && rsync ./syncCJData/newJsInfoOfSymbol/test.txt 172.16.153.39::licai/";
                    $rsync_cmd = "rsync $dataFile 172.16.153.39::licai/";
                    exec($rsync_cmd);
                }else{
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, "file no exits:".$dataFile);
                }
            }
            $end_time = CommonUtils::getMillisecond();
            $time = $end_time - $start_time;

            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "生成并推送股票相关问答和观点到NEWJS消耗时间:".$time);
        }catch (Exception $e){
            //var_dump($e);
            throw LcsException::errorHandlerOfException($e);
        }

    }

    /**
     * 创建同步到newsjs的数据
     */
    private function createSyncData(){
        $ret = array();
        //todo lcs_stock_cn
        //针对上证和A股统一处理
        $special_symbol = array('stock_cn','sh000001');
        foreach ($special_symbol as $special){
            if($special == 'stock_cn'){
                $sql_v_id = 'select id from lcs_view where ind_id=1 order by p_time desc limit 20';
            }else{
                $sql_v_id = "select r_id as id from lcs_symbol_relation where type=1 and symbol='$special' order by c_time desc limit 20";
            }
            $command = Yii::app()->lcs_r->createCommand($sql_v_id);
            $view_ids = $command->queryColumn();  // 已排序。
            if($view_ids) {
                $view_list = View::model()->getViewById($view_ids);
                if($view_list) {
                    foreach($view_list as $v_row) {
                        $planner_id[] = $v_row['p_uid'];
                        $pkg_id[] = $v_row['pkg_id'];
                    }
                    if($planner_id) {
                        $planner_list = Planner::model()->getPlannerById($planner_id);
                    }
                    if($pkg_id) {
                        $pkg_list = Package::model()->getPackagesById($pkg_id);
                    }
                    //$ret .= $this->pre_key.$special."=";
                    $item_str = '';
                    foreach($view_ids as $v_id) {
                        if(!array_key_exists($v_id, $view_list)){
                            continue;
                        }
                        $pkg_name=isset($pkg_list[$view_list[$v_id]['pkg_id']]['title']) ? $pkg_list[$view_list[$v_id]['pkg_id']]['title']:'';
                        $item_str .= $view_list["{$v_id}"]['p_time'] .'###';
                        $item_str .= $v_id .'###';
                        $item_str .= 'http://licaishi.sina.com.cn/web/viewInfo?v_id='. $v_id .'&ind_id='.$view_list[$v_id]['ind_id'].'###';
                        $item_str .= str_replace('"','',mb_convert_encoding(strip_tags($view_list[$v_id]['title']), 'gb18030', 'utf-8')) .'###';
                        $item_str .= $view_list[$v_id]['p_uid'] .'|||'. mb_convert_encoding($planner_list[$view_list[$v_id]['p_uid']]['name'],'gb18030','utf-8') .'|||'. $planner_list[$view_list[$v_id]['p_uid']]['image'] .'|||'. $view_list[$v_id]['pkg_id'] .'|||'. mb_convert_encoding(strip_tags($pkg_name),'gb18030','utf-8').'|||1';
                        $item_str .= '$$$';
                    }
                    $item_str .= "\n";
                    //update by weiguang3 20150723 key错误
                    //$ret[$this->pre_key. $symbol_info['symbol']] = $item_str;
                    $ret[$this->pre_key. $special] = $item_str;

                }
            }
        }


        //获取外汇最新观点和问答数据
        $this->getSyncForeignExchangeData($ret);

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


    /**
     * 获取具体的交易时间说说
     * @param $relatiion_id
     * @param $tab_idx
     * @param $newsjs_trade_data
     * @param $newsjs_trade_num
     */
    private function getCommentOfTradingTimeHandler($relatiion_id,$tab_idx, &$newsjs_trade_data,&$newsjs_trade_num){
        try{
            $curl =Yii::app()->curl;
            $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
            ///lixiang23 add 设置超时时间为13秒
            $curl->setTimeOut(13);
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
        }catch(Exception $ex){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($ex)->toJsonString().";错误检查点1");
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
    /*
    private function doCommentJson($data, &$rec, $errMsg){
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
                            $item=array();
                            $item[] = $cmn['c_time'];
                            $item[] = $cmn['cmn_id'];
                            $item[] = CommonUtils::getSubStrNew($cmn['content'],50,'..');
                            $item[] = $cmn['up_down'];
                            $item[] = 'lcs2_trade';
                            $item[] = $cmn['uid'] .'|||'. $cmn['name'] .'|||'. $cmn['image'].'|||'. $cmn['u_type'].'|||'.($cmn['u_type']==2?$cmn['company']:'');

                            $rec[$cmn['cmn_id']]=$item;
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
    }*/

    private function getDBW(){

        if(empty($this->db_w)){
            $this->db_w = Yii::app()->lcs_w;
        }
        $this->db_w->setActive(false);
        $this->db_w->setActive(true);
        return $this->db_w;
    }

    private function getDBR(){
        if(empty($this->db_r)){
            $this->db_r = Yii::app()->lcs_standby_r;
        }
        $this->db_r->setActive(false);
        $this->db_r->setActive(true);
        return $this->db_r;
    }


    /**
     * 获取安全的文本信息
     * @param unknown $str
     * @return mixed
     */
    private function getSaftStr($str){
        $str = str_replace(array('"',"\r","\n"),array('','',''), strip_tags($str));
        //return mb_convert_encoding($str, 'gb18030', 'utf-8');
        return iconv("UTF-8", "gb18030//IGNORE", $str);
        //return str_replace(array('"',"\r","\n"),array('','',''),mb_convert_encoding(strip_tags($str), 'gb18030', 'utf-8'));
    }

    /**
     * 生成锦囊相关股票的数据
     */
    private function createRelationSilkData($s_time,$e_time){
        if(empty($s_time) || empty($e_time)){
            throw new Exception('createRelationViewData 开始时间或结束时间为空');
            return;
        }

        //获取锦囊数据  最多50条
        $sql = "select id,title,content,p_time from lcs_silk_article where p_time>'".$s_time."' and p_time<='".$e_time."' and status=0";
        $silks = $this->getDBR()->CreateCommand($sql)->queryAll();
        $save_records = 0;
        if(is_array($silks) && sizeof($silks) >0 ){
            $cur_time = date('Y-m-d H:i:s');  //当前系统时间
            $count = 0; //记录需要插入的记录条数
            $values = ''; //
            $sql_ins = "insert into lcs_symbol_relation (type,symbol_type,symbol,r_id,c_time,u_time) values ";
            foreach($silks as $val){
                $preg_content = $val['title'].$val['content'];
                $preg_symbols = SymbolService::getPregSymbols($preg_content,1);
                if(!empty($preg_symbols)){
                    foreach ($preg_symbols as $preg){
                        $values .= "(5,'{$preg[0]}','{$preg[1]}',{$val['id']},'{$val['p_time']}','{$cur_time}'),";
                        $count++;
                    }
                }
                if($count>=100){
                    $values = substr($values,0,-1);
                    $save_records += $this->getDBW()->CreateCommand($sql_ins.$values)->execute();
                    $count = 0;
                    $values = '';
                }
            }
            if($count>0){
                $values = substr($values,0,-1);
                $save_records += $this->getDBW()->CreateCommand($sql_ins.$values)->execute();
            }
        }
        return $save_records;
    }

    /**
     * 生成关联行情的观点数据
     *
     * 写入数据表 lcs_symbol_relation
    id
    type 1观点2问答
    symbol_type 类型（stock_cn,a股fund_open,开基fund_etf,etf基金fund_close,封闭基金future_innern内盘期货 ）global_future 盘期货
    symbol
    r_id 观点或问答id
    u_time 记录更新时间
    c_time 观点发布时间 或问题回答时间
     *
     * 匹配的内容为：标题和ne
     *
     */
    private function createRelationViewData($s_time, $e_time){
        if(empty($s_time) || empty($e_time)){
            throw new Exception('createRelationViewData 开始时间或结束时间为空');
            return;
        }
        //获取观点数据  最多50条
        $sql = "select id,title,ind_id,content,content_pay,p_time from lcs_view where p_time>'".$s_time."' and p_time<='".$e_time."' and status=0 and ind_id in(1,2,4)";
        $views = $this->getDBR()->CreateCommand($sql)->queryAll();
        $save_records = 0;
        //echo 'views num:',sizeof($views),"\n";
        if(is_array($views) && sizeof($views) >0 ){
            $cur_time = date('Y-m-d H:i:s');  //当前系统时间
            $count = 0; //记录需要插入的记录条数
            $values = ''; //
            $sql_ins = "insert into lcs_symbol_relation (type,symbol_type,symbol,r_id,c_time,u_time) values ";
            foreach($views as $val){
                $preg_content = $val['title'].$val['content'];
                #$preg_symbols = $this->getPregSymbols($preg_content,$val['ind_id']);
                $preg_symbols = SymbolService::getPregSymbols($preg_content,$val['ind_id']);
                if(!empty($preg_symbols)){
                    foreach ($preg_symbols as $preg){
                        $values .= "(1,'{$preg[0]}','{$preg[1]}',{$val['id']},'{$val['p_time']}','{$cur_time}'),";
                        $count++;
                    }
                }
                if($count>=100){
                    $values = substr($values,0,-1);
                    $save_records += $this->getDBW()->CreateCommand($sql_ins.$values)->execute();
                    //echo $sql_ins.$values,"\n";
                    $count = 0;
                    $values = '';
                }
            }
            if($count>0){
                $values = substr($values,0,-1);
                $save_records += $this->getDBW()->CreateCommand($sql_ins.$values)->execute();
                //echo $sql_ins.$values,"\n";
            }
        }

        return $save_records;
    }


    /**
     * 生成关联行情的问答数据
     *
     * 写入数据表 lcs_symbol_relation
    id
    type 1观点2问答
    symbol_type 类型（stock_cn,a股fund_open,开基fund_etf,etf基金fund_close,封闭基金future_innern内盘期货 ）global_future 盘期货
    symbol
    r_id 观点或问答id
    u_time 记录更新时间
    c_time 观点发布时间 或问题回答时间
     *
     * 匹配的内容为：标题和标签
     *
     */
    private function createRelationAskData($s_time, $e_time){
        if(empty($s_time) || empty($e_time)){
            throw new Exception('createRelationAskData 开始时间或结束时间为空');
            return;
        }
        //获取问答数据  最多50条
        $sql = "SELECT aq.`id`, aq.`content` AS question, aa.`content` AS answer, aa.`content_pay` AS answer_pay, aa.`c_time` FROM lcs_ask_answer aa LEFT JOIN lcs_ask_question aq ON aa.`q_id`=aq.`id` WHERE aa.`c_time`>'{$s_time}' AND aa.`c_time`<='{$e_time}'; ";
        $asks = $this->getDBR()->CreateCommand($sql)->queryAll();
        $save_records = 0;
        //echo 'ask num:', sizeof($asks), "\n";
        if(is_array($asks) && sizeof($asks) >0 ){

            $cur_time = date('Y-m-d H:i:s');  //当前系统时间
            $count = 0; //记录需要插入的记录条数
            $values = ''; //
            $sql_ins = "insert into lcs_symbol_relation (type,symbol_type,symbol,r_id,c_time,u_time) values ";
            foreach($asks as $val){
                $preg_content = $val['question'].$val['answer'].$val['answer_pay'];
                $preg_content = strip_tags($preg_content);
                $preg_symbols = SymbolService::getPregSymbols($preg_content);

                if(!empty($preg_symbols)){
                    foreach ($preg_symbols as $preg){
                        $values .= "(2,'{$preg[0]}','{$preg[1]}',{$val['id']},'{$val['c_time']}','{$cur_time}'),";
                        $count++;
                    }
                }

                if($count>=100){
                    $values = substr($values,0,-1);
                    $save_records += $this->getDBW()->CreateCommand($sql_ins.$values)->execute();
                    //echo $sql_ins.$values,"\n";
                    $count = 0;
                    $values = '';
                }
            }
            if($count>0){
                $values = substr($values,0,-1);
                $save_records += $this->getDBW()->CreateCommand($sql_ins.$values)->execute();
                //echo $sql_ins.$values,"\n";
            }
        }
        return $save_records;
    }
}
