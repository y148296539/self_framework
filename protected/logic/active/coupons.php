<?php
/**
 * 加息券相关逻辑
 * - 两张表：interest_coupon（用户拥有的券）   interest_coupon_appl（券的使用情况表）
 *
 * @author cao_zl
 */
class logic_active_coupons {
    
    private static $_instance = null;
    
    /**
     * 表名：用户拥有的券
     * @var string 
     */
    private $_tbl_owner = 'interest_coupon';
    
    /**
     * 券的使用情况表
     * @var string 
     */
    private $_tbl_status = 'interest_coupon_appl';
    
//    const USE_STATUS
    
    /**
     * @return logic_active_coupons
     */
    public static function getInstance(){
        if(self::$_instance === null){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * 获取用户的标下加息券信息
     * 
     * @param int $user_id
     * @param int $borrow_id
     * @return array
     */
    public function getCouponsPayInfoByUseridAndBorrowId($user_id , $borrow_id){
        $record = utils_mysql::getSelector()
                ->from($this->_tbl_owner, 'u')
                ->leftJoin($this->_tbl_status, 's', 'u.interest_coupon_id = s.interest_coupon_id')
                ->fromColumns(array(
                    'u.interest_coupon_id as id', 'u.user_id' , 'u.rate' ,
                    's.borrow_id', 's.profit_amount' , 's.status' , 
                ))
                ->where('u.user_id = ?' , $user_id)
                ->where('s.borrow_id = ?' , $borrow_id)
                ->fetchRow();
        return $record;
    }
    
    
//    public function 
    
}
