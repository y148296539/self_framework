<?php

return array(
    'site_name'             => '测试环境功能API',
    'db'                    => array(
        //公用开发环境
        'host'                  => '192.168.11.8',
        'username'              => 'dtd_dev_v3',
        'password'              => 'BTEOkS9iIhmNR8Jx' ,
        'db_name'               => 'dtd_p2pweb_dev_v3',
        'port'                  => 3307,
        'charset'               => 'utf8',
        //本地数据库
//        'host'                  => '127.0.0.1',
//        'username'              => 'root',
//        'password'              => '' ,
//        'db_name'               => 'dtd_p2pweb_online',
//        'port'                  => 3306,
//        'charset'               => 'utf8',
    ),
    'redis'                 => array('127.0.0.1' , 6379),
    'memcache'              => array('192.168.11.15' , 11211),
    
    'register_modules'      => array(//注册模块名 - 未注册的模块无法加载
        'demo'
    ),
);