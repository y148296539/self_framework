<?php
/**
 * 邮件发送类
 *
 * @author cao_zl
 */
class utils_mail {
    
    /**
     * 
     * @param string $title 邮件标题
     * @param string $body 邮件主体 - 支持HTML标签
     * @param array $mailList 收件人列表，支持多人，格式：array(array('user1@mail.com' , '用户1') , array('user2@mail.com'  '用户2')) 
     * @param array $attachs 附件列表，支持多附件，格式：array(array('path1' , '附件显示名1') , array('path2' , '附件显示名2'))
     * @param array $ccMailList 抄送人列表，支持多人，格式如 收件人列表 
     * @param int $maxRetryNum 最大重试次数 - 支持1至10次重试
     * 
     * @return array statusCode , message , errorInfo
     * @throws Exception
     */
    public static function sendSystemMail($subject , $body , $mailList , $attachs=array() , $ccMailList=array() , $maxRetryNum=3){
        try{
            $mailClass = new self();
            $mailClass->setModeInfo();
            $mailClass->setSenderInfo();
            $mailClass->setRecevierInfo($subject , $body , $mailList , $attachs , $ccMailList);
            $maxRetryNum = ($maxRetryNum > 0 && $maxRetryNum < 11) ? intval($maxRetryNum) : 1;
            $tryInfo = '';
            $t = 1;
            while(true){
                if($mailClass->sendRequest()){
                    $message = 'success,'.$t;
                    break;
                }else{
                    $tryInfo .= "\r\n -- time : ".$t. "\r\n -- info : ".$mailClass->getErrorInfo();
                }
                if($t++ >= $maxRetryNum){
                    throw new Exception($tryInfo);
                }
                sleep(1);
            }
        }  catch (Exception $e){
            $statusCode = $e->getCode() ? $e->getCode() : 999;
            $errorInfo = $e->getMessage();
            $saveString = "error:".$errorInfo."\r\nparams:".json_encode(func_get_args());
            utils_file::writeLog(date('d').'.log', $saveString, 'mailError/'.date('Y-m'));
        }
        return array(isset($statusCode) ? $statusCode : 200 , isset($message) ? $message : null , isset($errorInfo) ? $errorInfo : null);
    }
    
    /**
     * @var PHPMailer
     */
    private $_mail = null;
    
    private $_errorInfo = '';
    
    private function __construct() {
        if(!class_exists('PHPMailer' , false)){
            include APPLICATION_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR .'plugins'.DIRECTORY_SEPARATOR.'phpMailer'.DIRECTORY_SEPARATOR.'PHPMailerAutoload.php';
        }
        $this->_mail =  new PHPMailer();
    }
    
    
    public function __destruct() {
        $this->_mail = null;
    }
    
    public function setModeInfo(){
        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
        $this->_mail->isSMTP();                                      // Set mailer to use SMTP
        $this->_mail->isHTML(true);                                  // Set email format to HTML
        $this->_mail->CharSet = 'utf-8';
    }
    
    public function setSenderInfo(){
        $this->_mail->Host          = 'smtp.exmail.qq.com';  // Specify main and backup SMTP servers
        $this->_mail->SMTPAuth      = true;                               // Enable SMTP authentication
        $this->_mail->Username      = 'service@dtd365.com';                 // SMTP username
        $this->_mail->Password      = 'dtd365!@#2015';                           // SMTP password
        $this->_mail->Port          = 25;                                    // TCP port to connect to
        $this->_mail->From          = 'service@dtd365.com';
        $this->_mail->FromName      = '当天金融在线';
//        $this->_mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    }
    
    
    public function setRecevierInfo($subject , $body , $mailList , $attachs=array() , $ccMailList=array()){
        //标题
        $this->_mail->Subject = $subject;
        //内容主体
        $this->_mail->Body    = $body;
        $this->_mail->AltBody = $body;
        //收件人列表
        foreach($mailList as $address){
            if(is_array($address)){
                $mailAddress = (isset($address[0]) && $address[0]) ? $address[0] : '';
                $userFlag = (isset($address[1]) && $address[1]) ? $address[1] : '';
                if($mailAddress){
                    $this->_mail->addAddress($mailAddress, $userFlag);
                }
            }elseif(is_string($address) && $address){
                $this->_mail->addAddress($address);
            }
        }
        //添加附件列表
        if($attachs && is_array($attachs)){
            foreach($attachs as $attachInfo){
                if(is_array($attachInfo)){
                    $attachPath = (isset($attachInfo[0]) && $attachInfo[0]) ? $attachInfo[0] : '';
                    $attachShowName = (isset($attachInfo[1]) && $attachInfo[1]) ? $attachInfo[1] : '';
                    if(file_exists($attachPath)){
                        $this->_mail->addAttachment($attachPath , $attachShowName);
                    }
                }elseif(is_string($attachInfo) && file_exists($attachInfo)){
                    $this->_mail->addAttachment($attachInfo);
                }
            }
        }
        //抄送人列表
        if($ccMailList && is_array($ccMailList)){
            foreach($ccMailList as $address){
                if(is_array($address)){
                    $mailAddress = (isset($address[0]) && $address[0]) ? $address[0] : '';
                    $userFlag = (isset($address[1]) && $address[1]) ? $address[1] : '';
                    if($mailAddress){
                        $this->_mail->addCC($mailAddress, $userFlag);
                    }
                }elseif(is_string($address) && $address){
                    $this->_mail->addCC($address);
                }
            }
        }
    }
    
    
    public function sendRequest(){
        if(!$this->_mail->send()) {
            $this->_errorInfo = $this->_mail->ErrorInfo;
            return false;
        } else {
            $this->_errorInfo = '';
            return true;
        }
    }
    
    public function getErrorInfo(){
        return $this->_errorInfo;
    }
    
}
