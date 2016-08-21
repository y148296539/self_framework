<?php
/**
 * mysql直连查询类 - PDO
 *
 * @author cao_zl
 */
class utils_sql_pdo {
    
    private $_host = '';
    
    private $_username = '';
    
    private $_password = '';
    
    private $_port = '';
    
    private $_db_name = '';
    
    private $_charset = '';
    /**
     * @var PDO
     */
    private $_dbh = null;//database handle
    /**
     * 
     * @staticvar array $_instance
     * @param string $dbConfigKey 系统配置中的键名，若不存，则使用传入的参数进行数据库连接
     * @param string $host 指定地址
     * @param string $username 指定用户名
     * @param string $password 指定密码
     * @param string $port 指定端口
     * @param string $db_name 指定数据库名
     * @param string $charset 指定编码
     * @return utils_sql_pdo
     */
    public static function getInstance($dbConfigKey , $host='' , $username='' , $password='' , $port='' , $db_name='' , $charset=''){
        static $_instance = array();
        $sourceKey = $dbConfigKey ? $dbConfigKey : md5($host."|".$port."|".$username.'|'.$password.'|'.$db_name.'|'.$charset);
        if(!isset($_instance[$sourceKey])){
            $_instance[$sourceKey] = new self($dbConfigKey , $host , $username , $password , $port , $db_name , $charset);
        }
        return $_instance[$sourceKey];
    }
    
    private function __construct($dbConfigKey , $host , $username , $password , $port , $db_name, $charset) {
        $db_config = $dbConfigKey ? Application::$config->get($dbConfigKey) : array();
        $this->_host        = isset($db_config['host'])     ? $db_config['host'] : $host;
        $this->_username    = isset($db_config['username']) ? $db_config['username'] : $username;
        $this->_password    = isset($db_config['password']) ? $db_config['password'] : $password;
        $this->_port        = isset($db_config['port'])     ? $db_config['port'] : $port;
        $this->_db_name     = isset($db_config['db_name'])  ? $db_config['db_name'] : $db_name;
        $this->_charset     = isset($db_config['charset'])  ? $db_config['charset'] : $charset;
        $this->connect();
    }
    
    private function __clone(){}

    /**
     * 连接数据库
     */
    public function connect(){
        $dsn = 'mysql:host='.$this->_host.';dbname='.$this->_db_name.';port='.$this->_port.';charset='.$this->_charset;
        $params = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'set names "'.$this->_charset.'"',
        );
        $this->_dbh = new PDO($dsn, $this->_username, $this->_password , $params);
        if($this->_dbh){
            $this->_dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
        }
    }
    
    /**
     * 获取多行结果集
     * 
     * @param sql $sql
     * @param array $bindParams
     * @return array
     */
    public function fetchAll($sql , $bindParams=array()){
        $statementHandle = $this->query($sql, $bindParams);
        if($statementHandle){
            $statementHandle->setFetchMode(PDO::FETCH_ASSOC);
            return $statementHandle->fetchAll();
        }else{
            return array();
        }
    }
    
    /**
     * 获取单行结果记录
     * 
     * @param string $sql
     * @param array $bindParams
     * @return array 单行结果
     */
    public function fetchRow($sql , $bindParams=array()){
        $statementHandle = $this->query($sql, $bindParams);
        return (!$statementHandle) ? array() : $statementHandle->fetch(PDO::FETCH_ASSOC);
    }
    
    
    /**
     * 执行SQL并返回影响的行数
     * @param string $sql
     * @param array $bindParams
     * @return int 影响行数
     */
    public function excuteAndAffectedNum($sql , $bindParams=array()){
        $statementHandle = $this->query($sql, $bindParams);
        return (!$statementHandle) ? 0 : $statementHandle->rowCount();
    }
    
    
    /**
     * 插入操作并返回自增ID
     * @param string $sql
     * @param array $bindParams
     * @return int 自增ID
     */
    public function insertAndGetId($sql , $bindParams=array()){
        $statementHandle = $this->query($sql, $bindParams);
        return $statementHandle ? $statementHandle->lastInsertId() : 0;
    }
    
    /**
     * 执行SQL语句
     * @param string $sql
     * @param array $bindParams
     * @return PDOStatement
     * @throws Exception
     */
    public function query($sql , $bindParams){
        try{
            if(!$this->_dbh){
                throw new Exception('pdo connect failed', 999);
            }
            $statementHandle = $this->_dbh->prepare($sql);
            if(!$statementHandle){
                throw new Exception('pdo prepare failed , sql:'.$sql);
            }
            if($bindParams){
                foreach($bindParams as $key => $value){
                    $statementHandle->bindValue($key, $value);
                }
            }
            if($statementHandle->execute() === false){
                $pdoError = $statementHandle->errorInfo();
                throw new Exception($pdoError[2].'|'.$statementHandle->errorCode() , $pdoError[1]);
            }
            return $statementHandle;
        }catch(PDOException $pdo_e){
            $string  = 'PDOException:'."\r\n";
            $string .= 'message:'.$pdo_e->getMessage()."\r\n";
            $string .= 'code:'.$pdo_e->getCode()."\r\n";
            $string .= 'sql:'.$sql."\r\n";
            $string .= 'params:'.json_encode($bindParams);
            utils_file::writeLog(date('d').'.log', $string, 'pdoQueryError/'.date('Y-m'));
            if(defined('DEBUG') && DEBUG){
                throw new Exception($pdo_e->getMessage(), $pdo_e->getCode(), $pdo_e->getPrevious());
            }
        }catch(Exception $e){
            $string  = 'Exception:'."\r\n";
            $string .= 'message:'.$e->getMessage()."\r\n";
            $string .= 'code:'.$e->getCode()."\r\n";
            $string .= 'sql:'.$sql."\r\n";
            $string .= 'params:'.json_encode($bindParams);
            utils_file::writeLog(date('d').'.log', $string, 'pdoQueryError/'.date('Y-m'));
            if(defined('DEBUG') && DEBUG){
                throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }
        return false;
    }
    

    /**
     * 
     * @return PDO
     */
    public function getPDOHandler(){
        return $this->_dbh;
    }
    

    public function __destruct() {
        $this->_dbh = null;
    }

}
