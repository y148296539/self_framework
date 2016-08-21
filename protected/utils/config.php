<?php
if(!defined('APPLICATION_CONFIG_DIR')){
    define('APPLICATION_CONFIG_DIR', APPLICATION_PATH.'protected'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR);
}
/**
 * 配置文件统一管理
 *
 * @author cao_zl
 */
class utils_config {
    
    private static $_configList = array();

    private $_file_name = '';
    
    private $_file_ext = '.php';
    
    private $_configRegister = array();
    
    private $_coverDir = '_coverConfig';

    /**
     * 读取配置文件的指定值
     * @param string $fileName 文件名
     * @param string $fileExt 文件名后缀
     * @return utils_config
     */
    public static function getFile($fileName , $fileExt='.php'){
        if(!isset(self::$_configList[$fileName.$fileExt])){
            self::$_configList[$fileName.$fileExt] = new self($fileName , $fileExt);
            self::$_configList[$fileName.$fileExt]->_loadFile();
            self::$_configList[$fileName.$fileExt]->_mergeEnvironmentFile();
        }
        return self::$_configList[$fileName.$fileExt];
    }
    
    
    private function __construct($fileName , $fileExt) {
        $this->_file_name = $fileName;
        $this->_file_ext = $fileExt;
    }

    private function _loadFile(){
        $filePath = APPLICATION_CONFIG_DIR.$this->_file_name.$this->_file_ext;
        if(!file_exists($filePath)){
            throw new Exception('config file not found - '.$filePath);
        }
        $this->_configRegister = include $filePath;
    }
    
    
    private function _mergeEnvironmentFile(){
        $coverFilePath = APPLICATION_CONFIG_DIR.$this->_coverDir.DIRECTORY_SEPARATOR.utils_environment::getEnvironent().'_'.$this->_file_name.$this->_file_ext;
        if(file_exists($coverFilePath)){
            $coverConfig = include $coverFilePath;
            if(!is_array($coverConfig)){
                throw new Exception('cover config file format failed! - '.$coverFilePath);
            }
            $this->_configRegister = utils_map::mergeArray($this->_configRegister , $coverConfig);
        }
    }
    
    /**
     * 返回配置中指定键名的值
     * 
     * @param string $config_key
     * @param mixed $default_value
     * @return mixed
     */
    public function get($config_key , $default_value=null){
        return isset($this->_configRegister[$config_key]) ? $this->_configRegister[$config_key] : $default_value;
    }


}