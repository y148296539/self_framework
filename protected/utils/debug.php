<?php
/**
 * DEBUG工具类
 */
class utils_debug{
    
    /**
     * 系统运行状态参数显示
     * @param string $flag
     * @param boolean $print_now
     * 
     * @return utils_debug_sysyemRuntime
     */
    public static function systemRunStatus(){
        static $_instance = null;
        if(!$_instance){
            $_instance = new utils_debug_sysyemRuntime();
        }
        return $_instance;
    }
    
    
}

/**
 * 系统运行状态参数显示类
 */
class utils_debug_sysyemRuntime{
    /**
     * 系统运行状态暂存
     * @var array
     */
    private $_systemInfo = array();
    
    public function __construct(){
        $this->_systemInfo = array(
            array(
                'flag'      => 'system_start',
                'time'      => Application::$global['system_start_time'],
                'memory'    => Application::$global['system_start_memory'],
            ),
        );
    }
    
    /**
     * 追踪锚点
     * @param string $flag 锚点标记
     */
    public function trace($flag){
        $this->_systemInfo[] = array(
            'flag'      => $flag,
            'time'      => microtime(true),
            'memory'    => memory_get_usage(true),
        );
        return $this;
    }
    
    /**
     * 展示系统运行信息
     * 
     * @param string $style 展示格式
     * 
     * @return string
     */
    public function show($style=utils_http::CONTENT_TYPE_HTML){
        $tempInfo = array();
        $show = '';
        foreach($this->_systemInfo as $info){
            $flag = $info['flag'];
            $use_time = $tempInfo ? ($info['time'] - $tempInfo['time']) : 0;
            $use_memory = $tempInfo ? ($info['memory'] - $tempInfo['memory']) : 0;
            if($style == utils_http::CONTENT_TYPE_HTML){//网页方式展示格式
                $show .= $this->_showStyleWebPage($flag , $use_time , $use_memory , $info['time'] , $info['memory']);
            }else{//命令行方式展示格式
                $show .= $this->_showStyleCommandLine($flag , $use_time , $use_memory , $info['time'] , $info['memory']);
            }
            $tempInfo = $info;
        }
        return $show;
    }
    
    private function _showStyleWebPage($flag , $use_time , $use_memory , $all_time , $all_memory){
        $html  = '<p>';
        $html .= '锚点标记: <span style="color:green;">'.$flag.'</span><br>';
        $html .= '自上一锚点至当前用时: <span style="color:green;">'.round($use_time , 4).'</span> s<br>';
        $html .= '自上一锚点至当前内存增加: <span style="color:green;">'.$use_memory.'</span> byte<br>';
        $html .= '锚点标记时间时间戳: <span style="color:green;">'. $all_time . '</span><br>';
        $html .= '当前占用总内存: <span style="color:green;">'.$all_memory.'</span> byte<br>';
        $html .= '</p><hr>';
        return $html;
    }
    
    
    private function _showStyleCommandLine($flag , $use_time , $use_memory , $all_time , $all_memory){
        $html  = '----------------------------->>>>' . "\n";
        $html .= '锚点标记: '.$flag . "\n";
        $html .= '自上一锚点至当前用时: '.round($use_time , 4)  . "s \n";
        $html .= '自上一锚点至当前内存增加: '.$use_memory.' byte' . "\n";;
        $html .= '锚点标记时间时间戳：'. $all_time  . "\n";
        $html .= '当前占用总内存：'.$all_memory . "\n";
        $html .= '------------------------------<<<<' . "\n";
        return $html;
    }
    
    public function save(){
        
    }
}