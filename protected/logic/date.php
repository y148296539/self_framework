<?php
/**
 * 日期相关逻辑
 *
 * @author cao_zl
 */
class logic_date {
    /**
     * 工作状态字段：非工作日
     */
    const WORK_false = 0;
    /**
     * 工作状态字段：工作日
     */
    const WORK_true = 1;
    
    private static $_instance = null;

    /**
     * 获取 T + x 天 后的时间戳
     * 
     * @param int $xDay 天数，T+几就给出几
     * @param int $Tday_time 起始时间时间戳
     * 
     * @return int
     */
    public static function getTdayAddXday($xDay , $Tday_time){
        $workday_time = $Tday_time;
        if($xDay){
            $day = 1;
            $workDay = 1;
            while(true){
                $workday_time = strtotime($day++.' day' , $Tday_time);
                if(logic_date::getInstance()->checkDateIsWorkDay($workday_time)){
                    $workDay ++;
                }
                if($workDay > $xDay){
                    break;
                }
            }
        }
        return $workday_time;
    }
    
    /**
     * 
     * @return logic_date
     */
    public static function getInstance(){
        if(!self::$_instance){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * 检查给定日期是否为工作日
     * - 工作日返回： true
     * - 非工作日返回： false
     * 
     * @param int $date_time 指定日期时间戳
     * @return boolean
     */
    public function checkDateIsWorkDay($date_time){
        //是否在指定日期列表中，若在，检查是工作日还是休息日
        $specialDays = $this->getSpecialDays();
        $date = date('Y-m-d' , $date_time);
        if(isset($specialDays[$date])){
            return ($specialDays[$date]['work'] == logic_date::WORK_true) ? true : false;
        }
        //判断周一到周日
        $week = date('w' , $date_time);
        return in_array($week, array('0' , '6')) ? false : true;
    }
    
    
    public function getSpecialDays(){
        return array(
            '2015-06-22'    => array(
                'work'      => logic_date::WORK_false,
            ),
        );
    }
    
    /**
     * 获取逾期时间的显示
     * @param string $deadLineDate 结束时间线，如：2015-03-02
     * @param string $fromDate 指定从该时间计算到$deadLineDate的时长，如：2015-03-01
     * 
     * @return int 天数，未到显示正数，逾期显示负数，如：逾期三天返回“-3”
     */
    public static function getDelayTimeShow($deadLineDate , $fromDate){
        $deadLine = strtotime(date('Y-m-d', strtotime($deadLineDate)));
        $fromDateTime = strtotime(date('Y-m-d', strtotime($fromDate)));
        $delayTime = $deadLine - $fromDateTime;
        $delayDays = ($delayTime > 0) ? ceil($delayTime/86400) : floor($delayTime/86400);
        return $delayDays;
    }
    
}
