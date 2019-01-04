<?php

/**
 * 股票相关服务
 * 识别文本内股票代码
 *
 */
class SymbolService{

    //自定义数组 特使的股票代码名称
    private static $symbols_special = array(
        array('symbol'=>'sh000001','name'=>'上证','type'=>'stock_cn','code'=>'000001'),
        array('symbol'=>'sh000001','name'=>'大盘','type'=>'stock_cn','code'=>'000001'),
        array('symbol'=>'sh000001','name'=>'A股','type'=>'stock_cn','code'=>'000001'),
    );

    //基金名称对应关系
    private static $symbols_future_special = array(
        'CBOT-黄豆'=>'大豆','CBOT-黄豆粉'=>'豆粕','CBOT-黄豆油'=>'豆油','CBOT-小麦'=>'强麦','CBOT-玉米'=>'玉米',
        'CME瘦猪肉'=>'猪肉','COMEX白银'=>'白银','COMEX黄金'=>'黄金','COMEX铜'=>'沪铜','IMM-加元'=>'加元',
        'IMM-欧元'=>'欧元','IMM-日元'=>'日元','IMM-瑞郎'=>'瑞郎','IMM-英镑'=>'英镑','LME铝3个月'=>'沪铝',
        'LME镍3个月'=>'镍','LME铅3个月'=>'沪铅','LME铜3个月'=>'沪铜','LME锡3个月'=>'锡','LME锌3个月'=>'沪锌',
        'No11糖03'=>'白糖','No11糖05'=>'白糖','No11糖07'=>'白糖','NYBOT白糖'=>'白糖','NYMEX天然气'=>'天然气',
        'NYMEX原油'=>'原油','PVC'=>'PVC','白糖'=>'白糖','白银'=>'白银','标普期货'=>'标普','玻璃'=>'玻璃',
        '布伦特原油'=>'原油','菜粕'=>'菜粕','菜油'=>'菜籽油','菜籽'=>'菜籽','道指期货'=>'道指','动力煤'=>'动力煤',
        '豆二'=>'大豆','豆粕'=>'豆粕','豆一'=>'大豆','豆油'=>'豆油','国债期货'=>'国债期货','恒生指数期货'=>'恒指',
        '沪铝'=>'沪铝','沪铅'=>'沪铅','沪铜'=>'沪铜','沪锌'=>'沪锌','黄金'=>'黄金','鸡蛋'=>'鸡蛋','甲醇'=>'甲醇',
        'PTA'=>'PTA','胶合板'=>'胶合板','焦煤'=>'焦煤','焦炭'=>'焦炭','粳稻'=>'粳稻','PP'=>'PP','沥青'=>'沥青',
        '伦敦铂金'=>'铂金','伦敦金'=>'黄金','伦敦钯金'=>'钯金',	'伦敦银'=>'白银','螺纹钢'=>'螺纹钢','美元指数期货'=>'美元指数',
        '棉花'=>'棉花','纳指期货'=>'纳指',	'期指'=>'股指期货','强麦'=>'强麦','燃油'=>'燃料油','热轧卷板'=>'热卷',
        '铁矿石'=>'铁矿石','晚籼稻'=>'晚籼稻','纤维板'=>'纤维板','早籼稻'=>'早籼稻','线材'=>'线材','橡胶'=>'橡胶',
        '塑料'=>'塑料','玉米'=>'玉米','棕榈'=>'棕榈油','硅铁'=>'硅铁','锰硅'=>'锰硅'
    );

    public static function getPregSymbols($preg_content,$ind_id=''){
        if(empty($preg_content)){
            return array();
        }
		
       if($ind_id == 1){
       		$sql = "select type,symbol,name,code from lcs_symbol where type='stock_cn'";
            $symbols = Yii::app()->lcs_standby_r->CreateCommand($sql)->queryAll();
       }elseif($ind_id == 2){
       		$sql = "select type,symbol,name,code from lcs_symbol where type in('fund_close','fund_etf','fund_lof','fund_open')";
            $symbols = Yii::app()->lcs_standby_r->CreateCommand($sql)->queryAll();
       }elseif($ind_id == 4){
       		$sql = "select type,symbol,name,code from lcs_symbol where type in('future_global','future_index','future_inner')";
            $symbols = Yii::app()->lcs_standby_r->CreateCommand($sql)->queryAll();
       }else{
            $sql = "select type,symbol,name,code from lcs_symbol;";
            $symbols = Yii::app()->lcs_standby_r->CreateCommand($sql)->queryAll();
            $symbols = array_merge($symbols,SymbolService::$symbols_special);
		}
       
        $future_type = array('future_inner','future_index','future_global');
        $preg_symbols = array();
        //匹配
        foreach($symbols as & $symbol){
            //判断是否已经存在
            if (array_key_exists($symbol['symbol'], $preg_symbols)) {
                continue;
            }

            //对期货类型的进行特殊处理 只匹配名称
            if(in_array($symbol['type'], $future_type)){
                $preg_name = isset(SymbolService::$symbols_future_special[$symbol['name']]) ? SymbolService::$symbols_future_special[$symbol['name']] : $symbol['name'];
                if(mb_stristr($preg_content,$preg_name,true,'utf-8') !== false){
                    $preg_symbols[$symbol['symbol']] = array($symbol['type'],$symbol['symbol']);
                }
            }else{
                //先匹配symbol
                if(mb_stristr($preg_content,$symbol['symbol'],true,'utf-8') !== false){
                    $preg_symbols[$symbol['symbol']] = array($symbol['type'],$symbol['symbol']);
                    continue;
                }
                //匹配全称或简称
                $name_arr = explode('--', $symbol['name']);
                if(count($name_arr)>=1 && !empty($name_arr[0]) && mb_stristr($preg_content,$name_arr[0],true,'utf-8') !== false){
                    $preg_symbols[$symbol['symbol']] = array($symbol['type'],$symbol['symbol']);
                    continue;
                }
                if(count($name_arr)>=2 && !empty($name_arr[1]) && mb_stristr($preg_content,$name_arr[1],true,'utf-8') !== false){
                    $preg_symbols[$symbol['symbol']] = array($symbol['type'],$symbol['symbol']);
                    continue;
                }
                //A股的匹配代码
                if('stock_cn'==$symbol['type'] && mb_stristr($preg_content, $symbol['code'], true, 'utf-8') !== false){
                    $preg_symbols[$symbol['symbol']] = array($symbol['type'],$symbol['symbol']);
                    continue;
                }
            }


        }

        return $preg_symbols;
    }

    /**
     * 解析股票信息
     * @param $dynamicInfo
     */
    public static function parseSymbol($dynamicInfo)
    {
        $sql = "select type,symbol,name,code from lcs_symbol where type='stock_cn' and name not like '%指数%' order by id asc";
        $symbols = Yii::app()->lcs_standby_r->CreateCommand($sql)->queryAll();
        $symbolArr = [];

        $placeholder = '@@@@@';//占位符
        $pattern = '/\$.*\$/U';
        $subject = $dynamicInfo['content'];
        preg_match_all($pattern,$subject,$result);
        $dynamicInfo['content'] = preg_replace($pattern,$placeholder,$subject);//不需要识别的使用占位符

        foreach ($symbols as $v){
            $key = "\${$v['name']}({$v['symbol']})\$";
            $value = [];
            $value[] = "\${$v['name']}({$v['symbol']})\$";
            $value[] = "{$v['name']}{$v['symbol']}";
            $value[] = "{$v['name']}{$v['code']}";
            $value[] = "{$v['name']}({$v['symbol']})";
            $value[] = "{$v['symbol']}{$v['name']}";
            $value[] = "（{$v['code']}）{$v['name']}";
            $value[] = "({$v['symbol']}){$v['name']}";
            $value[] = "{$v['name']}.{$v['symbol']}";
            $value[] = "{$v['name']}";
            $value[] = "{$v['symbol']}";
            $value[] = "（{$v['code']}）";
            $value[] = "{$v['code']}";
            $symbolArr[$key] = $value;
        }
        $flag = false;
        $i = 10;
        $result1 = [];
        foreach ($symbolArr as $k=>$v){
            foreach ($v as $item){
                if(strpos($dynamicInfo['content'],$item) !== false){
                    $i++;
                    $dynamicInfo['content'] = str_replace($item,$k,$dynamicInfo['content']);
                    preg_match_all($pattern,$dynamicInfo['content'],$matches);
                    $result1[$placeholder.$i] = $k;
                    $dynamicInfo['content'] = preg_replace($pattern,$placeholder.$i,$dynamicInfo['content']);//不需要识别的使用占位符
                    $flag = true;
                    break;
                }
            }
        }

        //将占位符替换回来
        foreach ($result[0] as $v){
            $dynamicInfo['content'] = preg_replace("/$placeholder/",$v,$dynamicInfo['content'],1);
        }
        $i = 10;
        foreach ($result1 as $v){
            $i++;
            $dynamicInfo['content'] = preg_replace("/{$placeholder}{$i}/",$v,$dynamicInfo['content']);
        }

        if($flag){//修改动态
            Dynamic::model()->update($dynamicInfo);

            $args['discussion_id'] = $dynamicInfo['id'];
            $args['discussion_type'] = 12;
            $args['type'] = 1;
            $args['p_uid'] = $dynamicInfo['p_uid'];
            self::requestApi($args);
        }
    }

    /**
     * 修改圈子中的动态
     * @param $args
     * @return mixed
     */
    private static function requestApi($args){
        try{
            if(defined("IN235") && IN235){
                $url = "http://10.13.32.235/inner/updateCircleDynamic";
                $_curl = Yii::app()->curl->setHeaders(array("Host:i.licaishi.sina.com.cn"))->setTimeOut(10);
            }else{
                $url = "http://licaishi.sina.com.cn/inner/updateCircleDynamic";
                $_curl = Yii::app()->curl->setTimeOut(10);
            }
            $args["access_token"] = CommonUtils::buildAccessToken($args);

            $result = $_curl->post($url, $args);
            if(empty($result)){
                throw new Exception("请求接口失败", 2);
            }
            $result = json_decode($result,true);
            if($result && isset($result['code']) && $result['code']==0){
                return $result['data'];
            }else{
                throw new Exception($result['msg'],$result['code']);
            }
        }catch (Exception $e){
            var_dump($e->getCode(),$e->getMessage());
        }
    }
}
