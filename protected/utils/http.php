<?php
/**
 * http请求相关的工具函数
 *
 * @author cao_zl
 */
class utils_http {
    
    const CONTENT_TYPE_HTML = 'text/html';
    
    const CONTENT_TYPE_JSON = 'application/json';
    
    const CONTENT_TYPE_text = 'application/text';


    public static function postRequest($url , $content , $timeout=8 , $sendCookie=false , $writeErrorLog=true , $header=array() , $responseHeader=false){
        try{
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT,$timeout); //
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.14) Gecko/20110301 Fedora/3.6.14-1.fc14 Firefox/3.6.14');
            curl_setopt($ch, CURLOPT_HEADER, $responseHeader ? true : 0);
            if($header){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            if($sendCookie){
                $cookieFile = self::_getCookieFile();
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            $responseAll = curl_exec($ch);
            if (false === $responseAll) {
                throw new Exception('curl_errno('.curl_errno($ch).'):curl_error('.curl_error($ch).')');
            }
            if($responseHeader){
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                // 根据头大小去获取头信息内容
                $headerInfo = self::paseHeader(substr($responseAll, 0, $headerSize));
                $html = substr($responseAll, $headerSize);
            }else{
                $html = $responseAll;
            }
            curl_close($ch);
        }  catch (Exception $e){
            $statusCode = $e->getCode() ? $e->getCode() : 990;
            $errorInfo = $e->getMessage();
            if($writeErrorLog){
                self::_writeRequestErrorLog('POST', $errorInfo, func_get_args());
            }
        }
        return array(isset($statusCode) ? $statusCode : 200 , isset($html) ? $html : '' , isset($errorInfo) ? $errorInfo : '' , isset($headerInfo) ? $headerInfo : '');
            
    }
    
    
    public static function getRequest($url , $timeout=8 , $sendCookie=false , $writeErrorLog=true,$header=array() , $responseHeader=false){
        try{
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
            curl_setopt($ch, CURLOPT_HEADER, $responseHeader ? true : 0);
            curl_setopt($ch, CURLOPT_TIMEOUT,$timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.14) Gecko/20110301 Fedora/3.6.14-1.fc14 Firefox/3.6.14');
            if($header){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            if($sendCookie){
                $cookieFile = self::_getCookieFile();
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            $responseAll = curl_exec($ch);
            if (false === $responseAll) {
                throw new Exception('curl_errno('.curl_errno($ch).'):curl_error('.curl_error($ch).')');
            }
            if($responseHeader){
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                // 根据头大小去获取头信息内容
                $headerInfo = self::paseHeader(substr($responseAll, 0, $headerSize));
                $html = substr($responseAll, $headerSize);
            }else{
                $html = $responseAll;
            }
            curl_close($ch);
        }  catch (Exception $e){
            $statusCode = $e->getCode() ? $e->getCode() : 990;
            $errorInfo = $e->getMessage();
            if($writeErrorLog){
                self::_writeRequestErrorLog('GET', $errorInfo, func_get_args());
            }
        }
        return array(isset($statusCode) ? $statusCode : 200 , isset($html) ? $html : '' , isset($errorInfo) ? $errorInfo : '', isset($headerInfo) ? $headerInfo : '');
    }
    
    
    
    private static function _getCookieFile(){
        return '/tmp/__cookieFile.cookie';
    }
    
    private static function _writeRequestErrorLog($method , $errorInfo , $inputParams){
        $filePath = 'http_request/'.date('Y-m');
        $fileName = date('d').'_error.log';
        $writeString  = 'error_info:'.$errorInfo."\r\nmethod:".$method."\r\nparams:".json_encode($inputParams)."\r\n";
        $writeString .= 'From:'.(isset($_SERVER['REQUEST_URI']) ? '(REQUEST_URI)'.$_SERVER['REQUEST_URI'] : '(SCRIPT_NAME)'.$_SERVER['SCRIPT_NAME'])."\r\n";
        $writeString .= 'IP:'.self::ip();
        utils_file::writeLog($fileName, $writeString , $filePath);
    }
    
    /**
     * 解析返回的头信息字符串
     * @param string $headerString
     * @return array
     */
    public static function paseHeader($headerString){
        $lineArray = explode("\r\n" , $headerString);
        $paseArr = array();
        foreach($lineArray as $line){
            if($line && ( ($pos = strpos($line , ':')) !== false)){
                $paseArr[substr($line, 0 , $pos)] = trim(substr($line, $pos + 1));
            }
        }
        return $paseArr;
    }
    
    public static function ip(){
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else if(isset($_SERVER["REMOTE_ADDR"])) {
                $realip = $_SERVER["REMOTE_ADDR"];
            } else {
                $realip = '0.0.0.0';
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }
    
    
    public static function getParam($key , $default=null){
        if(isset($_GET[$key])){
            $return = $_GET[$key];
        }elseif(isset($_POST[$key])){
            $return = $_POST[$key];
        }else{
            $return = $default;
        }
        return $return;
    }
    
    
    public static function isPostRequest(){
        if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
            return true;
        }
        return false;
    }
    
    
    
}
