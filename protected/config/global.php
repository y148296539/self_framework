<?php

return array(
    'site_name'             => 'php功能API',
    'default_module'        => 'default',
    'default_controller'    => 'index',
    'default_action'        => 'index',
    'error_controller'      => 'error',
    'error_action'          => 'error',
    'layout_dir'            => APPLICATION_PATH.'protected'.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR,
    'module_path'           => APPLICATION_PATH.'protected'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR,
//    'register_modules'      => array(//注册模块名 - 未注册的模块无法加载
//        'default'
//    ),
    'db'                    => array(
        'host'                  => '127.0.0.1',
        'username'              => 'root',
        'password'              => '' ,
        'port'                  => 3306,
        'db_name'               => 'test',
        'charset'               => 'utf8',
    ),
    'memcache'              => array('127.0.0.1' , 11211 , 60),
    'web_page_charset'      => 'UTF-8',
);