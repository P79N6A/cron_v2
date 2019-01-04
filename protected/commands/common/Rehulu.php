<?php
/**
 * 热葫芦回调
 * User: lixiang29
 * Date: 2017/08/30
 */

class Rehulu{
	const CRON_NO = 9904; //任务代码
	const BAIDU_AKYE = "MjQ1MDA4Mzg=";
    const BAIDU_AKYE2 = "MjQ1MDA4Mjk=";

    public function __construct(){

    }

    /**
     * 回调热葫芦
     *
     */
    public function callBack(){
        try{
            $start = time();
            $end = time()+60;
            while($start<$end){
                ///获取所有未执行回调的记录
                $data = Common::model()->getUnCallBack();
                if(!empty($data)){
                    foreach($data as $item){
                        if(isset($item['callback']) && $item['callback']!=''){
                            $header = substr($item['callback'],0,4);
                            if($header == "http"){
                                try{
                                    $akey = '';
                                    //百度sign处理
                                    if($item['sfrom'] == 4){
                                        echo "百度原生1\r\n";
                                        //url+akey进行md5加密
                                        $item['callback'] = str_replace("{{ATYPE}}","activate",$item['callback']);
                                        $item['callback'] = str_replace("{{AVALUE}}","0",$item['callback']);
                                        echo $item['callback']."\r\n";
										$signs = $item['callback'].self::BAIDU_AKYE;
                                        $sign = md5($signs);
										echo $sign."\r\n";
                                        $akey = self::BAIDU_AKYE;
										echo $akey."\r\n";
                                        $item['callback'] = $item['callback'].'&sign='.$sign;
                                    }
                                    //百度sign处理
                                    if($item['sfrom'] == 9){
                                        echo '百度sign处理\r\n';
                                        //url+akey进行md5加密
                                        $item['callback'] = str_replace("{{ATYPE}}","activate",$item['callback']);
                                        $item['callback'] = str_replace("{{AVALUE}}","0",$item['callback']);
                                        $signs = $item['callback'].self::BAIDU_AKYE2;
                                        $sign = md5($signs);
                                        $akey = self::BAIDU_AKYE2;
                                        $item['callback'] = $item['callback'].'&sign='.$sign;
                                        echo "\r\n".$item['callback']."\r\n";
                                    }
									echo "最后的调用\r\n".$item['callback'];
                                    $res = Yii::app()->curl->setTimeOut(10)->get($item['callback']);
                                    //记录回调
                                    $response = array(
                                        'data'=>$item,
                                        'res'=>$res,
                                        'akey'=>$akey
                                    );
                                    Common::model()->saveLog(json_encode($response),"info","推广回调");
                                    Common::model()->setRehuluCallBack($item['id']);
                                }catch(Exception $e){
                                    var_dump($e->getMessage());
                                }
                            }
                        }
                    }
                    sleep(1);
                }
                $start = time();
            }
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }
    
}
