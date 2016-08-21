<?php
/**
 * 自定义自加载类
 */
class ApplicationAutoload{

    private $_ruleMethodList = array(
        '_ruleCore',
        '_rule1',
        '_ruleComponents',
    );

    private $_pattern_rule1 = '%^(logic|utils)_%';

    public static function register($className){
        return self::getInstance()->load($className);
    }

    public static function getInstance(){
        static $_instance = null;
        if(!$_instance){
            $_instance = new self();
        }
        return $_instance;
    }

    private function __construct() {

    }

    public function load($className){
        try{
            foreach($this->_ruleMethodList as $ruleMethod){
                if($this->$ruleMethod($className) === true){
                    return true;
                }
            }
            return false;
        }catch (ExceptionAutoload $e){
            throw new Exception($e->getMessage() , $e->getCode() , $e->getPrevious());
        }catch (Exception $e){
            die('autoload error : '.$e->getMessage());
        }
        return true;
    }


    private function _ruleCore($className){
        if(substr($className, 0, 11) == 'Application'){
            $filePath = APPLICATION_CORE_PATH . $className.'.php';
            if(!file_exists($filePath)){
                throw new ExceptionAutoload("ruleCore class ($className) autoload failed:path($filePath)");
            }
            include $filePath;
            return true;
        }
        return false;
    }

    private function _rule1($className){
        if(preg_match($this->_pattern_rule1, $className)){
            $filePath = APPLICATION_CORE_PATH . '..' . DIRECTORY_SEPARATOR . strtr($className , array('_' => DIRECTORY_SEPARATOR)) . '.php';
            if(!file_exists($filePath)){
                throw new ExceptionAutoload("rule1 class ($className) autoload failed:path($filePath)");
            }
            include $filePath;
            return true;
        }
        return false;
    }


    private function _ruleComponents($className){
        $filename = APPLICATION_PATH.'protected'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.$className.'.php';
        if(is_file($filename)){
            include $filename;
            return true;
        }
        return false;
    }



}


class ExceptionAutoload extends Exception{

    public function __construct($message, $code=0, $previous=null) {
        if(!$code){
            $code = 990;
        }
        return parent::__construct($message, $code, $previous);
    }

}