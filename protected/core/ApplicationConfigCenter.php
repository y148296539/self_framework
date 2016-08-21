<?php

class ApplicationConfigCenter{
    
    private $_config_file = '';
    
    private $_configRegister = array(
        'charset'               => 'UTF-8',
        'error_controller'      => 'errorController',
        'error_action'          => 'indexAction',
        'default_controller'    => 'index',
        'default_action'        => 'index',
    );
    
    /**
     * 
     * @param string $configFilePath
     * @return ApplicationConfigCenter
     */
    public static function initConfig($configFilePath){
        self::_getInstance()->_setConfigFile($configFilePath);
        return self::_getInstance();
    }
    
    /**
     * 获取配置的值
     * @param string $configKey
     * @return mixed
     */
    public static function getConfig($configKey){
        if(!self::_getInstance()->_config_file){
            throw new Exception('application_configCenter:config_file_not_found');
        }
        return self::_getInstance()->_get($configKey);
    }
    
    private function _setConfigFile($configFilePath){
        if($this->_config_file){
            throw new Exception('application_configCenter_construct_error:init_can_be_called_only_once');
        }
        $this->_config_file = $configFilePath;
        $this->_setConfig();
    }
    
    /**
     * 
     * @staticvar null $_instance
     * @return ApplicationConfigCenter
     */
    private static function _getInstance(){
        static $_instance = null;
        if($_instance === null){
            $_instance = new self();
        }
        return $_instance;
    }
    
    private function _setConfig(){
        $coverConfig = require $this->_config_file;
        $this->_configRegister = utils_map::mergeArray($this->_configRegister, $coverConfig);
        $this->_mergeEnvironmentConfig();
    }
    
    private function _mergeEnvironmentConfig(){
        $environmentConfigCoverFile = dirname($this->_config_file).DIRECTORY_SEPARATOR.'coverGlobalConfig'.DIRECTORY_SEPARATOR.utils_environment::getEnvironent().'.php';
        if(is_file($environmentConfigCoverFile)){
            $environmentConfigCover = require $environmentConfigCoverFile;
            $this->_configRegister = utils_map::mergeArray($this->_configRegister, $environmentConfigCover);
        }
    }
    
    /**
     * 获取配置的值
     * @param string $configKey
     * @return mixed
     */
    private function _get($configKey){
        return isset($this->_configRegister[$configKey]) ? $this->_configRegister[$configKey] : null;
    }
}