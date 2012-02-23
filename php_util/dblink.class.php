<?php
class dblink
{
    /**
     * 存储单例对象 
     * @var array
     */
    private $instance = array() ;
        
    /**
     * 数据库配置数组
     * @var array
     */
    private $a_conf = array() ;
    
    /**
     * 连接的唯一的标识
     * @var string
     */
    private $s_key = null ;
    
    /**
     * @var string
     */
    static $single_instance;
    
    function __construct()
    {       
    }

    function __destruct()
    {
        $this->close();
    }

    public static function getInstance()
    {
    	if(!(self::$single_instance instanceof self)){
    		self::$single_instance = new self();
    	}
    	return self::$single_instance;
    }
    
    /**
     * 连接数据库
     * 
     * @param array $a_conf
     * @param return error:false success:connect
     */
    public function get_connect($a_conf)
    {  
        $this->a_conf = $a_conf ;

        if(empty($this->a_conf)) {
            log::write('DbLink>>>>:'.__CLASS__.'->'.__FUNCTION__.'[L'.__LINE__."] | Parameter is not set:".print_r($a_conf,true), 'error') ;
            return false ;
        } 
        
        // 生成唯一键
        $s_key = $this->build_unique_key() ;
        
        // 生成对象并返回
        if(!isset($this->instance[$s_key])) {            
            if (!$this->mysql_connect())
            {
                return false; 
            } 
        }
        //DEBUG && log::write(print_r($this->instance, true), "debug");
        if ($this->instance[$s_key]->ping()) {
        } else {
            log::write("Error: ".$this->instance[$s_key]->error,'error');
            return false;
        }
        return $this->instance[$s_key] ;
    }

    /**
     * 断开数据库连接
     * @param $s_key
     */
    public function close($s_key=false)
    {
        if( $s_key && isset($this->instance[$s_key]) ){
            $this->instance[$s_key] -> close();
            unset($this->instance[$s_key]);
        }else {
            foreach($this->instance as $key => $value)
            { 
                $value->close();
                unset($this->instance[$key]);
            }
        }
        return true ;
    }
    
    /**
     * 链接mysql 数据库 
     */
    private function mysql_connect()
    {
        if (isset($this->a_conf['host']) && isset($this->a_conf['port']) && isset($this->a_conf['user']) && isset($this->a_conf['passwd']))
        {     

            $mysqli = new mysqli($this->a_conf['host'],$this->a_conf['user'],$this->a_conf['passwd'],'',$this->a_conf['port']);
            if($mysqli->connect_errno)
            {
                log::write('mysqli_connect_error'.$mysqli->connect_error,'error');
                return false;
            }

            if(!$mysqli->set_charset("utf8"))
            {
                log::write('mysqli set_charset'.$mysqli->error,'error');
                $mysqli->close();
                return false;
            }
            $this->instance[$this->s_key] = $mysqli;
            return true ;
        }
        else
        {
            log::write('DbLink>>>>:'.__CLASS__.'->'.__FUNCTION__.'[L'.__LINE__."] | Parameter is not set.".print_r($a_conf,true), 'error') ;
            return false;
        }
    }

    private function build_unique_key()
    {
        // 赋值
        $s_host = isset($this->a_conf['host']) ? $this->a_conf['host'] : '' ;
        $s_port = isset($this->a_conf['port']) ? $this->a_conf['port'] : '' ;
        $s_user = isset($this->a_conf['user']) ? $this->a_conf['user'] : '' ;
        $s_pswd = isset($this->a_conf['passwd']) ? $this->a_conf['passwd'] : '' ;
        
        return $this->s_key = md5(sprintf("%s;%s;%s;%s;", $s_host, $s_port ,$s_user, $s_pswd));
    }
}
