<?php
class TaoGuList
{
    //任务代码
    const CRON_NO='14030';
    public function handle(){
        $page=(int)trim(Yii::app()->request->getParam('page',1));
        $num=(int)trim(Yii::app()->request->getParam('num',200));
        try{
            $taogulist=Taogu::model()->getTaoGuList($page,$num);
            $pages=$taogulist['pages'];
            $time=date('Y-m-d H:i:s');
            $trade_day=Calendar::model()->getNearCalendar($time);
            for($i=1;$i<=$pages;$i++){
                if(!empty($taogulist['data'])){
                    foreach ($taogulist['data'] as $v){
                        $params=$this->getParams($v,$trade_day);
                        TaoGu::model()->saveTaoGuList($v['p_uid'],$params);
                    }
                }
                $j=$i+1;
                sleep(2);
                $taogulist=TaoGu::model()-> getTaoGuList($j,$num);
            }
        }catch (Exception $e){
            $e=LcsException::errorHandlerOfException($e)->toJsonString();
            Common::model()->saveLog("同步信息失败:".$e,"error","sync_taotu_info");
            throw new Exception($e,-1);
        }
    }

    //参数
    private function getParams($v,$trade_day){
        $params=array();
        $params['p_uid']=$v['p_uid'];
        $params['name']=$v['name'];
        $params['service_status']=$v['service_status'];
        $params['open_taogu_time']=$v['u_time'];
        $params['sp_status']=$v['sp_status'];
        //分组数
        $params['group_num']=TaoGu::model()->getGroupNum($v['p_uid']);
        //当前持仓数
        $params['cur_hold_num']=TaoGu::model()->getCurHoldNum($v['p_uid']);
        //调仓记录数
        $params['silo_num']=TaoGu::model()->getSiloNum($v['p_uid'],$trade_day,1);
        //最近5交易日调仓次数
        $params['trade_silo_num']=TaoGu::model()->getSiloNum($v['p_uid'],$trade_day);
        //调入股票总数
        $params['symbol_num']=TaoGu::model()->getSymbolNum($v['p_uid']);
        //平均每只股票持仓天数
        $params['average_num']=0;
        if($params['symbol_num']>0){
            $day=TaoGu::model()->getSymbolDays($v['p_uid']);
            $params['average_num']=round($day/$params['symbol_num'],2);
        }
        //历史最高调出累计收益
        $maxIncom=TaoGu::model()->getMaxIncome($v['p_uid']);
        $params['max_income']=isset($maxIncom['rate'])?$maxIncom['rate']:'0';
        //当前持仓最高累计收益
        $params['cur_max_income']=Taogu::model()->getCurIncome($v['p_uid']);
        //订阅用户数量
        $params['sub_num']=TaoGu::model()->getUserNum($v['id']);
        //最近一次操作时间
        $new_option=TaoGu::model()->getNewRecord($v['p_uid']);
        $params['op_time']=isset($new_option['c_time'])?$new_option['c_time']:'0000-00-00 00:00:00';
        return $params;
    }
}