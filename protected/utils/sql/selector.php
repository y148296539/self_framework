<?php
/**
 * 查询辅助类
 *
 * @author cao_zl
 */
class utils_sql_selector {
    /**
     * @var utils_sql_pdo 
     */
    private $_pdo = null;
    
    private $_table = '';
    
    private $_leftJoin = array();
    
    private $_columns = '*';
    
    private $_where = array();
    
    private $_order = '';
    
    private $_group = '';
    
    private $_limit = 0;
    
    private $_bindParams = array();
    
    private $_offset = 0;
    
    private $_sql = '';
    
    private static $_single = null;
    
    /**
     * 初始化查询工具
     * @return utils_sql_selector
     */
    public static function initSelector(){
        if(!self::$_single){
            self::$_single = new self();
        }
        self::$_single->_init();
        return self::$_single;
    }
    
    /**
     * 接收数据库实例
     */
    private function __construct() {
    }
    
    /**
     * 初始化内置变量
     */
    private function _init() {
        $this->_table = '';
        $this->_leftJoin = array();
        $this->_columns = '*';
        $this->_where = array();
        $this->_order = '';
        $this->_group = '';
        $this->_limit = 0;
        $this->_offect = 0;
        $this->_bindParams = array();
        $this->_sql = '';
    }
    
    /**
     * 查询的主表
     * 
     * @param string $tableName 表名
     * @param string $asName 表别名
     * @return utils_selector
     */
    public function from($tableName , $asName=''){
        $this->_table = $tableName . ($asName ? ' as '.$asName : '');
        return $this;
    }
    
    /**
     * 左联表join left
     * 
     * @param string $tableName 表名
     * @param string $asName 表别名
     * @param string $onRule 关联条件
     * @return utils_selector
     */
    public function leftJoin($tableName , $asName , $onRule){
        $this->_leftJoin[] = 'left join ' . $tableName . ' as ' . $asName . ' on ' . $onRule;
        return $this;
    }
    
    /**
     * 查找的字段
     * @param array/string $columns 查询的字段，array('x.id,y.mid') 与 "x.id , y.mid" 两者等价
     * @return utils_selector 
     */
    public function fromColumns($columns='*'){
        if(is_array($columns)){
            $this->_columns = implode(',', $columns);
        }elseif($columns){
            $this->_columns = $columns;
        }
        return $this;
    }
    
    /**
     * 条件语句
     * @param string $whereString 条件语句,例如："userid = ?" 或 "id > ?"; 若不使用绑定，可直接写全where子句，如“stime between 1288888 and 120000000”
     * @param string $param 绑定的值,若传入“10888”，则上例中的?会被替换为 10888
     * @return utils_selector 
     */
    public function where($whereString , $param=''){
        $match = array();
        if(preg_match('%^\s*([\w\.]+)\s*(<>|[><=]|>=|<=)\s*(\?)\s*$%' , $whereString , $match)){
            $column = $match[1];
            $count = count($this->_bindParams);
            $bindKey = ':k'.$count;
            $this->_where[] = $column.$match[2].$bindKey;
            $this->_bindParams[$bindKey] = $param;
        }else{
            $this->_where[] = $whereString;
        }
        return $this;
    }
    
    /**
     * 分组字段
     * 
     * @param string $group
     * @return utils_selector
     */
    public function group($group){
        $this->_group = $group;
        return $this;
    }
    
    /**
     * 排序字段
     * @param array $order 排序条件,array('id asc' , 'weight desc')或"id asc,weight desc"，两者等效
     * @return utils_selector 
     */
    public function order($order){
        $this->_order = is_array($order) ? implode(',' , $order) : $order;
        return $this;
    }
    
    /**
     * 结果集数量
     * @param int $limit 返回结果数
     * @param int $page 页码
     * @return utils_selector 
     */
    public function limit( $limit , $page=0){
        if($limit){
           $this->_limit = (int) $limit; 
        }
        if($this->_limit && $page && $page > 1){
            $this->_offset = (intval($page) - 1) * $limit;
        }
        return $this;
    }
    
    /**
     * 获取查询的数据库.表名
     * @return string
     */
    private function _getDbTable(){
        return $this->_table;
    }
    
    /**
     * 准备查询用的sql
     */
    private function _prepareSql(){
        $this->_checkParams();
        $this->_sql  = 'select ';
        $this->_sql .= $this->_columns.' ';
        $this->_sql .= 'from '.$this->_getDbTable().' ';
        if($this->_leftJoin){
            $this->_sql .= implode(' ' , $this->_leftJoin).' ';
        }
        if($this->_where){
            $this->_sql .= 'where '.implode(' and ' , $this->_where).' ';
        }
        if($this->_group){
            $this->_sql .= 'group by '.$this->_group.' ';
        }
        if($this->_order){
            $this->_sql .= 'order by '.$this->_order.' ';
        }
        if($this->_limit){
            $this->_sql .= 'limit '.$this->_offset.','.$this->_limit;
        }
        
    }
    
    /**
     * 打印出当前的SQL字符串
     * @return string
     */
    public function __toString() {
        $this->_prepareSql();
        $sql = $this->_sql;
        if($this->_bindParams){
            foreach($this->_bindParams as $key => $value){
                $sql = str_replace($key, '"'.addslashes($value).'"', $sql);
            }
        }
        return $sql;
    }
    
    /**
     * 防止基本的参数错误
     * @throws Exception
     */
    private function _checkParams(){
        if(!$this->_table){
            throw new Exception('tableName must be set,now is empty' , 1);
        }
        if($this->_bindParams){
            foreach($this->_bindParams as $key => $value){
                if(is_null($value)){
                    throw new Exception('the key('.$key.') value is null,please give a value or use where_string"columnName is null"');
                }
            }
        }
    }
    
    
    public function connectPdo($dbConfigKey='db'){
        $this->_pdo = utils_sql_pdo::getInstance($dbConfigKey);
    }
    
    /**
     * 重建与数据库的连接，一般用不到
     * - 主要在使用fork子进程时丢失子进程数据库连接,刷新用
     */
    public function rebuildPdoConnect(){
        if($this->_pdo){
            $this->_pdo->connect();
        }
    }
        
    /**
     * 获取单条结果
     * 
     * @return array 一维数组
     */
    public function fetchRow(){
        $this->_prepareSql();
        $this->connectPdo();
        return $this->_pdo->fetchRow($this->_sql , $this->_bindParams);
    }
    
    /**
     * 获取多条结果 多维数组
     * 
     * @return array
     */
    public function fetchAll($rebuildPdoConnect=false){
        $this->_prepareSql();
        $this->connectPdo();
        return $this->_pdo->fetchAll($this->_sql , $this->_bindParams);
    }
    
    
    /**
     * 通过完整SQL查询
     * @param string $sql
     * @param array $bindParams
     * 
     * @return array
     */
    public function fetchAllBySql($sql , $bindParams = array() , $rebuildPdoConnect=false){
        $this->_sql = $sql;
        $this->connectPdo();
        return $this->_pdo->fetchAll($this->_sql);
    }
    
}
