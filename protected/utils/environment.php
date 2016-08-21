<?php
/**
 * 环境变量检测
 * 
 * @author caozl
 */
class utils_environment {
    /**
     * 开发环境标记
     */
    const FLAG_development = 'development';
    /**
     * 测试环境标记
     */
    const FLAG_testing = 'testing';
    /**
     * 生产环境标记
     */
    const FLAG_production = 'production';
    /**
     * 预发布环境标记
     */
    const FLAG_production2 = 'production2';
    /**
     * 返回环境标记
     * @return string
     */
    public static function getEnvironent(){
        if(defined('ENVIRONMENT') && in_array(ENVIRONMENT, array(self::FLAG_development , self::FLAG_testing , self::FLAG_production2))){
            return ENVIRONMENT;
        }
        return self::FLAG_production;
    }
    
    /**
     * 判断是否是开发环境
     * @return boolean
     */
    public static function isDevelopment(){
        return self::getEnvironent() == self::FLAG_development ? true : false;
    }
    
    /**
     * 判断是否是生产环境
     * @return boolean
     */
    public static function isProduction(){
        return self::getEnvironent() == self::FLAG_production ? true : false;
    }
    
    /**
     * 判断是否是测试环境
     * @return boolean
     */
    public static function isTesting(){
        return self::getEnvironent() == self::FLAG_testing ? true : false;
    }
    
    /**
     * 判断是否是预发布环境
     * @return boolean
     */
    public static function isProduction2(){
        return self::getEnvironent() == self::FLAG_production2 ? true : false;
    }
    
    
    
}
