<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/3/3
 * Time: 19:06
 */

class PlanTransactionMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 计划交易提醒
     * @param $msg type=planTransaction tran_id
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {

            $this->commonHandler->checkRequireParam($msg, array('tran_id'));
            $trans_map = Plan::model()->getPlanTransactionByIds($msg['tran_id']);
            if(empty($trans_map)){
                $trans_map = Plan::model()->getPlanTransactionByIds($msg['tran_id'],false);
            }
            $tran_info = isset($trans_map[$msg['tran_id']])?$trans_map[$msg['tran_id']]:null;
            if(empty($tran_info)){
                throw new Exception('计划的交易信息不存在'.$msg['tran_id']);
            }


            //订阅用户
            //$sub_uids = $this->getSubPlanUids($tran_info['pln_id']);
            $sub_uids = Yii::app()->lcs_r->createCommand("select uid from lcs_plan_subscription where pln_id=".intval($tran_info['pln_id'])." and status>0")->queryColumn();
            //TODO
            /* del by zwg 20160330 不在进行特殊处理
            if(!(defined('ENV')&&ENV=='dev')&&!in_array($tran_info['pln_id'],array('33458'))){
                $test_uids = array("3","46","17248545","105","1488","10923084");
                $sub_uids=array_intersect($sub_uids, $test_uids);
                //$sub_uids=in_array("3",$sub_uids)?array('3'):array();
            }*/

            //个性化 去掉关闭提醒的uid
            $sub_uids = Message::model()->filterCloseUids($sub_uids,1,4,1);
            //计划信息
            $plan_info = Yii::app()->lcs_r->createCommand("select name,number,init_value,p_uid from lcs_plan_info where pln_id=".$tran_info['pln_id']." limit 1")->queryRow();
            if(isset($plan_info['name'])){
                $plan_info['name'] .= ($plan_info['number']>9 ? $plan_info['number'] : "0".$plan_info['number'])."期";
            }
            //股票名称
            $stock_name= Yii::app()->lcs_r->createCommand("select name from lcs_ask_tags where type='stock_cn' and symbol='".$tran_info['symbol']."' limit 1")->queryScalar();
            //理财师
            $planner_info = Planner::model()->getPlannerById(array(intval($plan_info['p_uid'])));
            $planner_info = isset($planner_info[intval($plan_info['p_uid'])]) ? $planner_info[intval($plan_info['p_uid'])] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }


            if(!empty($sub_uids)){
                $msg_id=0;
                $msg_data = array(
                    'uid'=>'',
                    'u_type'=>1,
                    'type'=>4,
                    'relation_id'=>$tran_info['pln_id'],
                    'child_relation_id'=>$tran_info['id'],
                    'content'=>json_encode(array(
                        array('value'=>'您购买的计划','class'=>'','link'=>''),
                        array('value'=>"《".$plan_info['name']."》",'class'=>'','link'=>"/plan/".$tran_info['pln_id']."?type=dynamic"),
                        array('value'=>sprintf("%.2f",$tran_info['deal_price'])."元".($tran_info['type']==1 ? '调入' : '调出'),'class'=>'','link'=>''),
                        array('value'=>$stock_name."（".$tran_info['symbol']."）",'class'=>'','link'=>'/s/'.$stock_name),
                        array('value'=>$tran_info['deal_amount']."股",'class'=>'','link'=>'')
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'pln_id'=>$tran_info['pln_id'],
                        'plan_name'=>$plan_info['name'],
                        'planner_name'=>$planner_info['name'],
                        'deal_price'=>sprintf("%.2f",$tran_info['deal_price']),
                        'trans_type'=> $tran_info['type'],
                        'stock_name'=>$stock_name,
                        'symbol'=>$tran_info['symbol'],
                        'deal_amount'=>$tran_info['deal_amount'],
                        'total_price'=>sprintf("%.2f",$tran_info['deal_price']*$tran_info['deal_amount']),
                        'wgt_before'=>sprintf("%.2f",$tran_info['wgt_before']*100),
                        'wgt_after'=>sprintf("%.2f",$tran_info['wgt_after']*100),
                        'profit'=>sprintf("%.2f",$tran_info['profit']),
                        'single_ratio'=>($tran_info['hold_avg_cost']>0 && $tran_info['deal_amount']>0) ? sprintf("%.2f",(($tran_info['deal_price']*$tran_info['deal_amount']-$tran_info['transaction_cost'])/($tran_info['hold_avg_cost']*$tran_info['deal_amount'])-1)*100) : 0,
                        'profit_ratio'=>(isset($plan_info['init_value']) && $plan_info['init_value']>0) ? sprintf("%.2f",$tran_info['profit']/$plan_info['init_value']*100) : 0,
                        'reason'=>$tran_info['reason'],
                        'p_uid' => $plan_info['p_uid'],
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>"/plan/".$tran_info['pln_id']."?type=dynamic",
                    'c_time' => $tran_info['c_time'],
                    'u_time' => date("Y-m-d H:i:s")
                );
                foreach($sub_uids as $uid){
                    $msg_data['uid']=$uid;
                    $msg_id = Message::model()->saveMessage($msg_data);
                }

                //添加其他信息
                $msg_data['id']=$msg_id;
                $msg_data['content'] = json_decode($msg_data['content'],true);
                $msg_data['trans_type'] = $tran_info['type'];
                $msg_data['wgt_before'] = sprintf("%.2f",$tran_info['wgt_before']*100);
                $msg_data['wgt_after'] = sprintf("%.2f",$tran_info['wgt_after']*100);
                $msg_data['total_price'] = sprintf("%.2f",$tran_info['deal_price']*$tran_info['deal_amount']);
                $msg_data['profit'] = sprintf("%.2f",$tran_info['profit']);
                $msg_data['single_ratio'] = ($tran_info['hold_avg_cost']>0 && $tran_info['deal_amount']>0) ? sprintf("%.2f",(($tran_info['deal_price']*$tran_info['deal_amount']-$tran_info['transaction_cost'])/($tran_info['hold_avg_cost']*$tran_info['deal_amount'])-1)*100) : 0;
                $msg_data['profit_ratio'] = (isset($plan_info['init_value']) && $plan_info['init_value']>0) ? sprintf("%.2f",$tran_info['profit']/$plan_info['init_value']*100) : 0;
                $msg_data['reason'] = $tran_info['reason'];
                $msg_data['plan_name'] = $plan_info['name'];
                $msg_data['title'] = $plan_info['name'];
                $msg_data['symbol'] = $tran_info['symbol'];
                $msg_data['planner_name'] = $planner_info['name'];

                //用户超过500分组发送
                $uids_arr = array_chunk($sub_uids,500);
                foreach($uids_arr as $_uids){
                    $this->commonHandler->addToPushQueue($msg_data, $_uids, array(2,3,6,15));
                }
            }
            $log_data['uid']='';
            $log_data['relation_id'] = $msg['tran_id'];
            $log_data['ext_data'] = json_encode($sub_uids);

            //发布说说
            try{
                $curl =Yii::app()->curl;
                $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
                $url=LCS_WEB_INNER_URL.'/api/planComment';
                $params = array('pln_id'=>$tran_info['pln_id'],'content'=>'我有一笔新的'.($tran_info['type']==1 ? '调入' : '调出').'交易操作，大家可以看下。','is_planner'=>1,'discussion_type'=>7,'discussion_id'=>$msg['tran_id'],'p_uid'=>$plan_info['p_uid'],'is_anonymous'=>($tran_info['type']==1?1:0));
                //add by danxian 2017.3.23 增加校验签名
                $params['sign'] = $this->makeVerifySign($plan_info['p_uid'], $tran_info['pln_id'], $params['content']);

                $res = $curl->post($url,$params);
                if(!empty($res)){
                    $res_json = json_decode($res,true);
                    if(!isset($res_json['code']) || $res_json['code']!=0){
                        $log_data['ext_data'].=" 发送理财师交易说说 返回错误：".$res;
                    }
                }else{
                    $log_data['ext_data'].=" 发送理财师交易说说 返回数据为空";
                }
            }catch (Exception $e){
                $log_data['ext_data'].=" 发送理财师交易说说 error:".$e->getMessage();
            }

            //推送到理财师动态生成队列
            MomentsService::pushMomentQueue(Moments::DISCUSSION_TYPE_PLAN, $msg['tran_id']);
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }


    }

    /**
     * 生成签名校验系统内部调用
     * @param $p_uid
     * @param $pln_id
     * @param $content
     * @return string
     */
    private function makeVerifySign($p_uid, $pln_id, $content) {
        $salt = "plan_trans";
        $sign = md5($p_uid.$pln_id.$content.$salt);

        return $sign;
    }

    
}
