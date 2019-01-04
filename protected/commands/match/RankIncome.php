<?php
/**
 * 大赛排名收入
 */
class RankIncome
{
    const CRON_NO ='20180619';

    /**
     * 日排行收入
     */
    public function dayRank(){
        $hour = date("H");
        $day = date("Y-m-d");
        $active_id = array(1,2,3,4,5);
        if($hour>=16){
            foreach($active_id as $aid){
                $top_50 = self::getTopRank($aid,3,50);
                if($top_50 && count($top_50)>0){
                    ///日榜前50的用户uid
                    foreach($top_50 as $user){
                        $uid = $user['info']['uid'];
                        $parent = Match::model()->getParentByUid($uid,$user['info']['id']);
                        if(empty($parent)){
                            continue;
                        }
                        if(!empty($parent['parent_id'])){
                            $income = 188/2;
                            Match::model()->addMatchIncome($parent['match_id'],$uid,$income,1,'日排行榜('.$day.')',0,$user['rank_num']);
                            Match::model()->addMatchIncome($parent['match_id'],$parent['parent_id'],$income,3,'您的好友获得百万股神第一季日排行榜('.$day.')',$uid,$user['rank_num']);
                        }else{
                            $income = 188;
                            Match::model()->addMatchIncome($parent['match_id'],$uid,$income,1,'日排行榜('.$day.')',0,$user['rank_num']);
                        }
                    }
                }
            }
        }
    }

    /**
     * 周排行收入
     */
    public function weekRank(){
        $hour = date("H");
        $week = date("w");
        $active_id = array(1,2,3,4);
        $week_str = date("Y.m.d",strtotime("-5 day"))."-".date("Y.m.d");
        ///周五下午16点以后
        if($hour>=16 && $week==5){
            foreach($active_id as $aid){
                $top_20 = self::getTopRank($aid,2,20);
                if($top_20 && count($top_20)>0){
                    ///周榜前20的用户uid
                    foreach($top_20 as $user){
                        $uid = $user['info']['uid'];
                        $parent = Match::model()->getParentByUid($uid,$user['info']['id']);
                        if(empty($parent)){
                            continue;
                        }
                        if(!empty($parent['parent_id'])){
                            $income = 888/2;
                            Match::model()->addMatchIncome($parent['match_id'],$uid,$income,2,'周排行榜('.$week_str.')',0,$user['rank_num']);
                            Match::model()->addMatchIncome($parent['match_id'],$parent['parent_id'],$income,4,'您的好友获得百万股神第一季周排行榜('.$week_str.')',$uid,$user['rank_num']);
                        }else{
                            $income = 888;
                            Match::model()->addMatchIncome($parent['match_id'],$uid,$income,2,'周排行榜('.$week_str.')',0,$user['rank_num']);
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取榜单数据
     */
    public function getTopRank($active_id,$type,$num){
        if(defined("ENV") && ENV == "dev"){
            $url = "http://licaishi.sina.com.cn/stock_trade/api/getRankList";
        }else{
            $url = "http://stock-trade.sinalicaishi.com.cn/stock_trade/api/getRankList";
        }

		$params = array(
			'debug'=>1,
			'active_id'=>$active_id,
			'type'=>$type,			
			'page'=>1,			
			'num'=>$num,			
		);
        $header = array("Content-Type"=>"application/x-www-form-urlencoded");        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        try{
            $result = curl_exec($ch);
            $result = json_decode($result,true);
            if($result['code']==0 && isset($result['data']) && isset($result['data']['ranks'])){
                return $result['data']['ranks'];
            }else{
                Common::model()->saveLog("获取榜单数据错误","error","rank_income");
            }
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("获取榜单数据异常".$e->getMessage(),"error","rank_income");
            return false;
        }
    }
}
