<?php
/**
 * 充值相关
 *
 * @author cao_zl
 */
class logic_accounts_recharge {
    
    private $_recharge_tbl = 'deayou_account_recharge';
    private static $_instance = null;
    /**
     * 
     * @return logic_accounts_recharge
     */
    public static function getInstance(){
        if(!self::$_instance){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * 以ID倒叙排列表
     * @param array $fromColums 查找的列名，例：array('id' , 'nid' , 'user_id' , 'money' , 'addtime')
     * @param array $where 条件，键值对
     * @param int $limit 最大返回结果集数量
     * @return array
     */
    public function getListOrderById($fromColums , $where , $limit){
        $selector = utils_mysql::getSelector()->from($this->_recharge_tbl);
        if($fromColums){
            $selector->fromColumns($fromColums);
        }else{
            $selector->fromColumns('*');
        }
        if(isset($where['stime']) && $where['stime'] > 0){
            $selector->where('addtime >= ?' , $where['stime']);
        }
        if(isset($where['etime']) && $where['etime'] > 0){
            $selector->where('addtime < ?' , $where['etime']);
        }
        if(isset($where['verify_time_stime']) && $where['verify_time_stime'] > 0){
            $selector->where('verify_time >= ?' , $where['verify_time_stime']);
        }
        if(isset($where['verify_time_etime']) && $where['verify_time_etime'] > 0){
            $selector->where('verify_time < ?' , $where['verify_time_etime']);
        }
        if(isset($where['status']) && is_numeric($where['status'])){
            $selector->where('status = ?' , $where['status']);
        }
        if(isset($where['min_id']) && $where['min_id']){
            $selector->where('id > ?' , $where['min_id']);
        }
        $selector->order('id');
        $selector->limit($limit, 1);
        return $selector->fetchAll();
    }
    
    
}
