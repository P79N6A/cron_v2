<?php
/**
 * 统一的错误类
 * User: zwg
 * Date: 2015/5/18
 * Time: 17:40
 */

class LcsException extends CException {

    /**
     * 错误处理，返回异常类
     *
     * @param mixed $code
     * @param mixed $string
     * @param mixed $file
     * @param mixed $line
     */
    public static function errorHandler($code, $string, $file, $line) {
        $e = new self($string, $code);
        $e->line = $line;
        $e->file = $file;
        return $e;
    }

    /**
     * 错误处理，返回异常类
     * @param $exception
     * @return LcsException
     */
    public static function errorHandlerOfException($exception){
        if($exception instanceof LcsException){
            return $exception;
        }else if($exception instanceof Exception){
            $e = new self($exception->getMessage(), $exception->getCode());
            $e->line = $exception->getLine();
            $e->file = $exception->getFile();
            return $e;
        }else{
            $e = new self($exception, -1);
            return $e;
        }
    }


    /**
     * 获取异常的字符串
     * @return string
     */
    public function toJsonString(){
        $json_data['code']=$this->getCode();
        $json_data['message']=$this->getMessage();
        $json_data['file']=$this->getFile();
        $json_data['line']=$this->getLine();
        return json_encode($json_data,JSON_UNESCAPED_UNICODE);
    }
}