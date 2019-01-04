<?php

class SyncUserCorpsAndTrade{
    //任务代码
    const CRON_NO_UPDATE='20180702';

    public static $active_id = 2;

    public function UserCorpsTrade(){
        $user_info = Match::model()->getCorpsError();
        echo '更新的用户如下:--------';
        var_dump($user_info);
        $match_info = Match::model()->getMatchInfo();
        if($user_info && is_array($user_info)){
            foreach ($user_info as $item){
                try{
                    if($item['uid']){
//                        $trade_id = $this->initAccountInfo(0,$item['licaishi_uid'],self::$active_id);
//                        Match::model()->updateTrade($trade_id['id'],$item['licaishi_uid'],$match_info['id']);
                        Match::model()->updateCorps($item['uid']);
                    }
                }catch (Exception $e){
                    Common::model()->saveLog("战队信息同步失败:" . $e->getMessage() , "error", "sync_user_trade_corps");
                }

            }
        }
    }


    public function upUserTeam(){
        $uids = Match::model()->getUserSignUp();
        echo '用户信息';
        var_dump($uids);
        if($uids && is_array($uids)){
            foreach ($uids as $uid){
                $corps = Match::model()->getUserCorpsExist($uid['licaishi_uid']);
                if(!$corps){
                    echo '需要同步的用户:'.$uid['licaishi_uid'];
                   Match::model()->updateCorps($uid['licaishi_uid']); 
                }
            }
        }
    }

    public function getUrl(){
        if(defined('ENV_DEV') && ENV_DEV == 1){
            return "http://licaishi.sina.com.cn/stock_trade/api/";
        }else if(defined('IN235') && IN235){
            return "http://lcs.sinalicaishi.com.cn/stock_trade/api/";
        }else{
            return "http://stock-trade.sinalicaishi.com.cn/stock_trade/api/";
            #return "http://lcs.sinalicaishi.com.cn/stock_trade/api/";
        }
    }

    public function getCurl($url,$param,$headers=array()){
        $ch = curl_init();
        $param['tokenkey'] = "lcs_stock_trade";
        $param['tokenval'] = md5($param['tokenkey']."lcs6stock7trade");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if(!empty($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        return $ch;
    }
    /**
     * 初始化账户
     */
    public function initAccountInfo($u_type=0,$uid,$active_id)
    {
        $url = $this->getUrl() . "initAccount";
        $param = array();
        $param['u_type'] = $u_type;
        $param['uid'] = $uid;
        $param['active_id'] = $active_id;
        $header = array("Content-Type" => "application/x-www-form-urlencoded");
        try {
            $ch = $this->getCurl($url, $param, $header);
            $result = curl_exec($ch);
            $result = json_decode($result, true);
            if ($result['code'] == 0) {
                return $result['data'];
            } else {
                Common::model()->saveLog("初始化模拟交易账户信息错误" . json_encode($result) . json_encode($param), "error", "stock_trade");
                return false;
            }
        } catch (Exception $e) {
            Common::model()->saveLog("初始化模拟交易异常:" . $e->getMessage() . json_encode($param), "error", "stock_trade");
            return false;
        }
    }

    public function updateUserPhone(){
        $user_info = Match::model()->getUserPhoneReal();
        echo '更新的用户如下:--------';
        var_dump($user_info);
        if($user_info && is_array($user_info)){
            foreach ($user_info as $item){
                try{
                    if($item['licaishi_uid']){
                        $phone = CommonUtils::decodePhoneNumber($item['phone_number']);
                        Match::model()->updatePhoneReal($item['licaishi_uid'],$phone);
                        echo $phone.'\r\n';
                    }
                }catch (Exception $e){
                    Common::model()->saveLog("战队信息同步失败:" . $e->getMessage() , "error", "sync_user_trade_corps");
                }

            }
        }
    }

    public function inviteRank(){
        $invites = Match::model()->inviteNum();
        if(empty($invites)){
            return;
        }
        echo "用户邀请排行榜\r\n";
        var_dump($invites);
        $redis = Yii::app()->redis_w;
        $key = "lcs_baiwangushen_reward";
        $redis->delete($key);
        foreach ($invites as $k => $v){
            echo "邀请榜榜单uid".$v['parent_id']."邀请人数".$v['num'];
            $redis->zadd($key,$v['num'],$v['parent_id']);
        }
        echo "更新邀请榜成功！\r\n";
    }

    public function updateUserInfo(){
        $user_name = Match::model()->getNameAndImage(1);
        $user_we_img = Match::model()->getNameAndImage(2);
        $user_image =  Match::model()->getNameAndImage(3);


        echo "用户存在微信信息没有头像没有昵称\r\n";
        var_dump($user_we_img);
        if($user_we_img && is_array($user_we_img)){
            foreach ($user_we_img as $item){
                try{
                    if($item['licaishi_uid']){
                        $image = $item['wechat_img'];
                        $name = $item['wechat_name'];
                        Match::model()->updateSignUser(2,$image,$item['licaishi_uid']);
                        Match::model()->updateSignUser(1,$name,$item['licaishi_uid']);
                        echo '用户同步：'.$item['licaishi_uid'].'   头像为'.$image. '   昵称为'.$name.'\r\n';
                    }
                }catch (Exception $e){
                    Common::model()->saveLog("信息同步失败:" . $e->getMessage() , "error", "sync_user_trade_corps");
                }
            }
        }

        echo "用户没有昵称\r\n";
        var_dump($user_name);
        if($user_name && is_array($user_name)){
            foreach ($user_name as $item){
                try{
                    if($item['licaishi_uid']){
                        $name = '用户'.substr($item['phone_real'],4);
                        Match::model()->updateSignUser(1,$name,$item['licaishi_uid']);
                        echo '用户同步：'.$item['licaishi_uid'].'  姓名为'.$name.'\r\n';
                    }
                }catch (Exception $e){
                    Common::model()->saveLog("信息同步失败:" . $e->getMessage() , "error", "sync_user_trade_corps");
                }

            }
        }



        echo "用户存没有头像\r\n";
        var_dump($user_image);
        if($user_image && is_array($user_image)){
            foreach ($user_image as $item){
                try{
                    if($item['licaishi_uid']){
                        $image = 'https://www.sinaimg.cn/cj/licaishi/avatar/180/01481168660.jpg';
                        Match::model()->updateSignUser(2,$image,$item['licaishi_uid']);
                        echo '用户同步：'.$item['licaishi_uid'].'  头像为'.$image.'\r\n';
                    }
                }catch (Exception $e){
                    Common::model()->saveLog("信息同步失败:" . $e->getMessage() , "error", "sync_user_trade_corps");
                }
            }
        }
    }
}