<?php
/**
 * 理财师项目临时任务命令
 * User: zwg
 * Date: 2015/5/21
 * Time: 10:42
 */

class TestCommand extends LcsConsoleCommand {


    public function actionUserAuth(){
        $phones = UserAuth::model()->getAllUserAuthPhone();
        $uids = User::model()->getPhoneUidIm($phones);
        var_dump($uids);
    }
    public function actionTest($flag=1){
        if($flag){
            echo CommonUtils::getServerIp();
        }else{

        }
    }
    public function actionDaoru(){
        $sql = "select uid,p_uid,end_time from lcs_set_subscription where price=19800 and p_uid in (6150188584,6567967440)";
        $res = Yii::app()->lcs_w->createCommand($sql)->queryAll();
        foreach($res as $key=>$value){
            $sql1 = "insert into lcs_audio_subscription (`p_uid`,`uid`,`c_time`,`u_time`,`end_time`) value ({$value['p_uid']},{$value['uid']},'2018-12-27 00:00:00','2018-12-27 00:00:00',"."'"."{$value['end_time']}"."'".") ON DUPLICATE KEY UPDATE `end_time`="."'"."{$value['end_time']}"."'".";";
            echo $sql1."\n";
            $res = @Yii::app()->lcs_w->createCommand($sql1)->execute();
            if($res == 1){
                $all[] = $res;
            }
        }
        echo count($all);
        
        // var_dump($res);
    }

    public function actionNg(){
        $phone = '18688903048,13907079080,13162699617,13603705601,13836981141,13725525185,13957612532,18653721667,18678883792,18057095008,18986639380,15802700275,13925825553,13972671838,13681731334,13807833492,13994215099,13701637770,18905149696,13857665588,15764508734,13534082282,13701765119,17751599694,18777764568,13605462008,13727586296,13955177448,18665731890,18917578131,18930291237,18037412960,18016275090,13008763711,15921869647,13379258418,15768490966,13764675441,13905512486,13996187720,13708591073,18663755660,18142318226,13661560720,13335908827,13380882081,15155666273,13587250108,13317231319,13621918988,13828291580,13761978183,18909379221,15914168775,18559251688,17706332628,13888047809,13838388398,18031596888,13475139982,13977180550,13901616493,13381869975,13304346661,13055330808,17098656881,18905720968,15333798098,18610182960,15072046253,13777383178,18164170523,13551389099,17362923206,15313242351,13958215988,17695096024,18786644339,13567658229,13556431009,13751278028,13612648554,13433896035,13950890457,18903300358,15568274999,18637285812,13929888001,15138454252,13071238811,15810535070,15935590504,13965258312,13012982791,18560000013,13512775970,13974177749,13606085589,18998315497,18511531778,13794061976,15511987817,15905393009,15874934187,18117027339,15332387699,13831555266,13911182198,13811218875,15022252991,13602280460,13336176808,15905566825,13450705810,15983193892,13684284466,15653849009,13939028531,13186562851,17603088383,15920580139,18635438817,17318890916,13308006942,13801756509,13861280346,13257128285,13711016183,13817422018,13153651276,15766431703,13911383343,13371763353,13940514670,18026683928,13426169913,13940848181,13662426111,18902075171,13099965283,18601255596,13788983862,18023035056,13902233443,17305776280,13565301553,18923796046,13315863188,18001326620,13713639950,15019004663,15935416707,15235440056,15316213345,18926077115,13567456918,13708720066,18974569288,18738779696,13620081565,18904368588,18807278788,13705858383,15662503800,13563629221,13351119017,15352185421,15234392153,13228066992,13008926666,15899662520,13976744730,13601956857,18917001956,13591732699,15503615968,13710813808,18962695158,13702778005,13307094736,18916046833,13248089356,13399710331,15702009824,13646369836,13891636489,13801629911,18384582400,13790553660,13356385811,13895086616,13606996694,13342520006,13893204307,13783114316,13311599191,13764382540,15369878886,13691199009,16620644626,15073723765,13603325538,15995395284,13588568202,13636196656,13834030311,18783190267,13306199567,18538735150,13883252895,13381039750,18501530186,15014260172,15816855991,13354979435,18207527098,13305825301,15006294551,15165325850,13766886735,13705622626,15040184444,15339165572,13611052026,15062157777,17620700030,13696826846,15392823716,18612308397,13053883999,13422820639,13547978841,13955384828,13621971306,13608962618,15537615780,18611170966,13713413189,15569469310,13568186330,18991209159,13922480398,15221117158,13863164565,13301277653,13719996425,18453627797,18759905191,13661684846,13971927888,13682622470,13543515358,13598300296,13538363847,13778058555,13685221508,15109161163,13736004395,18858879358,13516779274,13032856285,18850570723,18736237236,18621381539,13689666582,13952527852,13646875385,13763311838,18641116528,13962205111,18650359281,15906145578,13855112983,15956976053,13639304966,15307145055,13777652000,18756028007,13712444817,13531666723,13502151441,13927402947,18773418780,13956570270,13829398945,18664106245,18238383856,18016340844,13723993728,13662682261,13162626355,18036907075,18766645188,18616295386,13764310774,18017760082,18603561983,15805466201,13661553591,15910998202,18088255435,13983456566,18605310281,13305353335,18957732239,13902945926,13395363410,13595166960,13472600606,17600223189,15604610929,15013638093,13323328316,15732456385,18140656076,18659362323,18036967766,15021955433,18621109768,13971579992,13036682568,13661503430,13938661782,18697150933,13937619562,13979488181,17793023405,13962252060,13525253003,13968369819,13701020577,18373136641,13166276891,15271506300,13381727678,13457219276,13691623776,18310536900,15618253715,13540484194,13964657918,13801839112,17701702088,18026243269,13007681715,18310536900,18559251688,17701702088,13601803075,15820756732,18505699821,18671764355,13711110461,13691623776,15035365005,13836981141,15618823552,13859917199,18858965166,15312002331,18991623118,13884185255,13920635519,18119899180,13701972271,13606501402,13816802565,15110115286,13866637320,13501120508,18052081695,18612308397,15801580174,15019004663,13872696655,13501650619,13892123811,13572539980,13892019930,18773418780,13598926018,18917695349,13801835876,18227759735,18632509993,13505174459,13534759560,13904601045,18686488120,18603164195,13917616198,13738340307,13906615901,18992307870,18605180590,13383827077,13635352317,13661856765,13616509231,13926190063,13852301817,13641491496,13290800207,15962967555,15003802567,13685380007,13620081565,13818181025,13564063369,13871285688,15160012600,13301277653,13524212010,13341535111,13588975488,18017760082,13761978183,13303520725,18961469126,13507570992,19942423361,13818037800,13567157345,13950139593,13838173681,15095242313,17861031188,18071081117,13154252518,13755354349,18310536900,15868538169,13701020577,18955543704,15029278907,13972303966,13937619562,18610653286,18738779696,13550509911,18053570388,18757389855,13983686038,15022252991,13002192192';

        $phone = explode(",", $phone);

        foreach ($phone as $key => $phone) {
            $auth = '[{"type":"4","zhen":"6"}]';
            $weixin = 1;
            $name = "系统添加(20181121)";
            $status = 0;
            $c_time = date("Y-m-d H:i:s",time());
            $u_time = date("Y-m-d H:i:s",time());

            // $sql = "select * from lcs_user_auth where phone=".$phone." and status=0;";

            // $res = Yii::app()->lcs_w->createCommand($sql)->queryAll();
            $sql1 = "update lcs_user_auth set `status`=-1 where phone=".$phone.";";
            Yii::app()->lcs_w->createCommand($sql1)->execute();

            $sql2 = "INSERT INTO lcs_user_auth (`phone`,`auth`,`weixin`,`name`,`status`,`c_time`,`u_time`) VALUES ('".$phone."','".$auth."','".$weixin."','".$name."','".$status."','".$c_time."','".$u_time."')";
            // if(empty($res)){
                $res = Yii::app()->lcs_w->createCommand($sql2)->execute();
                // $temp[] = 1;
                // echo "添加成功".$phone;
            // }else{
                // $error[] = $phone;
            // }
            var_dump($phone);
        }
        // var_dump($error);
        // echo count($temp);

    }

    public function actionMonitor(){
        $query = array(
            "query"=>array(
            ),
            "from"=>1,
            "size"=>100,
            "sort"=>[],
        );
        $query['query']['bool'] = array(
            'must'=>[
                'match'=>[
                    "category"=>[
                        "query"=>"sendVideoInfoToGoim_web"
                    ]
                ]
            ]
        );
        $query['sort'] = [
            "logtime"=>[
                "order"=>"desc"
            ]
        ];
        $json = json_encode($query);
        // echo $json;
        $url = "http://47.104.129.89:9200/";
        $url.= "savelog".'/_search';
        $header['content-type']="application/json; charset=UTF-8";
        $header['host']="es.licaishi.sina.com.cn";
        Yii::app()->curl->setHeaders($header);
        // if(defined('CUR_ENV') && CUR_ENV == 'dev'){
        //    $speechLists=Yii::app()->curl->post($url,$json);
        // }else{
           $speechLists=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url,$json);
        // }
        echo date("Y-m-d H:i:s",time())."\r\n";
        $data = json_decode($speechLists,true);
        foreach ($data['hits']['hits'] as $key => $value) {
            $strlen = strlen($value['_source']['message']);
            if($strlen < 500){
                var_dump($value['_source']['message']);
            }
    }
    }
    public function actionMessageData(){
        $sql = "select * from lcs_message where c_time<='2018-12-09 00:00:00'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        foreach ($data as $key => $value) {
            var_dump($value);
            break;
            $sql2 = "INSERT INTO lcs_customer (`uid`,u_type,`type`,relation_id,child_relation_id,content,content_client,link_url,is_read,c_time,u_time) VALUES ('".$value['uid']."','".$value['u_type']."','".$value['relation_id']."','".$value['child_relation_id']."','".$value['content']."','".$value['content_client']."','".$value['link_url']."','".$value['is_read']."','".$value['c_time']."','".$value['u_time']."')";
            $res = Yii::app()->lcs_w->createCommand($sql2)->execute();
            var_dump($res);
        }
    }
    public function actionGetTuiTest(){
        $push_data = '{"channel_type":9,"push_message":{"uid":"171429871","u_type":1,"type":12,"relation_id":"","child_relation_id":2724,"content":[{"value":"1231","class":"","link":""}],"link_url":"","c_time":"2018-12-17 13:11:39","u_time":"2018-12-17 13:11:39","content_client":"{\"t\":\"22\",\"title\":\"123\",\"image\":\"\",\"content\":\"1231\"}","channel":13},"push_user":[{"channel_type":"9","channel_id":"1104a8979283b7f90c7","u_type":"1","s_uid":"0","uid":"171429871","s_id":"203"},{"channel_type":"9","channel_id":"1104a8979283b7f90c6","u_type":"1","s_uid":"0","uid":"171429871","s_id":"205"},{"channel_type":"9","channel_id":"1104a8979283b7f90c6","u_type":"1","s_uid":"0","uid":"171429871","s_id":"203"}]}';
        Yii::app()->redis_w->Lpush("lcs_push_client_getui_queue",$push_data);
    }
    //导入数据
    public function actionCustomer(){
        $count_phone = 0;

        $data = array(
          array(
            "A"=>"罗燕c",
            "B"=>8645015,
            "C"=>13167202768,
            "D"=>"投教",
            "E"=>70302,
            "F"=>"投教",
            "G"=>NULL,
          ),
          array(
            "A"=>"汪海潮",
            "B"=>8644015,
            "C"=>13162546308,
            "D"=>"投教",
            "E"=>70304,
            "F"=>"投教",
            "G"=>
            NULL
          ),
         array(
            "A"=>"石昭昭",
            "B"=>8642015,
            "C"=>13167205829,
            "D"=>"投教",
            "E"=>70305,
            "F"=>"投教",
            "G"=>
            NULL
          ),
        );
        foreach ($data as $key => $value) {
            //手机号加密查询
            $phone = CommonUtils::encodePhoneNumber($value['C']);
            echo $value['A'];
                    $count_phone++;
                $uid = 0;
                    $u_time = date('Y-m-d H:i:s',time());
                    $c_time = date('Y-m-d H:i:s',time());
                    $ext_no = $value['B'];
                    $to_crm = $value['D'] == '投教' ? "1" : "2";
                    $in_crm = $value['F'] == '投教' ? "1" : "2";
                    $name = $value['A']."(723同步)";
                    $sql2 = "INSERT INTO lcs_customer (uid,name,ext_no,phone,to_crm,in_crm,c_time,u_time) VALUES ('".$uid."','".$name."','".$ext_no."','".$phone."','".$to_crm."','".$in_crm."','".$c_time."','".$u_time."')";
                    $res = Yii::app()->lcs_w->createCommand($sql2)->execute();

                    echo $res."\r\n";

                    echo $sql2."\r\n";

            $sql1 = "select * from lcs_user_index where phone=".$phone;

            $result = Yii::app()->lcs_r->createCommand($sql1)->queryAll();
            foreach ($result as $key => $value) {
                $sql3 = "update lcs_customer set `uid`=".$value['id']." where phone=".$phone.";";
                $res = Yii::app()->lcs_r->createCommand($sql1)->execute();

                echo $res."\r\n";

                echo $sql3."\r\n";
                
            }
        }
        echo $count_phone;
    }
    //微信推送修复
    public function actionWxPushFix($user_table=0,$ms="微信推送修复"){
        //查询用户信息
        $sql = "select uid,wx_public_uid from lcs_user_".$user_table." where wx_public_uid != ''";
        echo $sql."\r\n";
        $data  = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $counts = 0;
        foreach ($data as $key => $value) {
            $sql2 = "select * from lcs_message_channel_user where channel_id='".$value['wx_public_uid']."' and channel_type=1;";
            echo $sql2."\r\n";
            $channelData = Yii::app()->lcs_r->createCommand($sql2)->queryAll();
            var_dump($channelData);
            if(!empty($channelData)){
                continue;
            }
            $counts++;
            //同步到lcs_message_channel_user
            $sql1 = "insert into lcs_message_channel_user (`channel_type`,`channel_id`,`u_type`,`s_uid`,`uid`,`c_time`,`u_time`) value (1,'".$value['wx_public_uid']."',1,0,".$value['uid'].",'".date('Y-m-d H:i:s',time())."','".date('Y-m-d H:i:s')."');";
            echo $sql1."\r\n";
            echo Yii::app()->lcs_r->createCommand($sql1)->execute();
        }
        echo "同步数量".$counts;
    }
    //crm
    public function actionCrmPush(){
        $key = "lcs_sync_user_2_crm";
        $data = array(
            24905700,
            24905824,
            24905949,
            24906063,
            24906345,
            24906370,
            24906643,
            24906740,
            24906804,
            24906929,
            24907084,
            24907267,
            24907318,
            24907347,
            24907451,
            24907605,
            24907632,
            24907655,
            24907796,
            24907816,
            24908264,
            24908387,
            24908804,
            24908909,
            24908975,
            24909064,
            24909665,
            24909730,
            24909851,
            24910118,
            24910162,
            24910529,
            24910639,
            24910709,
            24910757,
            24910958,
            24910960,
            24911093,
            24911199,
            24911267,
            24911282,
            24911305,
            24911355,
            24911414,
            24911486,
            24911505,
            24911525,
            24911526,
            24911561,
            24911577,
            24911759,
            24911778,
            24911828,
            24911845,
            24911904,
            24911916,
            24912034,
            24912047,
            24912076,
            24912087,
            24912145,
            24912181,
            24912276,
            24912406,
            24912452,
            24912543,
            24912578,
            24912640,
            24912828,
            24912858,
            24912862,
            24912986,
            24913169,
            24913446,
            24913464,
            24913529,
            24913533,
            24913651,
            24913690,
            24913715,
            24913807,
            24913948,
            24914204,
            24914207,
            24914368,
            24914613,
            24914632,
            24914650,
            24914777,
            24914790,
            24914823,
        );
        foreach ($data as $key => $value) {
            echo $value."\r\n";
            continue;
            echo Yii::app()->redis_w->push($key,$value);
        }
    }

	public function actionFix($r){
		$sql = "select * from lcs_cron_log where cron_no=5001 and c_time>='2018-01-03' and message like '%order info error%'";
		$list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		foreach ($list as $item){
			$msg = $item['message'];
			$line = explode('--', $msg);
			$foo = explode('=', $line[2]);
			$order_id = $foo[1];
			$foo1 = explode('=', $line[1]);
			$pln_id = $foo1[1];
			$time = strtotime($line[6]);
			$price = $line[4];
			$amount = $line[5];
			$symbol = $line[3];
			
			$foo3 = explode(':', $line[0]);
			$action = $foo3[2];
			if($action == 'buy'){
				$type = 1;
			}elseif($action == 'sell'){
				$type = 2;
			}else{
				$type = 3;
			}			
			echo sprintf("%s %s %s %s %s %s %s\n",$pln_id,$time,$price,$order_id,$symbol,$amount,$type);
			if($r == 1 && ($type == 1 || $type == 2)){
				$res = PlanService::dealPlanOrder($pln_id,$order_id,$symbol,$price,$amount,$type,$time);
				print_r($res);
			}		
			if($r == 1 && $type == 3){
				PlanService::cancelOrder($pln_id, $order_id);
			}
		}
	}

    public function actionExportAskData()
    {
        $next = 0;
        do {
            $sql = "SELECT q.id,q.uid,q.p_uid,q.content,q.status,q.price,q.answer_num,a.content as a_content,a.content_pay as a_content_pay,a.score as a_score,a.score_reason as a_score_reason
                    FROM lcs_ask_question q left join lcs_ask_answer a on q.answer_id=a.id
                    WHERE 1 and q.id>{$next} order by q.id limit 10000";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if (empty($res)) break;

            foreach ($res as $row) {
                if (!empty($row['content']))
                    print_r("{$row['uid']}\t{$row['p_uid']}\t{$row['content']}\t{$row['status']}\t{$row['price']}\t{$row['a_content']}\t{$row['a_content_pay']}\t{$row['a_score']}\t{$row['a_score_reason']}\n");
            }
            $next = $row['id'];
        } while (1);
    }

    public function actionMem(){
        var_dump(Yii::app()->cache);exit;
    }

    public function actionTorgToken($n=0) {
        $_curl = Yii::app()->curl;

        $curr_time = date("Y-m-d H:i:s");
        $token_fr = "lcssp";

        // 1. 获取新token
        $token = Yii::app()->redis_r->get('lcs_test_torgtoken');
        if (empty($token) || $n==99) {
            $token_info = $_curl->get("http://cloud.sina.com.cn/service/auth/tabcd?appid=3725d623778f172f&phone=18888888888");
            $token_info = json_decode($token_info, true);
            if ($token_info['code'] !== 0) {
                print_r("token获取失败");
                exit(0);
            } else {
                print_r("\n{$curr_time}\t");
                print_r($token_info);
                $token = $token_info['data']['token'];
                Yii::app()->redis_w->setex('lcs_test_torgtoken', $token_info['data']['expires_in']+800, $token);
            }
        }
        
        // 2. 验证token有效性
        $user_info = $_curl->setHeaders(
            [
                "token: {$token}",
                "token-fr: {$token_fr}",
            ])->post("http://cloud.sina.com.cn/service/user/user-info");
        print_r("{$curr_time}:{$token}\t{$user_info}\n");

        exit(0);
    }

    public function actionCXinDaPlannerCache($p_uids=0){
        $cache = new \Memcached;
        $close_method = "quit";
        $cache_conf = [
            ['host' => '10.69.14.35', 'port' => 7817],
            ['host' => '10.69.16.107', 'port' => 7817],
        ];

        if (empty($p_uids)) {
            $sql = "select s_uid from lcs_planner where partner_id=18 order by u_time asc;";
            $planners = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
            if (empty($planners)) {
                print_r("done\n");
            }
        } else {
            $planners = explode(',', $p_uids);
        }
        
        foreach ($planners as $p_uid) {
            $key = "lcs_p_{$p_uid}";
            $key2 = "lcs_ask_p_ability_{$p_uid}";
            print_r("{$key}\t");
            print_r("{$key2}\t");
            foreach ($cache_conf as $cf) {
                $res = $cache->addServer($cf['host'], $cf['port']);
                print_r("||{$res}");
                $cache->get($key);
                // $res = $cache->delete($key);
                print_r("||{$res}");
                $cache->get($key2);
                // $res = $cache->delete($key2);
                print_r("||{$res}");
                $res = $cache->$close_method();
                print_r("||{$res}");
            }
            print_r("\n");
        }

        $sql = "select id from lcs_package where p_uid in (".implode(",", $planners).");";
        $pkg_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        foreach ($pkg_ids as $pkgid) {
            $key = "lcs_pkg_{$pkgid}";
            print_r("{$key}\t");
            foreach ($cache_conf as $cf) {
                $res = $cache->addServer($cf['host'], $cf['port']);
                print_r("||{$res}");
                $cache->get($key);
                // $res = $cache->delete($key);
                print_r("||{$res}");
                $res = $cache->$close_method();
                print_r("||{$res}");
            }
            print_r("\n");
        }

        $sql = "select id from lcs_view where p_uid in (".implode(",", $planners).");";
        $view_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        foreach ($view_ids as $viewid) {
            $key = "lcs_v_{$viewid}";
            print_r("{$key}\t");
            foreach ($cache_conf as $cf) {
                $res = $cache->addServer($cf['host'], $cf['port']);
                print_r("||{$res}");
                $cache->get($key);
                // $res = $cache->delete($key);
                print_r("||{$res}");
                $res = $cache->$close_method();
                print_r("||{$res}");
            }
            print_r("\n");
        }

        print_r("done\n");
    }

    public function actionXindaPlannerDataRemove($p_uid, $del_p=false) {
        // 1. 理财师的观点、锦囊、计划、问答全部删除
        // 2. 大赛计划删除
        // 3. 圈子的说说删除
        // 4. 动态删除
        
        // 理财师
        if ($del_p) {
            $sql = "DELETE FROM lcs_planner WHERE s_uid={$p_uid};";
            $res = Yii::app()->lcs_w->createCommand($sql)->execute();
            print_r("\n[{$res}]理财师：\n{$sql}\n");
        }
        
        // 圈子
        $sql = "DELETE FROM lcs_circle WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]圈子：\n{$sql}\n");
        
        // 计划数据
        $sql = "DELETE FROM lcs_plan_info WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]计划：\n{$sql}\n");

        // 观点包订阅
        $sql = "SELECT id FROM lcs_package WHERE p_uid={$p_uid}";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (!empty($res)) {
            $sql = "DELETE FROM lcs_package_subscription WHERE pkg_id IN (".implode(',', $res).");";
            $res = Yii::app()->lcs_w->createCommand($sql)->execute();
            print_r("\n[{$res}]观点包订阅：\n{$sql}\n");
        }

        // 观点包
        $sql = "DELETE FROM lcs_package WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点包1：\n{$sql}\n");

        // 观点包2
        $sql = "DELETE FROM lcs_package_audit WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点包2：\n{$sql}\n");

        // 观点
        $sql = "DELETE FROM lcs_view WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点1：\n{$sql}\n");

        // 观点2
        $sql = "DELETE FROM lcs_view_draft WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点2：\n{$sql}\n");

        // 问答
        $sql = "DELETE FROM lcs_ask_planner WHERE s_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]问答1：\n{$sql}\n");

        // 问答2
        $sql = "DELETE FROM lcs_ask_question WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]问答2：\n{$sql}\n");

        // 动态
        $sql = "DELETE FROM lcs_moments WHERE p_uid={$p_uid};";
        $res = Yii::app()->lcs_comment_w->createCommand($sql)->execute();
        print_r("\n[{$res}]动态：\n{$sql}\n");
    }

    public function actionXinDaPlannerData(){
        $sql = "select s_uid from lcs_planner where partner_id=18;";
        $planners = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        // 圈子
        $sql = "UPDATE lcs_circle SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]圈子：\n{$sql}\n");

        // 订单
        $sql = "UPDATE lcs_orders SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]订单：\n{$sql}\n");

        // 计划数据
        $sql = "UPDATE lcs_plan_info SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]计划：\n{$sql}\n");

        // 观点包
        $sql = "UPDATE lcs_package SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点包1：\n{$sql}\n");

        // 观点包2
        $sql = "UPDATE lcs_package_audit SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点包2：\n{$sql}\n");

        // 观点包3
        // $sql = "UPDATE lcs_package_history SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        // $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        // print_r("\n[{$res}]观点包3：\n{$sql}\n");

        // 观点
        $sql = "UPDATE lcs_view SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点1：\n{$sql}\n");

        // 观点2
        $sql = "UPDATE lcs_view_draft SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]观点2：\n{$sql}\n");

        // 问答
        $sql = "UPDATE lcs_ask_planner SET partner_id=18 WHERE s_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]问答1：\n{$sql}\n");

        // 问答2
        $sql = "UPDATE lcs_ask_question SET partner_id=18 WHERE p_uid IN (" . implode(",", $planners) . ");";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        print_r("\n[{$res}]问答2：\n{$sql}\n");
    }

    public function actionExportXindaPlanner()
    {
        $sql = "SELECT p.s_uid,p.real_name,p.gender,p.phone,p.cert_number,p.department,p.location,p.summary
                FROM lcs_planner p
                WHERE partner_id=18";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        print_r("微博id=姓名=性别（m男 f女）=手机号=资格证号=部门=省市=简介\n");
        foreach ($res as $row) {
            $row['summary'] = str_replace(array("\r\n", "\r", "\n"), "", $row['summary']);
            print_r("{$row['s_uid']}={$row['real_name']}={$row['gender']}={$row['phone']}={$row['cert_number']}={$row['department']}={$row['location']}={$row['summary']}\n");
        }
    }

    // 初始化信达投顾服务模块权限
    public function actionXindaPlannerSrvModule($the_file)
    {
        $srv_modules_conf = [
            "2" => 1,
            "3" => 2,
            "4" => 4,
            "5" => 8,
            "6" => 16,
            "7" => 32,
            "8" => 64,
        ];
        $planner_srv_modules_conf = [];
        $curr_time = date("Y-m-d H:i:s");

        try {
            $excel = new PHPExcel();
            $objReader = PHPExcel_IOFactory::createReader("Excel5");
            $objExcel = $objReader->load($the_file);
            $indata = $objExcel->getSheet(0)->toArray();
            foreach ($indata as $ii => $val) {
                //第0行为标题
                if (($ii != 0) && !empty($val['0'])) {
                    $planner_srv_modules_conf[$val['0']] = 0;
                    ($val['2'] == 1) && ($planner_srv_modules_conf[$val['0']] += $srv_modules_conf[2]);
                    ($val['3'] == 1) && ($planner_srv_modules_conf[$val['0']] += $srv_modules_conf[3]);
                    ($val['4'] == 1) && ($planner_srv_modules_conf[$val['0']] += $srv_modules_conf[4]);
                    ($val['5'] == 1) && ($planner_srv_modules_conf[$val['0']] += $srv_modules_conf[5]);
                    ($val['6'] == 1) && ($planner_srv_modules_conf[$val['0']] += $srv_modules_conf[6]);
                    ($val['7'] == 1) && ($planner_srv_modules_conf[$val['0']] += $srv_modules_conf[7]);
                    ($val['8'] == 1) && ($planner_srv_modules_conf[$val['0']] += $srv_modules_conf[8]);
                }
            }
        } catch (Exception $e) {
            printf("ERROR: %s\n", $e->getMessage());
            exit;
        }

        // print_r($planner_srv_modules_conf);

        $all_p_uids = array_keys($planner_srv_modules_conf);
        $sql = "SELECT p_uid FROM lcs_planner_power where p_uid IN (".implode(",", $all_p_uids).")";
        $exist_p_uids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($exist_p_uids)) $exist_p_uids = [];
        $un_exist_p_uids = array_diff($all_p_uids, $exist_p_uids);

        if (!empty($exist_p_uids)) {
            $sql = '';
            foreach ($exist_p_uids as $p_uid) {
                $sql .= "UPDATE lcs_planner_power SET srv_module={$planner_srv_modules_conf[$p_uid]},u_time='{$curr_time}' WHERE p_uid={$p_uid};";
            }
            print_r("{$sql}\n");
            $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        }

        if (!empty($un_exist_p_uids)) {
            $sql = 'INSERT INTO lcs_planner_power (p_uid, c_time, u_time, srv_module) VALUES ';
            foreach ($un_exist_p_uids as $p_uid) {
                $sql .= "({$p_uid}, '{$curr_time}', '{$curr_time}', {$planner_srv_modules_conf[$p_uid]}),";
            }
            $sql = rtrim($sql, ',');
            print_r("{$sql}\n");
            $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        }
    }

    public function actionExportData() {
        $sql = "SELECT p.s_uid,p.real_name,p.gender,p.phone,p.cert_number,p.department,p.location,p.summary,pp.curr_ror,p.u_time
                FROM lcs_planner p
                    LEFT JOIN lcs_planner_match pm ON pm.`match_id`=10001 AND pm.`p_uid`=p.`s_uid`
                    LEFT JOIN lcs_plan_info pp ON pm.`pln_id`=pp.`pln_id`
                WHERE p.company_id=93
                ORDER BY pp.curr_ror DESC";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        print_r("微博id=姓名=性别（m男 f女）=手机号=资格证号=部门=省市=简介=当前收益=最后登录时间\n");
        foreach ($res as $row) {
            $row['summary'] = str_replace(array("\r\n", "\r", "\n"), "", $row['summary']);
            print_r("{$row['s_uid']}={$row['real_name']}={$row['gender']}={$row['phone']}={$row['cert_number']}={$row['department']}={$row['location']}={$row['summary']}={$row['curr_ror']}={$row['u_time']}\n");
        }
    }

    public function actionCindaCircleStat($partner_id=18) {
        $sql = "SELECT id,title,p_uid FROM lcs_circle WHERE partner_id={$partner_id}";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($res)) {
            print_r("nothing need to do\n");
            exit;
        }

        $circle_ids = [];
        $circle_info_map = [];
        foreach ($res as $row) {
            $circle_ids[] = $row['id'];
            $circle_info_map[$row['id']] = $row;
        }

        $sql = "select relation_id as circle_id,comment_num,planner_comment_num,comment_num-planner_comment_num as user_comment_num
                    from lcs_comment_index_num 
                    where cmn_type=71 and relation_id in (".implode(",", $circle_ids).")
                    order by planner_comment_num desc,user_comment_num desc limit 30;";
        $res = Yii::app()->lcs_comment_r->createCommand($sql)->queryAll();

        print_r("圈子id\t圈子名称\t投顾ID\t总互动数\t投顾互动数\t用户互动数\n");
        foreach ($res as $row) {
            $circle_info = $circle_info_map[$row['circle_id']];
            print_r("{$row['circle_id']}\t{$circle_info['title']}\t{$circle_info['p_uid']}\t{$row['comment_num']}\t{$row['planner_comment_num']}\t{$row['user_comment_num']}\n");
        }
        exit(0);
    }

    public function actionPlannerCircleDetail($pname) {
        $sql = "select s_uid from lcs_planner where name='$pname'";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($res)) exit("nothing need todo\n");
        print_r($res);

        foreach($res as $p_uid) {
            print_r("$p_uid:\n");
            $sql = "select * from lcs_circle where p_uid={$p_uid}";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
            if (empty($res)) print_r("no circle\n");
            print_r($res);

            $sql = "select * from lcs_circle_notice where circle_id={$res['id']}";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if (empty($res)) print_r("no circle notice\n");
            foreach($res as $row) {
                $content = json_decode($row['notice'], true);
                print_r("{$row['id']}\t{$row['title']}\t{$content['content']}\n");
            }
        }
    }

    // 圈子信息统计
    public function actionCircleStat() {
        $total_join = 0;
        $total_aver = 0;
        $total_cmn = 0;
        $total_cmn_peop = 0;
        $total_cmn_planner = 0;
        print_r("圈子id\t累计已加入人数\t累计互动数\t累计互动人数\t人均互动数\t总访问人数（独立访客）\t投顾互动数\n");

        // 累计互动人数
        $sql = "SELECT COUNT(1) FROM (SELECT cmn_type,relation_id,uid,COUNT(1) FROM lcs_comment_master WHERE cmn_type=71 GROUP BY uid) t";
        $total_cmn_peop = Yii::app()->lcs_comment_r->createCommand($sql)->queryScalar();

        $circle_cmn_stat_map = [];
        $sql = "SELECT relation_id,comment_num,planner_comment_num FROM lcs_comment_index_num WHERE cmn_type=71";
        $res = Yii::app()->lcs_comment_r->createCommand($sql)->queryAll();
        foreach ($res as $row) {
            $circle_cmn_stat_map[$row['relation_id']] = $row;
        }
        $sql = "SELECT id,title,p_uid,user_num,comment_num,comment_num/user_num as average FROM lcs_circle order by average desc";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        foreach ($res as $row) {
            $planner_cmn_num = isset($circle_cmn_stat_map[$row['id']]) ? $circle_cmn_stat_map[$row['id']]['planner_comment_num'] : 0;
            print_r("{$row['id']}\t{$row['user_num']}\t{$row['comment_num']}\t{$row['average']}\tN/A\t{$planner_cmn_num}\n");

            $total_join += $row['user_num'];
            $total_cmn += $row['comment_num'];
            $total_cmn_planner += $planner_cmn_num;
        }
        $total_aver = round($total_cmn/$total_join, 2);
        print_r("\n总计\t{$total_join}\t{$total_cmn}\t{$total_cmn_peop}\t{$total_aver}\tN/A\t{$total_cmn_planner}\n");

        $mail_title = "圈子数据统计-" . date("Y-m-d",strtotime("-1 day"));
        $mail_content = "<table>
                            <tr>
                                <td></td>
                                <td>累计已加入人数</td>
                                <td>累计互动数</td>
                                <td>累计互动人数</td>
                                <td>人均互动数</td>
                                <td>总访问人数（独立访客）</td>
                                <td>投顾互动数</td>
                            </tr>
                            <tr>
                                <td>总计</td>
                                <td>{$total_join}</td>
                                <td>{$total_cmn}</td>
                                <td>{$total_cmn_peop}</td>
                                <td>{$total_aver}</td>
                                <td>N/A</td>
                                <td>{$total_cmn_planner}</td>
                            </tr>
                        </table>";
        $mail_to = [
            'zhihao6@staff.sina.com.cn',
            'xiaozhuo@staff.sina.com.cn',
            'caokai6@staff.sina.com.cn',
            'guobing1@staff.sina.com.cn',
            'shixi_xuxuan@staff.sina.com.cn',
        ];
        $sendMail = new NewSendMail($mail_title,$mail_content,$mail_to);

    }

    public function actionNotCircle() {
        $sql = "SELECT circle_id FROM lcs_circle_user GROUP BY circle_id";
        $join_circle_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        $sql = "SELECT id FROM lcs_circle WHERE id IN (".implode(",", $join_circle_ids).")";
        $exist_circle_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        $not_circle_ids = array_diff($join_circle_ids, $exist_circle_ids);
        print_r($not_circle_ids);

        if (!empty($not_circle_ids)) {
            $sql = "DELETE FROM lcs_circle_user WHERE circle_id IN (".implode(",", $not_circle_ids).")";
            Yii::app()->lcs_w->createCommand($sql)->execute();
        }
    }

    /**
     * 没有圈子的理财师补充创建圈子
     */
    public function actionSupplementCircle() {
        //查找没有圈子的理财师s_uid
        $sql = "select s_uid from lcs_planner where status=0 and s_uid not in (select p_uid from lcs_circle where status=0)";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (!empty($res)) {
            foreach ($res as $re) {
                $result = $this->actionCreatePlannerCircle(($re['s_uid']));
                echo 'p_uid:'.$re['s_uid'].' 圈子创建';
                echo $result ? '成功' : '失败';
                echo "\n";
            }
        } else {
            echo "没有需要补充创建圈子的理财师";
        }
    }

    // 初始化指定理财师免费圈子
    public function actionCreatePlannerCircle($pid=0) {
        $curr_time = date("Y-m-d H:i:s");

        // 开始初始化
        $sql = "select s_uid,name,image,summary,partner_id from lcs_planner where s_uid={$pid} and status=0";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        if (empty($res)) {
            print_r("nothing need to do.\n");
            return;
        } else {
            $planners_arr = array_chunk($res, 500);   
        }

        foreach ($planners_arr as $ps) {
            $values = "";
            foreach ($ps as $pi) {
                $values .= "({$pi['s_uid']},'{$pi['name']}的圈子','圈主还没有编辑简介哦~','{$pi['image']}',1,{$pi['partner_id']},'{$curr_time}','{$curr_time}'),";
            }
            $values = rtrim($values,',');

            $sql = "insert into lcs_circle (p_uid,title,summary,image,user_num,partner_id,c_time,u_time) values {$values}";
            Yii::app()->lcs_w->createCommand($sql)->execute();
        }

        $sql = "insert into lcs_circle_user (u_type,uid,circle_id,c_time,u_time) select 2 as u_type,p_uid as uid,id as circle_id,c_time,u_time from lcs_circle where p_uid={$pid}";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();

        return $res;
    }

    public function actionDeleteRepeatUserCircle() {
        $sql = "SELECT MAX(id) FROM lcs_circle_user GROUP BY u_type,uid,circle_id HAVING COUNT(*)>1";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        if (empty($res)) {
            print_r("nothing need to do.\n");
            return;
        } else {
            $tmp_arr = array_chunk($res, 500);   
        }

        foreach ($tmp_arr as $ids) {
print_r($ids);
            $sql = "DELETE FROM lcs_circle_user WHERE id IN (" . implode(",", $ids) . ");";
            Yii::app()->lcs_w->createCommand($sql)->execute();
        }

        echo "done";

    }

    // 初始化理财师免费圈子
    public function actionInitPlannerCircle() {
        $curr_time = date("Y-m-d H:i:s");

        // 清数据
        $sql = "delete from lcs_circle";
        Yii::app()->lcs_w->createCommand($sql)->execute();
        $sql = "delete from lcs_circle_notice";
        Yii::app()->lcs_w->createCommand($sql)->execute();
        $sql = "delete from lcs_circle_user";
        Yii::app()->lcs_w->createCommand($sql)->execute();

        // 开始初始化
        $sql = "select s_uid,name,image,summary,partner_id from lcs_planner where status=0";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        if (empty($res)) {
            print_r("nothing need to do.\n");
            return;
        } else {
            $planners_arr = array_chunk($res, 500);   
        }

        foreach ($planners_arr as $ps) {
            $values = "";
            foreach ($ps as $pi) {
                $values .= "({$pi['s_uid']},'{$pi['name']}的圈子','圈主还没有编辑简介哦~','{$pi['image']}',1,{$pi['partner_id']},'{$curr_time}','{$curr_time}'),";
            }
            $values = rtrim($values,',');

            $sql = "insert into lcs_circle (p_uid,title,summary,image,user_num,partner_id,c_time,u_time) values {$values}";
            Yii::app()->lcs_w->createCommand($sql)->execute();
        }

        $sql = "insert into lcs_circle_user (u_type,uid,circle_id,c_time,u_time) select 2 as u_type,p_uid as uid,id as circle_id,c_time,u_time from lcs_circle";
        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
    }

    public function actionCountPlannerComment() {
        $mem_start = memory_get_usage();
        $lcs_comment_count = [];
        $mem_end =  memory_get_usage();

        $db_r = Yii::app()->lcs_comment_r;
        for ($i=0; $i < 256; $i++) {
            print_r("count table [{$i}]\t[".count($lcs_comment_count)."]\t[".round(($mem_end-$mem_start)/1048576, 2)."M]\n");
            $sql = "
                SELECT uid, COUNT(1) AS total 
                FROM lcs_comment_{$i} 
                WHERE cmn_type IN (1,2) AND u_type=2 AND c_time>'2016-01-01' 
                GROUP BY uid 
                ORDER BY total DESC
            ";
            $res = $db_r->createCommand($sql)->queryAll();
            if (empty($res)) continue;

            foreach ($res as $row) {
                if (isset($lcs_comment_count[$row['uid']])) {
                    $lcs_comment_count[$row['uid']] += $row['total'];
                } else {
                    $lcs_comment_count[$row['uid']] = $row['total'];
                }
            }
            $mem_end =  memory_get_usage();
        }

        if (empty($lcs_comment_count)) {
            echo "nothing need to do";
            return;
        }

        arsort($lcs_comment_count);
        $top_30_pages = array_chunk($lcs_comment_count, 30, true);
        // print_r($top_30_pages[0]);

        $planner_maps = Planner::model()->getPlannerById(array_keys($top_30_pages[0]));

        foreach ($top_30_pages[0] as $s_uid => $count) {
            print_r("{$s_uid}\t{$planner_maps[$s_uid]['name']}\t{$count}\n");
        }
    }

    public function actionPackageCollectUser($pid=0) {
        if (empty($pid)) {
            echo "packge id none\n";
            return ;
        }

        $sql = "
            SELECT c.uid,u.phone 
            FROM lcs_collect c
                JOIN lcs_user_index u ON c.uid=u.id
            WHERE c.type=4 AND c.relation_id={$pid} AND u.phone!=''
        ";
        print_r("$sql\n");
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($res)) $res = [];

        foreach ($res as $row) {
            $phone = CommonUtils::decodePhoneNumber($row['phone']);

            print_r("{$row['uid']}\t{$phone}\n");
        }
        print_r("\n");
    }

    public function actionPropBuyUser($pid=0) {
        if (empty($pid)) {
            echo "prop id none\n";
            return ;
        }

        $sql = "
            SELECT o.uid,u.`name`,u.`phone`
            FROM lcs_orders o
                JOIN lcs_user_index u ON o.`type`=71 AND o.`relation_id`={$pid} AND o.`status`=2 AND o.`uid`=u.`id`;
        ";
        print_r("$sql\n");
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($res)) $res = [];

        foreach ($res as $row) {
            $phone = CommonUtils::decodePhoneNumber($row['phone']);

            print_r("{$row['uid']}\t{$phone}\t{$row['name']}\n");
        }
        print_r("\n");
    }

    /**
     * 0.0 数据库连接，两个库
     * 0.1 已知信达的 partner_id, match_id, pln_id 
     * 0.3 lcs_planner 表数据全导入
     * 1. 除新增字段外，其余字段（pln_id等）均保持不变
     * 2. 对于每一个pln_id进行以下操作
     * 2.1 导入计划数据
     * 2.1.1 plan_info信息增加match_id、partner_id插入或更新到plan_info表
     * 2.1.2 plan_asset、plan_assess、panic_buy等其他表信息插入或更新到对应表中
     **/
    public function actionLcsPlanToOrgPlan($pln_id=0, $partner_id=16, $match_id=10002) {
        // ==============
        // 工具方法
        $err_print = function($msg) {
            print_r("\033[01;31m{$msg}\033[0m\n");
            exit;
        };
        $info_print = function($msg) {
            print_r("\033[01;35m{$msg}\033[0m\n");
        };
        $scc_print = function($msg) {
            // print_r("\033[01;32m{$msg}\033[0m\n");
            print_r("{$msg}\n");
        };

        // ==============
        // 初始值
        $partner_id = intval($partner_id);
        $match_id = intval($match_id);
        $pln_id = intval($pln_id);
        if (empty($partner_id) || empty($match_id) || empty($pln_id)) {
            $err_print("[失败]partner_id、match_id、pln_id不能为空");
        } else {
            $scc_print("将导入数据：");
            $scc_print("partner_id:\t{$partner_id}");
            $scc_print("match_id:\t{$match_id}");
            $scc_print("pln_id:\t{$pln_id}");
            $scc_print("");
        }

        // ==============
        // 初始化数据库连接
        $lcs_db_r = Yii::app()->lcs_r;
        // $lcs_db_w = Yii::app()->lcs_w;
        $org_db_r = Yii::app()->org_r;
        $org_db_w = Yii::app()->org_w;

        // ==============
        // 数据导入开始
        // 1. plan_info信息导入
        $sql = "SELECT * FROM lcs_plan_info WHERE pln_id=:pln_id";
        $cmd = $lcs_db_r->createCommand($sql);
        $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
        $lpi = $cmd->queryRow();
        if (empty($lpi)) {
            $err_print("[失败][{$pln_id}]lcs_plan_info信息为空");
        } else {
            $sql = "DELETE FROM lcs_plan_info WHERE pln_id=:pln_id LIMIT 1";
            $cmd = $org_db_r->createCommand($sql);
            $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
            $res = $cmd->execute();

            $lpi['partner_id'] = $partner_id;
            $lpi['match_id'] = $match_id;
            $res = $org_db_w->createCommand()->insert("lcs_plan_info", $lpi);
            if(empty($res)) {
                $err_print("[失败][{$pln_id}]导入lcs_plan_info数据");
            } else {
                $scc_print("[成功][{$pln_id}]导入lcs_plan_info数据");
            }
        }

        // 2. lcs_plan_asset信息导入
        $sql = "SELECT * FROM lcs_plan_asset WHERE pln_id=:pln_id";
        $cmd = $lcs_db_r->createCommand($sql);
        $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
        $lpi = $cmd->queryAll();
        if (empty($lpi)) {
            $info_print("[无数据][{$pln_id}]lcs_plan_asset信息为空");
        } else {
            do {
                $sql = "DELETE FROM lcs_plan_asset WHERE pln_id=:pln_id LIMIT 1000";
                $cmd = $org_db_r->createCommand($sql);
                $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
                $res = $cmd->execute();
                sleep(1);
            } while (!empty($res));

            foreach ($lpi as $p) {
                unset($p['id']);
                $res = $org_db_w->createCommand()->insert("lcs_plan_asset", $p);
                if(empty($res)) {
                    $err_print("[失败][{$pln_id}|{$p['symbol']}]导入lcs_plan_asset数据");
                } else {
                    $scc_print("[成功][{$pln_id}|{$p['symbol']}]导入lcs_plan_asset数据");
                }
                sleep(1);
            }
        }

        // 3. lcs_plan_assess信息导入
        $sql = "SELECT * FROM lcs_plan_assess WHERE pln_id=:pln_id";
        $cmd = $lcs_db_r->createCommand($sql);
        $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
        $lpi = $cmd->queryRow();
        if (empty($lpi)) {
            $info_print("[无数据][{$pln_id}]lcs_plan_assess信息为空");
        } else {
            $sql = "DELETE FROM lcs_plan_assess WHERE pln_id=:pln_id LIMIT 1";
            $cmd = $org_db_r->createCommand($sql);
            $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
            $res = $cmd->execute();

            unset($lpi['id']);
            $res = $org_db_w->createCommand()->insert("lcs_plan_assess", $lpi);
            if(empty($res)) {
                $err_print("[失败][{$pln_id}]导入lcs_plan_assess数据");
            } else {
                $scc_print("[成功][{$pln_id}]导入lcs_plan_assess数据");
            }
        }

        // 4. lcs_panic_buy信息导入
        $sql = "SELECT * FROM lcs_panic_buy WHERE relation_id=:relation_id AND type=1";
        $cmd = $lcs_db_r->createCommand($sql);
        $cmd->bindParam(':relation_id', $pln_id, PDO::PARAM_INT);
        $lpi = $cmd->queryRow();
        if (empty($lpi)) {
            $info_print("[无数据][{$pln_id}]lcs_panic_buy信息为空");
        } else {
            $sql = "DELETE FROM lcs_panic_buy WHERE relation_id=:relation_id AND type=1 LIMIT 1";
            $cmd = $org_db_r->createCommand($sql);
            $cmd->bindParam(':relation_id', $pln_id, PDO::PARAM_INT);
            $res = $cmd->execute();

            unset($lpi['id']);
            $res = $org_db_w->createCommand()->insert("lcs_panic_buy", $lpi);
            if(empty($res)) {
                $err_print("[失败][{$pln_id}]导入lcs_panic_buy数据");
            } else {
                $scc_print("[成功][{$pln_id}]导入lcs_panic_buy数据");
            }
        }

        // 5. lcs_plan_transactions信息导入
        $sql = "SELECT * FROM lcs_plan_transactions WHERE pln_id=:pln_id";
        $cmd = $lcs_db_r->createCommand($sql);
        $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
        $lpi = $cmd->queryAll();
        if (empty($lpi)) {
            $info_print("[无数据][{$pln_id}]lcs_plan_transactions信息为空");
        } else {
            do {
                $sql = "DELETE FROM lcs_plan_transactions WHERE pln_id=:pln_id LIMIT 1000";
                $cmd = $org_db_r->createCommand($sql);
                $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
                $res = $cmd->execute();
                sleep(1);
            } while (!empty($res));

            foreach ($lpi as $p) {
                unset($p['id']);
                $res = $org_db_w->createCommand()->insert("lcs_plan_transactions", $p);
                if(empty($res)) {
                    $err_print("[失败][{$pln_id}|{$p['symbol']}]导入lcs_plan_transactions数据");
                } else {
                    $scc_print("[成功][{$pln_id}|{$p['symbol']}]导入lcs_plan_transactions数据");
                }
                sleep(1);
            }
        }
        
        // 6. lcs_plan_profit_stats信息导入
        $sql = "SELECT * FROM lcs_plan_profit_stats WHERE pln_id=:pln_id";
        $cmd = $lcs_db_r->createCommand($sql);
        $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
        $lpi = $cmd->queryRow();
        if (empty($lpi)) {
            $info_print("[无数据][{$pln_id}]lcs_plan_profit_stats信息为空");
        } else {
            $sql = "DELETE FROM lcs_plan_profit_stats WHERE pln_id=:pln_id LIMIT 1";
            $cmd = $org_db_r->createCommand($sql);
            $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
            $res = $cmd->execute();

            unset($lpi['id']);
            $res = $org_db_w->createCommand()->insert("lcs_plan_profit_stats", $lpi);
            if(empty($res)) {
                $err_print("[失败][{$pln_id}]导入lcs_plan_profit_stats数据");
            } else {
                $scc_print("[成功][{$pln_id}]导入lcs_plan_profit_stats数据");
            }
        }

        // 7. lcs_plan_profit_stats_history信息导入
        $sql = "SELECT * FROM lcs_plan_profit_stats_history WHERE pln_id=:pln_id";
        $cmd = $lcs_db_r->createCommand($sql);
        $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
        $lpi = $cmd->queryAll();
        if (empty($lpi)) {
            $info_print("[无数据][{$pln_id}]lcs_plan_profit_stats_history信息为空");
        } else {
            do {
                $sql = "DELETE FROM lcs_plan_profit_stats_history WHERE pln_id=:pln_id LIMIT 1000";
                $cmd = $org_db_r->createCommand($sql);
                $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
                $res = $cmd->execute();
                sleep(1);
            } while (!empty($res));

            foreach ($lpi as $p) {
                unset($p['id']);
                $res = $org_db_w->createCommand()->insert("lcs_plan_profit_stats_history", $p);
                if(empty($res)) {
                    $err_print("[失败][{$pln_id}|{$p['profit_date']}]导入lcs_plan_profit_stats_history数据");
                } else {
                    $scc_print("[成功][{$pln_id}|{$p['profit_date']}]导入lcs_plan_profit_stats_history数据");
                }
                sleep(1);
            }
        }

        // 8. ....
        
        $scc_print("[成功]导入数据结束");
        $scc_print("");
    }

    public function actionSignUpT1($cid=66)
    {
        $redis_key="lcs_investment_adviser_sign_up_planner";
        $s_uids = Yii::app()->redis_r->sMembers($redis_key);
        print_r("\n");
        if (!empty($s_uids)) {
            if (empty($cid)) {
                $sql = "SELECT s_uid,name FROM lcs_planner WHERE s_uid IN (" . implode(',', $s_uids) . ")";
            } else {
                $sql = "SELECT s_uid,name FROM lcs_planner WHERE company_id = {$cid} AND s_uid IN (" . implode(',', $s_uids) . ")";
            }
            // print("\n$sql\n");
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            foreach ($res as $row) {
                print_r("{$row['s_uid']}\t{$row['name']}\n");
            }
            print_r("\n total: " . count($res) . "\n\n");
        } else {
            print_r("nothing need to do\n");
        }
    }

    // 有过计划或具备证券资格的理财师列表
    public function actionAPlannerList($test=1)
    {
        $msg = "云集全国牛人，展现投顾风采！新浪财经拟于10月21日举行第五届全国投顾大赛，打造千万身价投顾孵化计划！速速登录理财师管理后台报名吧！http://t.cn/Rvk9RVJ，客服热线：010-82244469";
        $msg = iconv("UTF-8", "GB2312//IGNORE", $msg);
        // var_dump(CommonUtils::sendSms("15222871897", $msg));
        // var_dump(CommonUtils::sendSms("13810572780", $msg));
        // exit;

        $sql = "select s_uid,name,phone,cert_id from lcs_planner where status=0";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($res)) {
            print_r("nothing need to do.\n");
            return;
        }
        $planners_arr = array_chunk($res, 500);
        foreach ($planners_arr as $planners) {
            $p_ids = array_map(function($p) {return $p['s_uid'];}, $planners);
            $planner_plans = Plan::model()->getPlannerPlanIds($p_ids, "status IN (3,4,5,6,7)");
            foreach ($planners as $planner) {
                print_r("[{$planner['phone']}]\t\t");

                $s_uid = $planner['s_uid'];
                $name = $planner['name'];
                $phone = trim($planner['phone']);
                $cert_id = $planner['cert_id'];
                
                $plns = isset($planner_plans[$planner['s_uid']]) ? count($planner_plans[$planner['s_uid']]) : 0;

                if (($plns>0) || ($planner['cert_id']==1)) {
                    print_r("{$s_uid}\t{$name}\t{$phone}\t{$cert_id}\t{$plns}\t");
                    if ($test==99999) {
                        $status = CommonUtils::sendSms($phone, $msg);
                    } else {
                        $status = "test";
                    }
                    print_r("\t[{$status}]\n");
                } else {
                    print_r("\n");
                }
            }
        }
    }

    // 修复观点体验卡价格
    public function actionRepairPropPrice()
    {
        $sql = "select id,type,relation_id from lcs_prop where relation_price=0 and type=3002";
        $props = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (!empty($props)) {
            $price_map = array();

            $view_ids = array();
            foreach ($props as $row) {
                $view_ids[] = $row['relation_id'];
            }
            $sql = "select id,subscription_price from lcs_view where id in (". implode(",", $view_ids) .")";
            $res_price = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if (!empty($res_price)) {
                foreach ($res_price as $row) {
                    $price_map[$row['id']] = $row['subscription_price'];
                }
            }

            $sql = "";
            foreach ($props as $row) {
                if (isset($price_map[$row['relation_id']])) {
                    $sql .= "UPDATE lcs_prop SET relation_price={$price_map[$row['relation_id']]} WHERE id={$row['id']}; ";
                }
            }
            print_r("\n{$sql}\n");
            Yii::app()->lcs_w->createCommand($sql)->execute();
        }

    }

    public function actionInitPlanOptStyle()
    {
        $sql = "SELECT pln_id,stop_loss FROM lcs_plan_info WHERE opt_style=0";
        $plan = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($plan)) {
            foreach ($plan as $p) {
                $stop_line = abs($p['stop_loss']);
                $init_val = 0;

                if ($stop_line >= 0.01 && $stop_line <= 0.05) {
                    $init_val = 262144;
                } elseif ($stop_line > 0.05 && $stop_line <= 0.1) {
                    $init_val = 524288;
                } elseif ($stop_line > 0.1 && $stop_line <= 0.2) {
                    $init_val = 1048576;
                } elseif ($stop_line > 0.2 && $stop_line <= 0.3) {
                    $init_val = 2097152;
                } elseif ($stop_line > 0.3 && $stop_line <= 0.5) {
                    $init_val = 4194304;
                } else {}

                if ($init_val > 0) {
                    $sql = "UPDATE lcs_plan_info SET opt_style={$init_val} WHERE pln_id={$p['pln_id']}";
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                }
            }
        }
    }

    public function actionBatchCreateUserNameDB($gender='m', $num=1, $times=10)
    {
        $gender_ruler = UserNameService::$name_ruler[$gender];

        while ($times-- > 0) {
            if (empty($gender_ruler)) {
                break;
            } else {
                $curr_ruler = UserNameService::getRandomDicValue($gender_ruler);
                $curr_ruler = $curr_ruler['0'];
            }

            $begin_time = microtime(TRUE);
            $name = UserNameService::getRandomUserNameV2($gender, $curr_ruler, $num);
            $end_time = microtime(TRUE);
            $used_time = number_format($end_time - $begin_time, 4);

// print_r($name);
            if (empty($name)) {
                $index = array_search($curr_ruler, $gender_ruler);
                if ($index !== false) {
                    array_splice($gender_ruler, $index, 1);
                }
                printf(date('Y-m-d H:i:s').": [{$gender}:%7s] [{$used_time}s] create name ".count($name)."\n", $times+1);
                continue;
            }

            $values = "";
            foreach ($name as $n) {
                $values .= "('{$n}','{$gender}'),";
            }
            if (!empty($values)) {
                $values = rtrim($values,',');

                $transaction = Yii::app()->lcs_w->beginTransaction();
                try {
                    $sql = "insert into test_lcs_user_name (`name`,`gender`) values {$values}";
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                    $transaction->commit();
                    printf(date('Y-m-d H:i:s').": [{$gender}:%7s] [{$used_time}s] create name ".count($name)."\n", $times+1);
                } catch (Exception $e) {
                    $transaction->rollback();
                    printf(date('Y-m-d H:i:s').": [{$gender}:%7s] [FAIL] update name\n".$e->getMessage()."\n", $times+1);
                }
            }
        }
    }
    public function actionBachUpdateRepeatUsername($table_index='0')
    {
        print_r("\n");

        $sql = "select uid,name,count(*) as cc from lcs_user_{$table_index} where u_time>='2016-01-01 00:00:00' and name_u_time='0000-00-00 00:00:00' and name!=wb_name group by name having cc>1;";
        $user_info = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($user_info)) {
            printf(date('Y-m-d H:i:s').": update user name\t...........................\t[ABORT - because nothing need to do]\n");
            return ;
        }

        $sql = "";
        foreach ($user_info as $ui) {
            $u_time = '2000-12-31 00:00:00';
            $tmp_name = $ui['name'].'9';

            $sql_1 = "UPDATE `licaishi`.`lcs_user_{$table_index}` SET `name` = '{$tmp_name}',`name_u_time` = '{$u_time}' WHERE `lcs_user_{$table_index}`.`uid` = ".intval($ui['uid']);
            $sql_2 = "UPDATE `licaishi`.`lcs_user_index` SET `name` = '{$tmp_name}' WHERE `lcs_user_index`.`id` = ".intval($ui['uid']);
            $sql .= $sql_1.";".$sql_2.";";
        }
        if (!empty($sql)) {
            $transaction = Yii::app()->lcs_w->beginTransaction();
            try {
                $res = Yii::app()->lcs_w->createCommand($sql)->execute();

                $transaction->commit();
                printf(date('Y-m-d H:i:s').": update user:%s\t...........................\t[OK]\n", $res);
            } catch (Exception $e) {
                $transaction->rollback();
            }
        }
    }
    public function actionBatchUpdateOldUsername($table_index='0', $gender='m', $num=1, $times=1)
    {
        // 清除状态
        // UPDATE `licaishi`.`lcs_user_0` SET `name_u_time` = '0000-00-00' where name_u_time='2015-12-31 00:00:00'
        
        printf("\n");
        while ($times-- > 0) {
            $num=10000;

            $sql = "SELECT uid FROM `lcs_user_{$table_index}` WHERE `gender` = '{$gender}' AND `name_u_time` = '0000-00-00 00:00:00' AND `u_time` < '2016-01-01 00:00:00' LIMIT 0,{$num}";
            $user_info = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if (empty($user_info)) {
                printf(date('Y-m-d H:i:s').": update user name\t...........................\t[ABORT - because nothing need to do]\n");
                break;
            }

            $erwei = array_chunk($user_info, 200);
            $user_info = null;
            foreach ($erwei as $user_info) {
                $sql = "";
                foreach ($user_info as $ui) {
                    $u_time = '2015-12-31 00:00:00';
                    $tmp_name = CommonUtils::getShowName($ui['uid']);

                    $sql_1 = "UPDATE `licaishi`.`lcs_user_{$table_index}` SET `name` = '{$tmp_name}',`name_u_time` = '{$u_time}' WHERE `lcs_user_{$table_index}`.`uid` = ".intval($ui['uid']);
                    $sql_2 = "UPDATE `licaishi`.`lcs_user_index` SET `name` = '{$tmp_name}' WHERE `lcs_user_index`.`id` = ".intval($ui['uid']);

                    $sql .= $sql_1.";".$sql_2.";";
                }

                if (!empty($sql)) {
                    $transaction = Yii::app()->lcs_w->beginTransaction();
                    try {
                        $res = Yii::app()->lcs_w->createCommand($sql)->execute();

                        $transaction->commit();
                        printf(date('Y-m-d H:i:s').": update user:%s\t...........................\t[OK]\n", $res);
                    } catch (Exception $e) {
                        $transaction->rollback();
                    }
                }

            }
        }
    }
    public function actionDelUsernameDicIndex()
    {
        UserNameService::clearRulerDicIndex();
    }
    public function actionBatchUpdateUserName($table_index='0', $gender='m', $num=1, $times=1)
    {
        // 清除状态
        // UPDATE `licaishi`.`lcs_user_0` SET `name_u_time` = '0000-00-00' where name_u_time='2016-01-01 00:00:00'
        
        $gender_ruler = UserNameService::$name_ruler[$gender];

        printf("\n");
        while ($times-- > 0) {
            if (empty($gender_ruler)) {
                printf(date('Y-m-d H:i:s').": update user name\t...........................\t[ABORT - because all name used up]\n");
                break;
            } else {
                $curr_ruler = UserNameService::getRandomDicValue($gender_ruler);
                $curr_ruler = $curr_ruler['0'];
            }

            $num=10000;

            $sql = "SELECT uid FROM `lcs_user_{$table_index}` WHERE `gender` = '{$gender}' AND `name_u_time` = '0000-00-00 00:00:00' AND `u_time` >= '2016-01-01 00:00:00' LIMIT 0,{$num}";
            $user_info = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if (empty($user_info)) {
                printf(date('Y-m-d H:i:s').": update user name\t...........................\t[ABORT - because nothing need to do]\n");
                break;
            }

            $erwei = array_chunk($user_info, 200);
            $user_info = null;
            foreach ($erwei as $user_info) {
                    
                $user_count = count($user_info);
                $name_arr = UserNameService::getRandomUserNameV2($gender, $curr_ruler, $user_count);
                if (empty($name_arr)) {
                    $index = array_search($curr_ruler, $gender_ruler);
                    if ($index !== false) {
                        array_splice($gender_ruler, $index, 1);
                    }
                    printf(date('Y-m-d H:i:s').": update user name\t...........................\t[ABORT - because ruler %7s name used up]\n", $curr_ruler);
                    continue;
                }

                $sql = "";
                foreach ($user_info as $ui) {
                    $u_time = '2016-01-01 00:00:00';
                    if (empty($name_arr)) {
                        break;
                    } else {
                        $tmp_name = array_shift($name_arr);

                        $sql_1 = "UPDATE `licaishi`.`lcs_user_{$table_index}` SET `name` = '{$tmp_name}',`name_u_time` = '{$u_time}' WHERE `lcs_user_{$table_index}`.`uid` = ".intval($ui['uid']);
                        $sql_2 = "UPDATE `licaishi`.`lcs_user_index` SET `name` = '{$tmp_name}' WHERE `lcs_user_index`.`id` = ".intval($ui['uid']);
                        
                        $sql .= $sql_1.";".$sql_2.";";
                    }
                }
                if (!empty($sql)) {
                    $transaction = Yii::app()->lcs_w->beginTransaction();
                    try {
                        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
                        $transaction->commit();
                        printf(date('Y-m-d H:i:s').": update user:%s\t...........................\t[OK]\n", $res);
                    } catch (Exception $e) {
                        $transaction->rollback();
                    }
                }

            }
        }
    }


    // 删除一个 weibo ID 对应多个 uid (保留最小uid。其他订单产生订单等数据时，放弃删除操作)
    public function actionDelRepeatUser($num=1, $if_del=0) {
        $sql = "select s_uid from (SELECT s_uid, count(*) as cc FROM `lcs_user_index` group by s_uid having cc > 1) as tt limit 0,{$num}";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($res)) {
            print_r(date('Y-m-d H:i:s').": delete repeat user\t.....................\t[nothing need to do]\n");
            return 0;
        }
        
        foreach ($res as $u) {
            $sql = "SELECT id,s_uid  FROM `lcs_user_index` WHERE `s_uid` = ".intval($u['s_uid'])." ORDER BY `lcs_user_index`.`id` ASC";
            $userdel = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            unset($userdel['0']);
            foreach ($userdel as $ud) {
                $sql = "SELECT count(*) as cc FROM `lcs_collect` WHERE `uid` = ".intval($ud['id']);
                $if_collect = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
                if ($if_collect > 0) {
                    print_r(date('Y-m-d H:i:s').": delete repeat user(s_uid={$ud['s_uid']},uid={$ud['id']})\t.....................\t[ABORT - because have collect]\n");
                    continue;
                }

                $sql = "SELECT count(*) as cc  FROM `lcs_orders` WHERE `uid` = ".intval($ud['id']);
                $if_collect = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
                if ($if_collect > 0) {
                    print_r(date('Y-m-d H:i:s').": delete repeat user(s_uid={$ud['s_uid']},uid={$ud['id']})\t.....................\t[ABORT - because have orders]\n");
                    continue;
                }

                $transaction = Yii::app()->lcs_w->beginTransaction();
                try {
                    // 删除user_index表
                    $sql = "DELETE FROM `licaishi`.`lcs_user_index` WHERE `lcs_user_index`.`id` = ".intval($ud['id']);
                    // print_r($sql."\n");
                    if (intval($if_del)===1) {
                        Yii::app()->lcs_w->createCommand($sql)->execute();
                    }

                    // 删除user_n表
                    $table_index = substr($ud['id'], -1);
                    $sql = "DELETE FROM `licaishi`.`lcs_user_{$table_index}` WHERE `lcs_user_{$table_index}`.`uid` = ".intval($ud['id']);
                    // print_r($sql."\n");
                    if (intval($if_del)===1) {
                        Yii::app()->lcs_w->createCommand($sql)->execute();
                    }

                    $transaction->commit();
                    print_r(date('Y-m-d H:i:s').": delete repeat user(s_uid={$ud['s_uid']},uid={$ud['id']})\t.....................\t[OK]\n");
                } catch(exception $e) {
                    $transaction->rollback();
                    print_r(date('Y-m-d H:i:s').": delete repeat user(s_uid={$ud['s_uid']},uid={$ud['id']})\t.....................\t[FAIL - delete error]\n");
                }
            }
        }
        // print_r("\n");
    }

    //./protected/yiic test sortUserByDay --limit=1 >> ./log/SortUserByDay_20150830.log
    public function actionSortUserByDay($limit=5){
        $start_time='2014-07-01 00:00:00';
        $sqls = array(
            //免费提问的用户数
            'free_ask' => "SELECT COUNT(DISTINCT(uid)) as num FROM lcs_ask_question WHERE STATUS>0 AND is_price=0 AND c_time>='".$start_time."' AND c_time<=:end_time;",
            //免费解锁的用户数
            'unlock_free_ask' => "SELECT COUNT(DISTINCT(u.uid)) as num FROM lcs_unlock u LEFT JOIN lcs_ask_question aq ON u.`relation_id`=aq.`id` WHERE u.type=1 AND aq.is_price=0 AND u.c_time>='".$start_time."' AND u.c_time<=:end_time;",
            //免费观点包关注的用户数
            //'collect_free_package' => "SELECT DATE(c.c_time) AS sort_date, COUNT(c.uid) as num FROM lcs_collect c LEFT JOIN lcs_package p ON c.`relation_id`=p.`id` WHERE  c.`type`=4 AND p.`subscription_price`=0.00 AND  c.c_time>='2014-7-1' AND c.c_time<='2015-6-30 23:59:59' GROUP BY sort_date ASC;",
            'collect_free_package' => "SELECT COUNT(DISTINCT(c.uid)) as num FROM lcs_collect c WHERE  c.`type`=4 AND  c.c_time>='".$start_time."' AND c.c_time<=:end_time;",
            //免费观点解锁的用户数
            'unlock_free_view' => "SELECT COUNT(DISTINCT(uid)) as num FROM lcs_view_subscription  WHERE subscription_price=0.00 AND c_time>='".$start_time."' AND c_time<=:end_time;",
        );

        echo 'sort_date|';
        foreach(array_keys($sqls) as $k){
            echo $k,'|';
        }


        $_end_time = '2015-07-01 23:59:59';

        for($i=0; $i<$limit; $i++){
            $end_time = date('Y-m-d H:i:s',strtotime($_end_time)+($i*24*3600));
            $end_date = date('Y-m-d',strtotime($end_time));
            $result=array();
            foreach($sqls as $key=>$sql){
                $res = Yii::app()->lcs_standby_r->createCommand($sql)->bindParam(':end_time',$end_time,PDO::PARAM_STR)->queryRow();
                $result[$key] = !empty($res)&&isset($res['num'])?$res['num']:0;
            }

            echo "\n",$end_date,'|';
            foreach(array_keys($sqls) as $k){
                echo $result[$k],'|';
            }
        }
    }

    public function actionPushOldMessage(){
        $redis_key="lcs_new_view_message";
        $redis_value="407190";
        Yii::app()->redis_w->rPush($redis_key,$redis_value);
    }

    public function actionDelMessage(){

        $sql = 'delete from lcs_message where type=3 and relation_id=3880 and content_client like \'{"type":4%\' and c_time>\'2016-02-15 11:20:12\' and c_time<';
        for($i=0;$i<30;$i++){
            $e_time=date("Y-m-d H:i:s",strtotime("2016-02-15 13:16:12")+60*$i);
            $sql_str = $sql."'".$e_time."';";
            echo "cur_time:",date("Y-m-d H:i:s")," sql:",$sql_str,"\r\n";
            $count = Yii::app()->lcs_w->createCommand($sql_str)->execute();
            echo "count:",$count,"\r\n";
            sleep(2);
        }
    }


    public function actionPushMessage($type){
        $msg_data=array();
        if($type=='questionAnswer'){
            $msg_data['type']='questionAnswer';
            $msg_data['q_id']=488131;
            $msg_data['answer_id']=307798;
        }if($type=='questionScore'){
            //q_id  p_uid  score  uid u_name u_image score_reason
            $msg_data['type']='questionScore';
            $msg_data['q_id']=488131;
            $msg_data['p_uid']=2374359905;
            $msg_data['score']=5;
            $msg_data['score_reason']='评价测试';
            $msg_data['uid']=3;
            $msg_data['u_name']='财友12345';
            $msg_data['u_image']='http://tp2.sinaimg.cn/1951492121/50/22817433295/1';
        }else if($type=='commentPraise'){
            //type=commentPraise  cmn_id  cmn_type  u_type  uid  cur_uid cu_name cu_image
            $msg_data['type']='commentPraise';
            $msg_data['cmn_id']=11292;
            $msg_data['cmn_type']=1;
            $msg_data['u_type']=2;
            $msg_data['uid']=1190872560;
            $msg_data['cu_name']='财友12345';
            $msg_data['cu_image']='http://tp2.sinaimg.cn/1951492121/50/22817433295/1';
        }else if($type=='replayComment'){
            $msg_data['type']='replayComment';
            $msg_data['cmn_id']=11544;
        }else if($type=='planChange'){
            $msg_data['type']='planChange';
            $msg_data['pln_id']='28391';
        }else if($type=='planTransaction'){
            $msg_data['type']='planTransaction';
            $msg_data['tran_id']='17';
            $redis_key='lcs_fast_message_queue';
        }else if($type=='replayCommentNew'){
            $msg_data['type']='replayCommentNew';
            $msg_data['cmn_id']=11544;
        }else if($type=='newView'){
            $msg_data['type']='newView';
            $msg_data['v_id']=407380;
        }else if($type=='packageGCNew'){
            $msg_data=array("type" => "packageGCNew", "pkg_id" =>1108,'uids'=>array(13116008,171428866,10000180));
        }

        if(!empty($msg_data)){
            $redis_key=empty($redis_key)?'lcs_common_message_queue':$redis_key;
            Yii::app()->redis_w->rPush($redis_key,json_encode($msg_data,JSON_UNESCAPED_UNICODE));
            echo 'send ok type:'.$type;
        }else{
            echo 'not find type:'.$type;
        }

    }

    public function actionExecMessageHandler($type){
        Yii::import('application.commands.message.*');
        Yii::import('application.commands.msgQueueHandler.*');
        $handler = null;
        $msg_data = array();
        switch($type){
            case 'packageGCNew':
                $handler = new PackageGCNewMessageHandler();
                $msg_data=array("type" => "packageGCNew", "pkg_id" =>1108,'uids'=>array(13116008,171428866,10000180));
                break;
            case 'packageGCNotice':
                $handler = new PackageGCNoticeMessageHandler();
                $msg_data=array("type" => "packageGCNotice", "pkg_id" =>1108);
                break;
            case 'packageGCReply':
                $handler = new PackageGCReplyMessageHandler();
                $msg_data=array("type" => "packageGCReply", "pkg_id" =>1108,"cmn_id"=>9);
                break;
            case 'planGCNew':
                $handler = new PlanGCNewMessageHandler();
                $msg_data=array("type" => "planGCNew", "pln_id" =>100698,'uids'=>array(13115994));
                break;
            case 'planGCNotice':
                $handler = new PlanGCNoticeMessageHandler();
                $msg_data=array("type" => "planGCNotice", "pln_id" =>100698);
                break;
            case 'planGCReply':
                $handler = new PlanGCReplyMessageHandler();
                $msg_data=array("type" => "packageGCReply", "pln_id" =>100698,"cmn_id"=>125);
                break;
            case 'plannerToUserNotice':
                $handler = new PlannerToUserNoticeMessageHandler();
                $msg_data=array("type" => "plannerToUserNotice", "n_id" =>15);
                break;
            case 'createLiveNotice':
                $handler = new CreateLiveNoticeMessageHandler();
                $msg_data=array("type" => "createLiveNotice", "live_id" =>14, "to_u_type" => 1);
                break;
            case 'operateNotice':
                $handler = new OperateNoticeMessageHandler();
                //'notice_type','content','relation_id','url'
                $msg_data=array('type'=>'operateNotice',"notice_type"=>'12','content'=>"测试h5类型的小妹通知",'relation_id'=>"",'url'=>"http://licaishi.sina.com.cn");
                $msg_data['uids']='171429059';
                $msg_data['u_type']='2';
                break;
            case 'packageChange':
                $handler = new PackageChangeMessageHandler();
                //type=packageChange 'pkg_id',"pkg_name","p_uid","status",'reason'
                $msg_data=array('type'=>'packageChange',"pkg_id"=>'1108','pkg_name'=>"赵伟广付费",'p_uid'=>"1190872560",'status'=>"-3","reason"=>"观点包名称不合法");
                break;
            case 'circleMsg':
                $handler = new PlannerCircleMessageHandler();
                $msg_data = array('type' => 'plannerCircle', 'cmn_type' => 71, 'relation_id' => 36792, 'cmn_id' => 140);
                break;
            case 'circleNotice':
                $handler = new PlannerCircleNoticeMessageHandler();
                $msg_data = array('type' => 'plannerCircleNotice', 'n_id' => 259);
                break;
            case 'circleLiveNoticeStart':
                $handler = new PlannerCircleLiveNoticeStartMessageHandler();
                $msg_data = array('type' => 'plannerCircleLiveNoticeStart', 'n_id' => 258);
                break;
            case 'circleJoin':
                $handler = new PlannerCircleJoinMessageHandler();
                $msg_data = array('type' => 'plannerCircleJoin', 'uid'=>171429372, "p_uid"=>2318006357);
                break;
            case 'MomentsPlannerAttention':
                $handler = new MomentsPlannerAttentionMessageHandler();
                $msg_data = array('type'=>'momentsPlannerAttention', 'uid'=>171429372, "p_uid"=>2318006357, "attention"=>0);
                break;
            case 'Audio':
                $handler = new AudioMessageHandler();
                $msg_data = array('type'=>'Audio', 'relation_id'=>6150188584, "plannerName"=>'王建','id'=>'83');
            default:
                break;
        }


        if(!empty($handler)){
            $handler->run($msg_data);
        }else{
            echo 'not find '.$type;
        }


    }


    public function actionPush(){
        Yii::import('application.extensions.getui.*');
        Yii::import('application.commands.message.*');
        Yii::import('application.commands.msgQueueHandler.*');

        $msg='{"type":"operateNotice","notice_type":3,"content":"个推测试","relation_id":"1153","url":"http:\/\/licaishi.sina.com.cn","uids":"1930770607","channel":0}';
        $messageQueue = new MessageQueue();
        $messageQueue->processMessage($msg,"lcs_common_message_queue");

        //$msg='{"channel_type":4,"push_message":{"uid":"","u_type":1,"type":12,"relation_id":0,"child_relation_id":0,"content":[{"value":"个推测试","class":"","link":"http:\/\/licaishi.sina.com.cn"}],"link_url":"http:\/\/licaishi.sina.com.cn","c_time":"2016-03-14 19:38:02","u_time":"2016-03-14 19:38:02","content_client":"{\"t\":3,\"pln_id\":\"\",\"id\":\"1153\"}"},"push_user":[{"channel_type":"5","channel_id":"a259f1f4e77627089054bfa465f6d016","u_type":"1","s_uid":"1930770607","uid":"171428871"}]}';
        //$getuiPushQueue = new GetuiPushQueue();
        //$getuiPushQueue->sendMessage($msg);
    }


    public function actionGetui(){
        Yii::import('application.extensions.getui.*');
        Yii::import('application.commands.message.*');

        //$cid="6e9c299c316a5bf01ed5297bdf9ad8ae";
        //$cid="e8b931c0ea26bf4dfaa17901cb5c1e18";


        //$getui = new GetuiServiceApi();
        //$tpl = $getui->getLinkTemplateOfAndroid("通知测试","通知测试","http://licaishi.sina.com.cn");
        //$result = $getui->pushMessageToSingle($cid,$tpl);

        //$batchs = array('e8b931c0ea26bf4dfaa17901cb5c1e18'=>$tpl,'6e9c299c316a5bf01ed5297bdf9ad8ae'=>$tpl);
        //$result = $getui->pushMessageToSingleBatch($batchs);

        //$cnt_json=array("type"=>"1","alert"=>"透传消息","data"=>array("id"=>"123","uid"=>111));
        //$msg = json_encode($cnt_json);
        //$transmission_tpl = $getui->getTransmissionTemplateOfAndroid($msg);
        //$result = $getui->pushMessageToList(array('e8b931c0ea26bf4dfaa17901cb5c1e18','6e9c299c316a5bf01ed5297bdf9ad8ae'),$transmission_tpl);
        //var_dump($result);
        $getuiPush = new GetuiPushQueue();
        $msg='{"channel_type":2,"push_message":{"uid":"11477158","u_type":1,"type":2,"relation_id":"1679","child_relation_id":"427901","content":"[{\"value\":\"《点股成金》\",\"class\":\"\",\"link\":\"\\\/web\\\/packageInfo?pkg_id=1679\"},{\"value\":\"内更新了一条观点\",\"class\":\"\",\"link\":\"\"},{\"value\":\"：【今日一股】除权之前，有望加速拉升！\",\"class\":\"\",\"link\":\"\\\/view\\\/427901?ind_id=1\"}]","content_client":"{\"package_title\":\"点股成金\",\"view_title\":\"【今日一股】除权之前，有望加速拉升！\",\"summary\":\"个股题材：高送转股价表现：该股本周三连阳，股价为除权行情，加速拉升。\",\"ind_id\":\"1\",\"p_uid\":\"1160176332\",\"planner_name\":\"陈惠\",\"planner_image\":\"http:\\\/\\\/tp1.sinaimg.cn\\\/1160176332\\\/180\\\/40059601468\\\/0\",\"company\":\"福建天信投资\"}","link_url":"\/view\/427901?ind_id=1","c_time":"2016-03-11 15:51:14","u_time":"2016-03-11 15:51:14"},"push_user":[{"channel_type":"2","channel_id":"6e9c299c316a5bf01ed5297bdf9ad8ae","u_type":"1","s_uid":"5165469666","uid":"11477158"},{"channel_type":"2","channel_id":"e8b931c0ea26bf4dfaa17901cb5c1e18","u_type":"1","s_uid":"5165469666","uid":"11477158"}]}';
        $getuiPush->sendMessage($msg);
        //$getuiPush->processMessage();
    }



    public function actionWxPush(){
        Yii::import('application.commands.message.*');
        $arr = array('3'=>'onMsdsz0EjaTvFYh8h29hvurdZL4',
            '92'=>'onMsdsyFhL_8rqP1C4PRBuVLSPHI',
            '15651719'=>'onMsdswQtDHzH7NX-DTMKHhGiSvk',
            /*'10008516'=>'onMsds-RcpcJSWcHeNdL7avU1MgU',
            '10014389'=>'onMsds61sAuBwEVR7N3EyBF5snH8',
            '10030145'=>'onMsds8lNuFBRf1vWEQcq_TiVdfg',
            '10032418'=>'onMsdsynFWMx7RymY9Un6Th7ISeI',
            '10067570'=>'onMsds9K4OZJoTVm-S5uLR6ZOriw',
            '10067878'=>'onMsds0OpEgTGPgPiRqF-V9M3asA',
            '10077713'=>'onMsdsxXUJjy16Vsf4LRwjpO01FA',
            '10082685'=>'onMsds-zZbqY33wrUfAAZ0Mybqs4',
            '10085985'=>'onMsds5UkdVK194cnwrgVxSxPj5M',
            '10087602'=>'onMsds-90C_hNQcpqHQFslSq47xg',
            '10092920'=>'onMsds9gZAvWujGir5rIutVwO4lE',
            '10098451'=>'onMsds1W4GazwESV7dP3GodhL4hI',
            '10126557'=>'onMsdsy6OQ7HYSGRbEmwKo0fp_Zw',
            '10133652'=>'onMsds9BvIydHNi1PpNt7ZwMIzC8',
            '10149941'=>'onMsdsxsBmFwmMhwHrgxlQVfDG1o',
            '10187514'=>'onMsds5clv3MZCdrZIFt8VxGtk8o',
            '10188163'=>'onMsdswPqZH_xeFFcp5igwrgLSSw',
            '10192237'=>'onMsds5FURNnA2HehsG555BORbH8',
            '10211886'=>'onMsds7ItIqEqtqmHREiZGYoYkds',
            '10222422'=>'onMsdswP6YfOE6AOWA_7jfl5O5Hk',
            '10223235'=>'onMsds8Kb9Ep3-tzCnfU_q7sczjs',
            '10223307'=>'onMsdswRpwTKQhJpEu0OHlnjCRrg',
            '10227453'=>'onMsds7I1Znw8Ctretyn5t_aMbNo',
            '10259727'=>'onMsds7sQzNj3nPjrr399noTt2GE',
            '10282240'=>'onMsds1N0z7-cbAMiQ9mleXp_YmE',
            '10296384'=>'onMsds6fiXOHXG-v35elQXa-yswE',
            '10313782'=>'onMsds25sje9fUqlNoY6FfMkk2cQ',
            '10322754'=>'onMsds6YyY-uh0bd005TeHfQcuYo',
            '10336216'=>'onMsdszufX3UrcsUft5DNIK8goMU',
            '10341597'=>'onMsds62PBpTbX22XFvaRiYNQ4uk',
            '10345734'=>'onMsds7f616VxfK5vDmhih5bpnb4',
            '10352767'=>'onMsds-6ywXaECC_Mdtupuva2Ahw',
            '10364249'=>'onMsds8BudcXj108uoJ0dpcsywd0',
            '10375077'=>'onMsds0esz8u6_2VshWhQOiD60Vs',
            '10380111'=>'onMsds5P0qW8iEDHsbFheR9Snp14',
            '10406136'=>'onMsds7Yz83meM5NHQNQGTpzzjEM',
            '10421056'=>'onMsds1EgQdZmI_j3WbfiaswbESc',
            '10447692'=>'onMsds-eNAd4X5IohiVN5rGIJwtk',
            '10447733'=>'onMsds5-eUspbtRbGSVO9CZVjfJ8',
            '10469356'=>'onMsdsxtcOrEjPQDFw3Ww7IY6C3M',
            '10488885'=>'onMsds5yL9es2POCUbScZhGcBGiY',
            '10591121'=>'onMsds8uGTHMSICFWZ_O6OOv-6EM',
            '10648499'=>'onMsds4rzNWJRjqOYVWkA_EoJrwk',
            '10650991'=>'onMsds5vZTGYdeatbADrv0ftmsu4',
            '10726942'=>'onMsdsxjdctyztnPgi3pAprU3CSE',
            '10730815'=>'onMsds9UtZY9yiW6c1-toUupXQFc',
            '10751059'=>'onMsds9aoCZWK0HIr2Do22Nw-hns',
            '10792510'=>'onMsds-efyyHLOYje8PPi4CmeUH4',
            '10793474'=>'onMsds-j9wqzHGHOSbW0sPLrYQwI',
            '10797640'=>'onMsds7-sSEUo0KM_rjoLn05OFNw',
            '10806727'=>'onMsds7G3x6lkUsxPfIS4gPdI_-I',
            '10811081'=>'onMsds1Pi4KkWGi5qrmtdZgGhb1w',
            '10829574'=>'onMsds1_PlEC6kQspFqUBSM0TFLQ',
            '10830906'=>'onMsds48_iMuPCaXKP1qxI4Rs9hQ',
            '10833860'=>'onMsds3PUFOOTZgTniI9Ko-rCcbU',
            '10835473'=>'onMsds4Eh4_WqKbHGiuKP4hVIWYg',
            '10838191'=>'onMsds65VNF00K0RTIxgLJ0lvvzw',
            '10839642'=>'onMsdswU33EnjKgkmek3O1IFZ2X0',
            '10842394'=>'onMsds-vqNj_th6-pgC4gdo2_K5Y',
            '10862176'=>'onMsds06t_X1LuKrjV05R-6bKOeQ',
            '10862417'=>'onMsdsy2aIJTN9uYSFD9ep6fEFEM',
            '10877612'=>'onMsds6vgqEDcEW7ybWAkMD0kmL8',
            '10880821'=>'onMsds840Ua7ZLRqshwdm8oZbC1c',
            '10885371'=>'onMsds8eOcNIcOeReNQ4FTZ2-jXo',
            '10897305'=>'onMsds3t3DAmoLnoksw8WqcC5cCo',
            '10902010'=>'onMsdsya12VN4nuZ00SfNjtAMZ_o',
            '10910473'=>'onMsds1IWH9gFUp6D9rF_OgJOgzo',
            '10921331'=>'onMsds6F61cgyplsCCg0I-u0OH4k',
            '10959211'=>'onMsds1FU5-gTBpqLuvtN0hciOMU',
            '10987305'=>'onMsds7RUmcywbADpyMVqV8hWu8s',
            '10993389'=>'onMsdsz4C-WkRC_7nPwuzK9fBB2A',
            '11030627'=>'onMsds3mv9m8nDIkMSD0dnceA7fo',
            '11052218'=>'onMsdsyxte6uo3tOSRQ-f1JmaBMc',
            '11055330'=>'onMsds7-AVHtLOEZiCeaEgMeJBPw',
            '11059885'=>'onMsdswBHM9Z4HioUZjh6i--rkiM',
            '11080231'=>'onMsdsy3DV_cqoWAweytOHtl9AGc',
            '11082315'=>'onMsds6hOXFKADfCWN1wan-hsIu8',
            '11108399'=>'onMsds6b6x9MDowOPKBZPI9PBOlY',
            '11108729'=>'onMsds2UR6Wg5qJktXlNvzsQpe_w',
            '11122471'=>'onMsdsxAH2qbRro5I2Ljjuipzrjk',
            '11127824'=>'onMsdsxYv7i3yfrjRNCaDPTnPTqI',
            '11150634'=>'onMsds4sqVrpuVlLQrGemQCIwyZM',
            '11154266'=>'onMsdsyvbvWxBJx9DafKsjQY6UDE',
            '11157729'=>'onMsds0o9tkOs8CudFlQWrBp2Xwk',
            '11162720'=>'onMsds3tZbbtwJSSf8PszJNbVRsQ',
            '11198992'=>'onMsds5k1dt-gY3UK-y3b86XSxNQ',
            '11207631'=>'onMsds_pZy-UcNehQGHZXHpsRCgQ',
            '11212904'=>'onMsds9Xgz9H7VPkgDRz0ZW0Asm4',
            '11213531'=>'onMsds_Y4AeGfHHpEzVQCPUT4meA',
            '11220130'=>'onMsds9emFyVK58w2blERRInX2v8',
            '11227332'=>'onMsds0c1GtfPka3ioWDJ6pJ8gtk',
            '11229284'=>'onMsds6KPvWv_IrBrRl58tnFYSf8',
            '11253896'=>'onMsds-a5w8rQwzhtUEXSU8YI0PQ',
            '11256482'=>'onMsds979328j1ZkyuIClerqXX9U',
            '11287704'=>'onMsds0MpPVb7ZXWGd6ysmfAbKz0',
            '11303027'=>'onMsds1e7WN1XjIZ_e-Yvn2PlF6s',
            '11309224'=>'onMsds21FoZSwIzIfiQFQd5X7geY',
            '11321620'=>'onMsds7LflagCtnv4KwGJHgkcRS4',
            '11325236'=>'onMsdszkv05U8W2_AgJI7VhL5BjQ',
            '11335118'=>'onMsdsxPz83npoCEWrcS9GGb5imI',
            '11342033'=>'onMsds02esdJpwK8PluHCV9W3-Bg',
            '11345470'=>'onMsdsyCdFKe_o5PaPjj1N8iFFiQ',
            '11352625'=>'onMsds8NoN4d_Nz0bgAceHJsV8Zc',
            '11396454'=>'onMsds2wqwm5tqAJLgWEh3_4j38Q',
            '11405827'=>'onMsds4pS9q8kz494zHF_W8mnSSI',
            '11418387'=>'onMsds-sQnJIEKyG9_bdi9XSgbXQ',
            '11436045'=>'onMsds_suQf5Th9xsM3spIef_mo0',
            '11479958'=>'onMsds7xmLtNjxbjoq0VbTReuH3s',
            '11588056'=>'onMsdswZGYZfs2i6ZZTFhUmi_574',
            '11594219'=>'onMsds2_oRlvNa7EsFGILROB7tqk',
            '11677684'=>'onMsds4UvdMhD96GjOwG6tg2glyM',
            '11682801'=>'onMsds9H8O1D_QxW_YkAsoU0QGGQ',
            '11697089'=>'onMsds1k_PlU-aSTz6lzq95aaO_I',
            '11721277'=>'onMsdszUPqlDy1Fp8FAwQxtooph0',
            '11742467'=>'onMsds8t29hUinb0T0WrITZ5nI4k',
            '11801729'=>'onMsds6DJea6MnvhaqkOftT-Yc9s',
            '11820575'=>'onMsdszUbD8dzTfvovGTLvcru9q8',
            '11903132'=>'onMsds6XeoFbc4qZwUmJOO-Jswws',
            '11933228'=>'onMsds6uonUhrBzUfQd7VHQM_CRI',
            '11952962'=>'onMsdsx0fYGPVWNGRjdMdbA8Ju38',
            '11994196'=>'onMsds2iddfYdZkI04qOO0fmap_c',
            '11994914'=>'onMsds7SrgaIGzvON92BBkNowlVM',
            '12003745'=>'onMsds4I-iuunMNaxmbhDZwGdFzI',
            '12015645'=>'onMsds-NOrHfyrmbyUoP2efO0oLY',
            '12037917'=>'onMsds58BYZ_G0RXwuPPziqqEzHQ',
            '12046258'=>'onMsds6Bry3h-7oDix048VMEJyrU',
            '12050604'=>'onMsds0qh_UYQIGgBCxbv_mAY-U0',
            '12067651'=>'onMsds-8NIzJ_eezD9ZU_00aKqaA',
            '12071402'=>'onMsds1ZGrbaQP251LW2LUTSFJB8',
            '12073240'=>'onMsds8dKX-QxxDTy23QBiU9CdwU',
            '12086300'=>'onMsdswtvD20uHEb0Nizm4ilTAxY',
            '12097466'=>'onMsdsw4TkdofS8w26MK_uHM3hjA',
            '12115400'=>'onMsds5IHx3H9cZ25Zif956zNPjg',
            '12115786'=>'onMsds0tJWOzYK7MRH8RhyCFQXMw',
            '12123164'=>'onMsdsw_18XrpiznS-ceMt5bol0k',
            '12127412'=>'onMsds1CATZEr8ZD1mz3KEa5S814',
            '12137383'=>'onMsds6YswBnBesHgl7aLxrh3xIY',
            '12140322'=>'onMsds5XI6YGkD3FKaowhi_p0dt4',
            '12145383'=>'onMsdsyiguOFwsIrybs--WDj3ERo',
            '12145677'=>'onMsdsxobxyfsQ1nzvjzfIQfd1Eg',
            '12152250'=>'onMsds3DBAfuR9n4GhZZn0xEyzjM',
            '12154969'=>'onMsds4Z1xLWym1aZWABkG4NC_I0',
            '12171444'=>'onMsdsyqA09Y_Usffu0phmxW_i9E',
            '12173227'=>'onMsds3FKCEa93bz_g3c9r_yRE8k',
            '12182886'=>'onMsdswUr6ExlaztF20rCC5L6OKw',
            '12196722'=>'onMsdsyY1y2sN4QLgbD-0-cCYQYM',
            '12225163'=>'onMsds5roTNY9SZOJIwsCu09vYVE',
            '12244024'=>'onMsds4EQTExyicE-sYJ3bI65QpE',
            '12273271'=>'onMsds9s8dLrgUkQ1FynCcyoO3Fg',
            '12352873'=>'onMsdswRjeCR0GOYWwCiNRqlAHBQ',
            '12383388'=>'onMsds5K4Z_jU54ngghXudkRtsUY',
            '12401818'=>'onMsdsyTEjl2xAcWeHUGgDg0S6Xg',
            '12402813'=>'onMsds8zPDtn1hZaPRpvOVDEo2c4',
            '12442299'=>'onMsdsz4xX8EjQh4w_xtBK9SOpfA',
            '12494584'=>'onMsds5yHAE1lXf5nS2VyY0ugmKA',
            '12499819'=>'onMsdsxgMYfM0nb3Tgb_jCO24M0w',
            '12513945'=>'onMsdswvctBrogRT_7dEusmcyQWg',
            '12522521'=>'onMsds_xKdAIxvWdV4_RAbnuu8RE',
            '12522796'=>'onMsdszSYjOUYPtdxZD9dowa5ZwE',
            '12540654'=>'onMsds1BK6oPaee2fb8_Hsu2n6e4',
            '12540784'=>'onMsds_ktS-14NRWx4NDwt9Fe49I',
            '12545196'=>'onMsds9n9kqbL1eYss0kkQ6U6x_M',
            '12548711'=>'onMsds-CuBkjNdLrOG3TbdGPWXGI',
            '12597704'=>'onMsds18O_qxVg6ge9vKam1V9HHA',
            '12604811'=>'onMsds6gyqEoAJhBjtI1MuQK3zDI',
            '12674744'=>'onMsds087yKxJNGWOY6N7I1VdxjI',
            '12688651'=>'onMsds9nROMmDe_EmhWMRJetHbuk',
            '12709742'=>'onMsds9qlXw8Ip4D6zkTGR5Ared8',
            '12721261'=>'onMsds6lP3PZnybetXmMDBNPHA0Y',
            '12737925'=>'onMsdsyVleZC3ydO1dXgsSVLZ8Bw',
            '12755434'=>'onMsds3JZka1bY_gEjFxjvDf1b_8',
            '12771052'=>'onMsds6fAFizwXY6tNE1XbhemxVc',
            '12807330'=>'onMsdsyUQUuwTARMQ_40gSvdez3E',
            '12810299'=>'onMsdswkPyMsTFoQtgzrkyRX07nE',
            '12820523'=>'onMsds4h6Dphu_HT-CBpo6GBf4-c',
            '12831134'=>'onMsds1vWsRuM5_foWfZ6jDL_am8',
            '12850127'=>'onMsds5KxsicqX9PW3-WtK3KviKo',
            '12858184'=>'onMsdsxLoFnvDJ-tn9hyndPnrxJQ',
            '12871128'=>'onMsds5QzKgFnhhWY-VZUrXRnj6M',
            '12879148'=>'onMsds_I2FzuOW6FFwxgp6if59uY',
            '12890731'=>'onMsds9EDYuvOV-m3ZocsLvqt3U8',
            '12901973'=>'onMsds49_7cbrpvnuzFoFmw0Llk8',
            '12904688'=>'onMsds9MYvRIy1b-rUhT3go1Xyuo',
            '12912225'=>'onMsds8VClYt9WWS_yPxyTv2cMxc',
            '12914569'=>'onMsdsypCswOXGfkwe_rEitNOg44',
            '12933583'=>'onMsdsyXimjYQOZ1yQF1PziCFimg',
            '12941395'=>'onMsds_bhnNh_PxxpAJI0Lu1a9Qc',
            '12942440'=>'onMsdsytXHi-skZeBRTe9f09fl50',
            '12951022'=>'onMsds3izoEw7fEcL6yVB4gwrdo4',
            '12969264'=>'onMsdszG7u3qOm_WGQ3FYUsNqddo',
            '12981801'=>'onMsds1S5Xi60bYUNqXqAvoZBOtk',
            '12987206'=>'onMsds47fuQqo2d_mVmO1L5qnGBI',
            '13039945'=>'onMsds6ZPQlvz6Thg_s3N6GIivqE',
            '13088389'=>'onMsds91aaUUAkVKBkhJWn2o-ZRA',
            '13097556'=>'onMsds0XemMtUkiMSaieuW-e5VjA',
            '13100279'=>'onMsdszhZ0f9UkNV-Jp2lD27qk_0',
            '13103106'=>'onMsds9t8867d-JTGaj09uJt9JjI',
            '13104460'=>'onMsds9-drJ8Sg3w2z5piYTpU1IA',
            '13112916'=>'onMsds_oDKRCWugBEcKH_QG9ebuk',
            '13113467'=>'onMsdsykWc9sUlbUhPlVx0Gn-l4c',
            '13127144'=>'onMsds2ZwkJVnHY2mSy5cnadXbRo',
            '13131473'=>'onMsdsw25XcS6_sFhfiLgYhQWK7I',
            '13172876'=>'onMsds_VQksa127PEw-JpSwM4K3g',
            '13174433'=>'onMsds18THiiJ8AfgvlClNTlDrwk',
            '13177200'=>'onMsds_mVeU0lCvQjeM-Ops_8MA0',
            '13179235'=>'onMsds5PwKyiapdDbXTNaPA2urss',
            '13180294'=>'onMsds0ZF6Z_dm2RGuQs-NPPXTQg',
            '13191490'=>'onMsdszNh9QI_06NIu6VAW9wpm6M',
            '13203836'=>'onMsds6VtkjCwRL3j5ziLTIDIsfw',
            '13215360'=>'onMsds5pOv9MZRyi_PiK8zwAgQRY',
            '13235896'=>'onMsds16NJv9v_ml7dk-hIKyVIpI',
            '13315393'=>'onMsds2jGJaJ2Qvn-1LiLdV3TWi0',
            '13318512'=>'onMsds86IOLwBjbdbQYvlhO-5sUk',
            '13378786'=>'onMsds3d6cQm1Ovcs-lBglRvjZEQ',
            '13378907'=>'onMsds5nlqcxGMWIDIJgnra9lmMA',
            '13379963'=>'onMsds52gQyKZ-jsMh2AugMmY-_A',
            '13381644'=>'onMsds7wGss7B1bF8vh3FMyIW8hU',
            '13384685'=>'onMsdsxzSljW4RRpGEK11jb3Z2Hg',
            '13450416'=>'onMsds4RIysrr2src9CMRp3NsGLE',
            '13457681'=>'onMsds0_-zW9zb8tFaN1AZFVxDiM',
            '13539173'=>'onMsdswghX605B5M8tATzRQpbd1A',
            '13539956'=>'onMsds44VqzWE9KYBdZl6GPPM65w',
            '13544296'=>'onMsds6lnmhZ_3sKjlxnvQeaiAZQ',
            '13557887'=>'onMsds1sHRN-3TEUgok7dnWdk78k',
            '13714943'=>'onMsds29DHLVzyyx9SS-fW3b9k7k',
            '13771615'=>'onMsdszOrc72EehoD27Z9sRhEs1k',
            '13781576'=>'onMsds0cF1kA3aNBQnxa6MkrH9Bg',
            '13842492'=>'onMsds3wsXadpkiySxRQpYAiDznI',
            '13912250'=>'onMsds5dyOQjBXGc1ysDLGeH7QSE',
            '13914602'=>'onMsds3gCgP-nGbrCOFSfF0BsF90',
            '13918618'=>'onMsds2-NoITtEG2jpkzw5JxiYLs',
            '13921387'=>'onMsdszV_SioY4rZfvlkfxTxJ0nk',
            '14031086'=>'onMsdswS_jG0uC5hRktpDpFlbWtQ',
            '14072856'=>'onMsds1dRlkd5DlBoyotWqVHmPLo',
            '14100378'=>'onMsds1e-YL-6T7jHQX-BHnfdTRw',
            '14178303'=>'onMsds38d6975pDfGlbenClX740M',
            '14219577'=>'onMsds1jJYpfeIorYDbMSifMPgi8',
            '14241647'=>'onMsds_W-uez545n8xklhJkiOUPY',
            '14242147'=>'onMsds23UioONNkBE8siOtqNmpa4',
            '14274204'=>'onMsdszsTfhaZCKN2f5M1wVRRKXY',
            '14401825'=>'onMsds3OxDZGUZDtDID5R-traCZk',
            '14413195'=>'onMsdswcpY6VtlHvgDKbqPjslJiY',
            '14583380'=>'onMsds48seVeXqA6gknXXl4LHeuI',
            '14794594'=>'onMsds0jWCJ2v28uf-LSeXp8YAro',
            '14963194'=>'onMsds02mvFJMHPev4-XzQbF7vCo',
            '14981796'=>'onMsds71IOpsJNoE9-Mc48kshvQg',
            '15076116'=>'onMsds4vDF6c3GhEcgG2AJB23jlk',
            '15080012'=>'onMsds0z7TDnxA4sC_es9afUolQ4',
            '15162886'=>'onMsds2KLSu8ungPXCO72PsXy89o',
            '15435170'=>'onMsdszslSk1maWekXwOottbK_2E',
            '15642640'=>'onMsds5hhCExyVsLwoRf3CkuvjI8',
            '15704755'=>'onMsds8d3MKqkfmppDrbot93JbAk',
            '15990048'=>'onMsds4eUv1Q0hKFeaWU7DxyGcXM',
            '16015577'=>'onMsds76RIgslZpwpO1yoL3Dp9OA',
            '16237990'=>'onMsds2Ep-IVrC12Hn3ybqsYpnjc',
            '16252260'=>'onMsds3mU_h8ELi6jdL0BNVl0bxY',
            '16280967'=>'onMsds4j30u6fFVzwRcwHUXovAq8',
            '16281772'=>'onMsds48RM0Bylmqaw2NpZORqs0Q',
            '16352403'=>'onMsdswqU63Ix2Noo0pVBZPW7_No',
            '16368061'=>'onMsds9aoau4-CjyaRJHb2Dj8VJE',
            '16401949'=>'onMsds3GIATR16nz9DRDUYm401sc',
            '16427472'=>'onMsds4BDmxdeJWOkWFczjbVq6AY',
            '16553020'=>'onMsds2d-f_cc-Dx9KovO1OghNsE',
            '16603642'=>'onMsds8qpjDmnFSRZ2BQ2FaHBq1k',
            '16651192'=>'onMsds5paahjyigIqbl5BADeVZt0',
            '16709506'=>'onMsds37eOVj7-65oAYWxJCBjx5Q',
            '16723028'=>'onMsds-WMjnffdDDnpXzcHJD4nW8',
            '16819266'=>'onMsds5eWUG83-p7hlXsASr-X_2E',
            '16835658'=>'onMsds88LPHnL31U3xdU9dLjZpaA',
            '16838397'=>'onMsds1Ym95gSalVt5XLpFLJsNts',
            '16930185'=>'onMsds-SnI7M1sf2HvPHj3K5NK9w',
            '16990709'=>'onMsds328zVV3Q8xULIn6ujdvWMA',
            '17144411'=>'onMsds6x2jFBtyCX_SLVi8tVYR68',
            '17157762'=>'onMsdswPWr3aR3hDRhz0rGX7jvtQ'*/);

        $weixinMessage = new WeiXinMessagePushQueue();

        $result = array();
        foreach($arr as $k=>$v){
            $item = array('channel_id'=>$v);
            $res = $weixinMessage->sendTMessageOfPlannerService($item);
            if(!empty($res) && isset($res['errmsg'])){
                $result[$k]=$res['errmsg'];
            }else{
                $result[$k]='error';
            }
        }
        var_dump($result);
    }

    public function actionWxPushPlannerLiveTmp($is_t, $who){
        Yii::import('application.commands.message.*');

        $s_uids = array(
            3046552733, // 志豪
        );

        $yy = array(
            1420961173, // 晓茁
            2674367111, // 丽欢
        );

        if ($is_t==99) {
            $s_uids = array_merge($s_uids, $yy);
        }

        $config_msg = require(dirname(__FILE__)."/../config/testWxTplMsg/{$who}.php");
        if ($is_t==99999) {
            $s_uids = array_merge($s_uids, $yy, $config_msg['s_uids']);
        }

        $sql = "SELECT DISTINCT(channel_id), s_uid, uid FROM lcs_message_channel_user WHERE channel_type=1 AND s_uid IN (".implode(',', $s_uids).")";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($res)) {
            print_r("\nnothing need to do\n");
            return ;
        }

        print_r("\n");
        $result = array();
        $weixinMessage = new WeiXinMessagePushQueue();
        foreach($res as $row) {
            print_r("{$row['s_uid']}\t{$row['uid']}\t{$row['channel_id']}\t");

            $item = $config_msg['msg'];
            $item['channel_id'] = $row['channel_id'];

            $res = $weixinMessage->sendTMessageOfPlannerLiveTmp($item);
            if(!empty($res) && isset($res['errmsg'])) {
                print_r($res['errmsg']);
                // $result[$row['channel_id']]=$res['errmsg'];
            } else {
                print_r('error');
                // $result[$row['channel_id']]='error';
            }
            print_r("\n");
        }
        print_r("\n");
        // var_dump($result);
    }
    
    /**
     * 添加机构第三方测试消息.
     */
    public function actionPushPartnerMsg(){
        $redis_key="lcs_push_sdk_queue";
        $param=array();
        $param['type']="trade";
        $param['relation_id']=691;
        $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
        
        $msg_data = array(
            'uid' => 171429058,
            'u_type' => 1, //1普通用户   2理财师
            'type' => 1,
            'relation_id' => 0,
            'child_relation_id' => 0,
            'content' => json_encode(array(
                array('value' => "我是理财小妹，大家好，我说要有光", 'class' => '', 'link' => "http://www.baidu.com")
                    ), JSON_UNESCAPED_UNICODE),
            'link_url' => "http://www.baidu.com",
            'c_time' => date("Y-m-d H:i:s"),
            'u_time' => date("Y-m-d H:i:s")
        );

        $msg_data['type']=12;
        $param=array();
        $param['type']="sister";
        $param['to_id']=171429058;
        $param['content']=$msg_data;
        $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
        Message::model()->saveMessage($msg_data);
        
        $msg_data['type']=1;
        $param=array();
        $param['type']="ask";
        $param['to_id']=171429058;
        $param['content']=$msg_data;
        $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
        Message::model()->saveMessage($msg_data);
        
        $msg_data['type']=15;
        $param=array();
        $param['type']="notice";
        $param['to_id']=171429058;
        $param['content']=$msg_data;
        $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
        Message::model()->saveMessage($msg_data);
        
        $msg_data = array(
            'uid' => 10000349,
            'u_type' => 1, //1普通用户   2理财师
            'type' => 1,
            'relation_id' => 0,
            'child_relation_id' => 0,
            'content' => json_encode(array(
                array('value' => "我是理财小妹，大家好，我说要有光", 'class' => '', 'link' => "http://www.baidu.com")
                    ), JSON_UNESCAPED_UNICODE),
            'link_url' => "http://www.baidu.com",
            'c_time' => date("Y-m-d H:i:s"),
            'u_time' => date("Y-m-d H:i:s")
        );
                
        $msg_data['type']=12;
        $param=array();
        $param['type']="sister";
        $param['to_id']=10000349;
        $param['content']=$msg_data;
        $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
        Message::model()->saveMessage($msg_data);
        
        $msg_data['type']=1;
        $param=array();
        $param['type']="ask";
        $param['to_id']=10000349;
        $param['content']=$msg_data;
        $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
        Message::model()->saveMessage($msg_data);
        
        $msg_data['type']=15;
        $param=array();
        $param['type']="notice";
        $param['to_id']=10000349;
        $param['content']=$msg_data;
        $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
        Message::model()->saveMessage($msg_data);
         
//        $view_list=View::model()->getViewIdList("2016-08-12 12:00:00");
//        if(!empty($view_list)){
//            foreach($view_list as $item){
//                $param=array();
//                $param['type']="view";
//                $param['relation_id']=$item['id'];
//                $res=Yii::app()->redis_w->rPush($redis_key,json_encode($param));
//                var_dump($res);
//            }
//        }
//        
//        $transaction_list=  PlanTransactions::model()->getTransList(100038);
//        if(!empty($transaction_list)){
//            foreach($transaction_list as $item){
//                $param=array();
//                $param['type']="trade";
//                $param['relation_id']=$item['id'];
//                $res=Yii::app()->redis_w->rPush($redis_key,  json_encode($param));
//                var_dump($res);
//            }
//        }
    }

    public function actionSendSms() {
        ///全部短信处理队列
        $all_sms_queue_key = "lcs_all_sms_queue";
        ///快速短信处理队列
        $fast_sms_queue_key = "lcs_fast_sms_queue";
        ///待发送短信队列
        $future_sms_queue_key = "lcs_future_sms_queue";

        $data = array();
        $data['channel'] = 0;
        $data['mobiles'] = "13501136911";
        $data['content'] = "定时任务测试短信发送";
        $data['c_time'] = date("Y-m-d H:i:s", time());
        /// $data['send_time']="2016-11-07 17:40:00";
        $res = Yii::app()->redis_w->rpush($all_sms_queue_key, json_encode($data));
        var_dump($res);
    }


    public function actionAddTradeOrder($pln_id,$symbol,$deal_time,$type,$amount,$deal_price)
    {
        if (empty($symbol) || empty($deal_time) || empty($amount) || empty($deal_price) || empty($type)) {
            echo "参数错误";
            exit;
        }

        if (!in_array($type, array(1, 2))) {
            echo "类型不正确";
            exit;
        }

        $db_w = Yii::app()->lcs_w;
        $transaction = $db_w->beginTransaction();
        try {
            $order_info = array();
            $order_info['pln_id'] = $pln_id;
            $order_info['symbol'] = $symbol;
            $order_info['type'] = $type;
            $order_info['deal_amount'] = $amount;
            $order_info['order_amount'] = $amount;
            $order_info['order_price'] = $deal_price;
            $order_info['status'] = 1;
            $order_info['is_sub'] = 1;
            $order_info['is_handled'] = 0;
            $order_info['c_time'] = $deal_time;
            $order_info['u_time'] = $deal_time;
            $sql = "insert into lcs_plan_order(pln_id,symbol,type,deal_amount,order_amount,order_price,status,is_sub,is_handled,c_time,u_time) values('$pln_id','$symbol','$type','$amount','$amount','$deal_price',1,1,0,'$deal_time','$deal_time')";
            $db_w->createCommand($sql)->execute();
            $order_id = $db_w->getLastInsertID();

            ///买入
            if ($type == 1) {
                $cost = $this->getCost($amount * $deal_price, $type);
                $warrant_value = $amount * $deal_price + $cost;
                $sql = "update lcs_plan_info set available_value=available_value-$warrant_value,warrant_value=warrant_value+$warrant_value where pln_id='$pln_id' and available_value>=$warrant_value";
                $affect_row = $db_w->createCommand($sql)->execute();
                if ($affect_row <= 0) {
                    throw new Exception("可用资金不足");
                }
            } elseif ($type == 2) {
                ///卖出
                $sql = "update lcs_plan_asset set available_sell_amount=available_sell_amount-'$amount' where pln_id='$pln_id' and symbol='$symbol' and available_sell_amount>=$amount";
                $affect_row = $db_w->createCommand($sql)->execute();
                if ($affect_row <= 0) {
                    throw new Exception("可卖数量不足");
                }
            }
            $transaction->commit();
            $res = PlanService::dealPlanOrder($pln_id, $order_id, $symbol, $deal_price, $amount, $type, $deal_time);
            echo $res;
            $this->UpdatePlnUtime($pln_id, $deal_time);
        } catch (exception $e) {
            var_dump($e->getMessage());
            $transaction->rollback();
        }
    }

    public function actionGetPhone(){
        $data = [1750914764,10045966,20119230,16009062,13791654,10021442,20941240,10824774,10692855,12887571,10148553,17415810,20943889,20944402,20945574,20945687,20945858,20946203,10277941,13412146,16734892,12764035,20955294,20955327,11293047,10340339,20956154,17993173,20612386,11102374,11403712,16930788,16141052,12309270,20736369,15207988,13306507,10930303,12777951,12751229,20958699,20664493,20959725,10107340,14220884,10086279,20945481,17783131,20960791,20961218,20961340,18216770,20169689,10908424,20965323,20749029,20968918,20969123,20862674,20862670,20969175,16959653,10217126,15367399,18009838,20970629,20862144,20964185,13430452,20971128,10011700,20971571,14290853,20971991,20972537,20972572,17523395,20972804,20972924,17309752,10559345,12306533,14239452,20977373,20977551,20981589,11402957,20916557,20933868,20989295,20989373,20804041,17416145,13643824,20999094,21001610,21002727,19364447,11939703,21004035,21003261,11382334,10902516,21005323,21005774,20605045,21008439,21010861,17396693,13160616,21016421,10923084,20870312,21016859,21017000,21017084,10980027,12303672,21017786,16642214,16745392,17203761,20753157,19987431,21026780,13307266,21025975,21030745,19748665,15059594,11333417,10974125,21037586,17922307,21040008,21040066,16292629,21041281,21003301,12243238,21042669,21043895,13350716,12802091,12935667,17269189,16968359,21051712,11166277,20896797,21000709,12560460,17016822,21062561,21063710,17897378,12974136,21079803,17309746,21089947,10961783,21099817,16692453,10093649,21104939,21107164,16789756,21115992,10592862,21126932,21127597,16459549,21123429,10058854,20970220,20182686,21134464,21114679,21138506,12956805,10387854,11645302,21139987,12617523,21145196,21147989,21151356,21154746,11196693,21157206,20986354,21161253,19431136,21163250,20882640,20404200,21170289,12988332,21188177,21191387,20779182,11942091,21199970,10604697,20040672,21220036,21229480,10627215,21237318,21237560,21031078,10045129,21226254,21249277,21250901,17484621,21255276,21267597,21275259,21279199,21284589,21285381,13766904,16859676,20834534,21294268,21294788,21295182,19388641,21300439,21294818,21303347,13922933,19188345,21321940,21326934,20770485,21348877,21242736,21366291,12954071,21368161,21375460,21381082,17626508,13218195,17391960,21177893,21391359,21394394,13559887,21410278,21423808,21431944,21478344,16633030,11775722,21497718,21505705,11080240,12575379,21516049,21042165,12107490,21540939,10699600,21567380,20768783,21661118,21569692,10230404,11318204,21729619,21776379,21807621,21821687,20601071,11281669,12598383,10468081,15879799,21961389,13248853,21984199,21990560,21990410,21990678,10467723,14016556,20203051,16023109,21998379,21998464,13818508,21975073,22001563,22004530,22009259,15975333,22027226,22027357,22027976,22031920,22000334,22032061,10119365,13639092,22032435,21896887,22032667,22032860,22033561,14693000,22034234,22031549,22032177,22040511,22032314,17130414,22044171,22048830,22053638,13521363,22056472,21997107,22034234,22032522,11237118,19829745,22070885,21042227,22075375,22076931,22089302,22079154,22080023,22093917,10088947,13602488,22099571,22033290,22087999,22108038,22113732,22027403,22043903,22118066,22085842,22119343,22097568,22096687,22121546,22121574,22120489,10425017,13397971,22033293,13396860,22001638,22126149,22127176,22130932,22131234,22132559,22143266,22146129,22124651,22146791,16382880,22147051,21106518,22147699,22148242,22130525,22164584,22169200,16228852,13108686,19677633,22026122,11064223,22066139,21992720,22132342,10805530,21087615,22027226,21944361,22047921,22191816,22180566,14206510,16235458,22193246,21937705,21954514,22196037,10021905,20359583,14146859,12985626,13565446,17287828,22201643,19412727,10961514,11901361,22202999,16234211,13307558,21946975,22222242,22034748,22226247,22183337,11536701,22229298,21899707,22233181,22196832,21981751,20797365,16121514,16821028,22252422,20137041,19967942,22032549,22255559,22254331,10405578,22250183,20567195,22262875,12250017,16645754,20967636,21937219,22271987,10063179,12254589,10913682,10999180,22259626,12360191,22093180,22279994,13877853,21990958,13297765,10109505,22263418,22291671,22257597,16157752,22300452,12810861,22113655,16986965,12702207,22307912,12927073,12422470,10636219,16354310,16418902,22318110,22062532,22228279,22322667,22147889,16248979,22334916,11325626,22344739,20731567,20407667,14436976,22349626,22349938,22027130,22113660,10153572,22352398,22353314,10858364,22353562,20402574,22354129,22355188,22356367,22357195,22357460,22325411,20586518,20269310,20402763,22362686,19846034,15696877,20422466,11887128,22364185,14307499,20400702,15913224,20410664,20407986,22372790,13457681,22361465,22358227,10248954,20426279,22378071,22268913,22386967,22386554,11565657,20687470,17228271,22387935,10015536,22147025,13694089,22262961,10802766,22405208,20496383,20595749,20945496,13737187,14145739,22412853,13550581,22413630,22351674,11811689,22081959,22416193,15629883,22148838,12949587,22351220,22125958,13118074,22335204,22419176,10491744,22420350,22420991,22421467,22421500,22421542,22422296,22422698,16082969,22422825,22422962,18958866,22423172,22423264,22423883,22424039,19996209,22425409,22425506,22426338,22426350,22426484,10000443,15671928,20404044,12315957,16202309,17089018,22357648,13022156,22437241,22437282,22437312,22354098,20531936,10081200,22397570,22443974,17164155,22459188,22459188,22464005,22466069,22469798,13589499,22472172,21879846,22472485,22472977,12899220,17075610,22483339,22489743,22490121,22492728,22494750,22507213,13614561,22518112,21551860,22523940,22525252,22525691,22527927,22346925,22533532,22536350,22536647,22537059,12872661,22537512,22537613,22538071,22539053,22539208,22539388,22539725,17802987,20008859,22540001,22542366,22543216,22546148,22545255,22169452,22557789,22555983,22563976,18960080,22495856,22570290,22570353,22570606,22571678,22565402,10463274,22419273,22566648,22583066,22571476,22537400,22591471,22527327,22592037,22592168,22594626,22263335,22595850,22595947,22596146,22596194,22596261,22595769,22596372,22596345,22596638,22256258,22597138,22597185,22599008,22599014,22599702,22599806,22600774,22601114,22536197,22601437,22601616,22601696,22601980,22602007,22601966,22602030,22602101,22537147,22602456,22602636,22602648,22019954,22602823,22602467,22579987,22603091,22603344,22571069,22603816,22601863,22604408,22604429,22557032,20359882,22612996,22616785,11719540,22409716,20399831,10072700,22347135,22632261,22262930,22645219,22059123,22547002,10372276,22651530,22653722,13822067,22650354,22398518,22661706,22664845,21981751,22665372,22365903,22673727,10625830,18624435,22059139,22678681,22681478,22681636,16874529,22685036,22626339,22633955,20419850,22711765,22676102,22677717,20533332,22721429,22008145,21817759,22653836,22728512,22351393,20400072,22733961,20021532,22740164,22740794,22740794,22742905,22743200,22751738,18028242,14912908,22670068,16331560,18357929,19288978,22783205,11290258,22751742,22441514,22789197,22395526,22316589,22790172,22793334,22793334,20878641,22802226,22733207,22798579,12407130,22807399,10830675,22810450,22819221,22820351,22825385,22724627,22580501,22351196,22799460,22774773,22833028,22074977,22835035,22836563,10025681,20688494,20409217,20471060,22839882,22752038,11210098,22836563,22727469,21989851,22843542,10810705,14897984,22848042,22849871,22851042,22376465,22824544,22802079,22785224,18101112,19220187,19517348,22865220,13205536,22828163,20049705,22390785,22843551,22877890,22842335,22882827,22885525,18546829,22820546,22898006,22308243,22905090,22908256,22915901,22915901,22904449,21186954,22023437,21317918,22033286,15137082,22957814,22958596,14313197,22731592,13782078,22967802,10055178,12290511,22029124,22975381,22975470,22975502,22975594,22975682,22963004,13106966,22983271,22021459,22986937,22084461,22991962,17009169,23000364,10108089,22140844,17589354,21975656,22144670,22828750,23029986,22959842,20596808,20424400,22021586,22136134,22026729,21937720,23044690,23047218,23047627,23047627,21884609,22194801,22033067,22033244,22258326,22348578,21937423,23057400,23057400,23059171,23040372,23061873,22019609,22270026,23063863,22421383,23064739,23064759,23047296,16821422,22598251,22605851,19975125,21853435,23077620,23025464,22208831,23006990,22026793,23088955,23089396,23093554,23093643,23093695,23099230,22246160,22557605,22837778,19007979,23108746,22639216,23118393,23118458,23119087,23122196,23092907,23112981,23145202,13191930,15282104,22843154,20408259,22955670,14832672,23162953,23169753,22325469,23051901,22666310,23086146,12467984,13152801,10543611,13276838,17591099,23267383,23267544,23275238,23291313,18070463,14461590,23297144,20400220,22144670,14950524,15535240,23318371,22592198,23324915,23325032,22734717,22110061,22610273,23342211,23346470,22208403,23341489,22995560,22995560,22975865,23060920,12418996,22953562,22067340,14814270,13456881,22493961,18516304,23389164,22019729,23394336,20425646,22606526,22362546,20404445,23060577,23266774,22059176,23426218,22791178,22592198,23434295,23394709,20401333,22606526,15928215,23390011,22534369,22986487,23393565,23446390,17317009,23455443,22271175,16154191,23468002,16985766,23476044,23391866,14253066,19038007,10096326,23479204,23499258,22645388,23501082,23501077,23501362,23501633,23505450,23496276,16766926,22255964,23490485,23440430,22032654,14963536,23520318,22799261,23516058,11311518,23532090,17334945,23502020,23544638,23547559,23548365,23502192,12100771,23372431,23549635,23580308,23501092,23591586,23597465,22474180,23591167,23571942,22438320,23603566,23597465,23597465,23609468,23609468,23597465,20436357,23550194,23644740,22893727,22018811,23655554,23655554,23655979,23671284,23674614,23677017,23680007,17214567,21182891,23688752,23634763,23633789,23501379,23603540,22149156,22290012,17333707,23759297,23617663,10571424,22111515,22191309,23768582,23688785,23783724,23605247,23780867,23787974,23697695,22827098,18603835,12263698,22093917,19412727,23801442,23809414,13486449,23816098,23817240,23818507,23787603,20980252,23529595,20953348,15484287,23828820,20950203,23764095,23608535,23839615,23833425,23544168,23841959,23804101,23848141,23828834,14209361,23834365,23449419,22604987,23875030,23874187,23826303,23880759,23881664,10745057,23813612,22147380,23860728,12429462,17593650,23889378,23892629,21116055,23897237,23898567,23894670,12775245,12699136,10128932,11477158,23908663,23908942,15748343,23903827,23264196,23890667,10886797,23874272,23928329,11220130,23931521,22324292,20726214,23932642,23932700,23932765,23932891,13087300,17402091,23934765,10395011,13449595,23914304,20405024,12480967,23942503,23942980,22567952,23944664,23936387,23813946,23182502,23949726,23951928,23950763,23921449,23953343,23953608,14398946,10056130,22350212,20081531,23960300,23961489,23962563,23962563,18677059,23950122,23938796,23955260,22202773,23798657,13839844,22192550,10605881,23927842,10877612,16962716,23973537,23970627,23977288,23977288,23970501,19411179,23978839,23937400,21364952,10745057,23885186,22543820,23884883,23671910,13087300,23926975,19535511,23995827,23617663,12624532,11995343,24005372,24005372,23911503,23970860,24035127,23966627,23970532,24000634,24045408,24054517,24054630,19916183,23398840,24059889,24059943,23904452,15405597,24059900,23959429,23978254,23820254,24074961,23889887,24077724,24074016,24050165,23978254,24088843,24091240,23946864,24059971,23884724,23998683,23829397,23829397,24070443,13087300,24085000,23928910,24114460,24123243,14339445,23905644,1219262581,24130870,23809782,23816577,23816577,14804324,24143634,23374774,23966878,22360098,24151595,24074206,19920385,15725141,23959429,23617663,24088437,23835298,24169205,23645397,23920256,24173252,24151102,24177636,24179531,23980691,24185179,23926215,24190662,24190743,24177212,24192068,24193903,24194027,24195373,24171223,24014429,24200841,24200849,24200834,24195373,24205417,24205417,23906398,24215892,23933010,23762688,11679310,21112619,24165241,24241371,24241997,23813946,24192498,24257704,23765557,24295254,19899236,24299554,24310894,20430741,17571079,17571079,24313717,24313717,24313717,24313900,24317045,24313836,24330292,24330881,24335458,24340924,24374445,23932766,24014429,23901153,24416070,24416081,24416090,24416182,24416194,24416162,24416172,24416275,24416355,24416380,24417132,24417199,24164287,24056954,24255034,24029837,24440557,24463947,24098524,23273871,10819950,24494289,24494289,24523606,24474670,22537851,24441206,24626898,24626898,24632339,17316950,24641990,24643159,24643159,20426555,24662760,16999106,24375204,24614394,24682851,24700890,21005746,24636678,24636678,24718828,24718828,24718828,23918569,24732642,24732642,16640460,22358318,24510272,22171029,24761848,24775592,24775592,24775592,24776554,24776635,24776635,24776635,24777464,24777609,24777609,24777609,24785274,24785462,24789022,24419200,24792110,24792110,24792110,24793979,24478549,23999760,24266347,21856704,24799899,24801597,24807108,24673034,24820207,13683030,24836642,24836998,24837189,15393575,24837904,22604220,23806084,22263011,24785512,11011535,24841397,24841876,24842004,24842004,24842141,24842859,24842955,24843712,23631633,24845718,17527417,10472319,22540555,10757510,24850444,22122253,24854073,16467754,13582563,24854409,24081831,11241720,24854984,24855780,24857811,13250828,22075996,12702207,24264973,22537851,24860854,24838652,1585270250,24895807,22151473,24882720,23373464,17151946,24951804,18166852,20567028,24995139,25017338,13214553,25044055,25044414,25054169,24931269,24983733,25041547,25031564,25035799,18430118,25126529,25132607,24295210,25196603,17261482,25313440,25314890,25315629,25320440,25286295,25326257,25321692,25324207,24071568,25346253,25346772,5295669143,2827205010,10314474,12569974,10138085,15573982,16553783,11658376,10156987,11828639,11178345,20942259,10059629,20942382,20941292,11399781,20589002,20944005,20944556,20944721,13261789,20950210,15516804,17035124,20955085,11228873,17586310,10538944,20957918,20644649,11293279,13149741,14044673,19251195,10570129,10100040,20958396,12475974,11007748,13187951,20958706,10658971,12767831,12927833,12313872,20960064,13172808,11217135,10474966,13970527,20961033,20961095,12236125,10275845,12335605,10134010,20081314,20961896,17255942,14380820,10926916,20964129,13550917,12456831,20968783,20969057,20969315,20969686,20970042,20970110,20970420,20970488,20970474,12449530,20862147,20953755,20970832,20971028,20971033,20911139,20971254,20971487,20972708,20972882,20974625,14040809,12958518,20976578,12886604,20969409,20605656,15514979,10722243,20993156,13297696,20995192,12302068,21000540,13596092,14131883,21000144,21005820,21006049,21007141,20997154,21016519,12297122,10677329,17215559,12882767,11715329,21028083,17175277,16951206,21030770,21033053,21034461,15217180,14018336,21039961,12360523,12672314,21040369,21040462,21040925,21041792,21035629,10391313,21043091,11833713,10596685,21032885,21050494,21054739,10201766,21062941,18734846,17275967,21076258,21079274,21081630,21082128,2242,13191877,21090771,21091458,20870905,10746924,21099769,18102545,21103367,21103414,21105872,13547204,16247044,16768833,19854372,19643875,21115402,21117221,21120199,20977555,21087854,21125831,10897806,21108274,18428774,21119198,21140267,13644635,13200477,21146208,19560779,12314975,21157024,20961739,20723091,20908968,21157688,13411647,21159013,10710020,10674324,11253904,14898028,21174074,21178083,21182367,21184570,21187926,21188718,21189518,21189678,21200862,11136992,13795304,21207540,21213015,19969709,21218470,11046514,21218902,13943569,21226346,21232397,21159881,14371988,20412485,21272141,21085120,21290877,21326207,21331382,20636797,13932930,17527453,21383643,10703980,21385618,10453222,11030140,13669568,21346837,16653649,12732749,21417247,21305219,14181993,16907178,21478249,21481539,21508850,21510217,21521033,11350873,21526602,17467205,21439856,21690604,15423656,12914912,21708702,10857301,1124,21768105,11107885,17403843,21803689,16389008,21009180,16790979,21955334,20946892,5360602658,6283858385,19279627,21944361,21990563,21900626,21999689,21955947,22000337,16284726,22004351,22006033,22019525,22020544,22026815,22030254,21000739,22030645,22032517,22032746,22032772,21035128,22040662,20913357,22042024,10250269,22045609,22050185,22048024,22058662,21984925,22059979,22061525,22064270,22058315,22066252,22066350,20910598,21250901,22032475,22071737,22071996,22026393,22074977,21480751,21050494,22101391,22105238,17499705,16000285,22112947,22089555,22113977,22117674,21151615,22118189,54,22118749,22118789,22032403,22119586,22122607,22123650,10501078,22124535,22125963,22126929,22127488,21998781,22099699,22130297,22131420,14363859,22134248,13346172,22037114,22141313,10477720,22146482,22146554,12282356,22056542,22129853,22159145,18973429,22124651,22163660,21874746,22166154,22147417,12316000,20913444,22058508,11375279,22058191,22166717,18519072,22117738,22025563,12687880,16870070,15851028,16125305,13307116,15456407,22121445,22193563,17853931,13152801,22195303,22108408,627,22147881,16121514,22151537,21886599,17623228,16958102,16901572,22147417,21384808,21018972,22216271,22216253,10805034,11812298,21250901,16147583,13391290,22066022,12339822,21991696,19983966,12401758,22223402,21772129,21978794,14007693,22154046,16846445,15684041,17253913,11225818,22252422,15745341,22255458,16247165,22259350,10405578,22259531,22260843,14318197,22070472,22114396,22268654,22276488,12228349,11005302,22279590,19320544,22032229,22257597,22257597,22263007,22276363,16838913,11068947,22289029,21949166,22020091,22152509,22146376,20965774,22273472,21900626,22000506,22254307,22312851,14757304,12731706,19996883,17666961,22029569,22033174,11012092,22323581,21995362,22300674,20407413,16817549,18324460,14025928,22347406,22276471,13183433,22345503,22349388,22350529,22130163,22352297,22032364,20594753,22047871,22353764,22358999,22359124,20791192,17434247,18844993,17271779,20410092,20409663,21255312,17334107,22363579,22346323,10597079,20413382,13093473,20489022,22038166,22367830,20587808,22258839,22368441,22355429,22366257,18185387,20434635,20438307,22086964,20412354,22071889,22374634,22389244,22390803,22051750,21856511,22395155,22041711,17293731,22395914,22361882,22332008,22352867,22402353,19910072,22010943,22071972,22397596,17089159,22413919,20429701,22332008,22074862,22416657,15488244,18239298,12725764,22420902,22420991,22421500,22422394,22423228,10984014,20401584,22423687,13544309,22425506,22425506,22425721,22425907,22425907,22426338,22426602,22426640,22217538,22365998,22428807,22397576,22430122,10070723,17540485,20412610,22437312,10961222,22440061,10889202,15258238,20498909,20463595,22452327,22273749,22457081,22454911,22358110,22233010,22234765,15630207,22464005,10467551,20482137,13675727,20412594,22226070,22472748,22217720,22420194,22297523,20756170,22490157,10843872,22280375,22425663,22438175,22507270,20431863,21772272,22419373,22194801,10652778,22524608,22523847,22537231,22524409,16853060,22263754,22539981,22540507,14568676,22541635,22542452,22543710,22544647,22545217,22545725,22546298,22546398,20041275,22484386,22551838,22552885,22553409,22555405,22555981,21549623,22556673,22556811,22557062,22557803,22558035,22541679,12493514,22560914,22446195,22550734,22542933,22572259,22565646,22295203,22581034,22581290,22571975,18653828,22592500,22592722,22593010,10469542,22595402,22595660,22595846,22596091,22596191,22596267,22537335,22597732,22598000,22599915,22600986,22601190,22601214,22601228,22601481,22601619,22601695,22601750,22601925,22602015,22602126,22602465,22602475,22602565,22602581,22602599,22602826,21722123,20131615,21725266,22603352,22603497,22603942,22604003,22604035,22604292,22604399,22604694,22604699,22579193,20989338,20400515,22628028,22632313,22633843,22636292,22632261,22219373,22019685,22646684,10453974,15629883,22655156,22441514,22167357,22661706,22664098,11542069,22673533,22673727,22675363,22256258,17386087,22677674,10752802,22684607,11491996,22685432,22688104,13968762,22692818,22704580,22711076,12481328,22719133,22287776,20942313,22728544,22598251,13753080,22706607,22738830,22590333,22733332,22751738,22753083,10850377,22416836,22758961,22370576,10077459,22772037,22539854,22778892,22631711,13098915,22095745,22789174,22790955,21996205,18085820,22795300,22799819,12378245,16138359,22399528,22801053,22802116,22780629,15801239,20410630,22717472,22803882,22327984,22807585,10176395,22542823,22812386,10272135,22813847,22814427,22100509,22815861,16360128,22344788,22815548,22819604,22823308,22824442,19415867,22660872,22829368,22432514,10064594,22418082,14198916,16219621,22835409,22836429,22837533,20419170,14571706,17260712,22839778,22833492,11126929,22566648,21990560,11011214,22847925,17599764,10240888,22849329,22605962,19510902,22855534,22862344,21017999,22874800,22882827,22308243,16933742,21196833,17094375,22341053,22885416,22895471,22898951,22820772,22910452,10450049,22918380,22826343,22920673,17841090,22953972,21361410,22958596,22374815,22623198,22623198,22960163,22788864,22945959,22921564,17443638,22949781,20401615,22972703,22972918,13313367,22975381,22979537,22407215,19886177,22027113,22987373,20021532,22895461,22991962,16932103,15218476,21937705,23022430,22376510,14209361,22073704,21942883,23025878,23037465,14228966,23040428,20402373,22976583,22031491,23047310,23047627,21906642,22138945,22378401,23057400,19052056,22062381,22082867,13596299,23006990,23064662,22839277,22839277,23064927,23071310,23079761,23080522,23080640,20410347,22577284,22405436,22569835,23074864,23093695,23093777,23095437,23101512,22197022,23108746,20137041,21850671,23112981,23116805,23118393,23121837,23136839,20440788,16561673,18242488,20408259,23032353,17170497,23166103,23168076,19186385,13641924,23095437,23180389,23185364,23193567,23197145,22952557,20253692,23236716,22541679,23236654,21686905,22680171,23262886,23267169,10780899,13297489,22113660,23306765,15241904,20827475,10678710,22476178,23317300,22575423,23325032,22957558,23331285,11105375,20189276,16154191,23347814,13138545,23349044,14461590,12144503,18313542,23070667,23088960,23371037,23371153,22124889,23374478,23375164,23383537,12844650,23386273,23388022,14461590,22407183,23406355,23406355,23391866,22018811,23420605,22424877,22814905,22600774,23266774,10028946,23391866,23095437,22933754,23408958,23440430,21306440,22323094,11013013,17380792,23454244,16154191,13607589,16154191,23470908,23391866,23479598,23493753,23494364,13890923,23501633,23503117,23507857,10000791,23514672,23516463,23493956,23519188,23499696,23196505,10037538,23522166,23525402,23261613,23530509,23386732,23537417,23544419,22653045,22733332,23517290,23560737,23573515,23497471,21536245,23586951,13963234,23591944,23571942,20754571,22924227,22583663,22438320,23492688,23544038,23604027,22193729,23500986,23608836,23609468,23609468,21361410,23612124,23613677,23618720,23612124,11588892,23636410,23644751,23534838,23523195,22018811,23652509,23662520,23670946,23671067,23672727,23681603,23660020,23095769,23664540,23682627,23595372,10391425,23758833,23761548,23772433,23773440,23774373,23774373,23776027,15153322,23769612,23783559,22093917,23780867,23787974,23788182,17928598,23792456,23792599,23796658,23653450,13645021,16777628,19412727,23807150,20526132,23797167,23816098,23816764,23817240,20980252,23624316,13082894,23822638,23529595,18973429,20942410,11381177,23826532,23827847,23828157,23490485,23822671,23764095,11427883,23841959,23844116,22148680,23847530,23847686,22361616,23855285,23858983,23809795,23875361,10297971,23795171,23672148,23877979,20909840,22949781,23880702,10117862,14223580,16751615,23760777,23885990,10745057,23808237,23784232,23501092,23893554,21116055,21116055,23894697,23898567,22332008,23899615,11877506,23908389,23908942,15748343,23903827,22035270,23389164,23841050,23913999,23915879,23918569,23834763,23920754,23918725,20909097,23928329,23928366,12404472,23928497,23806795,23904178,23931876,23932076,23932700,23932891,23933711,23933778,23834763,23562743,22000425,12227292,23912992,23876391,23941383,10042723,16232629,23942083,23942083,13663475,20518294,23813946,13039924,23891300,16999142,23913614,23953608,23813946,23954546,23919171,23029263,23957264,23957264,23617663,23839477,23920007,23956024,23901153,23960162,23955999,23953343,23962449,23962506,23500117,13211656,23960870,23964643,23964927,23885715,23885715,23964643,23968747,23813946,20400759,23970094,23967307,23970532,20964159,15540701,23971446,15725141,23897984,23976620,23956940,23977288,23977288,12631344,19411179,23970501,15725141,12898868,11178803,23982977,23927403,23914668,23970860,13620183,23994232,23983066,23996348,23949730,18080190,16244622,21364952,23975328,22164568,24002815,24006560,11420894,24005372,24025102,19485327,21541934,24005372,24034589,23970860,23970860,24014181,24053469,24054517,24054630,24054630,24054630,24058370,24059923,24059889,24059943,24063227,22688675,23919594,16851961,23954771,24077232,24077724,24076767,24073884,24085613,24085747,23926773,24087865,18173454,24072252,23829397,22610273,12246621,23997325,24044902,24123924,24056942,13228386,16751505,24090965,20480981,12116585,24095594,24113149,24143634,16551742,17742694,20744879,22368244,20410040,24126091,10140367,24157782,24155095,24148125,13201698,23815717,23763062,24166661,24166661,24166820,23987601,24174921,24177530,22394287,24180650,24184513,24184513,24185179,21212918,23728895,15826565,24190662,24190743,12133730,24193903,24197676,24200834,24201747,24205411,24205411,24220468,20421625,12551738,23915127,23883285,11476318,13575200,19148181,24232417,24282997,18287295,16311309,24313900,24297823,24316741,24317785,24318218,24317045,22947957,22553409,24335458,24105182,24346738,23841119,24416051,24416087,24416068,24416043,24416200,24416216,24416339,24416262,24416283,24416369,24417163,24417145,24418691,23820994,24201061,17372986,22966490,24450674,24455620,15836892,20526032,22342214,24474693,24583217,11476318,14912908,23852561,24572127,14318197,24510272,23273871,24441206,24647929,24210678,24659577,24666677,24700890,24636678,24636678,24636678,24136710,24718828,24718828,24718828,24732642,24732642,24732642,19040255,22018811,20235084,24775592,24775592,24776554,24776554,24776635,24776635,24777464,24777464,24529210,24777609,24777609,24777609,24785274,24785274,24785274,24778115,24785325,24127835,24792110,24792110,24792110,24794361,24799899,24801597,24711384,24807108,24811298,24815357,24835201,24836998,24836998,12803587,24804979,24838125,22386415,24839415,24839437,24068798,24842004,24842004,24842141,24842141,22143821,24843509,22305533,12886378,12293995,10143109,10987305,24848178,22634590,23503278,10837470,24849624,24851178,22375505,24854626,24757339,24854734,11824338,20074457,24859462,24859541,24860854,24860854,15969081,19652075,22592620,24907562,16458791,24947821,24951929,23373464,16301803,16527297,24160853,25044052,25044065,25058057,13183600,22645957,19406973,25031577,25035862,24469745,16248047,15533893,21149550,25113130,11082931,13221859,24955005,25059874,25238342,25289592,25289580,13987290,21999529,25306258,25313440,25313440,17055584,25317461,25323666,25290595,25329162,25331350,25331860,12337307,25336409,25315300,12431002,25344277,2005350035,1668042745,12975058,14735604,10847517,13181646,20941604,14422213,14769486,12362457,20944173,20944510,20945510,13038812,20955009,12232180,20956002,12038783,20726586,20958226,18215517,10943591,19758757,12022772,17911774,10637515,12468472,12937740,13124108,13601485,13097154,20739340,10045168,12996429,13106040,12407478,20960859,20960995,20961035,10457881,20961135,10137546,20961475,20961694,10411439,11249737,20687450,20258084,10410028,11118966,12680117,20852871,20968789,13407071,12169469,20969715,20969871,20969544,14321145,10201884,20942176,20862094,20862176,20971036,20971099,20971160,20053342,20975250,20976192,20976949,10471522,11273219,20961862,10772447,21000534,12642551,11997493,21003042,13752770,13646905,21005547,21008234,12360813,16184744,21011400,16144955,17855732,21014374,16496172,21016018,11825288,18960080,21020803,21022413,19898170,18569414,18073451,11668547,20929337,21028251,21028719,20980179,11255250,19954177,15533244,21040320,18419500,21041367,21042173,21035996,21042819,21043662,20968936,21046003,10775217,13105966,21060075,12480205,18320424,11092991,21066561,11477158,10126560,21077522,21077937,17575053,20797290,10890857,21085462,21086320,21090522,21098831,21099693,19666600,21100228,21104731,21108042,17623228,17591091,21116055,11187656,12386915,21076603,21126998,21127852,21127711,17354049,15148166,21132735,20342687,21099905,21137502,21122495,21139357,13273271,21139182,17500981,21142877,21135430,10393282,13743981,21149053,11257867,13059967,21158456,12273981,19459331,19166612,10357316,21170186,21161470,12758893,13509609,12846572,21191170,12612069,21191434,21200396,14638854,19970736,21229302,10161072,15429408,21240002,21253558,21261637,21265658,20231534,13158533,13092668,21283654,19261400,21298834,21303215,21317279,13757735,21319882,21361375,21363219,21368321,11448768,21229491,20793092,21379846,21383059,19981736,10472822,20687232,20085457,17394996,11696703,21420500,21315661,12976617,21545556,17185673,21499241,13288571,21743866,10450989,21803689,13608515,19860609,20309277,16766926,13226489,21874562,10371913,21958618,11618142,21990352,21997039,21999081,21971339,21976698,22004038,22004530,21742362,10230227,21996816,22016054,22019225,22020561,22024520,22027050,22027562,22027609,22028596,22031215,19508428,22017622,10065552,22033272,22033420,22027586,22033640,22028022,22039384,22042983,22044007,22048044,22048497,14333839,22052197,20701189,22034234,22052144,21989633,22066254,16292629,22026381,22078466,22078615,22054362,22048058,20095910,22102648,22103625,18697077,22074412,22118548,22118803,22045522,22120095,22118996,22120370,10091791,22124892,21991040,22058862,22122304,14192849,19963688,22132502,22134028,22134248,22138389,22146710,13951067,22147963,21472080,21937540,22153490,22159145,16509530,21004061,16456482,10244887,16535726,21937791,19159673,22169025,22147679,22131931,10661174,22170334,22147481,22170334,22174643,11342033,22041789,13542770,22147360,22188777,22189678,12863484,22034471,17975509,12440201,17482690,22196734,22054541,22107234,11294340,22098063,13830071,22204755,16958102,21991787,21384808,22192753,22064339,13478624,22020278,14093030,22186962,22221825,22051921,22224588,22226168,16565955,22021459,20644787,22048058,22224321,22116204,12530220,22231631,22233746,12479989,17833873,22229323,22069104,22251080,22252021,22252422,22255458,11452379,11225818,22259350,22259101,21405109,22191169,10649756,22271987,22273383,22003588,15039954,22263778,22277289,17333707,22274490,12651073,22263007,20057672,20797365,18425663,21937540,16157752,22274388,22298454,17489808,22041742,21471825,11603554,14249773,20068315,20521354,22246817,22325411,22262212,15504888,10025732,22327823,22224630,22334918,22339879,10011754,10781388,22318752,10318033,22284956,17154248,13378344,16530523,13669450,22286706,22263335,22233010,22353149,20470522,20325379,22354075,12147103,22357709,17463791,20412973,21549038,22254638,17434247,16458881,17190801,22358324,22360844,11078587,20728650,17492912,13083741,22368245,20167754,22347319,12125517,22218803,22148242,20566551,22375505,20412176,22081959,20413824,22172255,13239358,22387116,11060681,22388194,13919843,10228640,13044705,22395472,15514979,22041711,12415584,22002619,12722774,16675629,20088557,22397596,22024870,22414050,20081194,15842881,17545467,22420991,22421467,21082248,22422454,22422489,20476065,22423264,22423742,12654040,22423961,22424039,22425380,22425506,22425907,22057333,22429520,20412709,22428581,22437312,15680110,15699252,20243524,22447365,20400682,16904466,22450858,22452327,21026023,16124428,1014989264,11506447,22462415,13203683,16063637,20081194,22350249,22489664,22490499,20095910,22496002,21982609,20909665,22498625,22298026,14883154,22514085,20804896,20406214,12721122,16537520,22537096,22537136,22537264,22000488,22539905,22539977,22540164,22540444,22540574,22540749,22545200,10859003,22547062,21882910,22550042,22555855,22557507,22557851,22558676,22562557,22565550,22567291,10772447,22569554,20403239,22571810,22545605,22567291,17612529,22576575,22263269,21057030,22581290,22347319,22586269,21339677,22587874,22588135,22588793,22591783,22591900,22592063,22592103,22226070,22595553,22595609,22595802,22263269,22596157,22596410,22597046,21937826,22347429,22598177,22598922,17293731,22267154,22600501,22600903,22600981,22601148,22601378,22601646,22601645,22601963,22601992,22602055,22602043,22602110,22602169,22603299,22603353,22603638,22603717,22603979,22604301,22604643,22604642,22604720,22604978,22605851,13637893,22613127,22323892,22034748,22622480,22347319,15737594,17455280,22631725,22632401,22633871,22632261,22055284,22366042,22649776,22652508,22658229,21821591,13840632,10357865,22673351,22673661,13803306,22690814,22336185,22164568,22708450,22713833,22631725,17264162,20109671,22679376,22737788,21467388,22742917,22743134,22555954,22753380,22743428,22764572,22774633,22777379,22576531,22751799,22751799,22789915,22786496,16277678,16537520,22798512,22378854,10793488,21480751,22322428,22807144,12103976,11660255,18279828,22812174,22812329,22812386,22813998,22815490,22815824,22411448,22819312,22819613,22820443,22822534,20354457,20579112,19415867,10003180,22833796,22819142,20483831,22836566,22840221,22840784,22831876,22842434,15810188,22843154,22263660,21995955,10016110,22793257,22828057,10658614,22855534,22865367,22871194,21773141,22872802,15967757,20466844,22495566,21358109,16473706,10250586,22885416,22885901,11031407,22910452,22916488,20405744,22922894,22871194,22945807,22947960,12827567,22228206,22950133,22124889,22282106,18244544,22411007,20937535,10881702,22685040,22895461,22254077,22884542,22973439,22969016,22975502,22976267,22979537,22979537,22026793,22975558,22046506,22987373,13487427,22988709,22973686,22991962,22954032,22992962,22966725,23018668,23012555,22613127,23021626,22163510,17195383,22034317,22429775,22676587,22654859,17018750,22344860,23044903,23045289,23047218,23047310,23047627,21306440,22355873,22073283,22055273,12293783,12832898,21306440,22976267,21942883,22259626,22985096,22071338,22054362,22826343,22794769,23064691,23064732,23064927,23065215,22204708,13395128,14165113,22118883,22381830,22125933,23068283,23050265,12783228,22569835,18352692,22995560,22049326,23087987,22099234,21893918,23093643,17576150,22968326,23101876,16767857,23106590,23108009,22537101,22526247,23022073,23118393,14379906,21865910,23124256,23040372,22897024,16941774,22751483,22965073,22530078,23168843,23169020,23150408,22665027,23180389,23187969,23194462,23196084,16157104,23202251,23223275,22168910,23232515,23232515,23236716,14966392,21177922,22296733,14318197,22320069,23235855,23236692,23114807,14782940,20995429,23268211,23270222,20945803,23272525,23274521,23282637,21422529,23296410,10005585,22810450,23086606,22579193,23237209,23324821,23327725,23280262,22409663,13483179,23344677,11546175,23266774,23235855,23371037,22051699,23326388,22194801,11423396,23387635,20400044,16343142,20402084,23394068,22885289,23391866,21264763,16284726,11132438,23423724,12642077,23435209,22573064,11079424,22332766,23302796,22351861,23438560,10247604,22579860,10105534,12704516,23491971,23492688,23493753,23493956,23495508,23496372,16541573,22148147,22924378,23500113,10118440,19517348,23511471,23507045,23525402,23527043,23532009,22018811,23440430,23545812,23546405,23546607,23548268,23550174,13155117,23553837,23553872,23523195,23551185,20404144,23566936,22254307,23571262,23532090,23586526,23571942,23477791,20407320,23606193,23597465,21361410,11617067,12149346,21493954,23634763,23640729,23585897,23619904,20410672,22018811,23664286,23671067,23680018,23644707,23694886,23704372,23704838,23530499,10391425,23674115,19877214,21203501,23671910,23660020,23761548,23768582,23770872,11381177,23236399,23634504,23765214,23780867,11141620,23791744,23792135,23792794,23793149,22827098,13307116,23809782,23810427,15681662,20412620,23818618,20980252,23819280,23688785,23826532,19390013,19390013,23792479,22380312,15823619,20966113,23816764,23820629,23847612,23855285,10246480,23830215,20400311,20966113,23870510,23863082,23827922,16992837,20851594,22707988,20402502,22064845,23816468,15344620,10273295,23812855,20429380,11477158,21116055,23898476,23869713,20400232,23891819,23537183,23858261,11120347,23908942,23882924,23899197,10886797,23918618,23921481,23922696,23928497,23899814,22621836,23904178,23931738,23888579,23932891,23933711,17630045,13811563,23922696,14339445,23032146,10066848,20510855,22676497,23942083,13663475,22328128,20254787,23947582,23948679,23950116,23953608,23029263,23957264,20081531,23960146,23960174,23838182,23962449,23962506,16860369,23962729,23962563,23963409,23964607,11241878,22473070,17703661,23970094,23970094,23941144,23960162,23972218,23972702,23973513,11381177,21319307,23974852,10021198,23975335,23970532,23977288,16532119,23977288,14895374,23885186,23976939,23813946,23903174,23790155,23995527,23998305,14223907,17241045,23963433,24002815,10145656,24004752,24004752,15581249,24005372,24031195,19485327,24034090,23966627,19485327,24023749,24043708,24049039,24054517,24054517,24054630,24010092,23953387,23953387,23882928,24058370,24058370,24059923,24059923,18065189,24059889,24059889,24063227,24066329,24059911,22052868,24072219,23954906,22320737,24013498,24077085,24077724,23874187,23766944,24050165,24085747,23882716,24088110,24088414,24088622,24088110,23987876,20419596,24097327,22332018,24107656,18653828,14339445,24113871,24098064,13616711,19109825,24129826,17125790,23970898,24075244,23972491,24138342,14804324,15681402,24074206,13568438,24000974,20745548,24150607,23952063,24113149,24156806,24157782,10134010,19056113,12878489,23815717,24162286,20399862,24166812,24167883,24169205,24169205,19837418,23893879,24180650,24192797,24193903,24193903,24193903,24192774,23926215,23954771,22949781,24197734,24014429,24200841,24201747,24205432,24164287,24216067,24220490,24219917,20406409,24223485,24223485,24210678,24056942,24175868,24241999,12887167,24192498,24192498,22371286,23783508,10369623,10219764,24291970,24287467,24287467,24313717,24313717,24297823,24313900,24318218,24313836,5907183201,24330881,24330881,24317045,24273551,22610273,24375891,24383983,24388566,23820368,24413921,24413961,24416074,24416096,24416075,24416064,24416191,24416212,24416334,24416249,24416272,24416350,24417113,24417146,24418302,22350324,22047578,24152334,24297818,24458413,24459992,24418804,15724114,24470728,24476196,22342214,24478173,24483215,13136004,24376589,24616450,24625274,24636678,20409612,24346738,24642317,23598074,24632339,10145656,24670360,24123797,24677314,24431840,24706657,24636678,24723829,24409356,24728235,23933010,23956175,22559511,24732103,23918569,24732642,24732642,24732642,24769678,24769678,24761848,24775592,24776554,24776554,24776554,24776587,24776635,24777464,24777464,24777609,24777609,24785274,24785274,24785325,24785325,24522242,24792110,24792110,24801597,24801597,24804156,23838780,22019556,24811374,24811452,24097106,24218902,24837680,24837966,23101431,20725085,24233241,24152334,24840077,24070443,24841844,24842141,10186347,24843532,15123100,24843150,24847399,24848466,21665474,24076028,24850458,16160738,24851524,24852787,24854578,24854765,24854850,24854966,24631655,24855780,22136134,11973630,23606422,13334592,24194245,23789842,20851595,16294938,24903096,24907450,24659005,16626499,24941757,16661654,13805642,16064556,23373464,24973623,10547601,25046830,25059126,25061871,10207449,25083934,25031363,25036063,25059830,25060247,25174747,25179428,13221859,20261422,17525574,22408536,13900411,25289592,10502549,25317461,25326257,25330929,25335560,25160438,11245381,16641634,17496693,11372358,20941627,20096901,10935388,11339393,20943947,20944247,16667976,12516922,15517954,14161172,17580422,20945764,12759580,16795119,14058151,10299987,14159645,10071918,457,20957602,12705981,20945242,16250387,12918124,20811835,20958325,17398723,20875186,20958431,11359931,13830071,20958620,13932500,20958751,20959021,14049190,19995357,20959777,11322254,20651549,20960710,20960831,20960873,20961008,12853182,20961310,20567195,13480653,20961879,19760592,11511223,12100771,20963501,20963616,11150097,10628382,18937540,11812298,10984854,20966074,10875740,20969383,20957453,20969939,15435680,20970584,20970739,20862097,20970845,20960771,20971083,17104776,12986923,20971357,10925045,20971718,10912174,20972129,15651719,20972585,12063177,20973736,20704905,20692868,20567659,10456848,11216312,15966503,12876489,11933260,20981789,13570703,10283738,20982008,20971431,16555383,20185816,13412671,10199473,20891502,11460227,20992402,11590340,11257370,20997091,11819410,19486464,21001214,20347003,10042842,13462411,11322823,20906299,14826728,14353927,20080166,13179274,10681059,21013853,10157884,12306288,12759940,21022768,11615132,21008387,10000397,21024075,11337028,21025106,20117715,12916842,13752985,21031303,20945556,21034825,10243029,18085707,21041255,21042405,12379621,21042530,21042935,17394467,13734317,16221486,21049656,13214289,21041202,20052297,10117862,15356509,15047750,21068220,18338450,21037170,21071372,21074233,21076622,21079907,21082347,21090741,17454227,21098278,18022049,21084082,21107969,21108017,11084543,21111762,21114692,10467128,21115117,21126609,21127161,10637283,10768879,21132492,21133526,21117325,10281906,21063791,17467450,10422725,21115820,21135430,21153906,20949858,21155922,21157178,21160048,21161224,21106967,149,10617175,17563398,21170280,21172755,17406110,21176887,21179486,11781546,21192708,14265823,21162500,12138297,13611090,13334592,21219524,21222520,21227369,13638150,21206443,13776350,21260079,21261124,21265759,21266367,21269146,21272307,21000677,21034419,21280807,10450004,11322671,20264826,21322013,21336254,21343793,21344475,13885192,19517490,21372944,10140558,12254589,15591755,12503418,12249296,21422509,12758889,21441853,15390768,21485806,21500673,21508045,15883883,21514809,21516932,21554490,21645837,17550843,21501196,10187277,21758784,12915777,21570447,21822295,10081973,13143091,21500185,21857909,21858838,21861524,21862283,15461856,12692936,21872038,21873595,21903377,21924457,15031444,21462426,21960556,21988024,17200901,21990458,21971508,17960352,21999529,22000762,22002619,21998059,21790573,22011515,22016775,22017114,1911815605,22017152,18967498,22028407,21937791,21954931,12318040,10265233,22033135,22033286,21997109,21937705,21989633,22039459,22031411,21884879,21977067,22042751,22044553,21106597,19982205,12900524,22064368,22064773,21990560,22068340,22070836,22072020,22052029,22095630,22099784,17524718,21988743,10128138,22117713,22117975,22119149,22120168,22120241,22122171,13794891,22099670,22019288,22125404,22128997,22134060,22134248,22048044,22141372,17776806,14663970,17708413,19095647,22146453,12282356,13000859,20908824,22014787,21908193,20764069,10171495,22170539,14005547,22031678,22051823,13000426,22175736,22166717,22165220,22186082,22027226,22038856,12976670,22190244,22178452,22182867,10938712,17474777,22130560,21990944,22193626,10096798,20626954,19208190,14638854,17741414,22104258,21936854,12898868,22062604,12478167,22204704,22204704,22205418,10442903,22094160,15999720,12984734,18519072,16284726,22039440,14077201,22201470,21978794,20084946,21908249,22204724,17741414,12584938,12005958,22231631,22233836,20137041,22035955,22247691,22048017,14684818,22251080,22067282,21909249,22237439,22265035,22255509,22265671,22267902,22269314,22273383,22055395,22149205,21997070,13798764,22237085,17522269,20008311,21998777,22261174,14049666,13749953,22291836,20680566,20431187,22310804,18714324,15317456,22308851,22258836,10072700,22329040,14216816,21937219,22219978,22335204,22261035,20644695,13257848,22342549,22190514,22344903,21937720,22345925,12043978,16641785,22122367,11299782,20401500,22350428,22351217,22073704,22353149,13626889,15953062,13105488,21248562,21494400,22356371,22357477,22357549,20422880,20586518,20754992,13234480,22338610,10422725,17434247,13141536,22237439,22359873,17492912,11841350,22366432,19038458,15694817,10986128,22366979,20433626,22319828,13357436,22358757,20811846,22179292,22371821,20412679,19320544,14472838,22380840,22268913,22352194,12982716,21468708,16567347,21680428,13463958,22395639,10613017,22403540,16120778,16170506,22373554,22296107,22397587,22285818,11195506,20481691,16240207,20400621,20401232,13673799,11119866,13633288,16250951,22420742,22420742,22420902,10380867,22421146,22368405,22421467,22421500,22421542,22026793,22421795,22422037,22422119,22422119,22018074,22364306,13622890,22424279,22424516,22425409,22425803,22426525,22426525,22426602,22426656,22426673,10151155,10594427,22437154,18420441,13947517,22439553,22447031,20530588,13188202,22420396,22029569,22452537,22273749,22467847,22470427,22472977,22463206,18917043,22480488,11087323,22483097,11883102,22486267,22486416,22435318,22489712,22492670,10081973,22505825,21975656,22509328,22026122,22514476,22507176,22521758,22263818,22416657,10423606,22525926,21909659,22527264,22527309,15962611,21936821,20407156,14351138,22537237,21021859,22537951,22538246,22538721,22538841,22539057,22458838,22540926,22541062,22541227,22541616,22542677,22542987,22543177,22543547,22544235,22544286,22544969,22545058,22540306,22545559,22541990,19064817,22550416,22172695,12360535,22269157,22557535,22558035,22558868,22558981,22441195,13303500,22561515,22562529,21936794,22563584,22568919,22571136,22556408,22573032,21314069,22574049,22576306,22549735,22581530,22583292,22568155,22446195,22586879,22572694,22537219,22589776,22589893,22591944,22592053,22592596,17460782,22250183,22595381,22595744,22595785,22595915,22596019,22596143,22596831,21936794,22597300,22597646,22597759,22599471,22599691,22600476,22537052,22601462,22601502,22601514,22601669,22601708,22601844,22601971,22602016,22602026,22601994,22602163,22602411,22602621,22602431,22602691,22602876,22118926,22603765,22603961,22597738,22604033,22604090,22604196,22603849,22604263,22604372,22604477,22604713,22604977,22078257,22570924,22487515,12184954,22070818,22645970,22647494,22258175,17449399,22661706,22669560,22673727,21937423,10919799,21400326,22701046,10597079,22285980,22731641,16009644,17164850,22734849,13642689,22720012,22345918,22740595,10705322,22743200,22744946,14528224,22751833,22228206,22753380,20414244,22766241,10266492,22772889,22351687,22778489,22778846,22350625,21769374,22781879,17900669,12453089,22783829,22793440,16872493,22797038,22782110,22406682,10206049,22802121,22768259,22021586,22805674,22805979,22807170,10740978,22026381,22812533,22357553,22061246,22624223,22221825,20133784,13786219,22815475,22806145,19415867,22006033,22819221,22256431,22242427,20483938,20412949,12925149,22815475,22833426,22833987,15777287,18744888,20588991,22837999,18433626,14912908,22834559,18681133,20403902,16959588,22851063,13898352,22828036,20842913,22526247,20142680,20408879,14778694,22886897,11031407,22892459,22885642,22900662,10935979,10215715,22896859,22915901,22171029,22935996,22949787,13086039,10277264,13989617,11738928,22798579,16109397,22958586,22836563,22961326,22352235,20449784,22967400,22396481,22383498,22969136,15358862,22529910,22973018,22973762,17062855,22975381,22975474,22828108,22811494,13087300,22099378,22986937,22835830,22122367,22300991,22986691,22952780,22992962,22986892,23001781,22538178,23001987,22083244,22167746,22033339,10039388,23020568,23032146,22795721,23040428,18146234,23043839,23044690,23044903,21937490,23046148,23047310,23047310,22194991,22806825,22045522,22033220,831,22147417,13606190,22037318,22204326,23064610,22381830,23064662,23064739,23065624,23065652,22577284,23066735,22536647,19492989,23077918,23073839,23079761,22220220,22271063,23084384,23085076,20333337,23087543,22989771,23093554,23093554,23093777,22225298,23090142,23100846,12392767,22837778,22194801,11883141,23106590,19873696,17453376,23113080,23118005,23118393,11072853,12216480,23121114,21731892,23095437,22753675,23152610,23006990,18365228,23166047,20418929,23047690,20672765,23168991,23169428,22589660,20526024,23172556,12617527,14193727,23095495,23195307,23200782,23222515,23224877,22034317,23232515,23227816,23237881,13837199,16753287,23250376,23055120,23256288,23266889,23267440,23274541,20402006,23274521,13297489,22129853,23302987,23321682,12905083,10182787,20529730,23344384,17392622,21190783,17511431,16959588,23060577,23369792,23231499,23371037,23371153,23371206,17023173,19974902,21172804,20213238,19973280,23388091,12047384,23391825,13483179,23394764,22018811,19373921,22701046,22995560,22472497,10993486,23418765,23277089,22541679,23426501,22424695,23430250,21859259,23364233,23438364,22406110,23445142,23445299,11032884,22378401,21784764,23454356,11030041,22472497,23435265,20560570,22539639,23482990,17126799,10121398,23358650,23493753,23495508,23499258,22645388,23500985,22183512,22286016,23511318,23515074,16470975,23520425,23522166,23494364,23532508,21222190,22018811,23537417,23531503,15671102,23438560,23548365,22774773,23553812,12004690,10552053,23572109,23575985,23576792,23578355,23585382,23577143,23586951,23587232,23587232,23591586,23578167,22360633,23602733,23597465,23604873,22893727,23600594,23612124,23613629,20844964,20056659,22019556,23641715,23523195,23646522,23648707,23648784,23655554,23656853,23671067,23680018,23683695,23095769,23661170,23678960,23501092,23704838,23706364,22290012,22194063,23698797,23751398,23758428,22735333,12530220,16470975,22111515,23761548,23761548,23767733,23771495,11387151,23776029,12605454,23780867,23793149,21142695,13307116,23812903,23816554,23818618,20980252,23757562,23824454,17535666,23825617,19390013,23831797,23785160,22202773,20969826,23842056,23816764,23860728,16625648,23864378,23866132,23871607,17090859,23854964,23501092,23842395,15659090,16224984,23874187,23516672,22064845,23879519,23887359,16884766,16846954,23888865,10995092,23810404,22227122,23501092,23898476,23904859,21306440,11120347,12759580,10366868,22420457,23831065,23811962,23912438,12900914,11140644,23897984,23873402,23928366,23928366,23928497,23929196,23931521,22324292,23932527,23933878,23903291,23385979,23903291,22516765,23937842,23882883,23942083,23942083,12699136,22037508,23182502,23949714,23949730,23950486,23950523,23950116,23952696,20494749,12775245,23941782,23957264,23937901,23960300,10028347,22681196,23963409,23963409,23964688,23964927,23964927,23963415,23963424,23885186,23939311,23960162,23931686,23894423,22266588,23974549,10635898,23824901,23973537,23813946,23970532,23976939,10232898,23970501,23959137,23983779,18065189,23889654,23963433,23988144,13616711,18152467,23995527,16949503,23906439,24002815,24003991,24004752,22147679,23856332,23978610,24031195,24023749,21541934,24042295,24058370,24058370,24058370,24059786,24059923,24059923,24059889,24009127,24066329,13977527,24059911,24059911,24059900,24073374,24073374,24073554,22130104,23811082,24076767,24084518,24085000,23903220,24085747,23926773,24092050,24019965,24097327,24097327,13789901,24111740,24112605,5693373328,10438345,24072560,24043361,24007246,24136360,24113149,24137274,24143735,24143634,24143634,24144445,24115689,22027050,23915902,24126091,24152754,24157782,24157782,23798969,24161107,24159734,24113149,24167883,24169205,17444914,24177912,22373240,24190743,24192774,24202187,24204677,24205458,24208271,24220510,24233599,23926215,17230319,14144619,19899236,23901153,24317785,24313836,24191150,22553409,24330881,20637162,24336038,24338998,24338349,24318621,24338998,24098534,24416055,24416089,24416082,24416056,24416205,24416165,24416329,24416285,24416334,24416351,23939552,24416982,24416977,24417148,24418578,24415372,24002652,22947957,24418804,20410558,22573604,23532090,22018811,24474670,22264272,23813946,24480732,24050878,13048394,24326244,24522242,24091139,24159171,14318197,23926215,24475295,11476318,24375891,24616450,24217347,24128128,24510272,24346738,10778442,24441206,24632572,24642134,10780899,24642349,10299804,17390947,12410952,22301958,24673034,16982528,24662760,24700992,24636678,24636678,24636678,24718828,24718828,24718828,24726040,20414145,24732642,24732642,24738125,20413460,11922747,20408163,24750979,24769678,24761848,24775592,24775592,24776554,24776554,24776554,24776635,24776635,24777464,24777609,24785274,24785274,24785325,24785325,24785325,24792110,24801597,22147571,24812603,24822248,24836998,24792699,24837375,24839296,24029337,10018746,24839761,24840214,24840476,11785039,23598074,24841222,10412606,24841602,24842141,10469608,24842913,24842997,24843422,24843502,24843538,24602843,24844733,24844787,24455664,24846999,21884957,24843150,20911725,10182969,23088869,24180383,24850893,20255022,17414236,24854420,24132424,24855353,17087465,24743830,10955224,24828733,24745021,24861510,24894889,24901644,24882720,24944444,19945596,24912302,24990746,14139276,25041729,25044053,25044601,16124194,19727386,25031499,25035920,25059864,17129588,20409556,16529257,17413996,24814454,25264243,25289592,25304346,25313440,25073924,25317298,20988374,25316444,25313466,25321692,25344277,25346253,1967527352,5731701442,14370446,10337657,20914859,11216800,20796060,10394569,11670742,10156075,20942313,11073883,15824498,12718106,10602405,20944197,20944579,20944632,13608429,12018292,20945331,20945887,19658050,17256943,10973827,20014888,15227964,19159673,20567658,17234564,15586506,12450388,17726710,16436023,10134370,10676948,13147316,13087585,11100774,12137194,16164661,19428280,12031871,13369227,11235184,10043756,20958461,13257671,10053670,13001412,13082834,20565123,20028847,10670883,20959655,11250048,20960830,20960895,20961005,12358366,20962638,10142297,17658406,10400444,20963637,20913815,12351298,1774,20969105,20969232,17686121,20969766,12603714,20970275,20970392,20862138,19780537,20971055,20971127,13989576,20972135,20528831,12429181,12155318,20976400,19165475,20976599,10043536,10043765,20977569,20977670,15016498,20981453,20765881,20985317,20538160,20910498,20988244,10109588,20989359,17505483,11927469,10664038,21000607,21001238,20903354,18276723,12863484,12627860,20793294,16439131,11081601,20909752,21014724,20917369,16605388,21016580,15305248,17327176,21017174,10410144,17028139,17560263,10859672,20979830,19077753,14029187,10016045,19950597,17088193,21033806,21030422,20909249,15530832,21040379,21041063,20981480,19434236,17433065,21042732,21042458,21044444,21045732,16147583,20913444,10181308,21044026,21054559,21056481,21056736,13166866,12178407,21062349,11086413,21079273,21070663,11154127,21080190,16854393,13136312,21082357,16683813,10341022,13306338,12098529,20414872,21098723,21083252,21076382,21105818,21114795,21115515,21117214,21122741,21123017,21103327,10283935,21127894,11033162,11197516,21131216,21132014,21133315,12573050,12884858,19967942,21103295,12263192,10063179,21140296,12709974,12349840,12083393,10169921,12539850,21143404,12123692,21145336,21146404,21146851,21148357,21151782,10833860,21157659,21157961,21161225,21162631,17554616,21172109,21178820,21187168,10877843,21204257,21220287,13293969,21227298,21234282,21240820,21246463,11094952,21261243,21274725,21285560,10763268,16944238,21294102,21302546,10850254,21314179,21294818,21335653,21291374,21335664,18090063,12836378,15651449,21368828,19053890,21377164,20435251,21384547,21300574,21388407,20745032,21392640,19079067,12214954,21434908,10172565,10433859,21493149,19445735,16531822,20964658,21555009,21579425,21681834,12729931,21712350,21732008,18864150,16414174,10772371,21880846,13000859,21942883,21944343,15817016,21997677,21999081,22000488,22000693,21990957,22003799,22016253,14463811,22018578,21997349,22020143,21003895,16224169,22027041,22029137,14077201,12832898,22031978,22032065,22032081,22032693,21984512,22045782,12686549,21997107,22055969,22059839,22061281,22057622,22040519,17166561,22074202,14423855,19208190,22044005,22032118,20124993,22103845,22105321,22101062,19250437,22086034,22109863,22111197,22113870,16708350,22114130,21027918,12896574,22122108,21937769,22099670,10395707,22125459,22059009,22018429,22129051,22129179,21884737,22140786,22146157,21333337,13320475,16627905,22147872,22148145,22148218,22138945,22153758,21937769,21316031,10984854,22052029,21900595,10984854,13055898,21865910,22147347,22176843,10891820,11100912,19983966,22167903,22130170,22160203,22187764,17288905,22188728,10805034,21990562,22113576,11370965,22041630,22048965,22193935,22198658,21879658,22099234,22185906,16958102,22205418,10072700,21262018,16458508,15446621,22215281,19900829,22186962,22058315,22184326,20581891,22023260,22073283,22225168,13586059,22199371,22229094,22225037,20884988,22210904,17574899,22232689,10194211,13247321,12810861,22247691,22248814,21900626,13115449,20137041,22250821,22251080,22067282,22252422,22033601,22251342,22051750,22256459,22247950,22258113,22262233,17335434,20734540,21937679,22148242,10094112,12227292,22035868,11295673,22266015,22138945,22248125,22062822,22282765,22275467,10780899,22285121,22286068,22274325,14136035,22183337,22275467,22294176,10126407,12401758,22108485,22300860,10101141,12986082,19052614,10850254,20985912,22274325,22272363,22316345,20144800,22318698,11478985,13366284,22289066,13947404,16265363,12856271,22332615,22334916,22338241,22340036,22342467,18533601,11341447,12047035,20526477,16478932,21937679,22353314,17089621,22284523,20412973,20586518,22338610,17434247,20419543,20410060,22362722,22062120,12631541,22363955,20475491,20407562,22365260,22361022,10986128,22366979,20899325,10766370,22106217,22369953,16760187,18185387,10993993,22372972,22324613,22033285,13841685,22378227,13302059,20029709,22385707,22284523,22363862,10639234,12919506,11598711,22318735,20752469,21759469,13630319,22124397,22406276,22344717,20413993,11097971,16385164,22411782,17545467,20409325,22109428,22413741,22217720,10032022,22172255,22416390,22129290,22420218,22420350,22420902,12506446,21103935,20414023,22421467,22421795,22422037,22422037,22422119,20399951,20528136,22423883,22425721,22425766,22425907,22426484,22024140,20912573,22395526,22316589,14466026,22428396,10717059,22426337,22435538,20414020,10141534,22350322,16502636,14884204,22235806,22430360,17899555,22454911,22464005,14965992,10885236,20422167,22361022,22482573,22483339,22472238,22472748,22489728,16599650,22492123,12844027,14360909,22502179,11803509,21939729,22037816,21760619,22043533,22080652,16062211,22520657,22523422,20723014,21092255,22527768,22353853,12886890,20805306,22536182,22536482,22536815,22537156,22537230,22537244,22538352,22052234,22539615,12515671,22540289,22540555,22541109,22234765,22414879,22542721,22542983,22543461,22543820,21656574,22547430,22546579,22484914,14883154,22555085,22352371,22557495,22558035,22558035,22337437,14882516,22560645,22561816,22534369,22133506,22537101,22564153,22535925,22570500,22571314,22561692,22570322,22224588,17303691,22499024,22586212,22554255,22589199,22537434,22589575,22543373,22591394,22591570,22248936,22591684,22557657,22592411,14873874,21868314,22259489,22595597,22595547,22595824,22595936,22596125,22596109,22596259,22596973,22597240,22597226,22597476,22597918,22597994,22598317,22598512,22598783,17709187,22601067,22601355,22601503,22601516,22601908,22601892,22602071,22602048,22602156,22602195,22602459,22568945,22602729,22602802,22602832,22603001,22603149,22603686,22603798,22603948,22291136,22604220,22604243,22604357,22604457,22604471,22604826,22536647,22606612,22607681,22134614,22570924,22347319,22627024,20400986,20504545,18810864,22638619,22147758,21850500,11677590,22649776,22655116,11558987,22659381,22662928,22666208,22667829,22650651,22673485,22674101,22676066,22277147,22680209,20504309,10908219,22650474,22428731,22703063,22432987,22652508,22714646,22373304,21936821,22722755,12203222,20470919,17271902,22653836,13134484,22732011,22733961,17164850,19346842,22734856,22736423,15986071,22743200,22751833,20407332,22495884,20404091,22733120,22763759,22686419,22778892,22441514,22734706,22044827,22791534,22791934,22783829,10102252,16486111,17368171,16179027,22275467,20772536,10556492,22001638,10469865,17495992,22808176,22539639,22809371,22811048,22812890,22236201,22815490,21780393,22316142,22808382,13786219,13786219,19415867,20532132,22823694,22824673,22819604,22829467,22819604,22833492,22075296,16671999,22843252,20409684,21995955,22848157,21937720,22676130,14048456,22650452,22802079,22855147,22866500,22752244,14052657,22791178,20399800,22604055,22878543,22878836,20410630,22884780,18539209,22891044,20413495,16154191,22896766,22899534,22366776,22798579,12460864,22912526,20410494,22924677,22922526,22926603,13086039,18423634,21998443,22225597,22963808,22964685,22968105,22110061,22973762,22975610,22874506,22978010,22164568,22975558,22234399,14070969,22136134,17350916,22166153,21946092,22992962,22529585,11225713,23019187,23019278,23019336,23019336,13122783,22059143,23022073,22359771,14466026,22255620,22124727,22839055,12530220,23038200,22054508,10689558,22976583,23044690,21937685,23047627,23051650,22450108,12694300,20867959,22070541,22299348,22055440,23002135,22179292,23064691,23063722,12859450,23066130,23067440,22443726,22589169,20404530,22125933,23055919,23073434,20410220,22441195,20448343,23077845,23077918,23079942,22046506,22026903,23085148,22261339,23093643,13022456,13191930,22943554,23096171,22949781,23067785,21865910,12605454,21261017,22026793,22136134,23118005,23118005,23118458,14689136,23123271,12474614,23150408,23153607,23159873,22432963,22966671,23166789,16761553,20578843,23182925,12467984,22523306,23197929,23223275,23224805,22071889,13152801,23236654,20531895,23250376,20784266,23236692,23265326,23076780,23272525,23291313,23324915,23325032,23325032,23331285,22204793,21749300,23333639,13720664,23326550,19436118,11343423,23321682,22133506,23371079,23375821,22588335,22415503,16428069,17854965,23391825,12189112,23394470,21548080,23379856,23389146,11813732,23394336,21306440,12809993,22480967,22363569,22213125,22424877,15404514,21871991,23434139,11949190,23022073,22071889,14209361,21038596,23439595,23452398,22610273,10695431,16154191,22281212,16154191,23480897,10093926,23486136,22379421,23491145,23495508,23500169,23501434,22924378,23505654,23508547,10336965,20516145,20483122,23514113,11826140,23514544,23516672,23518909,23519188,23520023,23522166,22406110,20405062,22255964,23532470,22541679,22193729,19038007,22774773,20056659,23542242,20400850,23547557,23548365,22055009,23550349,17161887,23557731,21536245,23588504,22878836,12094647,23065213,20407486,22424073,20791743,23386732,21493954,23597465,12226982,23612020,15522300,23620055,18769483,18573598,22774773,23640892,23645518,23655554,23662808,23600016,23661170,23671456,23692315,23676340,23674115,23756122,23758833,10874748,16347429,23636054,23675046,23769840,23525912,23780867,11141620,23792456,23792794,23793314,21061891,23813577,23816554,23817240,20980252,23822198,21361410,23826268,23688785,23792782,23824908,13557858,23809414,23829091,23112050,10153461,23841532,23762956,20412420,23863082,23785160,10598629,23434465,11630752,21306440,12002409,13177899,23879031,13486449,10213555,23886090,23866333,23887504,20863219,10360902,23887155,22227122,23894422,23898251,23897989,23903766,23811494,23904668,23389164,23783559,23908663,23908942,23910265,23899197,22895461,21138717,20576314,23918569,22024396,23919576,14213328,23886420,23929196,10515358,23931844,23932076,23932076,23932527,23932642,23932765,23932700,13652956,12721220,22519853,20485006,23401479,23902482,22255458,23942531,22541679,11797294,23831138,23946877,16999142,23949714,23950486,23938796,18570843,23956256,23957264,23956024,23955999,23950122,23962449,23962563,23963424,23951948,23880729,23970532,23971543,17703661,23974528,23974723,23970532,23973026,23981926,12997317,23884883,23983779,18065189,23995827,23997942,23961687,22082563,23995527,24000596,24001643,10780899,11381177,22039217,24005372,23975328,24035127,24035127,24039699,24000330,23973026,10126407,19411179,24046410,24054630,23953387,23953387,24059889,24066329,24059911,24059900,24059900,24073379,24074605,23897437,22000984,24077724,23814069,18145878,24085747,24085747,23803048,24087865,24088437,23958372,24019965,22968797,24093530,24097007,24058885,24094423,24109792,23816577,24114887,23897791,19293542,24075169,22344764,24129359,13960686,23966878,10617564,20489955,23773315,23533405,11630711,24138320,10162112,24150617,20399984,24151595,24110808,15377931,11756547,23835298,24169205,13087300,23893879,16295204,24185179,24187599,24068798,24190662,24192646,24192797,24193893,23926215,22332018,24192830,24164287,24241593,24245919,18238966,24284054,24287467,24295254,19899236,17358843,24317785,5969872159,21235686,22385240,24330292,24330881,24363083,24374445,23813946,10246036,17948902,24416065,24416054,24416076,24416197,24416196,24416221,24416341,24416266,24416378,24416364,24417059,24417199,24417491,24336038,24326142,24255097,24456005,23944409,17741414,13087300,22342214,24560026,24478173,24368723,24071489,16634906,12759580,18653828,24632289,6573928310,23970898,22753204,24375204,24655649,12604943,24714044,24636678,24718828,24718828,24726040,24729439,24732642,24735563,24737231,24740084,24476402,20420853,15799228,24769678,24761848,24775592,24775592,24775592,24776554,24776554,24776635,24776635,24776635,24777464,24375204,24782929,24777609,24777609,24783772,24785274,24785274,24785325,24522242,24792110,24792110,24792110,24419200,24793979,13896349,22070778,24799899,24801597,24807108,24812603,24822248,24838000,24659668,24085280,24840590,24840450,24842004,24842141,24086464,21030577,24843468,22655270,24844138,24844995,23980942,22540555,20937705,23830459,24854174,23915486,24036303,22376606,24860605,24861038,20648831,24902966,20533191,24951804,18644815,23373464,24982993,25017338,25044056,25044111,25044570,25044166,25052854,11872291,25061726,18093763,18524562,25059893,23379193,25128255,10804459,25160697,24789546,25255430,14312211,25289592,25289592,25313440,25317298,25313466,25322209,25285938,25327431,25341138,25344277,25346253,24830456,25314276];
        $len = count($data);
        $data_temp = [];
        $i = 0;
        echo 'uid      phone\n\r';
        while($i<=$len){
            $temp = array_slice($data,$i,$i+1000);
            $i = $i+1001;
            $sql = "select id,phone from lcs_user_index where id in (". implode(',',$temp) .") group by id";
            $result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if($result){
                foreach ($result as $k){
                    $data_temp[$k['id']] = CommonUtils::decodePhoneNumber($k['phone']);
                    echo $k['id'].'    '.  CommonUtils::decodePhoneNumber($k['phone']);
                }
            }
            sleep(1);
        }

    }

    public function UpdatePlnUtime($pln_id,$deal_time){
        $db_w = Yii::app()->lcs_w;
        $now = $deal_time; 
        $last = date("Y-m-d H:i:s",strtotime("-1 minute"));

        $db_w->createCommand("update lcs_plan_order set c_time='$now',u_time='$now',deal_time='$now' where pln_id='$pln_id' and u_time>='$last'")->execute();

        $db_w->createCommand("update lcs_plan_statement set c_time='$now',u_time='$now',deal_time='$now' where pln_id='$pln_id' and c_time>='$last'")->execute();

        $db_w->createCommand("update lcs_plan_transactions set c_time='$now',u_time='$now' where pln_id='$pln_id' and c_time>='$last'")->execute();
    }
    

	/**
	 * 手续费
	 * @param type $money
	 * @param type $type
	 * @return type
	 */
	public static function getCost($money,$type){
		if($type != 1 && $type != 2){
			return 0;
		}
		$cost = 0;
		$commission = $money * 0.0003; //万三佣金
		$tsfee = $money * 0.00002; //过户费		
		$commission = $commission > 5 ? $commission : 5;
		if($type == 1){ //买入
			$cost = $commission + $tsfee;			
		}else{ //卖出
			$stamps = $money * 0.001; //千分之一印花税
			$cost = $commission + $tsfee + $stamps;
		}
		return $cost;
	}
}
