<?php

/**
 * 用户昵称
 *
 * add by zhihao6 2016/03/24
 */

class UserNameService
{
    // 外部功能接口 2.0
    public static function getRandomUserNameV2($gender='m', $ruler='A+B+D', $num=100)
    {
        if (!in_array($gender, self::$gender_dic)) {
            $gender = self::getRandomDicValue(self::$gender_dic);
            $gender = $gender['0'];

            $ruler = self::getRandomNameRuler($gender);
        }

        $name = array();
        switch ($ruler) {
        case 'A+B': // no break
        case 'A+C':
            $name = self::XYRuler($gender, $num);
            break;
        case 'B+A': // no break
        case 'C+A':
            $name = self::YXRuler($gender, $num);
            break;
        default:
            $name = self::XYZRuler($gender, $num);
            break;
        }
        return $name;
    }
    public static function XYRuler($gender, $num)
    {
        if ($gender == 'f') {
            $x_ruler = 'A';
            $y_ruler = 'C';
        } else {
            $x_ruler = 'A';
            $y_ruler = 'B';
        }

        $x = self::$name_dic[$x_ruler];
        $y = self::$name_dic[$y_ruler];
        $max_x = count($x);
        $max_y = count($y);
        $x_i = self::getRulerDicIndex($gender, 'XY', $x_ruler);
        $y_i = self::getRulerDicIndex($gender, 'XY', $y_ruler);

        $name = array();
        while ($x_i < $max_x) {
            while ($y_i < $max_y) {
                $tmp_name = "{$x[$x_i]}{$y[$y_i]}";

                $y_i++;
                if (self::isNameLengthVaild($tmp_name)) {
                    $name[] = $tmp_name;
                    if (--$num < 1) {
                        break;
                    }
                }
            }
            if ($y_i >= $max_y) {
                $x_i++;
                $y_i = 0;
            }
            if ($num < 1) {
                break;
            }
        }
        
        self::setRulerDicIndex($gender, 'XY', $x_ruler, $x_i);
        self::setRulerDicIndex($gender, 'XY', $y_ruler, $y_i);

        return $name;
    }
    public static function YXRuler($gender, $num)
    {
        if ($gender == 'f') {
            $x_ruler = 'C';
            $y_ruler = 'A';
        } else {
            $x_ruler = 'B';
            $y_ruler = 'A';
        }

        $x = self::$name_dic[$x_ruler];
        $y = self::$name_dic[$y_ruler];
        $max_x = count($x);
        $max_y = count($y);
        $x_i = self::getRulerDicIndex($gender, 'YX', $x_ruler);
        $y_i = self::getRulerDicIndex($gender, 'YX', $y_ruler);

        $name = array();
        while ($x_i < $max_x) {
            while ($y_i < $max_y) {
                $tmp_name = "{$x[$x_i]}{$y[$y_i]}";

                $y_i++;
                if (self::isNameLengthVaild($tmp_name)) {
                    $name[] = $tmp_name;
                    if (--$num < 1) {
                        break;
                    }
                }
            }
            if ($y_i >= $max_y) {
                $x_i++;
                $y_i = 0;
            }
            if ($num < 1) {
                break;
            }
        }
        
        self::setRulerDicIndex($gender, 'YX', $x_ruler, $x_i);
        self::setRulerDicIndex($gender, 'YX', $y_ruler, $y_i);

        return $name;
    }
    public static function XYZRuler($gender, $num)
    {
        if ($gender == 'f') {
            $x_ruler = 'A';
            $y_ruler = 'C';
            $z_ruler = 'D';
        } else {
            $x_ruler = 'A';
            $y_ruler = 'B';
            $z_ruler = 'D';
        }

        $x = self::$name_dic[$x_ruler];
        $y = self::$name_dic[$y_ruler];
        $z = self::$name_dic[$z_ruler];
        $max_x = count($x);
        $max_y = count($y);
        $max_z = count($z);
        $x_i = self::getRulerDicIndex($gender, 'XYZ', $x_ruler);
        $y_i = self::getRulerDicIndex($gender, 'XYZ', $y_ruler);
        $z_i = self::getRulerDicIndex($gender, 'XYZ', $z_ruler);
// print_r("\n$x_i:$y_i:$z_i\n");

        $name = array();
        while ($x_i < $max_x) {
            while ($y_i < $max_y) {
                while ($z_i < $max_z) {
                    $tmp_name = "{$x[$x_i]}{$y[$y_i]}{$z[$z_i]}";

                    $z_i++;
                    if (self::isNameLengthVaild($tmp_name)) {
                        $name[] = $tmp_name;
                        if (--$num < 1) {
                            break;
                        }
                    }
                }
                if ($z_i >= $max_z) {
                    $y_i++;
                    $z_i = 0;
                }
                if ($num < 1) {
                    break;
                }
            }
            if ($y_i >= $max_y) {
                $x_i++;
                $y_i = 0;
            }
            if ($num < 1) {
                break;
            }
        }
// print_r("$x_i:$y_i:$z_i\n");
    
        self::setRulerDicIndex($gender, 'XYZ', $x_ruler, $x_i);
        self::setRulerDicIndex($gender, 'XYZ', $y_ruler, $y_i);
        self::setRulerDicIndex($gender, 'XYZ', $z_ruler, $z_i);

//         $x_i = self::getRulerDicIndex($gender, 'XYZ', $x_ruler);
//         $y_i = self::getRulerDicIndex($gender, 'XYZ', $y_ruler);
//         $z_i = self::getRulerDicIndex($gender, 'XYZ', $z_ruler);
// print_r("$x_i:$y_i:$z_i\n");

        return $name;
    }
    public static function clearRulerDicIndex()
    {
        $redis_key = "lcs_random_name_test";

        $res = Yii::app()->redis_w->hGetAll($redis_key);
        print_r($res);
        
        Yii::app()->redis_w->delete($redis_key);

        $res = Yii::app()->redis_w->hGetAll($redis_key);
        print_r($res);
    }
    public static function getRulerDicIndex($gender, $ruler, $dic)
    {
        $redis_key = "lcs_random_name_test";
        $filed_key = "{$gender}_{$ruler}_{$dic}";
        $index = Yii::app()->redis_r->hget($redis_key, $filed_key);
        if (empty($index)) {
            return 0;
        } else {
            return $index;
        }
    }
    public static function setRulerDicIndex($gender, $ruler, $dic, $val)
    {
        $redis_key = "lcs_random_name_test";
        $filed_key = "{$gender}_{$ruler}_{$dic}";
        $index = Yii::app()->redis_w->hset($redis_key, $filed_key, $val);
        if (empty($index)) {
            return 0;
        } else {
            return $index;
        }
    }
    public static function isNameLengthVaild($name)
    {
        $curr_len = (strlen($name) + mb_strlen($name,'UTF8')) / 2;
        if ($curr_len > self::$name_length) {
            return false;
        } else {
            return true;
        }
    }

    // 基础方法
    public static function getRandomNameRuler($gender='m')
    {
        $ruler = self::$name_ruler[$gender];
        $ruler = self::getRandomDicValue($ruler);
        $ruler = $ruler['0'];
        return $ruler;
    }
    public static function getRandomDicValue($dic, $num=1)
    {
        $dic_total_num = count($dic);
        $left_num = $num - $dic_total_num;
        if ($left_num < 0) {
            $random_keys = array_rand($dic, $num);
        } else {
            $left_num = $num % $dic_total_num;
            if ($left_num > 0) {
                $random_keys = array_rand($dic, $left_num);
                if (!is_array($random_keys)) {
                    $random_keys = array($random_keys);
                }
            } else {
                $random_keys = array();
            }
            $times = floor($num / $dic_total_num);
            while ($times-- > 0) {
                $random_keys = array_merge($random_keys, array_keys($dic));
            }
        }

        if (is_array($random_keys)) {
            $rtn = array();
            foreach ($random_keys as $key) {
                $rtn[] = $dic[$key];
            }
            return $rtn;
        } else {
            return array($dic[$random_keys]);
        }
    }
    public static function getNameDic($dic_index)
    {
        return self::$name_dic[$dic_index];
    }

    // 配置
    public static $gender_dic = array('m','f');
    public static $name_length = 14; // 中文算2个字符
    public static $name_ruler = array(
        'm' => array('A+B+D'),
        'f' => array('A+C+D'),
    );
    public static $name_dic = array(
        'A' => array(
            "A股","上证","深证","B股","H股","红筹","蓝筹","创业板","主板","牛市",
            "追涨","内盘","外盘","五连阳","实盘","短线","中线","长线","涨停","稳健",
            "期货","证券","股票","基金","美股","港股","台股","新三板","中小盘","领涨",
            "暴涨","商界","私募","公募","众筹","风投","领投","创业","P2P","激进",
            "稳涨","英武","富裕","温和","魅力","欢快","优雅",
        ),
        'B' => array(
            "小财神","财友","小财兜","财主","财迷","财娃","财豪","小财爷","招财猫","小王子",
            "高手","土豪","小能手","神探","大咖","神算手","男神","小咖","少爷","男人",
            "大叔","猛男","肌肉男","萌叔","少年","书生","孤侠","奇侠","大汉","大牛",
            "大拿","野马","水牛","猛犸","大象","驯鹿","小牛","猛兽","嵩山","远山",
            "草原","松柏","小狼","太阳","春田","清风","高山","荞麦","万年青","盆景",
            "西风","红日","旭日","山河","汪洋","山峰","森林","海浪","大师","队长",
            "摇钱树","小马甲","电机","机车","马达","潜艇","火箭","坦克","航母","轰炸机",
            "金牛","一霸","地王","金主","专业户","博物馆","保险箱","金条","银条","无崖子",
            "段誉","大彪","战鹰","红人","欧巴","大鹏","法师","杨过","火龙","大雕",
            "统领","元芳","将军","头子","先生","段正淳","神童","郭靖","洪七公","黄药师",
            "铁木真","韦小宝","黑白子","绿竹翁","张三","张三丰","逍遥子","乔峰","公子","阿古打",
            "吴长风","辛双清","岳老三","单伯山","单季山","单叔山","单小山","段延庆","段正明","范禹",
            "和里布","孟师叔","华赫艮","郁光标","卓不凡","范百龄","哈大霸","姜师叔","吴光胜","贾老者",
            "康广陵","容子矩","桑土公","唐光雄","奚长老","徐长老","诸保昆","崔百泉","崔绿华","鲍千灵",
            "褚万里","端木元","慕容博","慕容复","谭青","摘星子","慧方","慧观","慧净","薛慕华",
            "小沙弥","木华黎","丘处机","沈青刚","周伯通","段天德","郭啸天","郝大通","侯通海","盖运聪",
            "梁长老","梁子翁","计无施","木高峰","风清扬","丛不弃","王伯奋","王诚","王二叔","令狐冲",
            "宁中则","平夫人","平一指","申人俊","玉钟子","左冷禅","成不忧","齐堂主","吉人通","陆大有",
            "沙天江","秃笔翁","吴天德","严三星","余沧海","余人彦","张金鏊","易师爷","易堂主","英白罗",
            "英长老","岳不群","郑镖头","周孤桐","封不平","洪人雄","施戴子","施令威","闻先生","游迅",
            "葛长老","震山子","程遥迦","彭连虎","韩无垢","童大海","樊一翁","丁不三","丁不四","丁珰",
            "龙岛主","贝海石","木岛主","梅剑和","温正","温南扬","焦公礼","程青竹","褚红柏","董开山",
            "温方施","温方山","温方悟","温方达",
        ),
        'C' => array(
            "小财女","囡囡","静儿","点点","女神","女王","妹子","御姐","公主","夫人",
            "妞妞","甜甜","小妮子","小龙女","萌妹","美人","小金鱼","嘟嘟","丁丁","喵喵",
            "冰冰","彩月","朵朵","树懒","雪花","玫瑰","牡丹","茉莉","向日葵","水仙",
            "花梨","百合","杜鹃","海棠","小豆芽","木棉","甜甜圈","棉花糖","凌霄","深海鱼",
            "柠檬","宝宝","水滴","含笑","栀子花","热带鱼","柳芽","蒲公英","萨摩","泰迪",
            "菜包子","米老鼠","唐老鸭","小麦","水稻","萌芽","小鹿","月亮","秋葵","樱花",
            "布丁","英英","芒果","苹果","杏子","香蕉","海棠果","橙子","黑莓","杨桃",
            "樱桃","栗子","板栗","猕猴桃","山核桃","椰子","橘子","草莓","西瓜","火龙果",
            "毛桃","甜瓜","干果","无花果","榛子","荔枝","银杏","葡萄","青梅","哈密瓜",
            "山楂果","山楂","水蜜桃","金桔","龙眼","枇杷","柑桔","黑樱桃","香瓜","脐橙",
            "坚果","沙枣","桃子","梨","菠萝","李子","四季豆","豌豆","胡豆","毛豆",
            "黄豆芽","绿豆芽","甘蓝菜","包心菜","白菜","小白菜","水白菜","西洋菜","通心菜","花椰菜",
            "西兰花","空心菜","芥菜","芹菜","蒿菜","甜菜","紫菜","生菜","菠菜","",
            "香菜","发菜","榨菜","雪里红","莴苣","芦笋","竹笋","笋干","韭黄","白萝卜",
            "胡萝卜","菜瓜","丝瓜","南瓜","黄瓜","青瓜","冬瓜","小黄瓜","山芋","芋头",
            "香菇","草菇","金针菇","蘑菇","冬菇","萍菇","番茄","茄子","马铃薯","莲藕",
            "青椒","红尖椒","圆椒","花花","佳雪","魅雪","梦瑶","宁月","梦婷","梦田",
            "雅雯","梦舒","雪娴","秀影","梦梵","薇薇","思颖","欣然","静香","梦洁",
            "凌薇","美莲","雅静","雪丽","雪莉","依娜","伊娜","安娜","安妮","艾莉尔",
            "贝蒂","邦妮","卡米尔","卡米拉","戴安娜","芭拉","卡洛琳","凯瑟琳","彩凤","彩霞",
            "彩鸾","彩明","茗烟","若雨","雨婷","诗涵","紫妍","欣艺","雪瑶","亦晨",
            "文萱","大乔","宛儿","雨双",
        ),
        'D' => array(
            1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,
            21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,
            41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,
            61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,
            81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,
            101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,
            121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,
            141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,
            161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,
            181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,
            201,202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,
            221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,
            241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,
            261,262,263,264,265,266,267,268,269,270,271,272,273,274,275,276,277,278,279,280,
            281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,
            301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,318,319,320,
            321,322,323,324,325,326,327,328,329,330,331,332,333,334,335,336,337,338,339,340,
            341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,358,359,360,
            361,362,363,364,365,366,367,368,369,370,371,372,373,374,375,376,377,378,379,380,
            381,382,383,384,385,386,387,388,389,390,391,392,393,394,395,396,397,398,399,400,
            401,402,403,404,405,406,407,408,409,410,411,412,413,414,415,416,417,418,419,420,
            421,422,423,424,425,426,427,428,429,430,431,432,433,434,435,436,437,438,439,440,
            441,442,443,444,445,446,447,448,449,450,451,452,453,454,455,456,457,458,459,460,
            461,462,463,464,465,466,467,468,469,470,471,472,473,474,475,476,477,478,479,480,
            481,482,483,484,485,486,487,488,489,490,491,492,493,494,495,496,497,498,499,500,
            501,502,503,504,505,506,507,508,509,510,511,512,513,514,515,516,517,518,519,520,
            521,522,523,524,525,526,527,528,529,530,531,532,533,534,535,536,537,538,539,540,
            541,542,543,544,545,546,547,548,549,550,551,552,553,554,555,556,557,558,559,560,
            561,562,563,564,565,566,567,568,569,570,571,572,573,574,575,576,577,578,579,580,
            581,582,583,584,585,586,587,588,589,590,591,592,593,594,595,596,597,598,599,600,
            601,602,603,604,605,606,607,608,609,610,611,612,613,614,615,616,617,618,619,620,
            621,622,623,624,625,626,627,628,629,630,631,632,633,634,635,636,637,638,639,640,
            641,642,643,644,645,646,647,648,649,650,651,652,653,654,655,656,657,658,659,660,
            661,662,663,664,665,666,667,668,669,670,671,672,673,674,675,676,677,678,679,680,
            681,682,683,684,685,686,687,688,689,690,691,692,693,694,695,696,697,698,699,700,
            701,702,703,704,705,706,707,708,709,710,711,712,713,714,715,716,717,718,719,720,
            721,722,723,724,725,726,727,728,729,730,731,732,733,734,735,736,737,738,739,740,
            741,742,743,744,745,746,747,748,749,750,751,752,753,754,755,756,757,758,759,760,
            761,762,763,764,765,766,767,768,769,770,771,772,773,774,775,776,777,778,779,780,
            781,782,783,784,785,786,787,788,789,790,791,792,793,794,795,796,797,798,799,800,
            801,802,803,804,805,806,807,808,809,810,811,812,813,814,815,816,817,818,819,820,
            821,822,823,824,825,826,827,828,829,830,831,832,833,834,835,836,837,838,839,840,
            841,842,843,844,845,846,847,848,849,850,851,852,853,854,855,856,857,858,859,860,
            861,862,863,864,865,866,867,868,869,870,871,872,873,874,875,876,877,878,879,880,
            881,882,883,884,885,886,887,888,889,890,891,892,893,894,895,896,897,898,899,900,
            901,902,903,904,905,906,907,908,909,910,911,912,913,914,915,916,917,918,919,920,
            921,922,923,924,925,926,927,928,929,930,931,932,933,934,935,936,937,938,939,940,
            941,942,943,944,945,946,947,948,949,950,951,952,953,954,955,956,957,958,959,960,
            961,962,963,964,965,966,967,968,969,970,971,972,973,974,975,976,977,978,979,980,
            981,982,983,984,985,986,987,988,989,990,991,992,993,994,995,996,997,998,999,
        ),
    );


}
