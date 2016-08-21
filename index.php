<?php
/**
 * 在 protected 文件夹下新建文件夹 runtime ，赋予可写权限
 * 图片功能需要安装 magickwand 扩展 ，基于 imagemagick
 * 
 * 检查数据库配置文件，修改特定环境文件
 */

//设置时区
date_default_timezone_set('Asia/Shanghai');

//环境变量初始化 - 判断当前系统环境用于自动配置
if(function_exists('getenv') && getenv('APPLICATION_ENVIRONMENT') && in_array(getenv('APPLICATION_ENVIRONMENT') , array('development' , 'testing'))){
    define('ENVIRONMENT', getenv('APPLICATION_ENVIRONMENT'));
    define('DEBUG',true);
}else{//默认取不到环境变量，为线上环境，关闭DEBUG
    define('ENVIRONMENT', 'production');
    define('DEBUG',false);
}
//指定使用的配置文件
$config_file = 'global';
//载入引导文件
include dirname(__FILE__) . DIRECTORY_SEPARATOR. 'protected'.  DIRECTORY_SEPARATOR . 'Bootstrap.php';
//运行
Application::web($config_file)->run();