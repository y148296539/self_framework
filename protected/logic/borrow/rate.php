<?php
/**
 * 利率、换算 等的转换方法类
 *
 * @author cao_zl
 */
class logic_borrow_rate {
    
    /**
     * 获取指定第几期的利息金额
     * 
     * @param float $principal 本金
     * @param float $rate 年化利率 - 百分之十传入“0.1”
     * @param string $borrow_style 借款表记录中的 borrow_style , 值有“endday”，“endmonth”,"end"
     * @param int $borrow_period 借款表中的 borrow_period,借款总期数
     * @param int $nowPeriod 当前还的第几期,不指定则返回利息总额
     * 
     * @return float 计算出的结果
     */
    public static function getTargetPeriodInterest($principal , $rate , $borrow_style , $borrow_period , $nowPeriod=0){
        list($allPeriod , $timeUnit , $time) = self::getRepaymentParamsByBorrowSet($borrow_style, $borrow_period);
        $allIntereset = self::getInterest($principal, $rate, $time, $timeUnit);
        if($nowPeriod){
            $return = self::cutMoneyToPart($allIntereset, $allPeriod, $nowPeriod);
        }else{
            $return = $allIntereset;
        }
        return $return;
    }
    
    /**
     * 通过Borrow表中的设置，获取计息参数
     * @param string $borrow_style 借款表记录中的 borrow_style , 值有“endday”，“endmonth”,"end"
     * @param int $borrow_period 借款表中的 borrow_period,借款总期数
     * @return array array(还款总期数 , 时间单位 , 有效计息时长)
     */
    public static function getRepaymentParamsByBorrowSet($borrow_style , $borrow_period){
        if($borrow_style == 'endday'){
            $allPeriod = 1;
            $timeUnit = 'day';
            $time = $borrow_period;
        }elseif($borrow_style == 'endmonth'){
            $allPeriod = $borrow_period;
            $timeUnit = 'month';
            $time = $borrow_period;
        }elseif($borrow_style == 'end'){
            $allPeriod = 1;
            $timeUnit = 'month';
            $time = $borrow_period;
        }else{
            throw new Exception('borrow_style('.$borrow_style.') in the class (utils_rate) is not defined');
        }
        return array($allPeriod , $timeUnit , $time);
    }
    
    
    /**
     * 总利息的计算公式
     * 总利息 = 本金 * 年化利率 * 计息总期数 / 一年的总期数
     * 
     * @param float $principal 本金
     * @param float $rate 年化利率
     * @param int $time 计息时长 ，如：三个月传入"3"，十七天传入"17"，两年传入"2"
     * @param string $timeUnit 时长的单位，值有4种: day(天) , month(月) , year(年) , season(季度)
     * 
     * @return float 利息金额
     */
    public static function getInterest($principal , $rate , $time , $timeUnit){
        //一年整的利息
//        $fullYearInterest = bcmul($principal , $rate , 4);
        $fullYearInterest = round($principal * $rate , 4);
        list($yearUnit , $effectivePeriod) = self::_geInterestParams($time , $timeUnit);
        //时长参数的最大公约数
        $gys = self::getHCM($yearUnit , $effectivePeriod);
        if(!$gys){
            $gys = 1;
        }
        //计算公式如下：$interest = $fullYearInterest * ($effectivePeriod / $gys) / ($yearUnit / $gys)
//        $interest = bcmul(bcdiv($fullYearInterest , ($yearUnit / $gys) , 4) , ($effectivePeriod / $gys) , 2);
        $interest = round($fullYearInterest * ($effectivePeriod / $gys) / ($yearUnit / $gys) , 2);
        return $interest;
    }
    
    /**
     * 获取利息参数
     * 
     * @param int $time 计息时长 ，如：三个月传入"3"，十七天传入"17"，两年传入"2"
     * @param string $timeUnit 时长的单位，值有4种: day(天) , month(月) , year(年) , season(季度)
     * @return array
     */
    private static function _geInterestParams($time , $timeUnit){
        switch ($timeUnit):
            case 'day'://天
                $yearUnit = 365; //一年的计算期数
                $effectivePeriod = $time; //有效
                break;
            case 'month'://月
                $yearUnit = 12;
                $effectivePeriod = $time;
                break;
            case 'year'://年
                $yearUnit = 12;
                $effectivePeriod = $time * 12;
                break;
            case 'season'://季度
                $yearUnit = 12;
                $effectivePeriod = $time * 3;
                break;
            default:
                die('time unit error:'. $timeUnit.' not defined');
        endswitch;
        return array($yearUnit , $effectivePeriod);
    }
    
    /**
     * 将总金额平均切分，返回指定期数的金额
     * 
     * @param float $money 总金额
     * @param int $allPeriod 还款的总期数
     * @param int $nowPeriod 指定的还款期数
     * @return float
     */
    public static function cutMoneyToPart($money , $allPeriod , $nowPeriod){
        $onePart = bcdiv($money, $allPeriod, 2);
        if($nowPeriod == $allPeriod){//最后一期还款时，处理不足部分金额
            $return = bcsub($money , bcmul(($allPeriod - 1) , $onePart , 2 ) , 2);
        }else{
            $return = $onePart;
        }
        return $return;
    }
    
    
    /**
     * 取两数的最大公约数
     * 
     * @param int $m
     * @param int $n
     * @return int
     */
    public static function getHCM($m, $n) {
        if($m ==0 && $n == 0) {
            return false;
        }
        if($n == 0) {
            return $m;
        }
        while($n != 0){
            $r = $m % $n;
            $m = $n;
            $n = $r;
        }
        return $m;
    }
    
    /**
     * 还款计划
     * - 当前的还款方式是：无论多少期，过程中只还利息，本金最后一期还
     * - 若有在过程中还本金的，使用cutMoneyToPart处理每期本金
     * 
     * @param float $principal 本金
     * @param float $rate 年化利率 - 百分之十传入“0.1”
     * @param string $borrow_style 借款表记录中的 borrow_style , 值有“endday”，“endmonth”,"end"
     * @param int $borrow_period 借款表中的 borrow_period,借款总期数
     * @param int $begin_time 计算利息的开始时间,时间戳
     * @return array
     */
    public static function getRepaymentPlan($principal , $rate , $borrow_style , $borrow_period , $begin_time){
        list($allPeriod , $timeUnit , $time) = utils_rate::getRepaymentParamsByBorrowSet($borrow_style, $borrow_period);
        $plan = array();
        for($period = 1; $period <= $allPeriod ; $period ++){
            //当期应还利息
            $interest = utils_rate::getTargetPeriodInterest($principal, $rate, $borrow_style, $borrow_period , $period);
            //当期应还本金
            $repay_principal = ($period == $allPeriod) ? $principal : 0;
            //当期还款时间
            $repay_time = self::getTargetTime(($time / $allPeriod) * $period, $timeUnit, $begin_time);
            $plan[] = array(
                'period'    => $period, //第几期还款
                'interest'  => $interest,//利息
                'principal' => $repay_principal,//本金
                'repay_time'=> $repay_time,//还款时间，时间戳
            );
        }
        return $plan;
    }
    
    /**
     * 
     * @param int $long 时长
     * @param string $timeUnit 单位 ，如“day” “month”
     * @param int $begin_time 起始时间，不指定则使用当前时间，时间戳
     * 
     * @return int 时间戳
     */
    public static function getTargetTime($long , $timeUnit , $begin_time=0){
        return strtotime('+'.$long.' '.$timeUnit, $begin_time ? $begin_time : time());
    }
    
}
