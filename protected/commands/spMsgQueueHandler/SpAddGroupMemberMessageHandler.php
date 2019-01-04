<?php
/**
 * 理财师服务平台添加用户群组
 */

class SpAddGroupMemberMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new SpCommonHandler();
    }

    /**
     * 理财师观点包 通知用户评价
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('uid','g_id','g_type','g_relation_id','is_paid'));

            if (empty($msg['g_id'])) {
                // 1. 根据uid查找appid
                $partner_u_map = User::model()->getUserSource($msg['uid']);
                if (!empty($partner_u_map[$msg['uid']])) {
                    $partner_u_info = $partner_u_map[$msg['uid']];
                } else {
                    throw new Exception("错误的用户信息：未找到用户");
                }

                // 2. 根据appid查找partner_id
                $partner_info = Partner::model()->getPartnerByAppKey($partner_u_info['channel_id']);
                if (!empty($partner_info[$partner_u_info['channel_id']])) {
                    $partner_info = $partner_info[$partner_u_info['channel_id']];
                } else {
                    throw new Exception("错误的用户信息：未找到partner");
                }

                // 3. 根据 partner_id, g_type, g_relation_id 查找g_id，未找到则创建
                $group_info = Partner::model()->getPartnerGroupInfo(
                    [
                        "partner_id"  => $partner_info['id'], 
                        "relation_id" => $msg['g_relation_id'],
                        "type"        => $msg['g_type']
                    ]
                );
                if (!empty($group_info)) { // 创建新群组
                    $g_id = $group_info['id'];
                } else {
                    $query_url = "http://sp.zhihao6.sina.com.cn/inner-api/create-group";
                    $query_opt = [
                        "partner_id"  => $partner_info['id'], 
                        "relation_id" => $msg['g_relation_id'],
                        "type"        => $msg['g_type']
                    ];
                    $request_url = $query_url.'?'.http_build_query($query_opt);
print_r($request_url."\n");
                    $res = Yii::app()->curl->get($request_url);
                    $res = json_decode($res, true);
print_r($res);
print_r("\n");
                    if (!empty($res['data']['id'])) {
                        $g_id = $res['data']['id'];
                    } else {
                        throw new Exception("创建群组失败：" . json_encode($res));
                    }
                }
            } else {
                $g_id = $msg['g_id'];
            }

            // curl添加群组
            $query_url = "http://sp.zhihao6.sina.com.cn/inner-api/add-group-member";
            $query_opt = [
                "g_id" => $g_id,
                "uid" => $msg['uid'],
                "type" =>$type = empty($msg['is_paid']) ? 40 : 30
            ];
            $request_url = $query_url.'?'.http_build_query($query_opt);
print_r($request_url."\n");
            $res = Yii::app()->curl->get($request_url);
            $res = json_decode($res, true);
print_r($res);
print_r("\n");
            
            // 消息通知
            // ...

        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }
        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

}
