<?php
/**
 * 微博更新接口token
 *
 * @author lixiang23
 */

class RefreshToken
{
	const CRON_NO = 11001; //任务代码
	private $_ip_white_list = 'ip_token_white_list'; ///所有的ip白名单,涵盖动态池的所有ip
    private $_ip_token = 'ip_token_'; ///每个ip对应token值,lcs_ip_token_10_235,表示10_235表示10.235下的所有地址(10.235.0.0-10.235.255.255)
	private $_base_url = 'http://i.api.weibo.com/appverify/access_token.json';
	//仓石的key
    private $_base_appkey = '3159738548';

    public function process()
    {
        try{
            $ip_list = Yii::app()->redis_r->get(MEM_PRE_KEY.$this->_ip_white_list);
            if(empty($ip_list)){
                $ip_list = array("10.13.0.0","10.39.0.0","10.54.0.0","10.71.0.0","10.79.0.0","10.73.0.0","10.77.0.0","10.55.0.0","10.69.0.0");
                Yii::app()->redis_w->set(MEM_PRE_KEY.$this->_ip_white_list,json_encode($ip_list));
            }else{
                $ip_list = json_decode($ip_list,true);
                if(count($ip_list)<=5){
                    $ip_list = array("10.13.0.0","10.39.0.0","10.54.0.0","10.71.0.0","10.79.0.0","10.73.0.0","10.77.0.0","10.55.0.0","10.69.0.0");
                }
            }

            $ips = array(); 
            var_dump($ip_list);
            foreach($ip_list as $single_ip){

                $single_ip_array = explode('.',$single_ip);
                if(count($single_ip_array)!=4){
                    continue;
                }

                $ips[] = $single_ip_array[0].".".$single_ip_array[1];
                $ips = array_unique($ips);
                if(count($ips)==5){
                    $token = $this->getWeiboToken($ips);
                    if($token){
                        $this->saveWeiboToken($ips,$token);
                    }else{
                        Common::model()->saveLog("微博更新token失败:".json_encode($ips),"error","weibo_token");
                    }
                    ///每10秒执行一次，获取token接口一分钟不能调用太多次。
                    sleep(2);
                    $ips = array();
                }
            }

            if(count($ips)>0){
                $token = $this->getWeiboToken($ips);
                if($token){
                    $this->saveWeiboToken($ips,$token);
                }else{
                    Common::model()->saveLog("微博更新token失败:".json_encode($ips),"error","weibo_token");
                }
            }
        } catch (Exception $e) {
            Common::model()->saveLog("微博更新token异常出错:".json_encode($e->getMessage()),"error","weibo_token");
            throw LcsException::errorHandlerOfException($e);
        }
    }

    public function getWeiboToken($ips){

        $token = null;
        $url = $this->_base_url . "";
        $params = array();

        $params['source'] = $this->_base_appkey;
        $params['ips'] = implode(',',$ips);
        $res = Yii::app()->curl->get($this->_base_url,$params);
        $result = json_decode($res,true);
        if(!empty($result) && isset($result['token'])){
            $token = $result['token'];
        }else{
            throw LcsException::errorHandlerOfException("微博access_token更新失败");
            var_dump($result);
        }
        var_dump($token);
        return $token ? $token : null;
    }

    public function saveWeiboToken($ips,$token){
        foreach($ips as $single_ip){
            $ip_array = explode('.',$single_ip);
            $ip_str = $ip_array[0]."_".$ip_array[1];
            var_dump($ip_str);
            if($ip_str == "10_13"){
                try {
                    $url = "http://1.119.145.66/test/devWeiboToken?token=".$token;
                    $data = Yii::app()->curl->setTimeOut(3)->get($url);
                    throw new Exception("同步成功status:".$data, 1);
                } catch (Exception $e) {
                    Common::model()->saveLog("微博同步token到dev:".$token.$e->getMessage(),"info","weibo_token_dev");
                }
            }
            Yii::app()->redis_w->setex(MEM_PRE_KEY.$this->_ip_token.$ip_str,162000,$token);
            // Yii::app()->redis_test_w->setex(MEM_PRE_KEY.$this->_ip_token.$ip_str,162000,$token);
        }
    }
}
