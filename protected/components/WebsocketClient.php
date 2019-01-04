<?php
/**
 *
 * 实现了websocket客户端
 */
class WebsocketClient {
    public $callback = '';
    private $_host;
    private $_port;
    private $_path;
    private $_authorization;
    private $_origin;
    protected $_Socket = null;
    protected $_connected = false;
    protected $interactive = true;
    public function __construct() {
    }
    public function __destruct() {
        $this->disconnect();
    }
    public function sendData($data, $type = 'text', $masked = true) {
        if ($this->_connected === false) {
            return false;
        }
        if (!is_string($data)) {
            return false;
        }
        if (strlen($data) == 0) {
            return false;
        }
        $res = @socket_write($this->_Socket, $this->_hybi10Encode($data, $type, $masked));
        
        if ($res === 0 || $res === false) {
            return false;
        }
        
        return true;
    }
    public function run() {
        $socket = $this->_Socket;
        $sockets[] = $this->_Socket;
        while ( true ) {
            $write = $except = NULL;
            @socket_select($sockets, $write, $except, 1); // 阻塞状态，如果有数据的话才会继续

            $numBytes = @socket_recv($socket, $buffer, 65534, 0);
            if ($numBytes === false) {
                $sockErrNo = socket_last_error($socket);           
                if ($this->reconnect()) {
                    $socket = $this->_Socket;
                    $sockets[] = $this->_Socket;
                } else {
                    exit();
                }
            } elseif ($numBytes == 0) {
                $this->disconnect();
                $this->stderr("Client disconnected. TCP connection lost: " . $socket);
            } else {
                if ($revData = $this->_handleOneFrame($buffer)) {
                    call_user_func($this->callback, $revData);
                }
            }
        }
    }
    /**
     * 处理服务器传过来的数据，由于只有第一个socket中有websocket帧的长度，所以需要封装一下数据。
     */
    private function _handleOneFrame($buffer) {
        $frameHeader = $this->_frameHeader($buffer);
        $lenFromFrame=$frameHeader['dateLength'];
        $isMask = $frameHeader['isMasked'];
        $mask=$frameHeader['mask'];
        while (strlen($buffer)<$lenFromFrame) {
        	@socket_recv($this->_Socket, $tmp, $lenFromFrame-strlen($buffer), 0);
            $buffer.=$tmp;
        }
        $tData=substr($buffer, 0,$lenFromFrame);//当前帧
        $decodeData=$this->_hybi10Decode($tData,array('isMasked'=>$isMask,'mask'=>$mask));
        $lData=substr($buffer, $lenFromFrame);//剩余的数据
        if ($lData) {
        	$decodeData.=$this->_handleOneFrame($lData);
        }
        
        return $decodeData;
        
    }
    public function stderr($message) {
        if ($this->interactive) {
            //MSG($message);
        }
    }
    /**
     * Enter description here...
     *
     * @param unknown_type $host
     * @param unknown_type $port
     * @param unknown_type $path
     * @param unknown_type $authorization 用户名和密码的base64加密字符串
     * @param unknown_type $origin
     * @return unknown
     */
    public function connect($host, $port, $path, $authorization=false,$origin = false) {
        $this->_host = $host;
        $this->_port = $port;
        $this->_path = $path;
        $this->_authorization = $authorization;
        $this->_origin = $origin;
        
        $key = base64_encode($this->_generateRandomString(16, false, true));
        $header = "GET " . $path . " HTTP/1.1\r\n";
        $header .= "Host: " . $host . ":" . $port . "\r\n";
        $header .= "Upgrade: websocket\r\n";
        $header .= "Connection: Upgrade\r\n";
        $header .= "Sec-WebSocket-Key: " . $key . "\r\n";
        if ($origin !== false) {
            $header .= "Sec-WebSocket-Origin: " . $origin . "\r\n";
        }
        $header .= "Sec-WebSocket-Version: 13\r\n";
        if($authorization != false){
        	$header .= "Authorization: Basic $authorization";
        }
        $header .= "\r\n";
        $header .= "\r\n";
        
        // create socket
        $this->_Socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($this->_Socket, $host, $port);
        if (socket_write($this->_Socket, $header, strlen($header))) {
            $response = @socket_read($this->_Socket, 1024);
        } else {
            $this->_connected = false;
        }
        
        preg_match('#Sec-WebSocket-Accept:\s(.*)$#mU', $response, $matches);
        
        if ($matches) {
            $keyAccept = trim($matches[1]);
            $expectedResonse = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $this->_connected = ($keyAccept === $expectedResonse) ? true : false;
        }
        
        return $this->_connected;
    }
    public function checkConnection() {
        $this->_connected = false;
        
        // send ping:
        $data = 'ping?';
        @socket_write($this->_Socket, $this->_hybi10Encode($data, 'ping', true));
        $response = socket_read($this->_Socket, 1024);
        if (empty($response)) {
            return false;
        }
        $response = $this->_hybi10Decode($response);
        if (!is_array($response)) {
            return false;
        }
        if (!isset($response['type']) || $response['type'] !== 'pong') {
            return false;
        }
        $this->_connected = true;
        return true;
    }
    public function disconnect() {
        $this->_connected = false;
        is_resource($this->_Socket) and socket_close($this->_Socket);
    }
    public function reconnect() {
        $this->_connected = false;
        socket_close($this->_Socket);
        $this->connect($this->_host, $this->_port, $this->_path, $this->_authorization, $this->_origin);
    }
    private function _generateRandomString($length = 10, $addSpaces = true, $addNumbers = true) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
        $useChars = array ();
        // select some random chars:
        for($i = 0; $i < $length; $i++) {
            $useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
        }
        // add spaces and numbers:
        if ($addSpaces === true) {
            array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
        }
        if ($addNumbers === true) {
            array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
        }
        shuffle($useChars);
        $randomString = trim(implode('', $useChars));
        $randomString = substr($randomString, 0, $length);
        return $randomString;
    }
    private function _hybi10Encode($payload, $type = 'text', $masked = true) {
        $frameHead = array ();
        $frame = '';
        $payloadLength = strlen($payload);
        
        switch ($type) {
            case 'text' :
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;
            
            case 'close' :
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;
            
            case 'ping' :
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;
            
            case 'pong' :
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }
        
        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too
            // big)
            if ($frameHead[2] > 127) {
                $this->close(1004);
                return false;
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }
        
        // convert frame-head to string:
        foreach ( array_keys($frameHead) as $i ) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = array ();
            for($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }
            
            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        
        // append payload to frame:
        $framePayload = array ();
        for($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }
        
        return $frame;
    }
    private function _hybi10Decode($data, $isMask = array('isMasked'=>FALSE,'mask'=>array()), $isfirst = false) {
        $payloadOffset=0;
        if ($isMask['isMasked']) {
            for($i = $payloadOffset; $i < strlen($data); $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $$isMask['mask'][$j % 4];
                }
            }
            return $unmaskedPayload;
        } else {
            return $data;
        }
    }
    /**
     * 获取协议头部有用信息，返回头部后面的数据
     * @param $data 地址引用
     */
    private function _frameHeader(&$data) {
        $payloadLength = '';
        $mask = '';
        $unmaskedPayload = '';

        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;
        
        
        if ($isMasked) {//masked
            if ($payloadLength === 126) {
                $mask = substr($data, 4, 4);
            } elseif ($payloadLength === 127) {
                $mask = substr($data, 10, 4);
            } else {
                $mask = substr($data, 2, 4);
            }
        }
        
        if ($payloadLength === 126) {
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3])));
            $data=substr($data, 4);
        } elseif ($payloadLength === 127) {
            $tmp = '';
            for($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp);
            unset($tmp);
            $data=substr($data, 10);
        } else {
            $dataLength = $payloadLength;
            $data=substr($data, 2);
        }
        
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        switch ($opcode) {
            // text frame:
        	case 1 :
        	    $type = 'text';
        	    break;
        	case 2 :
        	    $type = 'binary';
        	    break;
        	    // connection close frame:
        	case 8 :
        	    $type = 'close';
        	    break;
        	    // ping frame:
        	case 9 :
        	    $type = 'ping';
        	    break;
        	    // pong frame:
        	case 10 :
        	    $type = 'pong';
        	    break;
        	default :
        	    return false;
        	    break;
        }
        
        
        /*
         * Masking key contains a 32-bit value used to mask the payload. Payload
         * contains the application data and custom extension data if the client
         * and server negotiated an extension when the connection was
         * established.
         */
        if ($isMasked) {
            return $dataLength += 4;
        } 
        return array('dateLength'=>$dataLength,'type'=>$type,'isMasked' => $isMasked, 'mask' => $mask);
    }
    /**
     * 设置回调函数
     * @param string $functions
     */
    public function setCallBack($functions) {
       
            $this->callback = $functions;
    }
}
