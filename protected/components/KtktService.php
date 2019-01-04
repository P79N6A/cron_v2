<?php

/*
 * 交易师 -- 股商合作接口
 * 文档地址：http://wiki.intra.sina.com.cn/pages/viewpage.action?pageId=117277264
 */

/**
 * Description of KtktService
 *
 * @author hailin3
 */
class KtktService {
    //put your code here
    private static $kt_domain = null;
    private static $partner = '1478489626';
    private static $key = '5ab4bdacb920a8c1e90f37ad2920013a';

    private static function selectDomain($tid = 0){       
        if(defined('ENV_DEV') && ENV_DEV == 1){                
            // self::$kt_domain = 'http://teacher.ktkt.com';
            self::$kt_domain = 'http://ppx.ktkt.com';
        }elseif($tid == '2209392243' || $tid == '1371755470589286'){
            // self::$partner = '1481702898';
            // self::$key = '431b81df7506fc625f52c65e353e11be';
            self::$kt_domain = 'http://px.ktkt.com';
        }else{
            self::$kt_domain = 'http://px.ktkt.com';
        }
    }
    /**
     *交易师id对应的商品规格id
     * ID=> (月=》skuid)
     * @var type 
     */
    private static $teacher = array(
        '1371765374235354'=>array( //徐小明
            '1'=>'1372193874736125',
            '6'=>'1372193892308569',
            '12'=>'1372193925314276',
        ),
        '1371765378749282'=>array( //冯矿伟
            '1'=>'1372193710695488',
            '6'=>'1372193738164293',
            '12'=>'1372193770667624'
        ),
        '1371669226385749'=>array( //薛利峰
            '1'=>'1395340707753448',
            '6'=>'1395335210057723',
            '12'=>'1395335240626619'
        ),
        '1371670240126840'=>array( //张磊
            '1'=>'1395335829219827',
            '6'=>'1395340455104931',
            '12'=>'1395340500178931'
        ),
        '1371665764182752'=>array( //李方波
            '1'=>'1395340831151896',
            '6'=>'1395340850233291',
            '12'=>'1395340871077353'
        ),
        '1371668507241183'=>array( //许相元
            '1'=>'1395341087444941',
            '6'=>'1395341115032054',
            '12'=>'1395341158614353'
        ),
        '1394202292180202'=>array( //东方红
            '1'=>'1394721959130490',
            '6'=>'1394722052167570',
            '12'=>'1394722092340935'
        ),
        '1371666782025420'=>array( //王伟强
            '1'=>'1395341469585861',
            '6'=>'1395341619763683',
            '12'=>'1395341641476390'
        ),
        '1396450749137270'=>array( //庞有明
            '1'=>'1396451001597906',
            '6'=>'1396451236507073',
            '12'=>'1396451258925287'
        ),
        '1396449535997896'=>array( //林宇哲
            '1'=>'1396451800564707',
            '6'=>'1396451832507734',
            '12'=>'1396451857740253'
        ),
		'1371655397165770'=>array( // 翟润洲
			'1'=>'1413904268526952',
            '6'=>'1413904290593763',
            '12'=>'1413904306646285'
		),
		'1371753078527602'=>array( // 韩霜	
			'1'=>'1413903790886712',
            '6'=>'1413903846744519',
            '12'=>'1413903876138304'
		),
    );
    
    private static function getSkuId($teacher_id,$month){
        if(isset(self::$teacher[$teacher_id][$month])){
            return self::$teacher[$teacher_id][$month];
        }
        return 0;        
    }   
    /**
     * 通知交易师开通权限
     * @param type $s_uid 理财师id
     * @param type $nickname 微博名
     * @param type $order_no 订单号
     * @param type $payment_id 支付宝流水号
     * @param type $pay_type 支付类型 默认 alipay wxpay
     * @param type $amount  订单金额     
     * @param type $teacher_id 交易师id
     * @param type $month 订阅几个月 1 6 12
     * @param type $phone 手机号
     */
    public static function ktNotice($s_uid,$teacher_id,$month,$nickname,$order_no,$payment_id,$amount,$bind_id,$bind_type,$pay_type='alipay',$phone,$payment_time){        
        self::selectDomain($teacher_id);        
        $sku_id = self::getSkuId($teacher_id, $month);        
        if(empty($sku_id)){
            return FALSE;
        }
        $param = array(            
            'uid'=>$s_uid,
            'nickname'=>$nickname,
            'order_id'=>'SINA'.$order_no,
            'sku_id'=>$sku_id,
            'payment_id'=>$payment_id,
            'payment_platform'=>$pay_type,
            'amount'=>$amount,
            'number'=>1,
            'time'=>  time(),            
            'partner'=>self::$partner,
            'bind_id'=>$bind_id,
            'bind_type'=>$bind_type,
            'phone'=>$phone,
            'payment_time'=>$payment_time
        );
        if($bind_type == 'phone'){
            unset($param['phone']);
        }
        $param = self::buildRequestPara($param);          
        $result = Yii::app()->curl->post(self::$kt_domain.'/api/v3/partners/order/notice', $param);        
        $res = @json_decode($result,TRUE);

        if(isset($res['code']) && $res['code'] == '200'){
            $level = 'success';
        }else{
            $level = 'fail';
        }
        $log = 'param:'.json_encode($param).'|||result:'.$result;
        Common::model()->saveLog($log,$level,'ktkt');
        return TRUE;
    }
    /**
    *免费聊天室地址
    */
    public static function freeKtFrame($teacher_id){
        self::selectDomain($teacher_id);
        $param = array(            
            'teacher_id'=>$teacher_id,
            'time'=>time(),
            'partner'=>self::$partner
        );  
        $param = self::buildRequestPara($param);
        $arg_str = self::createLinkstringUrlencode($param);
        $path = '/partner/room/public/frame?';
        return self::$kt_domain.$path.$arg_str;
    }
    /**
    * 直播流iframe
    */
    public static function logiciansFrame(){
        self::selectDomain($teacher_id);
        $param = array(                        
            'time'=>time(),
            'partner'=>self::$partner
        );  
        $param = self::buildRequestPara($param);
        $arg_str = self::createLinkstringUrlencode($param);
        $path = '/partner/logicians?';
        return self::$kt_domain.$path.$arg_str;
    }
    /**
     * 获取交易师iframe的id
     * @param type $uid        用户id
     * @param type $teacher_id 理财师微博id
     * @param type $date       图文教程回放id
     * @param type $video_type 课程类型  0 图文课程 1 视频课程 2 系统课程
     * @param type $class_id   系统课程详情
     */
    public static function ktFrame($uid,$teacher_id,$date='',$video_type=0,$class_id=0){
        self::selectDomain($teacher_id);
        $param = array(
            'uid'=>$uid,
            'teacher_id'=>$teacher_id,
            'time'=>time(),
            'partner'=>self::$partner
        );        
        switch($video_type){
            case "1": //视频课
                if(!empty($class_id)){
                    $param['class_id'] = $class_id;
                }
                break;
            case "2": //系统课
                if(!empty($class_id)){
                    $param['class_id'] = $class_id;
                }
                break;            
            default: //聊天室
                if(!empty($date)){
                    $param['date'] = $date;
                }
                break;
        }        
        $param = self::buildRequestPara($param);
        $arg_str = self::createLinkstringUrlencode($param);
        
        switch($video_type){
            case "1": //视频课                
                $path = empty($class_id) ? '/partner/room/vedio/frame?' : '/partner/history/detail/frame?';
                $url = self::$kt_domain.$path.$arg_str;
                break;
            case "2": //系统课                
                $path = empty($class_id) ? '/partner/class/index/frame?' : '/partner/class/detail/frame?';
                $url = self::$kt_domain.$path.$arg_str;
                break;
            default: //聊天室                
                $path = empty($date) ? '/v2/partner/room/vip/frame?' : '/v2/partner/room/vip/history/frame?';
                $url = self::$kt_domain.$path.$arg_str;
                break;
        }  
        return $url;
    }

        
    public static function buildRequestPara($para_temp) {
        ksort($para_temp);
        reset($para_temp);     
//        print_r($para_temp);die;
        $prestr = self::createLinkstring($para_temp);
        //生成签名结果
        $prestr = $prestr.  self::$key;                
        $mysign = md5($prestr);
        //签名结果与签名方式加入请求提交参数组中
        $para_temp['sign'] = $mysign;
        return $para_temp;
    }
    public static function createLinkstring($para) {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg.=$key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }
    public static function createLinkstringUrlencode($para) {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg.=$key . "=" . urlencode($val) . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }
    /**
     * 
     * @param type $s_uid 用户id
     * @param type $order_id 订单号
     * @param type $teacher_id 交易师老师id
     * @param type $month 订单的amount
     * @param type $payment_id 订单的pay_number
     * @param type $payment_platform 平台
     * @param type $fee 金额
     * @return boolean
     */
    public static function ktRefund($uid,$order_id,$teacher_id,$month,$payment_id,$payment_platform,$fee,$refund_type,$reason){
        self::selectDomain($teacher_id);
        $sku_id = self::getSkuId($teacher_id, $month);
        $param = array(
            'uid'=>$uid,
            'order_id'=>'SINA'.$order_id,
            'sku_id'=>$sku_id,
            'payment_id'=>$payment_id,
            'payment_platform'=>$payment_platform,
            'amount'=>$fee,
            'number'=>1,
            'time'=>  time(),
            'partner'=>self::$partner,
            'refund_cid'=>$refund_type,
            'refund_intro'=>$reason     
        );        
        $param = self::buildRequestPara($param);                     
        $result = Yii::app()->curl->post(self::$kt_domain.'/api/v3/partners/order/refund', $param);           
        $res = @json_decode($result,TRUE);
        if(isset($res['code']) && $res['code'] == '200'){
            $level = 'success';
        }else{
            $level = 'fail';
        }
        $log = 'param:'.json_encode($param).'|||result:'.$result;
        Common::model()->saveLog($log,$level,'ktkt');
        return TRUE;
    }
    
    /**
     * @param type $refund_type 退款方式 1用户退 2 运营退
     * @param type $pay_number 支付流水号
     * @param type $order_no 支付订单号
     * @param type $price 金额
     * @param type $reason 退款理由
    */
    public static function payRefund($refund_type,$pay_number,$order_no,$price,$reason){
        if(empty($pay_number) || empty($order_no) || $price <0 || empty($reason)){
    		return false;
    	}
    	$order_info = Orders::model()->getOrdersInfo($order_no,'refund_lock,pay_type','','','w');
    	if(empty($order_info) || $order_info['status'] != 2 || $order_info['refund_lock'] == 1){
    		return false;
    	}        
        $res = false;
        //TODO::新浪支付退款             
        $configs = Video::model()->getPlannerLiveConfigByIds($order_info['p_uid']);
        $teacher_id = isset($configs[$order_info['p_uid']]['relation_id']) ? $configs[$order_info['p_uid']]['relation_id'] : '0';
        if($order_info['type'] == Orders::ORDER_TYPE_KTCOURSE_SUBSCRIPTION){                
            $platfrom_dict = array(
                '10'=>'alipay',
                '12'=>'wxpay',
                '14'=>'unionpay'
            );
            $payment_platform = isset($platfrom_dict[$order_info['pay_type']]) ? $platfrom_dict[$order_info['pay_type']] : 'alipay';                                         
            $result = self::ktRefund($order_info['uid'], $order_no, $teacher_id, $order_info['amount'], $order_info['pay_number'], $payment_platform, $order_info['price'],$refund_type,$reason);
            if(!$result){
                return $result;
            }
            //插入退款申请表数据
            Refund::model()->saveRefund($order_no, $pay_number, $price, '', $reason, '', $refund_type);
            //更新订单表
            Orders::model()->updateOrdersStatus($order_no, Orders::ORDER_STATUS_REFUNDING);
            //股商订单停止服务
            LiveSubscription::model()->refundKtSub($order_info['uid'],$order_info['p_uid'],$order_info['amount']);
            $res = true;                                
        }
        return $res;
    }  
    /**
    * @param type $uid 用户uid
    * @param type $name 昵称
    */
    public static function upateNickname($uid,$name){
        self::selectDomain();        
        $param = array(
            'uid'=>$uid,
            'name'=>$name,            
            'time'=>  time(),
            'partner'=>self::$partner            
        );
        $param = self::buildRequestPara($param);             
        $result = Yii::app()->curl->post(self::$kt_domain.'/api/partners/update/nickname', $param);        
        $res = @json_decode($result,TRUE);
        if(isset($res['code']) && $res['code'] == '200'){
            $level = 'success';
        }else{
            $level = 'fail';
        }
        $log = 'param:'.json_encode($param).'|||result:'.$result;
        Common::model()->saveLog($log,$level,'ktkt');
        return TRUE;
    }    
}
