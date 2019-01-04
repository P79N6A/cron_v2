<?php

/**
 * Created by PhpStorm.
 * User: meixin
 * Date: 2017/7/20
 * Time: 上午11:43
 */
class AgutestCommand extends LcsConsoleCommand
{


    // 初始化信达投顾服务模块权限
    public function actionAgu($the_file)
    {


        $excel = new PHPExcel();
        $objReader = PHPExcel_IOFactory::createReader("Excel5");
        $objExcel = $objReader->load($the_file);
        $sheetNames = $objReader->listWorksheetNames($the_file);
        var_dump($sheetNames);
        $sheetCount = $objExcel->getSheetCount();

        $i = 0 ;
        $duplicate_name = ['陈华','李彬','李波','陈静','孙怀青','李娜','张磊','刘涛','A股之龙','水生wevall','胡亚伟',
            '胡亚伟','李文宪','马鑫','王娟','孟潭','张健','杨佳丽'];
        /**
         * | real_name    | count |
        +--------------+-------+
        | 陈华         |     2 |
        | 李彬         |     2 |
        | 李波         |     2 |
        | 陈静         |     2 |
        | 孙怀青       |     2 |
        | 李娜         |     2 |
        | 张磊         |     2 |
        | 刘涛         |     2 |
        | A股之龙      |     2 |
        | 水生wevall   |     2 |
        | 胡亚伟       |     2 |
        | 李文宪       |     2 |
        | 马鑫         |     2 |
        | 王娟         |     2 |
        | 孟潭         |     2 |
        | 张健         |     2 |
        | 杨佳丽       |     2 |
         */
        while($i<$sheetCount-1) {

            //选择第一个工作表
            $currentSheet = $objExcel->getSheet($i);

            //取得一共有多少列
            //$allColumn = $currentSheet->getHighestColumn();
            $allColumn = 'H';

            //取得一共有多少行
            $allRow = $currentSheet->getHighestRow();

            echo $allColumn."--".$allRow."\n";
            //exit;

            $code_name = $p_name_arr = $code_arr = [];
            for($currentRow = 2;$currentRow<=$allRow;$currentRow++) {
                $p_name = trim($currentSheet->getCell("D".$currentRow)->getValue());
                $code =  trim($currentSheet->getCell("H".$currentRow)->getValue());
		$res = preg_match("/\d+/" , $code, $matches);
		if(!$res) continue;
		$code = ($matches[0]);
                if(empty($code) || empty($p_name)) continue;
		$p_name_arr[] = $p_name;
		$code_arr[] = $code;
		$code_name[$code] = $p_name;
                $zu = $sheetNames[$i];
                $fuzeren = $currentSheet->getCell("C".$currentRow)->getValue();
                if(in_array($p_name ,$duplicate_name)) {
                    file_put_contents('chongming.txt' , $zu."\t".$fuzeren."\t".$p_name."\t".$code."\n");
                    continue;
                }
                echo $p_name."\t".$code."\n";
                #if($currentRow>5) break;
            }
            $code_info = AguCircle::model()->getCodeInfo($code_arr);
            $pinfo = AguCircle::model()->getPuid($p_name_arr);
            foreach ($code_name as $code=>$name){
		if(!isset($code_info[$code])){
                	$str = $name."\t".$code;
                	file_put_contents("Agu 不存在.txt" ,$str."\n" , FILE_APPEND);
			continue;
		}
		if(!isset($pinfo[$name])){
                	$str = $name."\t".$code;
                	file_put_contents("理财师 不存在.txt" ,$str."\n" , FILE_APPEND);
			continue;
		}
                //插入数据
                $data = array(
                    'type'          => 3001,
                    'relation_id'   => $code_info[$code]['id'],
                    //'u_type'        => 2,
                    'p_uid'         => $pinfo[$name]['s_uid'],
                    'title'         => $code_info[$code]['name']."的圈子",
		    'summary'       => "圈主还没有编辑简介哦~",
                    'c_time'        => date('Y-m-d H:i:s'),
                    'u_time'        => date('Y-m-d H:i:s'),
                );
                $id = AguCircle::model()->insertCircle($data);
                $str = $id."\t".$name."\t".$code."\t".implode("\t" , $data);
                #$str = $name."\t".$code."\t".implode("\t" , $data);
echo $str."\n";
                file_put_contents($sheetNames[$i].".txt" ,$str."\n" , FILE_APPEND);
#                exit;
            }

#            exit;
            $i++;

        }


    }

    /**
     * 初始化A股理财师圈子图片和关注人数
     */
    public function actionAgulicaishiImage(){
        $sql = "select count(1) from lcs_circle where type=3001";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        var_dump($res);
        $sql = "select id,p_uid from lcs_circle where type=3001";
        $circle_info = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $p_uid = [];
        foreach($circle_info as $item) {
            $p_uid[] = $item['p_uid'];
        }
        // 开始初始化
        $sql = "select s_uid,name,image,partner_id from lcs_planner where s_uid in (".implode(',',$p_uid).") and status=0";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $planner_info = [];
        foreach($res as $item){
            $pid = $item['s_uid'];
            $planner_info[$pid] = $item;
        }
        foreach($circle_info as $item){
            $p_info = isset($planner_info[$item['p_uid']]) ? $planner_info[$item['p_uid']] : null;
            if(empty($p_info)) continue;
            $up_data = array(
                'image' => $p_info['image'],
                'user_num' => 1,
            );
            $res = Yii::app()->lcs_w->createCommand()->update('lcs_circle' , $up_data, "id=:id ",array(':id'=>$item['id']));
            if($res){
                $str = $item['id']."\t".$item['p_uid'];
                file_put_contents("circle_planner.txt" ,$str."\n" , FILE_APPEND);

            }
            exit;

        }


    }

    /**
     * 初始化关注圈子用户【圈主理财师自己】
     */
    public function actionAguCircleUser(){
        $sql = "select count(1) from lcs_circle where type=3001";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        var_dump($res);
        $sql = "select id,p_uid,c_time,u_time from lcs_circle where type=3001";
        $circle_info = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $p_uid = [];
        foreach($circle_info as $item) {
            $p_uid[] = $item['p_uid'];
        }
        // 开始初始化
        $sql = "select s_uid,name,image,partner_id from lcs_planner where s_uid in (".implode(',',$p_uid).") and status=0";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $planner_info = [];
        foreach($res as $item){
            $pid = $item['s_uid'];
            $planner_info[$pid] = $item;
        }

        foreach($circle_info as $item){
            $insert_data = array(
                'u_type'    =>  2,
                'uid'       =>  $item['p_uid'],
                'circle_id' =>  $item['id'],
                'c_time'    =>  $item['c_time'],
                'u_time'    =>  $item['u_time']
            );
            Yii::app()->lcs_w->createCommand()->insert('lcs_circle_user' , $insert_data);
            $insert_id = Yii::app()->lcs_w->getLastInsertID();
            if($insert_id){
                $str = $insert_id."\t".$item['id']."\t".$item['p_uid'];
                file_put_contents("circle_user.txt" ,$str."\n" , FILE_APPEND);

            }
            exit;
        }

    }


}
