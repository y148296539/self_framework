<?php

class errorController extends baseController{
    
    
    public function errorAction(){
        if(defined('DEBUG') && DEBUG){
            $errorInfo = $this->getParam('errorInfo');
            if($errorInfo){
                print_r($errorInfo);
                exit;
            }
            $exceptionInfo = $this->getParam('exceptionInfo');
            if($exceptionInfo && ($exceptionInfo instanceof Exception)){
                echo 'exception:' , '<br>';
                echo 'code:' , $exceptionInfo->getCode() , '<br>';
                echo 'message:' , $exceptionInfo->getMessage() , '<br>';
//                echo 'line:' , $exceptionInfo->getLine() , '<br>';
                echo 'previous:';
                var_dump($exceptionInfo->getPrevious());
                exit;
            }
            $fatalError = $this->getParam('fatalError');
            if($fatalError){
                echo 'fatalError:' , '<br>';
                print_r($fatalError);
                exit;
            }
            echo 'error page';
        }else{
            echo 'errorPage';
        }
    }
    
    
    
    
}