<?php
/**
* 用户更改信息后的相关处理
*/
class UserChangeNotice{
    const CRON_NO = 1503;
    public function main(){
        $redis_key = MEM_PRE_KEY . 'user_change_info_list';
        $flag = true;
        while($flag){
            $uid = Yii::app()->redis_w->lPop($redis_key);
            if($uid === false){
                $flag = false;
            }
            if(!empty($uid)){
                $this->handle($uid);
            }
        }
        
    }

    private function handle($uid){
        $userinfo = User::model()->getUserInfoByUid($uid);
        $is_kt_sub = PlannerLive::model()->checkKtSub($uid);
        if($is_kt_sub){
            $this->upateNickname($uid,$userinfo['name']);
        }
    }  

    /**
    * @param type $uid 用户uid
    * @param type $name 昵称
    */
    public function upateNickname($uid,$name){                
        $param = array(
            'uid'=>$uid,
            'name'=>$name,            
            'time'=>  time(),
            'partner'=>'1478489626'            
        );
        
        ksort($param);
        reset($param);             
        $arg = "";
        while (list ($key, $val) = each($param)) {
            $arg.=$key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        //生成签名结果
        $prestr = $arg.'5ab4bdacb920a8c1e90f37ad2920013a';        
        $mysign = md5($prestr);
        //签名结果与签名方式加入请求提交参数组中
        $param['sign'] = $mysign;
        // print_r($param);
        $res = Yii::app()->curl->post('http://px.ktkt.com/api/partners/update/nickname', $param);
        // echo $res;             
        return TRUE;
    }    


}
?>