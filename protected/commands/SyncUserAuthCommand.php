<?php
/**
 * 异步刷新bid信息 1998
 * User: lining
 * Date: 2018/2/6
 */

class SyncUserAuthCommand extends LcsConsoleCommand {

    /**
     * 1998 同步信息
     *
     */
    public function actionSyncAuth(){
        try{
            $start = time();
            $end = time()+60;
            while ($start<$end) {
                // 读取队列
                $sync_user_key = 'lcs_syncuserauth';
                $val = Yii::app()->redis_w->lpop($sync_user_key);
                if(!$val){
                    echo "没有要同步的数据\n";
                    sleep(2);
                }else{
                    $url = "http://api.baidao.com/dataplatform/api/2/qq/dispatch.json?bid=$val";

                    $redisKey = "lcs_getuserauth_".$val;

                    $result = Yii::app()->curl->get($url);
                    
                    $result = json_decode($result,true);
                    
                    if($result['code'] == 1 && !empty($result['qq'])){
                        
                        $res = Yii::app()->redis_w->set($redisKey,$result['qq'],3600);
                        if($res){
                            echo "更新成功";
                        }
                    }
                    // break;
                }
                $start = time();
            }
        }catch(Exception $e){
            var_dump($e->getMessage());
            //Cron::model()->saveCronLog(GetNewFromCaixun::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    public function actionSyncMarket(){
        $res = array('hk','us');
        foreach ($res as $key => $value) {
            $this->requestUrl($value);
        }
    }

    public function requestUrl($market){
        $redisKey = "lcs_market_".$market;

        $params = array(
            'hk' => array('hsi','hscei','hscci'),
            'us' => array('.DJI','.ixic','.inx'),
        );
        $requestUrl = "http://stock.finance.sina.com.cn/iphone/api/openapi.php/Index_Service.index";

        $data = array();
        //参数构建
        foreach ($params[$market] as $key => $value) {
            $params = array(
                "app_key" => "4135432745",
                "format" => "json",
                "index" => $value,
                "market" => $market
            );

            $data[$value] = Yii::app()->curl->buildUrl($requestUrl,$params);     
        }
        //参数请求
        $info = $this->curl_multi($data);

        $responseData = $this->valueFormat($info,$market);

        $info["tag"] = date("Y-m-d H:i:s",time());
        $redis_info = json_encode($info);
        Yii::app()->redis_w->setex($redisKey,1200,$redis_info);
        
        echo $redisKey."update success";
    }
    /**
	 * 格式化返回数据
	 * 
	 */
	public function valueFormat($info,$market){
		foreach ($info as $key => $value) {
            $datas = json_decode($value,true);

            if($datas['result']['status']['code'] != 0){
                $this->apiResult->setError(RespCode::PARAM_ERR,"接口异常");
                echo $this->apiResult;
                return;
            }
            $responseTempData = $datas['result']['data']['data'];

            if($market == "us"){
                $key = substr($key, 1);
            }
            if($key == "tag" || $key == "ag"){
            	break;
            }
            
            $responseData[$key] = array(
                "rise" => $responseTempData['rise'],
                "fall" => $responseTempData['fall'],
                "equal" => $responseTempData['equal'],
            );
		}
		return $responseData;
	}
    /**
     * curl_multi
     * @param array $data
     * @return type
     */
    protected function curl_multi(array $data) {
        if (empty($data)) {
            return [];
        }

        $ch = [];
        $mh = curl_multi_init();
        foreach ($data as $key => $v) {
            if (Yii::app()->request->getParam('test', 0) == 1) {
                echo $v['url'] . "\n\t" . $v['post_str'] . "\r\n";
            }
            $ch[$key] = curl_init();
            curl_setopt($ch[$key], CURLOPT_URL, $v);
            curl_setopt($ch[$key], CURLOPT_HEADER, 0);
            curl_setopt($ch[$key], CURLOPT_TIMEOUT, 10);
            curl_setopt($ch[$key], CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, 1);
            if (isset($v['header_arr'])) {
                curl_setopt($ch[$key], CURLOPT_HTTPHEADER, $v['header_arr']);
            }

            curl_multi_add_handle($mh, $ch[$key]);
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) == -1) {
                usleep(1);
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
                $info = curl_multi_info_read($mh);
                if (false !== $info) {
                    //print_r($info);
                }
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        $cons = $infos = [];
        foreach ($data as $key => $v) {
            $infos[$key] = $info = curl_getinfo($ch[$key]);
            $cons[$key] = curl_multi_getcontent($ch[$key]);
            curl_multi_remove_handle($mh, $ch[$key]);
        }
        curl_multi_close($mh);

        if (Yii::app()->request->getParam('test', 0) == 2) {
            print_r($infos);
        }

        return $cons;
    }    
}
