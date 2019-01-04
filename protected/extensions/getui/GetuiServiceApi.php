<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/3/3
 * Time: 16:43
 */

class GetuiServiceApi {

    const CHANNEL_ANDROID ='4';
    const CHANNEL_IOS ='5';

    #信达个推帐号
    const CHANNEL_ANDROID_XINDA ='7';
    const CHANNEL_IOS_XINDA ='8';

    #财道个推帐号
    const CHANNEL_ANDROID_CAIDAO ='9';
    const CHANNEL_IOS_CAIDAO ='10';

    #财道投教个推帐号
    const CHANNEL_ANDROID_CAIDAO_TJ ='11';
    const CHANNEL_IOS_CAIDAO_TJ ='12';

    const U_TYPE_USER='1';
    const U_TYPE_PLANNER='2';


    //http的域名
    //https的域名  https://api.getui.com/apiex.htm
    private $host = 'http://sdk.open.api.igexin.com/apiex.htm';
    private $appId = '';
    private $appKey = '';
    private $masterSecret = '';
    private $offlineExpireTime = 86400000; //1天 离线时间单位为毫秒，例，两个小时离线为3600*1000*2
    private $isOffLine = true;

    //返回结果定义
    private $resp_data = array('result'=>'-1','msg'=>'','data'=>null);




    public function __construct(){
        require_once(dirname(__FILE__) . '/' . 'IGt.Push.php');
        require_once(dirname(__FILE__) . '/' . 'igetui/IGt.AppMessage.php');
        require_once(dirname(__FILE__) . '/' . 'igetui/IGt.APNPayload.php');
        require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.BaseTemplate.php');
        require_once(dirname(__FILE__) . '/' . 'IGt.Batch.php');
        require_once(dirname(__FILE__) . '/' . 'igetui/utils/AppConditions.php');
        require_once(dirname(__FILE__) . '/' . 'igetui/template/notify/IGt.Notify.php');
    }

    private function getAppInfo($channel=self::CHANNEL_ANDROID,$u_type=self::U_TYPE_USER){
        $appInfo = array();

        if($channel==self::CHANNEL_ANDROID){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='bH9zPyRTAx7K40yDSSZVX6';
                $appInfo['appKey']='foOcPFUIlU8n1OWdZszws9';
                $appInfo['masterSecret']='ltI4qIprjw91Zzz9NXUNBA';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='m49sMNG4i06j9BRssYKWz5';
                $appInfo['appKey']='6caASqSdTM6WYKSOCPF2H4';
                $appInfo['masterSecret']='hWHfG9SvfF9brvFZJ33d26';
            }
        }else if($channel==self::CHANNEL_IOS){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='uZRpchAjsm5HQcojwuvgC7';
                $appInfo['appKey']='rooYDzxcAR9RfgiGR2aPL9';
                $appInfo['masterSecret']='K9lOlXw60y8Km9MRKt2RS';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='OuRvyZJQ078GkP5X4RalI4';
                $appInfo['appKey']='eMNGwcNcp69cpDZKERoHp';
                $appInfo['masterSecret']='SH42UUSZGR8sYK3k6fYUH9';
            }
        }else if($channel==self::CHANNEL_ANDROID_XINDA){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='swcSLRd7OeAa5hJgE4BQw8';
                $appInfo['appKey']='oKAwsTOOji6EnjIB80Uwl7';
                $appInfo['masterSecret']='6KOc29Ztdu6oOmjks63dN6';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='WsVU9BszMkAWiNdMus3ur8';
                $appInfo['appKey']='Z8ia1084hcAGHKOWYTORX';
                $appInfo['masterSecret']='LtFcYi9fUr5cYRoWP4ges6';
            }
        }else if($channel==self::CHANNEL_IOS_XINDA){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='YbtGIfxZQUA4S5cVhaKRD5';
                $appInfo['appKey']='dC40NxouHT7o8aZkUdkd06';
                $appInfo['masterSecret']='iuv3FsS2Cx6QKvFlBl8DP6';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='oi5PQgGXoh9fwOUW0awf83';
                $appInfo['appKey']='4yJlcclZi48uBz392C7Jf3';
                $appInfo['masterSecret']='Z1TotgKv2X7EWhqQBp68h2';
            }
        }else if($channel==self::CHANNEL_ANDROID_CAIDAO){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='LkcblxKKf58XCl7XWFxI25';
                $appInfo['appKey']='HW1sJvRzdp9p6SPhVx4HM1';
                $appInfo['masterSecret']='6LfG0j2yX18SAEl95FHWk3';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='m49sMNG4i06j9BRssYKWz5';
                $appInfo['appKey']='6caASqSdTM6WYKSOCPF2H4';
                $appInfo['masterSecret']='hWHfG9SvfF9brvFZJ33d26';
            }
        }else if($channel==self::CHANNEL_IOS_CAIDAO){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='LkcblxKKf58XCl7XWFxI25';
                $appInfo['appKey']='HW1sJvRzdp9p6SPhVx4HM1';
                $appInfo['masterSecret']='6LfG0j2yX18SAEl95FHWk3';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='OuRvyZJQ078GkP5X4RalI4';
                $appInfo['appKey']='eMNGwcNcp69cpDZKERoHp';
                $appInfo['masterSecret']='SH42UUSZGR8sYK3k6fYUH9';
            }
        }else if($channel==self::CHANNEL_ANDROID_CAIDAO_TJ){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='slF34gryEKAP4elikdRJR8';
                $appInfo['appKey']='Dk73aJHynM9NgSvGueoYl5';
                $appInfo['masterSecret']='RFjTRdsvXY7CbCNwWeMNA3';
            }
        }else if($channel==self::CHANNEL_IOS_CAIDAO_TJ){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='bA8cYYdq6s7TnT6bM25Og';
                $appInfo['appKey']='evkj35qITT92ltqIPKoFh';
                $appInfo['masterSecret']='8xxw5jWWql533BkTD77l34';
            }
        }

        //测试环境配置
        if($channel==self::CHANNEL_ANDROID && defined('ENV')&&ENV=='dev'){
             if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='IlGtZl4REK81J5i5VIMbv3';
                $appInfo['appKey']='2NCITGEauc6PsRecs8N9u4';
                $appInfo['masterSecret']='6WHy1cr2Or6o1OPXrSSiB';
                /*   $appInfo['appId']='LkcblxKKf58XCl7XWFxI25';
                $appInfo['appKey']='HW1sJvRzdp9p6SPhVx4HM1';
                $appInfo['masterSecret']='6LfG0j2yX18SAEl95FHWk3'; */
             }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='Zi6MsCQRmpADQIs4hCy209';
                $appInfo['appKey']='H8HXIpK79a6xp8oJ2sHAp8';
                $appInfo['masterSecret']='WoGK1I0cn39biDH4RG1Wb8';
             }
        }


        if($channel==self::CHANNEL_IOS && defined('ENV')&&ENV=='dev'){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='4JQZ2reMjt5BvDNzR2Par3';
                $appInfo['appKey']='Wo8eQtkQgVAqOI4YAGFMF7';
                $appInfo['masterSecret']='QJv0l0LdSP6aWWRRbdE8Y';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='qL0u35coAiApKRKepQSQ74';
                $appInfo['appKey']='nrJcbkfIF76T6cEYeR7ExA';
                $appInfo['masterSecret']='Pa9bfgGjDe9e8fyeUvfWL9';
            }
        }

        if($channel==self::CHANNEL_IOS_XINDA && defined('ENV')&&ENV=='dev'){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='IP0pUmOHsO6hzJIClQIr27';
                $appInfo['appKey']='suEaSZaOPh8btPs0rm9yd3';
                $appInfo['masterSecret']='Gs2QIitIKu9HzzTUVMDWb3';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='7IzpxlEy7dAQc1AVUYLrC4';
                $appInfo['appKey']='FdCFq3ulZk5gKQdyGY0y46';
                $appInfo['masterSecret']='wce2jKu7in51SeSQT6Ohh9';
            }
        }

        if($channel==self::CHANNEL_ANDROID_XINDA && defined('ENV')&&ENV=='dev'){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='QVnSSZHokZ80GmgnYAqCm5';
                $appInfo['appKey']='sOFCv4rnha7r5Ch65sd943';
                $appInfo['masterSecret']='uUVjl5rpuF6QPqgn9NnJB2';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='6T9sfg6vH07uZCmu7Kipf4';
                $appInfo['appKey']='hxqXbtvQXn5kHFvPFxzAZ9';
                $appInfo['masterSecret']='1nxAqXSi4YAb6mdsTlhx96';
            }
        }

        if($channel==self::CHANNEL_IOS_CAIDAO && defined('ENV')&&ENV=='dev'){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='IlGtZl4REK81J5i5VIMbv3';
                $appInfo['appKey']='2NCITGEauc6PsRecs8N9u4';
                $appInfo['masterSecret']='6WHy1cr2Or6o1OPXrSSiB';
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='qL0u35coAiApKRKepQSQ74';
                $appInfo['appKey']='nrJcbkfIF76T6cEYeR7ExA';
                $appInfo['masterSecret']='Pa9bfgGjDe9e8fyeUvfWL9';
            }
        }

        if($channel==self::CHANNEL_ANDROID_CAIDAO && defined('ENV')&&ENV=='dev'){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='IlGtZl4REK81J5i5VIMbv3';
                $appInfo['appKey']='2NCITGEauc6PsRecs8N9u4';
                $appInfo['masterSecret']='6WHy1cr2Or6o1OPXrSSiB';
             /*   $appInfo['appId']='LkcblxKKf58XCl7XWFxI25';
                $appInfo['appKey']='HW1sJvRzdp9p6SPhVx4HM1';
                $appInfo['masterSecret']='6LfG0j2yX18SAEl95FHWk3'; */
            }else if($u_type==self::U_TYPE_PLANNER){
                $appInfo['appId']='Zi6MsCQRmpADQIs4hCy209';
                $appInfo['appKey']='H8HXIpK79a6xp8oJ2sHAp8';
                $appInfo['masterSecret']='WoGK1I0cn39biDH4RG1Wb8';
            }
        }

        if($channel==self::CHANNEL_IOS_CAIDAO_TJ && defined('ENV')&&ENV=='dev'){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='QUzewHQZbc5KWtSAf5xmO';
                $appInfo['appKey']='DUgpSAFe8rAB3UuPzkqhT8';
                $appInfo['masterSecret']='5UAskDMCXm7xutme2thRn4';
            }
        }

        if($channel==self::CHANNEL_ANDROID_CAIDAO_TJ && defined('ENV')&&ENV=='dev'){
            if($u_type==self::U_TYPE_USER){
                $appInfo['appId']='VkhPCL1nY5AFfJRjYXhsU9';
                $appInfo['appKey']='UAUhpAjN868FTvD2nBMSk8';
                $appInfo['masterSecret']='wwXMaAVHlS72cyByLLSuWA';
            }
        }
        
        if(empty($appInfo)){
            throw new Exception('appInfo is error');
        }
        return $appInfo;
    }



    /**
     * 单推接口案例
     * @param $cid
     * @param $template
     * @param string $channel
     * @param string $u_type
     * @return array
     * @throws Exception
     */
    function pushMessageToSingle($cid,$template,$channel='',$u_type=''){
        $appInfo = $this->getAppInfo($channel,$u_type);
        $igt = new IGeTui($this->host,$appInfo['appKey'],$appInfo['masterSecret'],false);

        //个推信息体
        $message = new IGtSingleMessage();
        $message->set_isOffline($this->isOffLine);//是否离线
        $message->set_offlineExpireTime($this->offlineExpireTime);//离线时间
        $message->set_data($template);//设置推送消息类型
        //$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //接收方
        $target = new IGtTarget();
        $target->set_appId($appInfo['appId']);
        $target->set_clientId($cid);
        //$target->set_alias(Alias);
        $rep = null;
        try {
            $rep = $igt->pushMessageToSingle($message, $target);
        }catch(RequestException $e){
            $requestId =$e->getRequestId();
            $rep = $igt->pushMessageToSingle($message, $target,$requestId);
        }

        $this->resp_data['data']=json_encode($rep);
        if(isset($rep['result']) && $rep['result']=='ok'){
            $this->resp_data['result']='1';
            $this->resp_data['msg']=$rep['status'];
        }else{
            $this->resp_data['msg']=isset($rep['result'])?$rep['result']:'';
        }

        return $this->resp_data;

    }


    /**
     * 批量发送过个个人消息  适合每个人的消息内容不同
     * @param $batchs   key=>cid 用户ID  value=>template 模板消息
     * @param string $channel
     * @param string $u_type
     * @return array
     * @throws Exception
     */
    function pushMessageToSingleBatch($batchs,$channel='',$u_type=''){
        $appInfo = $this->getAppInfo($channel,$u_type);
        putenv("gexin_pushSingleBatch_needAsync=false");

        $igt = new IGeTui($this->host, $appInfo['appKey'],$appInfo['masterSecret'],false);
        $batch = new IGtBatch($appInfo['appKey'], $igt);
        $batch->setApiUrl($this->host);


        $batch_count=0;
        if(!empty($batchs) && count($batchs)>0){
            foreach($batchs as $cid=>$template){
                if(empty($cid) || empty($template)){
                    continue;
                }
                //个推信息体
                $message = new IGtSingleMessage();
                $message->set_isOffline($this->isOffLine);//是否离线
                $message->set_offlineExpireTime($this->offlineExpireTime);//离线时间
                $message->set_data($template);//设置推送消息类型
                //$message->set_PushNetWorkType(1);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送

                $target = new IGtTarget();
                $target->set_appId($appInfo['appId']);
                $target->set_clientId($cid);
                $batch->add($message, $target);
                $batch_count++;
            }
        }
        if($batch_count<=0){
            $this->resp_data['msg']='cid is null';
            return $this->resp_data;
        }

        try {
            $rep = $batch->submit();
        }catch(Exception $e){
            $rep=$batch->retry();
        }
        $this->resp_data['data']=json_encode($rep);
        if(isset($rep['result']) && $rep['result']=='ok'){
            $this->resp_data['result']='1';
            $this->resp_data['msg']='send '.$batch_count.' ok';
        }else{
            $this->resp_data['msg']=isset($rep['result'])?$rep['result']:'';
        }

        return $this->resp_data;
    }


    /**
     * 同一个模板消息推送给多个用户
     * @param $cids   客户ID  数字
     * @param $template  模板信息
     * @param string $channel
     * @param string $u_type
     * @return Array
     * @throws Exception
     */
    function pushMessageToList($cids,$template,$channel='',$u_type=''){
        $appInfo = $this->getAppInfo($channel,$u_type);
        putenv("gexin_pushList_needDetails=true");
        putenv("gexin_pushList_needAsync=true");

        $igt = new IGeTui($this->host, $appInfo['appKey'], $appInfo['masterSecret']);

        //个推信息体
        $message = new IGtListMessage();
        $message->set_isOffline($this->isOffLine);//是否离线
        $message->set_offlineExpireTime($this->offlineExpireTime);//离线时间
        $message->set_data($template);//设置推送消息类型
        //$message->set_PushNetWorkType(1);	//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //$contentId = $igt->getContentId($message);
        $contentId = $igt->getContentId($message,"toList任务别名功能");	//根据TaskId设置组名，支持下划线，中文，英文，数字

        $targetList=array();
        if(!empty($cids) && count($cids)>0){
            foreach($cids as $cid){
                if(empty($cid)){
                    continue;
                }
                $target = new IGtTarget();
                $target->set_appId($appInfo['appId']);
                $target->set_clientId($cid);
                //$target->set_alias(Alias);
                $targetList[] = $target;
            }
        }

        if(count($targetList)<=0){
            $this->resp_data['msg']='cid is null';
            return $this->resp_data;
        }


        try{
            $rep = $igt->pushMessageToList($contentId, $targetList);
        }catch (Exception $e){
            $rep = $igt->pushMessageToList($contentId, $targetList);
        }


        $this->resp_data['data']=json_encode($rep);
        if(isset($rep['result']) && $rep['result']=='ok'){
            $this->resp_data['result']='1';

            $status_res = array();
            if(isset($rep['details']) && !empty($rep['details'])){
                $err_token = array();
                foreach($rep['details']  as $k=>$status){
                    if(array_key_exists($status,$status_res)){
                        $status_res[$status]++;
                    }else{
                        $status_res[$status]=1;
                    }
                    if($status=="TokenMD5Error"){
                        $err_token[]=$k;
                    }
                }
                if(!empty($err_token)){
                    $status_res['err_tokens']=$err_token;
                }
            }

            $this->resp_data['msg']='send '.count($targetList).' ok '.json_encode($status_res);

        }else{
            $this->resp_data['msg']=isset($rep['result'])?$rep['result']:'';
        }

        return $this->resp_data;

    }



    /**
     * 获取链接类型的通知模板
     * @param $title  通知栏标题
     * @param $msg  通知栏内容
     * @param $url 打开连接地址
     * @param string $logo 通知栏logo
     * @param string $s_time 客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
     * @param string $e_time 客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
     * @param string $channel
     * @param string $u_type
     * @return IGtLinkTemplate
     * @throws Exception
     */
    public function getLinkTemplateOfAndroid($title,$msg,$url,$logo='',$s_time='', $e_time='',$channel='',$u_type=''){
        $appInfo = $this->getAppInfo($channel,$u_type);
        $template =  new IGtLinkTemplate();
        $template ->set_appId($appInfo['appId']);//应用appid
        $template ->set_appkey($appInfo['appKey']);//应用appkey
        $template ->set_title($title);//通知栏标题
        $template ->set_text($msg);//通知栏内容
        $template ->set_logo($logo);//通知栏logo
        $template ->set_isRing(true);//是否响铃
        $template ->set_isVibrate(true);//是否震动
        $template ->set_isClearable(true);//通知栏是否可清除
        $template ->set_url($url);//打开连接地址
        //设置ANDROID客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
        if(!empty($s_time)&&!empty($e_time)){
            $template->set_duration($s_time,$e_time);
        }

        return $template;
    }


    /**
     * 透传消息类型模板
     * @param $msg  内容
     * @param string $s_time 客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
     * @param string $e_time 客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
     * @param string $channel
     * @param string $u_type
     * @return IGtTransmissionTemplate
     * @throws Exception
     */
    public function getTransmissionTemplateOfAndroid($msg,$s_time='', $e_time='',$channel='',$u_type=''){
        echo "推送消息\r\n";
        var_dump($msg);
        echo "推送渠道:$channel\r\n";
        $appInfo = $this->getAppInfo($channel,$u_type);
        echo "appInfo:\r\n";
        var_dump($appInfo);

        $content = json_decode($msg,true);
        $content_client = json_decode($content['content_client'],true);
        

        $template =  new IGtTransmissionTemplate();
        $template->set_appId($appInfo['appId']);//应用appid
        $template->set_appkey($appInfo['appKey']);//应用appkey
        $template->set_transmissionType(2);//透传消息类型 收到消息是否立即启动应用，1为立即启动，2则广播等待客户端自启动
        $template->set_transmissionContent($msg);//透传内容 不支持转义字符  2048中/英字符
        //设置ANDROID客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
        if(!empty($s_time)&&!empty($e_time)){
            $template->set_duration($s_time,$e_time);
        }
        //设置ANDROID客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
        if(!empty($s_time)&&!empty($e_time)){
            $template->set_duration($s_time,$e_time);
        }
        $content_client['content'] = isset($content_client['content'])?$content_client['content']:$content['alert'];
        $title = isset($content_client['title'])?$content_client['title']:"新浪理财师";

        $notify = new IGtNotify();
        $notify -> set_payload("{}");
        $notify -> set_title($title);
        $notify -> set_content($content_client['content']);

        $notify -> set_intent("intent:#Intent;action=com.sina.licaishi.notification;launchFlags=0x4000000;package=cn.com.sina.licaishi.client;component=cn.com.sina.licaishi.client/com.sina.licaishi.ui.activity.MainTabActivity;i.type=".$content['type'].";S.content_client=".$content['content_client'].";i.child_relation_id=".$content['child_relation_id'].";i.relation_id=".$content['relation_id'].";end");
        
        $notify -> set_type(NotifyInfo_type::_intent);

        $template -> set3rdNotifyInfo($notify);

        return $template;
    }

    /**
     * @param $msg_json 数据内容 json 数据结构  必须字段 alert type
     * @param string $s_time 客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
     * @param string $e_time 客户端在此时间区间内展示消息  格式如下：2015-03-06 13:18:00
     * @param string $channel
     * @param string $u_type
     * @return IGtAPNTemplate|IGtTransmissionTemplate
     * @throws Exception
     */
    function getTransmissionTemplateOfIos($msg_json,$s_time='', $e_time='',$channel='',$u_type=''){
        echo "推送消息\r\n";
        var_dump($msg_json);        
        echo "推送渠道:$channel\r\n";
        $appInfo = $this->getAppInfo($channel,$u_type);
        echo "appInfo:\r\n";
        var_dump($appInfo);
        $template =  new IGtTransmissionTemplate();
        $template->set_appId($appInfo['appId']);//应用appid
        $template->set_appkey($appInfo['appKey']);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent(json_encode($msg_json));//透传内容
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息

        //APN简单推送
        ///$alertMsg=new SimpleAlertMsg();
        $alertMsg=new DictionaryAlertMsg();
        $alertMsg->title=isset($msg_json['title'])?$msg_json['title']:'新浪理财师';
        $alertMsg->body=isset($msg_json['alert'])?$msg_json['alert']:'点击查看理财师详情';;

        $apn = new IGtAPNPayload();
        $apn->alertMsg=$alertMsg;
        $apn->badge=1;
        //判断是否添加声音
        if(isset($msg_json['isRing'])){
            if($msg_json['isRing']){
                $apn->sound="";
                echo "声音:\r\n";
                echo $apn->sound,"\r\n";
            }else{
                $apn->sound=("com.gexin.ios.silence");
            }
        }

        $apn->add_customMsg("type",isset($msg_json['type'])?$msg_json['type']:'-99');
        unset($msg_json['alert']);
        unset($msg_json['aps']);  // add zhihao6 2017/01/18
        unset($msg_json['type']);
        // unset($msg_json['title']);
        unset($msg_json['isRing']);
        if(!empty($msg_json)){
            foreach($msg_json as $k=>$v){
                $apn->add_customMsg($k,$v);
            }
        }
        $apn->contentAvailable=1;
        $apn->category="ACTIONABLE";
        try{
            $template->set_apnInfo($apn);
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
        return $template;
    }
}
