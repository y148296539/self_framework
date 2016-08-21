<?php
/**
 * 站内普通用户账户逻辑
 *
 * @author cao_zl
 */
class logic_accounts_user {
    
    /**
     * 当前逻辑主表 - 用户资产总表
     * @var string
     */
    private $_tbl = 'deayou_account';
    /**
     * 资产变动日志表
     * @var string
     */
    private $_tbl_account_log = 'deayou_account_log';
    
    /**
     * 单例实例化
     * @staticvar null $_instance
     * @return logic_accounts_user
     */
    public static function getInstance(){
        static $_instance = null;
        if(!$_instance){
            $_instance = new self();
        }
        return $_instance;
    }
    
    /**
     * 获取用户账户记录
     * 
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserAccounts($user_id){
        $selector = utils_mysql::getSelector()->from($this->_tbl)
                ->where('user_id = ?' , $user_id);
        return $selector->fetchRow();
    }
    
    /**
     * 获取日志列表
     * 
     * @param array $where 查询条件 
     * @param string/array 排序条件
     * @param int $page 第几页
     * @param int $limit 结果集数量
     * @return array
     */
    public function getAccountLogList($where , $order='id desc' , $page=1 , $limit=20){
        $selector = utils_mysql::getSelector()->from($this->_tbl_account_log);
        if(is_array($where) && isset($where['user_id']) && $where['user_id']){
            $selector->where('user_id = ?' , $where['user_id']);
        }
        if(is_array($where) && isset($where['type']) && $where['type']){
            $selector->where('type = ?' , $where['type']);
        }
        if(is_array($where) && isset($where['stime']) && $where['stime']){
            $selector->where('addtime >= ?' , $where['stime']);
        }
        if(is_array($where) && isset($where['etime']) && $where['etime']){
            $selector->where('addtime < ?' , $where['etime']);
        }
        if($order){
            $selector->order($order);
        }else{
            $selector->order('id desc');
        }
        $selector->limit($limit, $page);
        return $selector->fetchAll();
    }
    
    
}
