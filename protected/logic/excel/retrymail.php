<?php
/* 
 * 报表重发邮件相关逻辑
 */
class logic_excel_retrymail {
    
    private $_tmpFileDir = '';
    
    private $_tmpFileName = '';
    
    private $_mailInfo = array();
    
    public function __construct() {
        $this->_tmpFileDir = APPLICATION_TMP_PATH.'mailRetry'.DIRECTORY_SEPARATOR;
    }
    
    public function sendMail($subject, $body, $emails , $attachs , $ccMailList){
        $this->_mailInfo = array(
            'subject'   => $subject,
            'body'      => $body,
            'attachs'   => $attachs,
            'emails'    => $emails,
            'cc_mails'  => $ccMailList,
        );
        $this->_saveMailSendInfo();
        $result = utils_mail::sendSystemMail($subject, $body, $emails , $attachs , $ccMailList);
        if($result[0] == 200){
            $this->_clearMailSendInfo();
            return true;
        }
        return false;
    }
    
    /**
     * 保存邮件发送参数
     */
    private function _saveMailSendInfo(){
        $this->_tmpFileName = $this->_tmpFileDir.'retry_'.md5(json_encode($this->_mailInfo)).'.tmp';
        utils_file::writeFile($this->_tmpFileName, serialize($this->_mailInfo), 'w');
    }
    
    /**
     * 清空邮件发送参数
     */
    private function _clearMailSendInfo(){
        unlink($this->_tmpFileName);
    }

    
    

    public function retryFaildMail(){
        if(!is_dir($this->_tmpFileDir)){
            return false;
        }
        $retryTime = 3;//尝试重发的次数
        $dirHandle = opendir($this->_tmpFileDir);
        while (false !== ($file = readdir($dirHandle))){
            if ($file != "." && $file != "..") {
                //可能出现fatalError级别的错误，所以将发邮件的动作放到子进程中，失败可以进行重发尝试
                for($child = 1; $child <= $retryTime ; $child ++){
                    $pid= pcntl_fork();
                    if ($pid == -1) { 
                        throw new Exception('could not fork');
                    } else if($pid){
                        pcntl_wait($status);//主进程等待子进程结束，防止重复读取
                    } else {//子进程分发
                        $this->_tmpFileName = $this->_tmpFileDir . $file;
                        if(file_exists($this->_tmpFileName)){
                            $this->_mailInfo = unserialize(file_get_contents($this->_tmpFileName));
                            echo 'do retry:' , $this->_mailInfo['subject'] , "\n";
                            $result = utils_mail::sendSystemMail($this->_mailInfo['subject'], $this->_mailInfo['body'], $this->_mailInfo['emails'] , $this->_mailInfo['attachs'] , $this->_mailInfo['cc_mails']);
                            if($result[0] == 200){
                                $this->_clearMailSendInfo();
                            }
                        }
                        exit;//子进程结束
                    }
                }
            }
        } 
        closedir($dirHandle);
    }
    
    
    public function caiwuMailList(){
        
    }
    
    
}

