<?php
/**
 * 用户信息处理逻辑
 *
 * @author cao_zl
 */
class logic_user_user {
    
    private static $_instance = null;
    /**
     * 用户主表表名
     * @var string
     */
    private $_tbl_user = 'deayou_users';
    /**
     * 用户信息表表名
     * @var string
     */
    private $_tbl_userinfo = 'deayou_users_info';
    
    /**
     * @return logic_user_user
     */
    public static function getInstance(){
        if(!self::$_instance){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct() {
    }
    
    /**
     * 获取用户的users表记录
     * 
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUser($user_id){
        return $user_id ? utils_mysql::getSelector()->from($this->_tbl_user)
                ->where('user_id = ?' , $user_id)
                ->fetchRow() : array();
    }
    
    /**
     * 获取用户的users_info表记录
     * 
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserInfo($user_id){
        return $user_id ? utils_mysql::getSelector()->from($this->_tbl_userinfo)
                ->where('user_id = ?' , $user_id)
                ->fetchRow() : array();
    }
    
    
    
}
