<?php
/**
 * 定时任务脚本引导文件
 * @author cao_zl
 */
set_time_limit(0);

$__systemOpts = getCMDParams('e');
if(in_array($__systemOpts['e'] , array('development' , 'testing' , 'production2'))){
    define('DEBUG',true);
    define('ENVIRONMENT' , $__systemOpts['e']);//系统环境类型
}elseif($__systemOpts['e']){
    define('DEBUG',true);//命令行执行错误显示出错误详情
    define('ENVIRONMENT' , 'production');//系统环境类型
}else{
    die('welcome!');
}

//设置时区
date_default_timezone_set('Asia/Shanghai');

//加载引导文件
include dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Bootstrap.php'; 

//系统初始化
$configFile = 'global';
Application::command($configFile);

/**
 * 获取命令行中传入的参数
 * - 该函数只在第一次调用时有效，后续补充声明的参数无法获取
 * - 短参数只接受 "e"和"o" 两个值
 * - 长参数可自定义 例如 "email" ，对应命令行中传入 --email exp@mail.com
 * 
 * @param string/array $param_keys
 * @return array 有值返回传入的值，未获取到则返回false
 */
function getCMDParams($param_keys){
    if(!is_array($param_keys)){
        $param_keys = array($param_keys);
    }
    $longopts = array();
    foreach($param_keys as $param_key){
        $longopts[] = $param_key.':';
    }
    $params = getopt('e:o:', $longopts);
    $param_keys = array_unique(array_merge($param_keys , array('e' , 'o')));
    foreach($param_keys as $param_key){
        $return[$param_key] = ($params && isset($params[$param_key])) ? $params[$param_key] : false;
    }
    return $return;
}

