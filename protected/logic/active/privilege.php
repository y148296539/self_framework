<?php
/**
 * 与特权相关的逻辑
 *
 * @author cao_zl
 */
class logic_active_privilege {
    
    private static $_instance = null;
    /**
     * 特权实收表表名
     * @var string 
     */
    private $_tbl_nid = 'deayou_circle_user_privilege_nid';
    /**
     * 特权加值记录表
     * @var string
     */
    private $_tbl_log = 'deayou_circle_user_privilege_log';
    /**
     * 特权累计表
     * @var string
     */
    private $_tbl_privilege = 'deayou_circle_user_privilege';
    /**
     * 
     * @return logic_active_privilege
     */
    public static function getInstance(){
        if(!self::$_instance){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * 用户指定时刻的特权值
     * - 从日志中回溯，一般不使用
     * 
     * @param int $user_id 用户uid
     * @param int $infoid 活动ID
     * @param int $moment 指定时刻的时间戳
     * @return float
     */
    public function getUserPrivilegeFromLog($user_id , $infoid , $moment){
        $sumRow = utils_mysql::getSelector()->from($this->_tbl_log)
                ->fromColumns('sum(p_rate) as sum_p_rate')
                ->where('invite_userid = ?' , $user_id)
                ->where('infoid = ?' , $infoid)
                ->where('uptime < ?' , $moment)
                ->fetchRow();
        return $sumRow ? $sumRow['sum_p_rate'] : 0;
    }
    
    /**
     * 获取用户在活动中的特权
     * 
     * @param int $user_id 用户uid
     * @param int $infoid 活动ID
     * 
     * @return float
     */
    public function getUserPrivilege($user_id , $infoid){
        $record = utils_mysql::getSelector()->from($this->_tbl_privilege)
                ->fromColumns('p_rate')
                ->where('user_id = ?' , $user_id)
                ->where('infoid = ?' , $infoid)
                ->fetchRow();
        return $record ? $record['p_rate'] : 0;
    }
    
    /**
     * 获取用户在指定标的特权收益记录 - 已起息的标才有记录
     * 
     * @param int $user_id 用户ID
     * @param string $borrow_nid 标的标记
     * @param int $infoid 指定活动的ID，不传则不区分
     * @return array
     */
    public function getUserPrivilegeNidByBorrowNid($user_id , $borrow_nid , $infoid=0){
        $selector = utils_mysql::getSelector()->from($this->_tbl_nid)
                ->fromColumns('p_rate , wx_account_tender , wx_acconut_interest')
                ->where('user_id = ?' , $user_id)
                ->where('borrow_nid = ?' , $borrow_nid);
        if($infoid){
            $selector->where('infoid = ?' , $infoid);
        }
        return $selector->fetchRow();
    }
    
    /**
     * 获取指定标的特权总金额
     * @return float
     */
    public function getSumPrivilegeByBorrowNid($borrow_nid){
        $record = utils_mysql::getSelector()->from($this->_tbl_nid)
                ->fromColumns('sum(wx_acconut_interest) as sum')
                ->where('borrow_nid = ?' , $borrow_nid)
                ->fetchRow();
        return ($record && $record['sum']) ? $record['sum'] : 0;
    }
    
}
