<?php
/**
 * sql辅助用update拼装工具
 *
 * @author caozl
 */
class utils_sql_updater {
    /**
     *
     * @var utils_sql_pdo
     */
    private $_pdo = null;
    
    private $_tableName = '';
    
    private $_where = array();
    
    private $_update = array();
    
    private $_update_counter = array();
    
    private $_update_string = array();

    private $_bindParams = array();
    
    private $_sql = '';
    
    /**
     * 
     * @param string $tableName
     * @return utils_sql_updater
     */
    public static function init($tableName){
        $utils = new self($tableName);
        return $utils;
    }
    
    private function __construct($tableName){
        $this->_tableName = $tableName;
    }
    
    /**
     * 条件语句
     * @param string $whereString 条件语句,例如："userid = ?" 或 "id > ?"; 若不使用绑定，可直接写全where子句，如“stime between 1288888 and 120000000”
     * @param string $param 绑定的值,若传入“10888”，则上例中的?会被替换为 10888
     * @return utils_updateSql 
     */
    public function where($whereString , $param=''){
        $match = array();
        if(preg_match('%^\s*([\w\.]+)\s*(<>|[><=]|>=|<=)\s*(\?)\s*$%' , $whereString , $match)){
            $column = $match[1];
            $count = count($this->_bindParams);
            $this->_where[] = $column.$match[2].':'.$column.$count;
            $this->_bindParams[':'.$column.$count] = $param;
        }else{
            $this->_where[] = $whereString;
        }
        return $this;
    }
    
    /**
     * 传入需要修改的键值对
     * 
     * @param array $kvArr 例如：array('name' => 'new name' , 'update_time' => time());
     * @return utils_updateSql
     */
    public function update($kvArr){
        $this->_update = $kvArr;
        return $this;
    }
    
    /**
     * 对字段进行增减
     * 
     * @param string $column 键名
     * @param numeric $num 增减的值，加一传入“1” ，减三传入“-3”
     */
    public function updateCounter($column , $num){
        $this->_update_counter[] = $column . ' = ' . $column . ' + (' . $num .')';
        return $this;
    }
    
    /**
     * 直接修改字段，如：column = column1 + column2
     * 
     * @param string/array $string
     * @return utils_updateSql
     */
    public function updateString($string){
        if(is_array($string)){
            foreach($string as $s){
                $this->_update_string[] = $s;
            }
        }else{
            $this->_update_string[] = $string;
        }
        return $this;
    }
    
    /**
     * 检查所有参数是否有效
     * @throws Exception
     */
    private function _checkParams(){
        if(!$this->_tableName){
            throw new Exception('tableName must be set' , 1);
        }
        if(!$this->_update && !is_array($this->_update) && !$this->_update_counter){
            throw new Exception('update keyValueArray must be set' , 10);
        }
        foreach($this->_update as $key => $value){
            if(is_numeric($key)){
                throw new Exception('update keyValueArray must be column=>updateValue' , 20);
            }
        }
    }
    
    /**
     * 
     * @return string
     */
    public function __toString() {
        return $this->_prepareSafeSql();
    }
    
    /**
     * 处理SQL语句
     */
    private function _prepareSql(){
        $this->_sql = 'update '.$this->_tableName.' set ';
        $update = array();
        if($this->_update){
            foreach($this->_update as $key => $value){
                $update[] = $key . '=' . $value;
            }
        }
        if($this->_update_counter){
            foreach($this->_update_counter as $value){
                $update[] = $value;
            }
        }
        if($this->_update_string){
            foreach($this->_update_string as $value){
                $update[] = $value;
            }
        }
        $this->_sql .= implode(',' , $update).' ';
        if($this->_where){
            $this->_sql .= 'where '.implode(' and ' , $this->_where);
        }
        if($this->_bindParams){
            foreach($this->_bindParams as $key => $value){
                $this->_sql = str_replace($key, '"'.addslashes($value , $this->_mysql->db_link).'"', $this->_sql);
            }
        }
    }
    
    /**
     * 执行修改的保存动作
     * 
     * @return int 成功返回影响的记录行数，失败返回0，SQL错误则直接打印出来
     */
    public function save(){
        try{
            $this->_checkParams();
            $this->_prepareSql();
            $affect_num = $this->_pdo->affected_rows($this->_sql);
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            $affect_num = 0;
        }
        return $affect_num;
    }
    
}



