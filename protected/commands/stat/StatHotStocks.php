<?php

class StatHotStocks{
    
    const CRON_NO = 8003; //任务代码
    /**
     * 获取3天内问答和观点数最多的热门股票top100
     */
    public function getHotStocks() {
        $insert_counter = 0;
        $update_counter = 0;
        $start_time = CommonUtils::getMillisecond();
        try {
            $stocks = Symbol::model()->getAskTagsList('stock_cn');            
            $begin_time = date("Y-m-d H:i:s", time() - 86400 * 3);
            //问答相关数 和 最后时间
            $ask_tag = Ask::model()->getAskTagSdata($begin_time);
            //观点相关数 和最后时间
            $view_tag = View::model()->getViewTagSdata($begin_time);
            $records = 0;
            $count=0;
            if (!empty($stocks)) {
                $datas=array();
                foreach ($stocks as $s) {                    
                    $ext_data = array(
                        'type' => 'stock_cn',
                        'name' => $s['symbol'],
                        'ask_count' => 0,
                        'view_count' => 0,
                        'last_sub_time' => '0000-00-00 00:00:00',
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                    if (isset($ask_tag[$s['id']])) {
                        $ext_data['ask_count'] = $ask_tag[$s['id']]['count'];
                        $ext_data['last_sub_time'] = $ask_tag[$s['id']]['c_time'];
                    }
                    if (isset($view_tag[$s['id']])) {
                        $ext_data['view_count'] = $view_tag[$s['id']]['count'];
                        $ext_data['last_sub_time'] = (strtotime($view_tag[$s['id']]['c_time'])> strtotime($ext_data['last_sub_time']))?$view_tag[$s['id']]['c_time']:$ext_data['last_sub_time'];
                    }
                    $datas[]=$ext_data;
                    $count++;
                    /*try {
                        $insert_counter += Yii::app()->lcs_w->createCommand()->insert("lcs_user_optional_ext", $ext_data);
                    } catch (Exception $e) {
                        $update_counter += Yii::app()->lcs_w->createCommand()->update("lcs_user_optional_ext", $ext_data, "type='stock_cn' and name='" . $s['symbol'] . "'");
                    }*/
                }

                $sql_ins = "INSERT INTO lcs_user_optional_ext (`type`,`name`,`ask_count`,`view_count`,`last_sub_time`,`c_time`,`u_time`) VALUES ";
                $sql_upd = " ON DUPLICATE KEY UPDATE `ask_count`=VALUES(ask_count),`view_count`=VALUES(view_count),`last_sub_time`=VALUES(last_sub_time),`u_time`=VALUES(u_time);";
                $sql_data = "";
                $data_arr = array_chunk($datas,100);
                foreach($data_arr as $item){
                    foreach($item as $stock){
                        $sql_data .="('stock_cn','".$stock['name']."',".intval($stock['ask_count']).",".intval($stock['view_count']).",'".$stock['last_sub_time']."','".$stock['c_time']."','".$stock['u_time']."'),";
                    }
                    if(!empty($sql_data)){
                        $sql_data = substr($sql_data, 0, -1);
                        $records += Yii::app()->lcs_w->createCommand($sql_ins.$sql_data.$sql_upd)->execute();
                        $sql_data='';
                    }
                }

            }
            //热门股票
            $hot_stocks = Yii::app()->lcs_standby_r->createCommand("select name,ask_count+view_count as r_count from lcs_user_optional_ext order by r_count desc limit 100")->queryColumn();            
            Yii::app()->redis_w->set("lcs_hot_stocks_top_100", json_encode($hot_stocks));            
            // 热门股票新    
            $hot_stocks = Yii::app()->lcs_r->createCommand("select name,ask_count+view_count as r_count from lcs_user_optional_ext order by r_count desc limit 100")->queryAll();
            Yii::app()->redis_w->set("lcs_hot_stocks_top_100_new", json_encode($hot_stocks));
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'time:'.(CommonUtils::getMillisecond()-$start_time).'  records:'.  $records.'   stocks:'.$count);
            //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'time:'.(CommonUtils::getMillisecond()-$start_time).'  insert:'.$insert_counter.'   update records:'.$update_counter);
        } catch (Exception $ex) {
            throw LcsException::errorHandlerOfException($ex);
        }
    }
}
