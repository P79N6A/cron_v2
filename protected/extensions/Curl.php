<?php
/**
* Curl wrapper for Yii
* @author hackerone
*/
class Curl extends CComponent{

	private $_ch;

	// config from config.php
	public $options;

	// default config
	private $_config = array(
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_AUTOREFERER    => true,
	CURLOPT_CONNECTTIMEOUT => 10,
	CURLOPT_TIMEOUT        => 3,
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:5.0) Gecko/20110619 Firefox/5.0'
	);

	private function _exec($url){

		$this->setOption(CURLOPT_URL, $url);
		$c = curl_exec($this->_ch);
		if(!curl_errno($this->_ch)){
			return $c;
		}else{
			throw new CException(curl_error($this->_ch));
		}
		return false;

	}

	public function get($url, $params = array()){
		$this->setOption(CURLOPT_HTTPGET, true);

		return $this->_exec($this->buildUrl($url, $params));
	}

	/**
	 * post方法
	 * 若要post直接支持array做参数需要额外设置请求头
	 * @param String $url
	 * @param Array $params
	 * @return String
	 */
	public function post($url, $params = array()){
		$this->setOption(CURLOPT_POST, true);
		// http://www.php.net/manual/en/function.curl-setopt.php
		// If value is an array, the Content-Type header will be set to multipart/form-data. 
		//$paramsString = http_build_query($params);
		
		if(is_array($params)){
			$paramsString = http_build_query($params);
		}else if(is_string($params)){
			$paramsString = $params;
		}
		
		$this->setOption(CURLOPT_POSTFIELDS, $paramsString);

		return $this->_exec($url);
	}
	
	/**
	 * 设置响应超时时间
	 *
	 * @param unknown_type $time
	 */
	public function setTimeOut($time){
		$time = intval($time);
		if($time<1 || $time>60){
			$time = 3;
		}
		$this->setOption(CURLOPT_TIMEOUT, $time);
		return $this;
	}

	public function put($url, $data, $params = array()){

		// write to memory/temp
		$f = fopen('php://temp', 'rw+');
		fwrite($f, $data);
		rewind($f);

		$this->setOption(CURLOPT_PUT, true);
		$this->setOption(CURLOPT_INFILE, $f);
		$this->setOption(CURLOPT_INFILESIZE, strlen($data));

		return $this->_exec($this->buildUrl($url, $params));
	}

	public function delete($url, $params = array()) {

		$this->setOption(CURLOPT_RETURNTRANSFER, true);
		$this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');

		return $this->_exec($this->buildUrl($url, $params));
	}

	public function buildUrl($url, $data = array()){
		$parsed = parse_url($url);
		isset($parsed['query'])?parse_str($parsed['query'],$parsed['query']):$parsed['query']=array();
		$params = isset($parsed['query'])?array_merge($parsed['query'], $data):$data;
		$parsed['query'] = ($params)?'?'.http_build_query($params):'';
		if(isset($parsed['port'])){
			$parsed['host'] .= ":$parsed[port]";
		}
		if(!isset($parsed['path']))
		$parsed['path']='/';
		return $parsed['scheme'].'://'.$parsed['host'].$parsed['path'].$parsed['query'];
	}

	public function setOptions($options = array()){
		curl_setopt_array( $this->_ch , $options);

		return $this;
	}

	public function setOption($option, $value){

		curl_setopt($this->_ch, $option, $value);

		return $this;
	}

	public function setHeaders($header = array())
	{
		if($this->_isAssoc($header)){
			$out = array();
			foreach($header as $k => $v){
				$out[] = $k .': '.$v;
			}
			$header = $out;
		}

		$this->setOption(CURLOPT_HTTPHEADER, $header);

		return $this;
	}


	private function _isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	public function getError()
	{
		return curl_error($this->_ch);
	}

	public function getInfo()
	{
		return curl_getinfo($this->_ch);
	}

	public function closeCurl() {
		curl_close($this->_ch);
	}

	// initialize curl
	public function init(){
		try{
			$this->_ch = curl_init();
			$options = is_array($this->options)? ($this->options + $this->_config):$this->_config;
			$this->setOptions($options);

			$ch = $this->_ch;
			Yii::app()->onEndRequest=array($this,'closeCurl');
			
		}catch(Exception $e){
			throw new CException('Curl not installed');
		}
	}
}
