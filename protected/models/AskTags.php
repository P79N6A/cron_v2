<?php

/**
 * 问答的标签
 *
 */
class AskTags extends CActiveRecord {
	
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	
	public function tableName(){
		return TABLE_PREFIX .'ask_tags';
	}
	
	
	/**
	 * 根据问题内容解析出标签
	 *
	 * @param unknown_type $question
	 */
	public function getTagsByType($type){
		
		$type = (array)$type;
		if(empty($type)){
			return array();
		}else{
			foreach ($type as $key=>$val){
				$type["$key"] = "'$val'";
			}
		}
		
		$sql = "select id,name,type,code,symbol from ".$this->tableName()." where type in (".implode(',',$type).')';
		$cmd = Yii::app()->lcs_r->CreateCommand($sql);
		$res =$cmd->queryAll();
		return $res;
	}
	
	/**
	 * 根据tag名字获取信息
	 *
	 */
	public function getTagsByName($name,$type){

		$name = (array)$name;
		$type = (array)$type;
		$res = array();
		$where_name = '';
		$where_type = '';
		if(sizeof($name) > 0){
			foreach ($name as $val){
				$where_name .= Yii::app()->lcs_r->getPdoInstance()->quote($val).",";
			}
			$where_name = substr($where_name,0,-1);
			if(sizeof($type) > 0){
				$where_type = 'type in (';
				foreach ($type as $val ){
					$where_type .= Yii::app()->lcs_r->getPdoInstance()->quote($val).",";
				}
				$where_type = substr($where_type,0,-1).') and ';
			}
			$sql = "select id,symbol,name from ".$this->tableName()." where $where_type name in ($where_name)";

			$cmd = Yii::app()->lcs_r->CreateCommand($sql);
			$res =$cmd->queryAll();
		}

		return $res;

	}

    /**
     * 根据symbol获取信息
     * @param $type
     * @param $symbol
     * @return array
     */
    public function getTagsBySymbol($type,$symbol){
        $type = (array)$type;
        $symbol = (array)$symbol;

        $res = array();
        $where_type = '';
        $where_symbol = '';
        if(sizeof($type) > 0){
            foreach ($type as $val){
                $where_type .= Yii::app()->lcs_r->getPdoInstance()->quote($val).",";
            }
            $where_type = substr($where_type,0,-1);
            if(sizeof($symbol) > 0){
                foreach ($symbol as $val ){
                    $where_symbol .= Yii::app()->lcs_r->getPdoInstance()->quote($val).",";
                }
                $where_symbol = substr($where_symbol,0,-1);
            }
            $sql = "select id,symbol,name,code from ".$this->tableName()." where type in($where_type) and symbol in ($where_symbol)";

            $cmd = Yii::app()->lcs_r->CreateCommand($sql);
            $r =$cmd->queryAll();

            if(!empty($r)){
                array_walk($r,function($v) use (&$res){
                    $res[$v['symbol']] = $v;
                });
            }
        }

        return $res;
    }
    
    public function getTagBySymbol($symbol)
    {
        $mem_key = MEM_PRE_KEY . '_a_tag_'.$symbol;
        $result = Yii::app()->cache->get($mem_key);
        if (false === $result) {
            $sql = 'SELECT `id`,`type`,`code`,`symbol`,`name`,`pinyin` FROM ' . $this->tableName() . ' WHERE `symbol`=:symbol LIMIT 1';
            $result = Yii::app()->lcs_r->createCommand($sql)->bindParam(':symbol', $symbol, PDO::PARAM_STR)->queryRow();
            Yii::app()->cache->set($mem_key, $result, 3600);
        }
        
        return $result;
    }

    public function getTagbyId($id){
        $sql = "select id,type,code,symbol,name,pinyin,c_time from ". $this->tableName(). " where id =:id";
        $cmd = Yii::app()->lcs_r->CreateCommand($sql);
        $cmd->bindParam(':id', $id, PDO::PARAM_INT);
        $res = $cmd->queryRow();
        return $res;
    }


    /**
     * 根据股票代码查找
     * @param $s_word
     * @param int $page
     * @param int $num
     * @return array|unknown
     */
    public function getAskTagInfobySearchCode($s_word, $page=1, $num=15){

        $db_r = Yii::app()->lcs_r;
        $offset = CommonUtils::fomatPageParam($page, $num);
        $where_str = "type='stock_cn' and  code like '${s_word}%'";
        $sql_total = 'select count(id) as total from '.$this->tableName()." where " .$where_str;
        $cmd_count = $db_r->createCommand($sql_total);
        $total = $cmd_count->queryScalar();

        $total_page = ceil($total/$num);
        if($page > $total_page){
            $data = [];
            $result = CommonUtils::getPage($data, $page, $num, $total);
            return $result;

        }
        $sql = "select id,name,type,code,symbol from ".$this->tableName().' where '.$where_str
            . ' limit :offset,:limit;';
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':offset', $offset, PDO::PARAM_INT);
        $cmd->bindParam(':limit', $num, PDO::PARAM_INT);
        $data = $cmd->queryAll();

        $result = CommonUtils::getPage($data, $page, $num, $total);

        return $result;
    }

    public function getTagbyids($ids){

        $ids = (array)$ids;
        if(empty($ids)){
            return [];
        }
        $sql = "select id,type,symbol,name from ". $this->tableName(). " where id in(".implode(',', $ids).")";
        $res = Yii::app()->lcs_r->CreateCommand($sql)->queryAll();
        $symbol_info = [];
        if(!empty($res)) {
            foreach($res as $item){
                $symbol_info[$item['id']] = $item;
            }
        }

        return $symbol_info;
    }

}