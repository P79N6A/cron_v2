<?php
/**
 * 数据备份服务
 */

class DumpService {

	#日志文件输出路径
	private $_log_path;

	public function __construct(){
		$this->_log_path = dirname(__FILE__)."/../../log/";
	}

    /**
    * 导出理财师数据结构
    */ 
    public function DumpStruct($target_db, $output_file){
        $db_config = $this->getConStr($target_db);
        $cmd_str = "mysqldump -h".$db_config['host']." -P".$db_config['port']." -u".$db_config['username']." -p".$db_config['password']." --opt -d ".$db_config['dbname']." --single-transaction --set-charset --default-character-set=utf8 > ".$this->_log_path.$output_file;
        exec($cmd_str);
    }

    /**
     * 按照条件导出数据
     *
     */
    public function mysqldump($db,$table,$where){
        var_dump("mysqldump process $table");
        $db_config = $this->getConStr($db);
        $cmd_str = "mysqldump -h".$db_config['host']." -P".$db_config['port']." -u".$db_config['username']." -p".$db_config['password']." ".$db_config['dbname']." --single-transaction -t --set-charset --default-character-set=utf8 > ".$this->_log_path.$table.".sql";
        exec($cmd_str." ".$table." --where=\"".$where."\"");
    }

    /**
     * 条件中数据量太大会失败，因此分页导出数据
     *
     */
    public function mysqldumpPageData($db,$table,$where,$data){
        var_dump("mysqldump pagedata process $table");
        $db_config = $this->getConStr($db);
        $temp_array = array();
        foreach($data as $item)
        {
            $temp_array[] = $item;
            if(count($temp_array) == 100){
                $output_name = $table.rand(10000,99999);
                $in_where = $this->convertArrayToStr($temp_array);
                $new_where = str_replace("|replace|",$in_where,$where);
                $cmd_str = "mysqldump -h".$db_config['host']." -P".$db_config['port']." -u".$db_config['username']." -p".$db_config['password']." ".$db_config['dbname']." --single-transaction -t --set-charset --default-character-set=utf8 ";
                $cmd_str = $cmd_str." ".$table." --where=\"".$new_where."\" > ".$this->_log_path.$output_name.".sql";
                exec($cmd_str);
                $temp_array = array();
            }
        }

        if(count($temp_array)>0){
            $output_name = $table.rand(10000,99999);
            $in_where = $this->convertArrayToStr($temp_array);
            $new_where = str_replace("|replace|",$in_where,$where);
            $cmd_str = "mysqldump -h".$db_config['host']." -P".$db_config['port']." -u".$db_config['username']." -p".$db_config['password']." ".$db_config['dbname']." --single-transaction -t --set-charset --default-character-set=utf8 ";
            $cmd_str = $cmd_str." ".$table." --where=\"".$new_where."\" > ".$this->_log_path.$output_name.".sql";
            exec($cmd_str);
        }
    }

    /**
     * 将数组转化为，隔开的字符串
     *
     */
    public function convertArrayToStr($con_array){
        $in_str = "";
        $sigle = 0;
        foreach($con_array as $item){
            if($sigle == 0){
                $in_str = "'".$item."'";
            }else{
                $in_str = $in_str.",'".$item."'";
            }
            $sigle = $sigle + 1;
        }
        return $in_str;
    }

    /**
     * 从数据库配置中提取出连接字符串
     *
     */
    public function getConStr($db){
        $db_config = array();
        $connect_str = $db->connectionString;
        $connect_str = explode(';',$connect_str);

        foreach($connect_str as $item){
            $item_array = explode('=',$item);
            if(count($item_array)==2){
                if(strstr($item_array[0],'host')){
                    $db_config['host'] = $item_array[1];
                }elseif(strstr($item_array[0],'port')){
                    $db_config['port'] = $item_array[1];
                }elseif(strstr($item_array[0],'dbname')){
                    $db_config['dbname'] = $item_array[1];
                }
            }
        }
        $db_config['username'] = $db->username;
        $db_config['password'] = $db->password;
        return $db_config;
    }

    /**
    * 获取该机构所有理财师的id
    *
    */
    public function getPartnerPuids($partner_id){
        $db_r = Yii::app()->lcs_r;
        $sql = "select s_uid from lcs_planner where partner_id='$partner_id'";
        $res = $db_r->createCommand($sql)->queryAll();
        $result = array();
        foreach($res as $item){
            $result[] = $item['s_uid']; 
        }
        return $result;
    }

    /**
    * 获取该机构的channel_id
    *
    */
    public function getPartnerChannelId($partner_id){
        $db_r = Yii::app()->lcs_r;
        $sql = "select app_key from lcs_partner where id='$partner_id'";
        $res = $db_r->createCommand($sql)->queryScalar();
        return $res;
    }

    /**
    * 获取该机构的pln_id集合
    */
    public function getPartnerPlnIds($partner_id){
        $db_r = Yii::app()->lcs_r;
        $sql = "select pln_id from lcs_plan_info where partner_id='$partner_id'";
        $res = $db_r->createCommand($sql)->queryAll();
        $result = array();
        foreach($res as $item){
            $result[] = $item['pln_id']; 
        }
        return $result;
    }

    /**
    * 获取问答追加问题的id
    *
    */
    public function getPartnerAddQuestionIds($parnter_id,$p_uids){
        $db_r = Yii::app()->lcs_r;
        $sql = "select q_add_id from lcs_ask_question where p_uid in (".implode(',',$p_uids).") and q_add_id !=0";
        $res = $db_r->createCommand($sql)->queryAll();
        $result = array();
        foreach($res as $item){
            $result[] = $item['q_add_id']; 
        }
        return $result;
    }

    /**
    * 打包文件
    */
    public function tarFile($target,$condition){
        exec("cd ".$this->_log_path."; tar -cf $target $condition");
    }

    /**
    * 移动文件
    */
    public function mvFile($target,$path){
        exec("cd ".$this->_log_path."; mv $target $path");
    }

    /**
    * 清空文件
    */
    public function clearSQLFile($condition){
        exec("cd ".$this->_log_path.";rm $condition");
    }

    /**
    * 获取机构的用户uid
    */
    public function getPartnerUids($channel_id){
        $db_r = Yii::app()->lcs_r;
        $sql = "select uid from lcs_user_channel where channel_id='$channel_id'";
        $res = $db_r->createCommand($sql)->queryAll();
        $result = array();
        foreach($res as $item){
            $result[] = $item['uid']; 
        }
        return $result;
    }
}
