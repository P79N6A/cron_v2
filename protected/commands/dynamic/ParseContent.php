<?php
/**
 * 解析微观点的文本内容
 *
 */
class ParseContent{
    const CRON_NO =  14201;
    private $_stock_plate = array();

    public function Process(){
        $end = time() + 60;
        $this->_stock_plate = Stock::model()->getAllStockPlate();
        while(true){
            Yii::app()->lcs_r->setActive(false);
            Yii::app()->lcs_r->setActive(true);

            Yii::app()->lcs_w->setActive(false);
            Yii::app()->lcs_w->setActive(true);

            Yii::app()->lcs_comment_r->setActive(false);
            Yii::app()->lcs_comment_r->setActive(true);

            Yii::app()->lcs_comment_w->setActive(false);
            Yii::app()->lcs_comment_w->setActive(true);

            Yii::app()->lcs_standby_r->setActive(false);
            Yii::app()->lcs_standby_r->setActive(true);

            $start = time();
            if($start>=$end){
                break;
            }
            $this->Parse();
            sleep(1);
        }
    }

    /**
     * 解析文本中的股票，板块
     */
    public function Parse(){
        $redis_key = MEM_PRE_KEY."dynamic_udpate";
        $id = Yii::app()->redis_w->lpop($redis_key);
        if($id){
            $dynamic_list = Dynamic::model()->getDynamicByIds(array($id));
            if(isset($dynamic_list[$id])){
                $dynamic_info = $dynamic_list[$id];

                ///板块数据
                $preg_stock_plate = $this->ParseStockPlate($dynamic_info['content']);
                if(count($preg_stock_plate)>0){
                    foreach($preg_stock_plate as $plate){
                        Stock::model()->addStockPlateRelation($plate['ei'],$plate['scode'],1,$dynamic_info['id']);
                    }
                }

                ///股票数据
                $preg_symbols = SymbolService::getPregSymbols($dynamic_info['content']);
                if(!empty($preg_symbols)){
                    $values = "";
                    $cur_time = date("Y-m-d H:i:s");
                    foreach ($preg_symbols as $preg){
                        $values .= "(6,'{$preg[0]}','{$preg[1]}',{$dynamic_info['id']},'{$dynamic_info['c_time']}','{$cur_time}'),";
                    }
                    $values = rtrim($values,',');
                    Symbol::model()->addSymbolRelation($values);
                }
                
                //解析股票信息
                SymbolService::parseSymbol($dynamic_info);
            }
        }
    }

    /**
     * 解析股票板块
     */
    public function ParseStockPlate($content){
        $res = array();
        foreach($this->_stock_plate as $key=>$val){
            ///加两个字符，使得查找结果匹配后永远大于0
            $r = mb_strpos("xx".$content,$key,0,"utf8");
            if($r && $r>0){
                $res[] = $val;
            }
        }
        return $res;
    }

    public function test($id)
    {
        $dynamic_list = Dynamic::model()->getDynamicByIds(array($id));
//        var_dump($id,$dynamic_list);die;

        //解析股票信息
        SymbolService::parseSymbol($dynamic_list[$id]);

        echo 'ok';
    }
}
