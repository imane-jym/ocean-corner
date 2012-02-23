<?php
function send_data($socket, $pack)
{
    while(strlen($pack) > 0)
    {
        $len = socket_write($socket, $pack, strlen($pack));
        if ($len === false)
        {
            if (socket_last_error($socket) == SOCKET_EINTR)
            {
                continue;
            }
            else
            {
                log::write("socket_write fail: reason: ".socket_strerror(socket_last_error($socket)). "\n", "error");
                return -1;
            }
        }
        $pack = substr($pack, $len); 
    }
    return 0;
}

function send_data_and_nonblock($socket, $pack, $timeout)
{
    $s_time = time();
    while(strlen($pack) > 0)
    {
        $len = socket_write($socket, $pack, strlen($pack));
        if ($len === false)
        {
            if (socket_last_error($socket) == SOCKET_EINTR || socket_last_error($socket) == SOCKET_EAGAIN)
            {
                usleep(1);
                continue;
            }
            else
            {
                log::write("socket_write fail: reason: ".socket_strerror(socket_last_error($socket)), "warn");
		socket_close($socket);
                return -1;
            }
        }
        else
        {
            $pack = substr($pack, $len); 
            $s_time = time();
        }

        if (time() - $s_time > $timeout)
        {
            log::write("timeout send_data_nonblock","warn");            
            socket_close($socket);
            return -1;
        }
        usleep(100);
    }
    return 0;
}

function recv_data_and_nonblock($socket, $pack_size, &$pack, $timeout)
{
    $s_time = time();
    while(strlen($pack) < $pack_size)
    {
        $recv_data = socket_read($socket, 4096);
        if ($recv_data === false)
        {
            if (socket_last_error($socket) == SOCKET_EINTR || socket_last_error($socket) == SOCKET_EAGAIN)
            {
                usleep(1);
                continue;
            }
            else
            {
                log::write("socket_read fail:reason: ".socket_strerror(socket_last_error($socket)),"warn");
                socket_close($socket);
                return -1;
            }
        } 
        else if ($recv_data == "")
        {
            log::write("socket_read zero bytes:reason:".socket_strerror(socket_last_error($socket)), "warn");
            socket_close($socket);
            return -1;
        }
        else
        {
            $pack .= $recv_data;
            $s_time = time();
        }

        if (time() - $s_time > $timeout)
        {
            log::write("timeout send_data_nonblock","warn");            
            socket_close($socket);
            return -1;
        }
        usleep(100);
    } 
    return 0;
}

function recv_data($socket, $pack_size, &$pack)
{
    while(strlen($pack) < $pack_size)
    {
        $recv_data = socket_read($socket, 4096);
        if ($recv_data === false)
        {
            if (socket_last_error($socket) == SOCKET_EINTR)
            {
                continue;
            }
            else
            {
                log::write("socket_read fail:reason: ".socket_strerror(socket_last_error($socket)),"error");
                return -1;
            }
        } 
        if ($recv_data == "")
        {
            log::write("socket_read zero bytes:reason:".socket_strerror(socket_last_error($socket)), "warn");
            return -2;
        }
        $pack .= $recv_data;
    } 
    return 0;
}

function init_connect_and_nonblock($ip, $port, &$socket)
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false)
    {
        log::write("socket_create fail: reason: ".socket_strerror(socket_last_error()),"warn"); 
        socket_close($socket);
        return -1;
    }
    $result = socket_connect($socket, $ip, $port);
    if ($result === false)
    {
        log::write("socket_connect() fail:reason: ".socket_strerror(socket_last_error($socket)),"warn");
        socket_close($socket);
        return -1;
    } 
    socket_set_nonblock($socket);
    return 0;
}

function init_connect($ip, $port, &$socket)
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false)
    {
        log::write("socket_create fail: reason: ".socket_strerror(socket_last_error()),"error"); 
        return -1;
    }
    $result = socket_connect($socket, $ip, $port);
    if ($result === false)
    {
        log::write("socket_connect() fail:reason: ".socket_strerror(socket_last_error($socket)),"error");
        return -1;
    } 
    return 0;
}

function is_ais($userid, $c_socket)
{
    $request_pack_format = array("L2SL2");
    $request_pack_content = array(
        'len' => 18,
        'seq' => 0,
        'cmd_id' => 0x022,
        'status' => 0,
        'accout_id' => $userid,
    );                          
    $para = array_merge($request_pack_format, $request_pack_content);
    $request_pack = call_user_func_array('pack',array_values($para));

    DEBUG && log::write(print_r($request_pack_content, true), "debug");

    if (send_data($c_socket, $request_pack))
    {   
        log::write(__FUNCTION__ . " send data to login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }       
    $response_pack = "";

    if (recv_data($c_socket, 4, $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   

    $temp = unpack("Llen", $response_pack);
    if (recv_data($c_socket, $temp['len'], $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   
    $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id", $response_pack);
    if ($response_pack_content['status_code'] != 0)
    { 
        return -1;
    }
    $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id/Csex/Lbirthday", $response_pack);
    $time = time(); 
    if ($response_pack_content['birthday'] == 0)
    {
        return 1;
    }
    $user_birthtime = strtotime($response_pack_content['birthday'] . " + 18 year");
    if ($time >= $user_birthtime)
    {
        return 0;
    }
    else
    {
        return 1;
    }
}

function is_active($user_id)
{
    if (init_connect(LOGIN_IP, LOGIN_PORT, $c_socket))
    {
        socket_close($c_socket);
        log::write("connect login fail","error");
        return -1;
    }

    $request_pack_format = array("L2SL2L");
    $request_pack_content = array(
        'len' => 19,
        'seq' => 0,
        'cmd_id' => 0xA029,
        'status' => 0,
        'accout_id' => $user_id,
        
        'game_id' => 131
    );                          
    $para = array_merge($request_pack_format, $request_pack_content);
    $request_pack = call_user_func_array('pack',array_values($para));

    DEBUG && log::write(print_r($request_pack_content, true), "debug");

    if (send_data($c_socket, $request_pack))
    {   
        log::write(__FUNCTION__ . " send data to login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }       
    $response_pack = "";

    if (recv_data($c_socket, 4, $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   

    $temp = unpack("Llen", $response_pack);
    if (recv_data($c_socket, $temp['len'], $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   
    socket_close($c_socket);
    $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id", $response_pack);
    DEBUG && log::write(print_r($response_pack_content, true), "debug");
    if ($response_pack_content['status_code'] != 0)
    { 
        return -1;
    }
    $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id/Cis_active", $response_pack);
    if ($response_pack_content['is_active'])
    {
        return 0;
    }
    else
    {
        return 1;
    }
}

function login_confirm_temp($username, $password, &$result, $domain, $sid)
{
    if (!isset($username)) 
    {   
        log::write("login_confirm para unset username:{$username}","error");
        return -1;
    }       
    global $g_sys_conf;

    $is_userid = 0;
    if (strpos($username, '@') === false)
    {
        if (!is_numeric($username))
        {
            return -1;
        }
        else
        {
            if ($username <= 0)
            {
                return -1;
            }
        }
        $is_userid = 1;
    }

    if (init_connect(LOGIN_IP, LOGIN_PORT, $c_socket))
    {
        socket_close($c_socket);
        log::write("connect login fail","error");
        return -1;
    }
    
    $region = $g_sys_conf['region'][$domain];
    if (!isset($region))
    {
        $region = $g_sys_conf['region']['default'];
    }

    $request_pack_format = array("L2SL2a64a64a64S3La20");
    if ($is_userid)
    {
        $request_pack_content = array(
            'len' => 240,
            'seq' => 0,
            'cmd_id' => 0xA038,
            'status' => 0,
            'accout_id' => $username,

            'email' => 0,
            'passwd' => $password, 
            'sid' => $sid,
            'login_channel' => 61,
            'region' => $region,
            'gameid' => 131,
            'ip' => 0,
            'extra_data' => ''
        );                          
    }
    else
    {
        $request_pack_content = array(
            'len' => 240,
            'seq' => 0,
            'cmd_id' => 0xA038,
            'status' => 0,
            'accout_id' => 0,

            'email' => $username,
            'passwd' => $password, 
            'sid' => $sid,
            'login_channel' => 61,
            'region' => $region,
            'gameid' => 131,
            'ip' => 0,
            'extra_data' => ''
        );                          
    }
    $para = array_merge($request_pack_format, $request_pack_content);
    $request_pack = call_user_func_array('pack',array_values($para));

    DEBUG && log::write(print_r($request_pack_content, true), "debug");
    
    if (send_data($c_socket, $request_pack))
    {   
        log::write(__FUNCTION__ . " send data to login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }       
    $response_pack = "";

    if (recv_data($c_socket, 4, $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   

    $temp = unpack("Llen", $response_pack);
    if (recv_data($c_socket, $temp['len'], $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   
    $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id", $response_pack);
    if ($response_pack_content['status_code'] == 0)
    {
        $result['user_id'] = $response_pack_content['account_id'];
        $ais = is_ais($result['user_id'], $c_socket);
        if ($ais == -1)
        {
            socket_close($c_socket);
            return -1;
        }
        else
        {
            $result['ais'] = $ais;
            socket_close($c_socket);
            return 0;
        }
    }       
    else if ($response_pack_content['status_code'] == 1103)
    {
        $result['user_id'] = $response_pack_content['account_id'];
        socket_close($c_socket);
        return -2;
    }
    else
    {
        socket_close($c_socket);
        return -1;
    }
}

function login_confirm($username, $password, &$result, $domain)
{
    if (!isset($username)) 
    {   
        log::write("login_confirm para unset username:{$username}","error");
        return -1;
    }       
    global $g_sys_conf;

    $is_userid = 0;
    if (strpos($username, '@') === false)
    {
        if (!is_numeric($username))
        {
            return -1;
        }
        else
        {
            if ($username <= 0)
            {
                return -1;
            }
        }
        $is_userid = 1;
    }

    if (init_connect(LOGIN_IP, LOGIN_PORT, $c_socket))
    {
        socket_close($c_socket);
        log::write("connect login fail","error");
        return -1;
    }
    
    $region = $g_sys_conf['region'][$domain];
    if (!isset($region))
    {
        $region = $g_sys_conf['region']['default'];
    }

    $request_pack_format = array("L2SL2a64a16S3L");
    if ($is_userid)
    {
        $request_pack_content = array(
            'len' => 108,
            'seq' => 0,
            'cmd_id' => 0xA021,
            'status' => 0,
            'accout_id' => $username,

            'email' => 0,
            'passwd' => $password, 
            'login_channel' => 61,
            'region' => $region,
            'gameid' => 131,
            'ip' => 0
        );                          
    }
    else
    {
        $request_pack_content = array(
            'len' => 108,
            'seq' => 0,
            'cmd_id' => 0xA021,
            'status' => 0,
            'accout_id' => 0,

            'email' => $username,
            'passwd' => $password, 
            'login_channel' => 61,
            'region' => $region,
            'gameid' => 131,
            'ip' => 0
        );                          
    }
    $para = array_merge($request_pack_format, $request_pack_content);
    $request_pack = call_user_func_array('pack',array_values($para));

    DEBUG && log::write(print_r($request_pack_content, true), "debug");
    
    if (send_data($c_socket, $request_pack))
    {   
        log::write(__FUNCTION__ . " send data to login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }       
    $response_pack = "";

    if (recv_data($c_socket, 4, $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   

    $temp = unpack("Llen", $response_pack);
    if (recv_data($c_socket, $temp['len'], $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from login_server fail", "error");
        socket_close($c_socket);
        return -1;
    }   
    $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id", $response_pack);
    if ($response_pack_content['status_code'] == 0)
    {
        $result['user_id'] = $response_pack_content['account_id'];
        $ais = is_ais($result['user_id'], $c_socket);
        if ($ais == -1)
        {
            socket_close($c_socket);
            return -1;
        }
        else
        {
            $result['ais'] = $ais;
            socket_close($c_socket);
            return 0;
        }
    }       
    else if ($response_pack_content['status_code'] == 1103)
    {
        $result['user_id'] = $response_pack_content['account_id'];
        socket_close($c_socket);
        return -2;
    }
    else
    {
        socket_close($c_socket);
        return -1;
    }
}

function get_crown_balance($mimi_id)
{
    if (!isset($mimi_id)) 
    {   
        log::write("get_crown_balance para unset mimi_id:{$mimi_id}","error");
        return false;
    }       
    if ($mimi_id <= 0)
    {
        return false;
    }

    if (init_connect(CROWN_IP, CROWN_PORT, $c_socket))
    {
        socket_close($c_socket);
        log::write("connect crown fail","error");
        return false;
    }

    $request_pack_format = array("L2SL2a16");
    $request_pack_content = array(
        'len' => 34,
        'seq' => 0,
        'cmd_id' => 0x4601,
        'status' => 0,
        'accout_id' => $mimi_id,
        'account_pwd' => '' 
    );                          
    $para = array_merge($request_pack_format, $request_pack_content);
    $request_pack = call_user_func_array('pack',array_values($para));

    DEBUG && log::write(print_r($request_pack_content, true), "debug");

    if (send_data($c_socket, $request_pack))
    {   
        log::write(__FUNCTION__ . " send data to crown_server fail", "error");
        socket_close($c_socket);
        return false;
    }       
    $response_pack = "";

    if (recv_data($c_socket, 4, $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from crown_server fail", "error");
        socket_close($c_socket);
        return false;
    }   
    $temp = unpack("Llen", $response_pack);
    if (recv_data($c_socket, $temp['len'], $response_pack))
    {
        log::write(__FUNCTION__ . " recv data from crown_server fail", "error");
        socket_close($c_socket);
        return false;
    }   

    socket_close($c_socket);
    $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id", $response_pack);
    if ($response_pack_content['status_code'] == 0)
    {
        $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id/Lmb_num_balance/Laccount_status", $response_pack);
        if ($response_pack_content['account_status'] != 0)
        {
        return array('balance' => 0,
                    'code' => 1,
                    'message' => '该帐户皇冠已经被锁定');
        }
    
        return array('balance' => $response_pack_content['mb_num_balance'] / 100,
                    'code' => 0,
                    'message' => 'success');
    }       
    else if ($response_pack_content['status_code'] == 113)
    {
        log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
        return array('balance' => 0,
                    'code' => 1,
                    'message' => '该帐户皇冠已经被锁定');
    }
    else
    {
        log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
        return array('balance' => 0,
                    'code' => 1,
                    'message' => '其他错误');
    }
}

function update_crown_balance($db, $user_id, $amount, $updatetype, $desc, $trade_str, $domain, $charname)
{
    if (!isset($user_id, $amount, $updatetype, $desc, $trade_str, $domain, $charname)) 
    {   
        log::write("update_crown_balance para unset user_id:{$user_id}, amount:{$amount}, updatetype:{$updatetype}, desc:{$desc}, traceid:{$trade_str}","error");
        return false;
    }       
    if ($user_id <= 0)
    {
        return false;
    }
    $ret = $db->get_trade_id($trade_str, $user_id + BASE_ID, $amount, $updatetype, $desc, $trade_id, $domain, $charname);
    if ($ret == -1)
    {
        log::write("get trade id fail","error");
        return false;
    }

    if (init_connect(CROWN_IP, CROWN_PORT, $c_socket))
    {
        socket_close($c_socket);
        log::write("connect crown fail","error");
        return false;
    }

    if ($amount <= 0)
    {
        $amount = abs($amount);
        $product_id = $db->get_product_id($updatetype, $desc);
        if ($product_id == false)
        {
            log::write("get product id fail","error");
            return false;
        }
        $channelId = CROWN_CHANNEL;
        $securityCode = CROWN_SAVEID;
        $packagebody = pack('a16L2SLL','',$user_id, $product_id, 1, $amount * 100, $trade_id);
        $verify_code = md5("channelId=$channelId&securityCode=$securityCode&data=$packagebody");

        $request_pack_format = array("L2SL2Sa32a16L2SLL");
        $request_pack_content = array(
            'len' => 86,
            'seq' => 0,
            'cmd_id' => 0x4702,
            'status' => 0,
            'accout_id' => $user_id,
            'channel_id' => $channelId,
            'verify_code' => $verify_code,
            'account_pwd' => '',
            'dest_acount_id' => $user_id,
            'product_id' => $product_id,
            'product_count' => 1,
            'mb_num' => $amount * 100,
            'consume_trans_id' => $trade_id 
        );                          
        $para = array_merge($request_pack_format, $request_pack_content);
        $request_pack = call_user_func_array('pack',array_values($para));

        DEBUG && log::write(print_r($request_pack_content, true), "debug");

        if (send_data($c_socket, $request_pack))
        {   
            log::write(__FUNCTION__ . " send data to crown_server fail", "error");
            socket_close($c_socket);
            return false;
        }       
        $response_pack = "";

        if (recv_data($c_socket, 4, $response_pack))
        {
            log::write(__FUNCTION__ . " recv data from crown_server fail", "error");
            socket_close($c_socket);
            return false;
        }   
        $temp = unpack("Llen", $response_pack);
        if (recv_data($c_socket, $temp['len'], $response_pack))
        {
            log::write(__FUNCTION__ . " recv data from crown_server fail", "error");
            socket_close($c_socket);
            return false;
        }   

        socket_close($c_socket);
        $response_pack_content = unpack("Lpackage_len/Lseq_num/Scommand_id/Lstatus_code/Laccount_id/Ltrade_id1/Ltrade_id2/Lmb_num_balance", $response_pack);
        DEBUG && log::write(print_r($response_pack_content,true), 'debug');
        $transaction_id = ($response_pack['trade_id2'] << 32) + $response_pack_content['trade_id1'];
        if ($response_pack_content['status_code'] == 0)
        {
            if (false == $db->update_trade_id($trade_id, $transaction_id))
            {
                $sql = "update db_wizard_user_data.t_production_trade set transaction_id = $transaction_id where id = $trade_id";
                log::write_data($sql, 'Crown');
            }
            return array('balance' => $response_pack_content['mb_num_balance'] / 100,
                'code' => 0,
                'message' => 'success');
        }       
        else if ($response_pack_content['status_code'] == 112)  //订单已经处理
        {
            if (false == $db->update_trade_id($trade_id, $transaction_id))
            {
                $sql = "update db_wizard_user_data.t_production_trade set transaction_id = $transaction_id where id = $trade_id";
                log::write_data($sql, 'Crown');
            }
            return array('balance' => $response_pack_content['mb_num_balance'] / 100,
                'code' => 0,
                'message' => 'success');
        }
        else if ($response_pack_content['status_code'] == 105)
        {
            log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
            return array('balance' => 0,
                'code' => 1,
                'message' => '米币账户余额不足');
        }
        else if ($response_pack_content['status_code'] == 107)
        {
            log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
            return array('balance' => 0,
                'code' => 1,
                'message' => '超过每月消费上限');
        }
        else if ($response_pack_content['status_code'] == 108)
        {
            log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
            return array('balance' => 0,
                'code' => 1,
                'message' => '超过单笔消费上限');
        }
        else if ($response_pack_content['status_code'] == 113)
        {
            log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
            return array('balance' => 0,
                'code' => 1,
                'message' => '该帐户皇冠已经被锁定');
        }
        else
        {
            log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
            return array('balance' => 0,
                'code' => 1,
                'message' => '其他错误');
        }
    }
    else
    {
        log::write(__FUNCTION__ . print_r($response_pack_content, true), "warn");
        return false;
    }
}

