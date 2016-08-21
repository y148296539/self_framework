<?php
/**
 * 借款记录相关
 *
 * @author cao_zl
 */
class logic_borrow_borrow {
    /**
     * style 表名
     * @var string
     */
    private $_tbl_borrow_style = 'deayou_borrow_style';
    /**
     * type 表名
     * @var string
     */
    private $_tbl_borrow_type = 'deayou_borrow_type';
    /**
     * 投资总表表名
     * @var string
     */
    private $_tbl_tender = 'deayou_borrow_tender';
    /**
     * 借款总表表名
     * @var string
     */
    private $_tbl_borrow = 'deayou_borrow';
    
    /**
     * @staticvar null $_instance
     * @return logic_borrow_borrow
     */
    public static function getInstance(){
        static $_instance = null;
        if($_instance === null){
            $_instance = new self();
        }
        return $_instance;
    }
    
    /**
     * 通过标记，获取借款的还款方式
     * 
     * @staticvar array $_style
     * @param string $borrow_style
     * @return string
     */
    public function getBorrowStyleName($borrow_style){
        static $_style = array();
        if(!isset($_style[$borrow_style])){
            $row = utils_mysql::getSelector()->from($this->_tbl_borrow_style)
                    ->fromColumns('name')
                    ->where('nid = ?' , $borrow_style)
                    ->fetchRow();
            $_style[$borrow_style] = $row ? $row['name'] : '未定义';
        }
        return $_style[$borrow_style];
    }
    
    
    /**
     * 获取标种名称
     * 
     * @staticvar array $_type
     * @param string $borrow_type
     * @return string
     */
    public function getBorrowTypeName($borrow_type){
        static $_type = array();
        if(!isset($_type[$borrow_type])){
            $row = utils_mysql::getSelector()->from($this->_tbl_borrow_type)
                    ->fromColumns('name')
                    ->where('nid = ?' , $borrow_type)
                    ->fetchRow();
            $_type[$borrow_type] = $row ? $row['name'] : '未定义';
        }
        return $_type[$borrow_type];
    }
    
    
    /**
     * 获取借款的显示用时长
     * 
     * @param int $borrow_period borrow表中的borrow_period
     * @param string $borrow_style borrow表中的borrow_style
     * @param string $borrow_type borrow表中的borrow_type
     * @return string
     */
    public function getBorrowDurationShow($borrow_period , $borrow_style , $borrow_type){
        //坑爹的表记录 - cao
        if ($borrow_style == 'endday' || $borrow_type == "day"){
            $durationUnit = "天";
        }else{
            $durationUnit = "个月";
        }
        return $borrow_period.$durationUnit;
    }
    
    
    /**
     * 获取标的投满时间
     * 
     * @param array $borrow_record 借款标单条记录
     * @return int 有结果返回时间戳，无结果返回0
     */
    public function getBorrowFullTime($borrow_record){
        if(is_array($borrow_record) && isset($borrow_record['tender_last_time']) && $borrow_record['tender_last_time']){
            return $borrow_record['tender_last_time'];
        }elseif(is_array($borrow_record) && isset($borrow_record['borrow_nid']) && $borrow_record['borrow_nid']){
            $lastTender = utils_mysql::getSelector()->from($this->_tbl_tender)
                    ->fromColumns('addtime')
                    ->where('borrow_nid = ?' , $borrow_record['borrow_nid'])
                    ->order('id desc')
                    ->fetchRow();
            return $lastTender ? $lastTender['addtime'] : 0;
        }elseif(is_string($borrow_record) || is_numeric($borrow_record)){
            $lastTender = utils_mysql::getSelector()->from($this->_tbl_tender)
                    ->fromColumns('addtime')
                    ->where('borrow_nid = ?' , $borrow_record)
                    ->order('id desc')
                    ->fetchRow();
            return $lastTender ? $lastTender['addtime'] : 0;
        }else{
            return 0;
        }
    }
    
    /**
     * 获取指定日期(0点到24点)的满标borrow_nid列表
     * @param string $date 日期格式"2015-05-07"
     * @return array
     */
    public function getReverifyBorrowNidByDate($date){
        static $borrowList = array();
        if(!isset($borrowList[$date])){
            $borrowList = utils_mysql::getSelector()->from($this->_tbl_borrow)
                    ->fromColumns('borrow_nid')
                    ->where('reverify_status = 3')
                    ->where('reverify_time >= ?' , strtotime($date))
                    ->where('reverify_time < ?' , strtotime($date) + 86400)
                    ->fetchAll();
            $borrowList[$date] = utils_map::dealResult2SimpleArray($borrowList, 'borrow_nid');
        }
        return $borrowList[$date];
    }
    
    
}
