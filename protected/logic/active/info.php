<?php
/**
 * 活动信息相关
 *
 * @author cao_zl
 */
class logic_active_info {
    
    private $_tbl_active_info = 'deayou_activityinfo';
    
    private $_tmpPacketList = array();
    
    /**
     * 
     * @staticvar null $_instance
     * @return logic_active_info
     */
    public static function getInstance(){
        static $_instance = null;
        if($_instance === null){
            $_instance = new self();
        }
        return $_instance;
    }
    
    private function __construct(){
        
    }
    
    /**
     * 获取红包类型信息
     * @param string $flg 表中flg字段，对应用户红包表中的infoid
     * @return array
     */
    public function getPacketTypeInfo($flg){
        if($flg && !isset($this->_tmpPacketList[$flg])){
            $this->_tmpPacketList[$flg] = utils_mysql::getSelector()->from($this->_tbl_active_info)
                    ->fromColumns(array('title' , 'flg' , 'description' , 'status' , 'ctime' , 'endtime' , 'starttime'))
                    ->where('flg = ?' , $flg)
                    ->fetchRow();
        }
        return $flg ? $this->_tmpPacketList[$flg] : array();
    }
}
