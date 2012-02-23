<?php

class dbhandler
{
	/*
     * 数据库实例
     */
    private $instance;
    
    /*
     * 数据连接信息
     */
    private $db_info = array();

	/**
     * 构造函数
     */
    function __construct()
    {
        $this->instance = dblink::getInstance();
    }

    public function db_config_init($db_host,$db_user,$db_passwd,$db_port)
    {
        if (!isset($db_host,$db_user,$db_passwd,$db_port)) 
        {
            log::write("Mysql db_config_init para unset host:{$db_host} user:{$db_user} passwd:{$db_passwd} port:{$db_port}","error");
            return false;
        }
        $mysqli_config;
        $config_arr = array('host'=> $db_host,
                            'user'=> $db_user,
                            'passwd' => $db_passwd,
                            'port' => $db_port);
        if ($mysqli_config = $this->instance->get_connect($config_arr))
        {
            $sql = "select * from db_wizard_user_config.t_db_info order by db_id;";
            if($mysqli_result = $mysqli_config->query($sql))
            {   
                $result_row = array();         
                while(($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                {
                    $result_row[$row['db_name']] = array('host'=>$row['db_host'],
                                                         'user'=>$row['db_user'],
                                                         'passwd'=>$row['db_passwd'],
                                                         'port'=>$row['db_port']);
                }
                $this->db_info = $result_row;
                
                $this->db_info["db_wizard_user_config"] = array('host'=>$db_host,'user'=>$db_user,'passwd'=>$db_passwd,'port'=>$db_port);
                $this->db_info["db_wizard_user_data"] = array('host'=>$db_host,'user'=>$db_user,'passwd'=>$db_passwd,'port'=>$db_port);
                //DEBUG && log::write(print_r($this->db_info, true), "debug");
                $mysqli_result->close();       
                $this->instance->close();
                return true;            
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli_config->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }

    private function escape($str)
    {
        $search=array("\\","\0","\n","\r","\x1a","'",'"');
        $replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
        return str_replace($search,$replace,$str);
    }

    private function get_table_name($mimi_id) 
    {
        return "db_wizard_user_" . floor($mimi_id % 10000 / 100) . ".t_user_" . ($mimi_id % 100);
    }

    private function get_db_name($mimi_id)
    {
        return "db_wizard_user_" . floor($mimi_id % 10000 / 100);
    }

    public function login_security_insert($user_id)
    {
        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "insert into db_wizard_user_data.t_login_security(user_id, mount) values(" . $user_id . ", 1) ON DUPLICATE KEY UPDATE mount = mount + 1";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                log::write("Mysql Error:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    } 
    
    public function login_security_get($user_id)
    {
        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "select * from db_wizard_user_data.t_login_security where user_id = {$user_id}";
            DEBUG && log::write($sql, "debug");
            if($mysqli_result = $mysqli->query($sql))
            {   
                if (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                {
                    $info = array(
                                'user_id' => $row['user_id'],
                                'mount' => $row['mount']
                                        );
                }
                $mysqli_result->close();       
                if ($info['mount'] >= ERROR_NUMBER)
                {
                    return -2;
                }
                else
                {
                    return 0;
                }
            }
            else
            {   
                log::write("Mysql Error:[{$sql}], {$mysqli->error}","error");
                return -1;     
            }
        }
        else
        {
            return -1;
        }
    } 

    public function login_security_delete($user_id)
    {
        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "delete from db_wizard_user_data.t_login_security where user_id = {$user_id}";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                log::write("Mysql Error:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    } 
    
    
    public function update_record($info)
    {
        $conn_info = $this->db_info[$this->get_db_name($info['user_id'])];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $temp = "";
            if (isset($info['accttype']))
            {
                $temp .= 'accttype = '. $info['accttype'] . ',';
            }
            if (isset($info['acctregtime']))
            {
                $temp .= 'acctregtime = '. $info['acctregtime'] . ',';
            }
            if (isset($info['hascharacter']))
            {
                $temp .= 'hascharacter = '. $info['hascharacter'] . ',';
            }
            if (isset($info['mute']))
            {
                $temp .= 'mute = '. $info['mute'] . ',';
            }
            if (isset($info['susp']))
            {
                $temp .= 'susp = '. $info['susp'] . ',';
            }
            if (isset($info['permissions']))
            {
                $temp .= 'permissions = \''. $this->escape($info['permissions']) . '\',';
            }
            if (isset($info['userhasgifts']))
            {
                $temp .= 'userhasgifts = '. $info['userhasgifts'] . ',';
            }
            if (isset($info['bannedflag']))
            {
                $temp .= 'bannedflag = '. $info['bannedflag'] . ',';
            }
            if (isset($info['last_time']))
            {
                $temp .= 'last_time = '. $info['last_time'] . ',';
            }
            if (isset($info['accumulate_time']))
            {
                $temp .= 'accumulate_time = '. $info['accumulate_time'] . ',';
            }
            if (isset($info['pre_flag']))
            {
                $temp .= 'pre_flag = '. $info['pre_flag'] . ',';
            }
            if ($temp != "")
            {
            $temp = substr($temp, 0, strlen($temp) - 1); 
            }
            $sql = "update " . $this->get_table_name($info['user_id']) ." set " . $temp . " where user_id = {$info['user_id']}";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    } 

    public function update_record_affect($info)
    {
        $conn_info = $this->db_info[$this->get_db_name($info['user_id'])];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $temp = "";
            if (isset($info['accttype']))
            {
                $temp .= 'accttype = '. $info['accttype'] . ',';
            }
            if (isset($info['acctregtime']))
            {
                $temp .= 'acctregtime = '. $info['acctregtime'] . ',';
            }
            if (isset($info['hascharacter']))
            {
                $temp .= 'hascharacter = '. $info['hascharacter'] . ',';
            }
            if (isset($info['mute']))
            {
                $temp .= 'mute = '. $info['mute'] . ',';
            }
            if (isset($info['susp']))
            {
                $temp .= 'susp = '. $info['susp'] . ',';
            }
            if (isset($info['permissions']))
            {
                $temp .= 'permissions = \''. $this->escape($info['permissions']) . '\',';
            }
            if (isset($info['userhasgifts']))
            {
                $temp .= 'userhasgifts = '. $info['userhasgifts'] . ',';
            }
            if (isset($info['bannedflag']))
            {
                $temp .= 'bannedflag = '. $info['bannedflag'] . ',';
            }
            if (isset($info['last_time']))
            {
                $temp .= 'last_time = '. $info['last_time'] . ',';
            }
            if (isset($info['accumulate_time']))
            {
                $temp .= 'accumulate_time = '. $info['accumulate_time'] . ',';
            }
            if (isset($info['pre_flag']))
            {
                $temp .= 'pre_flag = '. $info['pre_flag'] . ',';
            }
            if ($temp != "")
            {
            $temp = substr($temp, 0, strlen($temp) - 1); 
            }
            $sql = "update " . $this->get_table_name($info['user_id']) ." set " . $temp . " where user_id = {$info['user_id']}";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                if ($mysqli->affected_rows >= 1)
                {
                    return 0;
                }
                else
                {
                    return 1;
                }
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return -1;     
            }
        }
        else
        {
            return -1;
        }
    } 


    public function get_info($mimi_id)
    {
        if (!isset($mimi_id))
        {
            log::write("Mysql get_info para unset mimi_id:{$mimi_id}","error");
            return false;
        }

        $conn_info = $this->db_info[$this->get_db_name($mimi_id)];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "select * from " . $this->get_table_name($mimi_id) ." where user_id = {$mimi_id}";
            DEBUG && log::write($sql, "debug");
            if($mysqli_result = $mysqli->query($sql))
            {   
                if (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                {
                    $info = array(
                                'user_id' => $row['user_id'],
                                'accttype'=>$row['accttype'],
                                'acctregtime' => $row['acctregtime'],
                                'hascharacter' => $row['hascharacter'],
                                'mute' => $row['mute'],
                                'susp' => $row['susp'],
                                'permissions' => $row['permissions'],
                                'userhasgifts' => $row['userhasgifts'],
                                'bannedflag' => $row['bannedflag'],
                                'last_time' => $row['last_time'],
                                'accumulate_time' => $row['accumulate_time'],
                                'pre_flag' => $row['pre_flag']
                                        );
                }
                $mysqli_result->close();       
                return $info;            
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
    
    public function insert_info($info)
    {
        $conn_info = $this->db_info[$this->get_db_name($info['user_id'])];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            if (isset($info['permissions']))
            {
                $permissions = "'{$this->escape($info['permissions'])}'"; 
            }
            else
            {
                $permissions = 'DEFAULT';
            }
            $sql = "insert ignore into " . $this->get_table_name($info['user_id']) ."(user_id,accttype,acctregtime,hascharacter,mute,susp,permissions,userhasgifts,bannedflag,last_time,accumulate_time,pre_flag) values('{$info['user_id']}','{$info['accttype']}','{$info['acctregtime']}','{$info['hascharacter']}','{$info['mute']}','{$info['susp']}',". $permissions .", '{$info['userhasgifts']}', '{$info['bannedflag']}','{$info['last_time']}', '{$info['accumulate_time']}', '')";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
    
    public function reg_insert($info)
    {
        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "insert ignore into db_wizard_user_data.t_registration(user_id,gender,school,name,fillcolor,trimcolor) values('{$info['user_id']}','{$info['gender']}','{$info['school']}','{$info['name']}','{$info['fillcolor']}','{$info['trimcolor']}')";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
    
    public function reg_get($mimi_id)
    {
        if (!isset($mimi_id))
        {
            log::write("Mysql get_info para unset mimi_id:{$mimi_id}","error");
            return false;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];

        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "select * from db_wizard_user_data.t_registration where user_id = {$mimi_id}";
            DEBUG && log::write($sql, "debug");
            if($mysqli_result = $mysqli->query($sql))
            {   
                if (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                {
                    $info = array(
                                'user_id' => $row['user_id'],
                                'gender'=>$row['gender'],
                                'school' => $row['school'],
                                'name' => $row['name'],
                                'fillcolor' => $row['fillcolor'],
                                'trimcolor' => $row['trimcolor']
                                        );
                }
                $mysqli_result->close();       
                return $info;            
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
 
    public function reg_delete($mimi_id)
    {
        if (!isset($mimi_id))
        {
            log::write("Mysql get_info para unset mimi_id:{$mimi_id}","error");
            return false;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "delete from db_wizard_user_data.t_registration where user_id = {$mimi_id}";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }

    function get_product_id($updatetype, $desc)
    {
        if (!isset($updatetype, $desc))
        {
            log::write(__FUNCTION__ . " para unset updatetype:{$updatetype}, desc:{$desc}","error");
            return false;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "insert ignore into db_wizard_user_data.t_production(updatetype,`desc`) values('{$updatetype}','{$desc}')";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                $sql = "select * from db_wizard_user_data.t_production where updatetype = $updatetype and `desc` = '$desc'";
                if($mysqli_result = $mysqli->query($sql))
                {   
                    if (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                    {
                        return $row['id']; 
                    }
                    return false;            
                }
                return false;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }

    function get_trade_id($trade_str, $user_id, $amount, $updatetype, $desc, &$trade_id, $domain, $charname)
    {
        if (!isset($trade_str, $user_id, $amount, $updatetype, $desc, $domain, $charname))
        {
            log::write(__FUNCTION__ . " para unset trade_str:{$trade_str}, trade_id:{$trade_id}, user_id:{$user_id}","error");
            return -1;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "insert ignore into db_wizard_user_data.t_production_trade(user_id,trade_id,amount,updatetype,`desc`,time, domain, charName) values('{$user_id}','{$trade_str}','{$amount}','{$updatetype}','{$desc}','".time()."', '{$domain}', '{$charname}')";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                $sql = "select * from db_wizard_user_data.t_production_trade where trade_id = '$trade_str'";
                if($mysqli_result = $mysqli->query($sql))
                {   
                    if (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                    {
                        $trade_id = $row['id']; 
                        return 0;
                    }
                    return -1;            
                }
                return -1;
            }
            else
            {   
                log::write_data($sql, 'Crown');
                return -1;     
            }
        }
        else
        {
            return -1;
        }
    }

    function get_order($user_id)
    {
        if (!isset($user_id))
        {
            log::write(__FUNCTION__ . " para unset user_id:{$user_id}","error");
            return false;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "select * from db_wizard_user_data.t_production_trade where user_id = '$user_id'";
            DEBUG && log::write($sql, "debug");
            if($mysqli_result = $mysqli->query($sql))
            {   
                while (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                {
                    $info[] = array(
                                'id' => $row['id'],
                                'user_id' => $row['user_id'],
                                'trade_id'=>$row['trade_id'],
                                'transaction_id'=>$row['transaction_id'],
                                'amount'=>$row['amount'],
                                'updatetype' => $row['updatetype'],
                                'desc' => $row['desc'],
                                'time' => $row['time'],
                                'domain' => $row['domain'],
                                'charname' => $row['charname']
                                        );
                }
                $mysqli_result->close();       
                return $info;            
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
    
    public function update_trade_id($id, $transaction_id)
    {
        if (!isset($id, $transaction_id))
        {
            log::write(__FUNCTION__ . " para unset id:{$id} transaction_id:{$transaction_id}","error");
            return false;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "update db_wizard_user_data.t_production_trade set transaction_id = $transaction_id where id = $id";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                return false;     
            }
        }
        else
        {
            return false;
        }
    }

    public function gift_insert($info)
    {
        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "insert ignore into db_wizard_user_data.t_gift_list(rewarditem_id,user_id,gift_id,reward_qty,time) values('{$info['rewarditem_id']}','{$info['user_id']}','{$info['gift_id']}','{$info['reward_qty']}','".time()."')";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return true;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
    
    function getGiftList($mimi_id)
    {
        if (!isset($mimi_id))
        {
            log::write(__FUNCTION__ . " para unset mimi_id:{$mimi_id}", "error");
            return false;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $limit_num = 100;
            $sql = "select * from db_wizard_user_data.t_gift_list where user_id = {$mimi_id} and flag = 0 limit ". ($limit_num + 1);
            DEBUG && log::write($sql, "debug");
            if($mysqli_result = $mysqli->query($sql))
            {
                $i = $limit_num;   
                while (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                {
                    $info['gift_info'][] = array(
                                'rewarditem_id' => $row['rewarditem_id'],
                                'gift_id' => $row['gift_id'],
                                'reward_qty' => $row['reward_qty']
                                        );
                    $i--;
                    if ($i == 0)
                    {
                        break;
                    }
                }

                $mysqli_result->close();       
                if ($mysqli->affected_rows > $limit_num)
                {
                    $info['success'] = 'true';
                    $info['hasmoregifts'] = 'true'; 
                }
                else
                {
                    $info['success'] = 'true';
                    $info['hasmoregifts'] = 'true'; 
                }

                return $info;            
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
    
    function inGameRedemption($mimi_id, $item_id)
    {
        if (!isset($mimi_id, $item_id))
        {
            log::write(__FUNCTION__ . " para unset mimi_id:{$mimi_id}, item_id:{$item_id}", "error");
            return false;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $limit_num = 100;
            $sql = "select * from db_wizard_user_data.t_gift_list where user_id = {$mimi_id} and flag = 0 limit " . ($limit_num + 1);
            DEBUG && log::write($sql, "debug");
            if($mysqli->query($sql))
            {
                if ($mysqli->affected_rows > 1)
                {
                    $info['hasmoregifts'] = 'true'; 
                }
                else
                {
                    $info['hasmoregifts'] = 'false'; 
                }

                $sql = "select * from db_wizard_user_data.t_gift_list where flag = 0 and rewarditem_id = {$item_id}";
                if($mysqli_result = $mysqli->query($sql))
                {
                    if (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                    {
                        $info['gift_info'] = array(
                            'rewarditem_id' => $row['rewarditem_id'],
                            'gift_id' => $row['gift_id'],
                            'reward_qty' => $row['reward_qty']
                        );
                    }
                    else
                    {
                        $info['success'] = 'true';
                        if ($info['hasmoregifts'] == 'false')
                        {
                            $info['hasmoregifts'] = 'true';
                        }
                        return $info;
                    }

                    $mysqli_result->close();       
                    $sql = "update db_wizard_user_data.t_gift_list set flag = 1 where rewarditem_id = {$item_id}";
                    if($mysqli->query($sql))
                    {
                        $info['success'] = 'true';
                        return $info;            
                    }
                    else
                    {
                        log::write('mysql', 'error');
                        return false;
                    }
                }
                else
                {   
                    log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                    return false;     
                }
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return false;     
            }
        }
        else
        {
            return false;
        }
    }
    
    public function domain_insert($info)
    {
        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "insert ignore into db_wizard_user_data.t_domain(domain,notifcationUrl) values('{$info['domain']}','{$info['notificationUrl']}')";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return 0;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return -1;     
            }
        }
        else
        {
            return -1;
        }
    }

    public function domain_delete($info)
    {
        if (!isset($info))
        {
            log::write("Mysql domin_delete para unset info","error");
            return -1;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];
        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "delete from db_wizard_user_data.t_domain where domain = '{$info['domain']}'";
            DEBUG && log::write($sql, "debug");
            if(true === $mysqli->query($sql))
            {
                return 0;
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return -1;     
            }
        }
        else
        {
            return -1;
        }
    }

    public function domain_get_other($domain)
    {
        if (!isset($domain))
        {
            log::write("Mysql domain_get_other para unset domain:{$domain}","error");
            return -1;
        }

        $conn_info = $this->db_info['db_wizard_user_data'];

        if ($mysqli = $this->instance->get_connect($conn_info))
        {
            $sql = "select * from db_wizard_user_data.t_domain where domain != '{$domain}'";
            DEBUG && log::write($sql, "debug");
            if($mysqli_result = $mysqli->query($sql))
            {   
                if (($row = $mysqli_result->fetch_array(MYSQLI_ASSOC)) != NULL)
                {
                    $info = array(
                                'domain' => $row['domain'],
                                'notifcationUrl'=>$row['notifcationUrl']
                                        );
                }
                $mysqli_result->close();       
                return $info;            
            }
            else
            {   
                log::write("Mysql Erorr:[{$sql}], {$mysqli->error}","error");
                return -1;     
            }
        }
        else
        {
            return -1;
        }
    }
}
