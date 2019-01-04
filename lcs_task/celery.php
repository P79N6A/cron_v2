<?php

class celery{
    public $content_type = 'application/json';

    public function PostTask($task,$args,$task_args=[]){
        if(!is_array($args)){
            throw new Exception("args should be an array");
        }
        $id = uniqid('php_',true);         
        if (array_keys($args) === range(0, count($args) - 1)) {
            $kwargs = [];
        }else {
            $kwargs = $args;
            $args = [];
        }
        $task_array = array_merge(
            ['id' => $id,'task' => $task,'args' => $args,'kwargs' => (object)$kwargs],
            $task_args
        );
        $task = json_encode($task_array);       
        return $this->PostToExchange($task);
    }
    public function PostToExchange($task){        
        $body = json_decode($task, true);
        $message = $this->GetMessage($task);
        $message['properties'] = [
            'body_encoding' => 'base64',
            'reply_to' => $body['id'],
            'delivery_info' => [
                'priority' => 0,
                'routing_key' => 'celery',
                'exchange' => 'celery',
            ],
            'delivery_mode' => 2,
            'delivery_tag'  => $body['id']
        ];
        return $this->ToStr($message);        
    }
    protected function GetHeaders(){
        return new \stdClass;
    }
    protected function GetMessage($task){
        $result = [];
        $result['body'] = base64_encode($task);
        $result['headers'] = $this->GetHeaders();
        $result['content-type'] = $this->content_type;
        $result['content-encoding'] = 'binary';
        return $result;
    }
    protected function ToStr($var){
        return json_encode($var);
    }
}


?>