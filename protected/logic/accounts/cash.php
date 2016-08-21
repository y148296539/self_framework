<?php
/**
 * 提现逻辑
 *
 * @author cao_zl
 */
class logic_accounts_cash {
    
    private $_tbl_cash = 'deayou_account_cash';//提现申请表
    
    /**
     * @staticvar null $_instance
     * @return logic_accounts_cash
     */
    public static function getInstance(){
        static $_instance = null;
        if($_instance === null){
            $_instance = new self();
        }
        return $_instance;
    }
    
    /**
     * 获取用户的提现冻结总金额
     * 
     * @param int $user_id 用户ID
     * @param string $date 指定日期的冻结，不指定日期则查询用户的提现冻结总金额
     * @return float
     */
    public function getUserCashRorstSum($user_id , $date=''){
        $selector = utils_mysql::getSelector()->from($this->_tbl_cash)
                ->fromColumns('sum(total) as sum')
                ->where('user_id = ?' , $user_id);
        if($date){
            $selector->where('addtime between '.strtotime($date).' and '.(strtotime($date) + 86399));
        }
        $result = $selector->fetchRow();
        return $result ? $result['sum'] : 0;
    }
    
    
    /**
     * 翻译状态值
     * @param int $status
     * @return string
     */
    public function translateStatus($status){
        switch ($status):
            case '-2':
                return '自动审核通过';
            case '-1':
                return '提现中';
            case '0':
                return '待人工审核-未处理状态';
            case '1':
                return '提现成功';
            case '2':
                return '提现失败';//冻结资金已归还
            case '3':
                return '用户撤销';
            case '4':
                return '回调资金异常';
            case '5':
                return '公司余额不足';
            case '6':
                return '自动审核不通过';
            case '7':
                return '请求发送出错';//包含发送中状态
            case '8':
                return '网络异常';
            case '9':
                return '订单号重复提交';//老状态，已废弃
            case '10':
                return '未知异常错误';//老状态，已废弃
            case '11':
                return '提现失败，冻结资金未归还';
            default :
                return '不明状态'.$status;
        endswitch;
        
        
    }
    
    
    
    
    
}
