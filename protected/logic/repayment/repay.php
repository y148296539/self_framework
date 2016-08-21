<?php
/**
 * borrow_repay表的逻辑处理
 *
 * @author cao_zl
 */
class logic_repayment_repay {
    /**
     * repay表的表名
     * @var string
     */
    private $_tbl = 'deayou_borrow_repay';
    /**
     * recover表的表名
     * @var string 
     */
    private $_recover_tbl = 'deayou_borrow_recover';
    /**
     * tender表名
     * @var string 
     */
    private $_tender_tbl = 'deayou_borrow_tender';

    /**
     * 
     * @staticvar null $_instance
     * @return logic_repayment_repay
     */
    public static function getInstance(){
        static $_instance = null;
        if(!$_instance){
            $_instance = new self();
        }
        return $_instance;
    }

    /**
     * 通过ID，获取用户还款表的一条记录
     * 
     * @param int $id
     * @return array
     */
    public function getRepayRow($id){
        $row = utils_mysql::getSelector()->from($this->_tbl)
                ->where('id = ?' , $id)
                ->fetchRow();
        return $row;
    }
    
    /**
     * 通过借款标的NID获取当前标的所有还款合并记录
     * 
     * @param  $borrow_nid 借款标识
     * 
     * @return array 记录集合
     */
    public function getRepayRecordsGroupPeriodByBorrowNid($borrow_nid){
        $records = utils_mysql::getSelector()->from($this->_tbl)
                ->fromColumns(array(
                    'user_id','borrow_nid','repay_status', 'repay_period',
                    'repay_yestime','repay_time',
                    'sum(repay_account) as sum_repay_account',//应还总金额
                    'sum(repay_capital) as sum_repay_capital',//应还本金
                    'sum(repay_interest) as sum_repay_interest',//应还利息
                    'sum(repay_account_yes) as sum_repay_account_yes' , //还款总金额
                    'sum(repay_capital_yes) as sum_repay_capital_yes' , //还款中本金金额
                    'sum(repay_interest_yes) as sum_repay_interest_yes',//还款中利息金额
                ))
                ->where('borrow_nid = ?' , $borrow_nid)
                ->group('repay_period')
                ->order(array('repay_period'))
                ->fetchAll();
        return $records;
    }
    
    /**
     * 通过借款标记和期数，分组获取投资人相关的还款记录
     * 
     * @param string $borrow_nid 借款标记
     * @param int $period 第几期还款
     * @param int $limit 返回结果数量
     * @param int $minId 从多少的ID开始
     * @return array
     */
    public function getRecoverRecordsGroupUidByBorrowNidAndPeriod($borrow_nid , $period , $limit=0, $minId=false){
        $selector = utils_mysql::getSelector()->from($this->_recover_tbl)
                ->fromColumns(array(
                    'id','status','user_id','recover_status',
                    'count(id) as record_num',
                    'sum(recover_account) as sum_recover_account' , //还款总金额
                    'sum(recover_zs_interest) as sum_recover_zs_interest',//官方需补贴赠送本金的利息
                ))
                ->where('borrow_nid = ?' , $borrow_nid)
                ->where('recover_period = ?' , $period)
                ->group('user_id')
                ->order('id');
        if($minId !== false){
            $selector->where('id > ?' , $minId);
        }
        if($limit){
            $selector->limit($limit);
        }
        return $selector->fetchAll();
    }
    
    /**
     * 获取用户在指定标下的每期还款统计信息
     * 
     * @param string $borrow_nid 标号
     * @param string $user_id 用户ID
     * @return array
     */
    public function getRecoverRecordsGroupPeriodByBorrowNidAndUserid($borrow_nid , $user_id){
        $records = utils_mysql::getSelector()->from($this->_recover_tbl)
                ->fromColumns(array(
                    'user_id','status','recover_status','recover_period',
                    'recover_time', 'recover_yestime',
                    'sum(recover_account) as sum_recover_account',//应还总金额
                    'sum(recover_capital) as sum_recover_capital',//应还本金
                    'sum(recover_interest) as sum_recover_interest',//应还利息
                    'sum(recover_account_yes) as sum_recover_account_yes' , //已还总金额
                    'sum(recover_capital_yes) as sum_recover_capital_yes' , //还款中本金金额
                    'sum(recover_interest_yes) as sum_recover_interest_yes',//还款中利息金额
                ))
                ->where('borrow_nid = ?' , $borrow_nid)
                ->where('user_id = ?' , $user_id)
                ->group('recover_period')
                ->order('recover_period')
                ->fetchAll();
        return $records;
    }
    
    
    /**
     * 通过借款标记和期数，获取投资人相关的还款记录
     * 
     * @param string $borrow_nid 借款标记
     * @param int $period 第几期还款
     * @param int $limit 返回结果数量
     * @param int $minId 从多少的ID开始
     * @return array
     */
    public function getRecoverRecordsByBorrowNidAndPeriod($borrow_nid , $period , $limit=0, $minId=false){
        $selector = utils_mysql::getSelector()->from($this->_recover_tbl, 'rc')
                ->leftJoin($this->_tender_tbl, 'te', 'rc.tender_id=te.id')
                ->fromColumns(array(
                    'rc.id','rc.status','rc.user_id','rc.recover_status',
                    'rc.recover_account' , //还款金额
                    'rc.recover_zs_interest',//官方需补贴赠送本金的利息
                    'te.payment'
                ))
                ->where('rc.borrow_nid = ?' , $borrow_nid)
                ->where('rc.recover_period = ?' , $period)
                ->order('rc.id');
        if($minId !== false){
            $selector->where('rc.id > ?' , $minId);
        }
        if($limit){
            $selector->limit($limit);
        }
        return $selector->fetchAll();
    }
    
    /**
     * 获取今日待还款的列表 - 可指定开始日期和结束日期
     * 
     * @param $targetDate 指定查询的开始日期，不指定则使用系统的默认时间，格式: 2015-03-30
     * @return array
     */
    public function getTodayRepayList($targetDate=''){
        $stime = $targetDate ? strtotime($targetDate) : strtotime(date('Y-m-d'));
        $etime = $stime + 86400;
        $repayRows = utils_mysql::getSelector()->from($this->_tbl)
                ->fromColumns('user_id, borrow_nid , repay_period')
                ->where('repay_time >= ?' , $stime)
                ->where('repay_time < ?' , $etime)
                ->where('repay_status = ?' , 0)
                ->where('repay_type = ?' , 'wait')
                ->group('borrow_nid')
                ->order('id')
                ->fetchAll();
//        $todayAllRows = array();
//        if($repayRows){
//            foreach($repayRows as $repayRow){
//                $todayRow = array('borrow_nid' => $repayRow['borrow_nid'] , 'repay_period' => $repayRow['repay_period']);
//                if(!in_array($todayRow, $todayAllRows)){
//                    $todayAllRows[] = $todayRow;
//                }
//            }
//        }
//        return $todayAllRows;
        return $repayRows;
    }
    
    /**
     * 获取今日以前(包括今日)需要还款的列表
     * - 指定日期的00:00:00~23:59:59之间
     * 
     * @param $targetDate 指定日期，不指定则使用系统的默认时间，格式: 2015-03-30
     * @return array
     */
    public function getBeforeTodayRepayList($targetDate=''){
        $etime = ($targetDate ? strtotime($targetDate) : strtotime(date('Y-m-d'))) + 86400;
        $rows = utils_mysql::getSelector()->from($this->_tbl)
                ->fromColumns('borrow_nid , repay_period , user_id')
                ->where('repay_time < ?' , $etime)
                ->where('repay_status = ?' , 0)
                ->where('repay_type = ?' , 'wait')
                ->group('borrow_nid')
                ->order('id')
                ->fetchAll();
        return $rows;
    }
    
    /**
     * 获取指定时间段的待还金额统计
     * 
     * @param int $stime 开始时间的时间戳（大于等于）
     * @param int $etime 结束时间的时间戳（小于等于）
     * @return array
     */
    public function getWaitRepayInfoByStartAndEndTime($stime , $etime){
        $countInfo = utils_mysql::getSelector()->from($this->_tbl)
                ->fromColumns(array(
                    'user_id' ,
                    'sum(repay_account) as sum_repay_account'
                ))
                ->where('repay_time >= ?' , $stime)
                ->where('repay_time <= ?' , $etime)
                ->where('repay_status = ?' , 0)
                ->where('repay_type = ?' , 'wait')
                ->group('user_id')
                ->fetchAll();
        return $countInfo;
    }
    
}
