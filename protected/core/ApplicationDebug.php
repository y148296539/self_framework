<?php
/**
 * 异常捕获与处理类
 *
 * @author cao_zl
 */
class ApplicationDebug {

    public static function catchErrorInfo($errno, $errstr, $errfile, $errline, $errcontext) {
        $dealClass = new self();
        $dealClass->setErrorInfo($errno, $errstr, $errfile, $errline, $errcontext);
        $dealClass->writeErrorLog();
        $dealClass->dealError();
    }

    private $_errorInfo = array();

    public function setErrorInfo($error_level, $error_string, $error_file, $error_line, $error_context) {
        $this->_errorInfo = array(
            'error_level' => $error_level,
            'error_string' => $error_string,
            'error_line' => $error_line,
            'error_file' => $error_file,
//            'error_context' => $error_context,
        );
    }

    public function writeErrorLog() {
        $writeString  = 'TYPE:error' . "\r\n";
        $writeString .= 'error_level:' . $this->_errorInfo['error_level'] . "\r\n";
        $writeString .= 'error_string:' . $this->_errorInfo['error_string'] . "\r\n";
        $writeString .= 'error_line:' . $this->_errorInfo['error_line'] . "\r\n";
        $writeString .= 'error_file:' . $this->_errorInfo['error_file'] . "\r\n";
        $writeString .= 'From:' . (isset($_SERVER['REQUEST_URI']) ? '(REQUEST_URI)' . $_SERVER['REQUEST_URI'] : '(SCRIPT_NAME)' . $_SERVER['SCRIPT_NAME']) . "\r\n";
        $writeString .= 'IP:' . utils_http::ip() . "\r\n";
        utils_file::writeLog(date('m-d').'.log', $writeString, $this->_getLogDir());
    }

    public function dealError() {
        if (Application::$mode == Application::MODE_WEBAPPLICATION) {
            $errorController = Application::$config->get('error_controller');
            $errorAction = Application::$config->get('error_action');
            $module = Application::$config->get('default_module');
            ApplicationRouterDistribution::adapter($errorAction, $errorController, $module, array('errorInfo' => $this->_errorInfo));
        } elseif (defined('DEBUG') && DEBUG) {
            echo '<pre>';
            print_r($this->_errorInfo);
            echo '</pre>';
            exit;
        } else {
            echo '<pre>';
            print_r($this->_errorInfo);
            echo '</pre>';
            exit;
        }
    }
    
    
    private function _getLogDir(){
        return 'debug/'.date('Y');
    }

    /**
     *
     * @var Exception
     */
    private $_exception = null;

    /**
     * 处理异常
     * @param Exception $exception
     */
    public static function catchException(Exception $exception) {
        $handler = new self();
        $handler->_setException($exception);
        $handler->_writeExceptionLog();
        $handler->_dealException();
    }

    public function _setException(Exception $exception) {
        $this->_exception = $exception;
    }

    private function _writeExceptionLog() {
        $writeString  = 'TYPE:exception' . "\r\n";
        $writeString .= 'code:' . $this->_exception->getCode() . "\r\n";
        $writeString .= 'message:' . $this->_exception->getMessage() . "\r\n";
        $writeString .= 'previous:' . json_encode($this->_exception->getPrevious()) . "\r\n";
        $writeString .= 'From:' . (isset($_SERVER['REQUEST_URI']) ? '(REQUEST_URI)' . $_SERVER['REQUEST_URI'] : '(SCRIPT_NAME)' . $_SERVER['SCRIPT_NAME']) . "\r\n";
        $writeString .= 'IP:' . utils_http::ip() . "\r\n";
        utils_file::writeLog(date('m-d').'.log', $writeString, $this->_getLogDir());
    }

    private function _dealException() {
        if (Application::$mode == Application::MODE_WEBAPPLICATION) {
            $errorController = Application::$config->get('error_controller');
            $errorAction = Application::$config->get('error_action');
            $module = Application::$config->get('default_module');
            if ($this->_exception->getCode() == 404) {
                if (($this->_exception->getMessage() != $module.'|'.$errorController . "|" . $errorAction)) {
                    ApplicationRouterDistribution::adapter($errorAction, $errorController, $module, array('exceptionInfo' => $this->_exception));
                } else {
                    die('page not found from error page');
                }
            }
        } elseif (defined('DEBUG') && DEBUG) {
            $nextLine = "<br>\r\n";
            echo 'exception >>>> ', $nextLine;
            echo 'code:', $this->_exception->getCode(), $nextLine;
            echo 'message:', $this->_exception->getMessage(), $nextLine;
            echo 'previous:';
            var_dump($this->_exception->getPrevious());
            exit;
        } else {//什么条件都没匹配，目前是命令行
            $nextLine = "<br>\r\n";
            echo 'exception >>>> ', $nextLine;
            echo 'code:', $this->_exception->getCode(), $nextLine;
            echo 'message:', $this->_exception->getMessage(), $nextLine;
            echo 'previous:';
            var_dump($this->_exception->getPrevious());
            exit;
        }
    }
    
    public static function catchFatalError(){
        if ($e = error_get_last()) {
            switch($e['type']){
              case E_ERROR:
              case E_PARSE:
              case E_CORE_ERROR:
              case E_COMPILE_ERROR:
              case E_USER_ERROR:  
//                ob_end_clean();
                $writeString  = 'TYPE:fatalError' . "\r\n";
                $writeString .= 'message:' . $e['message'] . "\r\n";
                $writeString .= 'file:' . $e['file'] . "\r\n";
                $writeString .= 'line:' . $e['line'] . "\r\n";
                utils_file::writeLog(date('m-d').'.log', $writeString, 'debug/'.date('Y'));
                if (Application::$mode == Application::MODE_WEBAPPLICATION) {
                    $errorController = Application::$config->get('error_controller');
                    $errorAction = Application::$config->get('error_action');
                    $module = Application::$config->get('default_module');
                    ApplicationRouterDistribution::adapter($errorAction, $errorController, $module, array('fatalError' => $e));
                    exit;
                } elseif (defined('DEBUG') && DEBUG) {
                    $nextLine = "<br>\r\n";
                    echo 'fatalError >>>> ', $nextLine;
                    print_r($e);
                    exit;
                } else {//什么条件都没匹配，目前是命令行
                    $nextLine = "<br>\r\n";
                    echo 'fatalError >>>> ', $nextLine;
                    print_r($e);
                    exit;
                }
                break;
            }
        }
    }
    
    

}