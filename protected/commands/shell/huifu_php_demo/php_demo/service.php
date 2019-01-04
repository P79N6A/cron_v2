<?php
define("LAJP_IP", 'localhost');
define("LAJP_PORT", 21230);
define("PARAM_TYPE_ERROR", 101);
define("SOCKET_ERROR", 102);
define("JAVA_EXCEPTION", 104);

function lajpCall()
{
    $args_len = func_num_args();
    $arg_array = func_get_args();

    if ($args_len < 1) {
        throw new Exception("[Service Error] lajpCall function's arguments length < 1", PARAM_TYPE_ERROR);
    }
    if (!is_string($arg_array[0])) {
        throw new Exception("[Service Error] lajpCall function's first argument must be string \"class_name::method_name\".", PARAM_TYPE_ERROR);
    }

    if (($socket = socket_create(AF_INET, SOCK_STREAM, 0)) === false) {
        throw new Exception("[Service Error] socket create error.", SOCKET_ERROR);
    }

    if (socket_connect($socket, LAJP_IP, LAJP_PORT) === false) {
        throw new Exception("[Service Error] socket connect error.", SOCKET_ERROR);
    }

    $request = serialize($arg_array);
    $req_len = strlen($request);
    $request = $req_len . "," . $request;
    $send_len = 0;
    do {
        if (($sends = socket_write($socket, $request, strlen($request))) === false) {
            throw new Exception("[Service Error] socket write error.", SOCKET_ERROR);
        }
        $send_len += $sends;
        $request = substr($request, $sends);

    } while ($send_len < $req_len);
    $response = "";
    while (true) {
        $recv = "";
        if (($recv = socket_read($socket, 1400)) === false) {
            throw new Exception("[Service Error] socket read error.", SOCKET_ERROR);
        }
        if ($recv == "") {
            break;
        }
        $response .= $recv;
    }
    socket_close($socket);
    $rsp_stat = substr($response, 0, 1);
    $rsp_msg = substr($response, 1);
    if ($rsp_stat == "F") {
        throw new Exception("[Service Error] Receive Java exception: " . $rsp_msg, JAVA_EXCEPTION);
    } else {
        if ($rsp_msg != "N") {
            return unserialize($rsp_msg);
        }
    }
}
