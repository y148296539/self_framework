<?php
/**
 * 红包表相关
 *
 * @author cao_zl
 */
class logic_active_packet {
    /**
     * 用户拥有的红包表
     * @var string
     */
    private $_tbl_packet = 'deayou_packet_users';
    
    /**
     * 
     * @staticvar null $_instance
     * @return logic_active_packet
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
     * 用户获得的红包列表
     * 
     * @param array $where 查找条件
     * @param array/string $order 排序条件
     * @param int $page 第几页
     * @param int $limit 每页展示的结果集数量
     * @param array $columns 查询的字段
     * 
     * @return array
     */
    public function getUserPacketList($where=array() , $order=array('id') , $page=0 , $limit=0 , $columns='*'){
        $seletor = utils_mysql::getSelector()->from($this->_tbl_packet)
                ->fromColumns($columns);
        if($where && is_array($where)){
            if(isset($where['min_id']) && is_numeric($where['min_id'])){
                $seletor->where('id > ?' , $where['min_id']);
            }
            if(isset($where['pstatus']) && is_numeric($where['pstatus'])){
                $seletor->where('pstatus = ?' , $where['pstatus']);
            }elseif(isset($where['pstatus']) && is_array($where['pstatus'])){
                $seletor->where('pstatus in ('.implode(',' , $where['pstatus']).')' );
            }
            if(isset($where['update_stime']) && is_numeric($where['update_stime'])){
                $seletor->where('uptime >= ?' , $where['update_stime']);
            }
            if(isset($where['update_etime']) && is_numeric($where['update_etime'])){
                $seletor->where('uptime < ?' , $where['update_etime']);
            }
            if(isset($where['typereturn']) && is_numeric($where['typereturn'])){
                $seletor->where('typereturn = ?' , $where['typereturn']);
            }
        }
        if($order){
            $seletor->order($order);
        }else{
            $seletor->order('id desc');
        }
        $seletor->limit($limit, $page);
        return $seletor->fetchAll();
    }
    
    /**
     * 返回红包类型
     * - 1为现金红包，2为本金红包
     * @param int $type
     * 
     * @return string
     */
    public function getPacketType($type){
        switch($type):
            case 1:
                return '现金红包';
            case 2:
                return '本金红包';
            case 0:
                return '可提现红包';
            default:
                return '未定义类型'.$type;
        endswitch;
    }
    
    /**
     * 返回红包的使用状态
     * @param int $status
     * @return string
     */
    public function getPacketStatus($status){
        if($status){
            switch($status){
                case 1:
                    return '已使用';
                case 2:
                    return '待查2';
                case 3:
                    return '使用中';
                default:
                    return '未定义'.$status;
            }
        }
        return 'null';
    }
    
    /**
     * 统计用户在指定日期投资成功(起息)的红包总金额统计
     * 
     * @param int $userid
     * @param string $date
     * 
     * @return array key:packet_sum,extra_packet_sum
     */
    public function countTenderSuccessPacketByDateAndUserId($userid , $date){
        $borrow_nids = logic_borrow_borrow::getInstance()->getReverifyBorrowNidByDate($date);
        if($borrow_nids){
            $tenderRecrods = utils_mysql::getSelector()->from('deayou_borrow_tender')
                    ->fromColumns(array('packid' , 'extra_packid'))
                    ->where('user_id = ?' , $userid)
                    ->where('borrow_nid in ("'.implode('","', $borrow_nids).'")')
                    ->where('(length(packid) > 5 or length(extra_packid) > 5)')
                    ->fetchAll();
            if($tenderRecrods){
                $packetIdList = array();
                $extraPacketIdList = array();
                foreach($tenderRecrods as $tenderRecrod){
                    if($tenderRecrod['packid']){
                        $packetIdList[] = $tenderRecrod['packid'];
                    }
                    if($tenderRecrod['extra_packid']){
                        $extraPacketIdList[] = $tenderRecrod['extra_packid'];
                    }
                }
                if($packetIdList){
                    $countPacket = utils_mysql::getSelector()->from($this->_tbl_packet)
                            ->fromColumns('sum(returnprice) as sum')
                            ->where('packid in ("'.implode('","' , $packetIdList).'")')
                            ->fetchRow();
                }
                if($extraPacketIdList){
                    $countExtraPacket = utils_mysql::getSelector()->from($this->_tbl_packet)
                            ->fromColumns('sum(returnprice) as sum')
                            ->where('packid in ("'.implode('","' , $extraPacketIdList).'")')
                            ->fetchRow();
                }
            }
        }
        return array(
            'packet_sum'        => isset($countPacket) ? $countPacket['sum'] : 0,
            'extra_packet_sum'  => isset($countExtraPacket) ? $countExtraPacket['sum'] : 0,
        );
    }
    
    
}
