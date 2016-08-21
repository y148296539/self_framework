<?php
/**
 * ftp工具类
 *
 * @author cao_zl
 */
class utils_ftp {
 
    private $_host      = '';
    private $_username  = '';
    private $_password  = '';
    private $_port      = 21;
    private $_passive   = true;
    private $_conn_id   = false;
    
    /**
     * FTP可操作的根目录
     * @var string
     */
    private $_baseDir   = '';
    
    /**
     * ftp连接入口
     * 
     * @staticvar array $_ftpList 
     * @param string $configKey ftp配置文件中的键名，配置键名，若不给定，则使用传入的参数进行连接
     * @param array $selfConfig 配置数组 : $config = array('hostname'=>'','username'=>'','password'=>'','port'=>''...);
     * @return \utils_ftp
     */
    public static function getInstance($configKey , $selfConfig=array()){
        static $_ftpList = array();
        $linkKey = $configKey ? $configKey : md5($selfConfig);
        if(!isset($_ftpList[$linkKey])){
            $_ftpList[$linkKey] = new self($configKey , $selfConfig=array());
            $_ftpList[$linkKey]->_connect();
        }
        return $_ftpList[$linkKey];
    }
    
    /**
     * @param string $configKey 配置键名，若不给定，则使用传入的参数进行连接
     * @param array $selfConfig 配置数组 : $config = array('hostname'=>'','username'=>'','password'=>'','port'=>''...);
     */
    private function __construct($configKey , $selfConfig=array()) {
        $configs = utils_config::getFile('ftp')->get($configKey);
        $configSet = $configs ? $configs : array();
        $this->_host        = isset($configSet['host']) ? $configSet['host'] : $selfConfig['host'];
        $this->_username    = isset($configSet['username']) ? $configSet['username'] : $selfConfig['username'];
        $this->_password    = isset($configSet['password']) ? $configSet['password'] : $selfConfig['password'];
        $this->_port        = isset($configSet['port']) ? $configSet['port'] : $selfConfig['port'];
    }
    
    
    /**
     * 建立FTP连接
     *
     * @return  boolean
     */
    private function _connect() {
        if(false === ($this->_conn_id = @ftp_connect($this->_host,$this->_port))) {
            $this->_error("ftp_unable_to_connect");
        }
         
        if(false === ftp_login($this->_conn_id, $this->_username, $this->_password)) {
            $this->_error("ftp_unable_to_login");
        }
         
        if(true === $this->_passive) {
            ftp_pasv($this->_conn_id, true);
        }
         
        return true;
    }
 
     
    /**
     * 目录改变
     *
     * @access  public
     * @param   string  目录标识(ftp)
     * @param   boolean 
     * @return  boolean
     */
    public function cdDir($path = '') {
        if($path == '' OR ! $this->_isconn()) {
            return false;
        }
         
        $result = ftp_chdir($this->_conn_id, $path);
         
        if($result === false) {
//            $this->_error("ftp_unable_to_chgdir:dir[".$path."]");
            return false;
        }
         
        return true;
    }
     
    /**
     * 目录生成
     *
     * @access  public
     * @param   string  目录标识(ftp)
     * @param   int     文件权限列表  
     * @return  boolean
     */
    public function mkdir($path = '', $permissions = NULL) {
        if($path == '' OR ! $this->_isconn()) {
            return false;
        }
         
        $result = ftp_mkdir($this->_conn_id, $path);
        
        if($result === false) {
            $this->_error("ftp_unable_to_mkdir:dir[".$path."]");
            return false;
        }
         
        if( ! is_null($permissions)) {
            $this->chmod($path,(int)$permissions);
        }
         
        return true;
    }
     
    /**
     * 上传
     *
     * @access  public
     * @param   string  本地目录标识
     * @param   string  远程目录标识(ftp)
     * @param   string  上传模式 auto || ascii
     * @param   int     上传后的文件权限列表  
     * @return  boolean
     */
    public function upload($localpath, $remotepath, $mode = 'auto', $permissions = NULL) {
        if( ! $this->_isconn()) {
            return false;
        }
         
        if( ! file_exists($localpath)) {
            $this->_error("ftp_no_source_file:".$localpath);
            return false;
        }
         
        if($mode == 'auto') {
            $ext = $this->_getext($localpath);
            $mode = $this->_settype($ext);
        }
         
        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;
         
        $result = ftp_put($this->_conn_id, $remotepath, $localpath, $mode);
         
        if($result === false) {
            $this->_error("ftp_unable_to_upload:localpath[".$localpath."]/remotepath[".$remotepath."]");
            return false;
        }
         
        if( ! is_null($permissions)) {
            $this->chmod($remotepath,(int)$permissions);
        }
         
        return true;
    }
    
    /**
     * 生成文件的保存路径
     * @param string $path
     */
    public function preparePath($path){
        $pathCut = explode('/' , $path);
        $path_info = $this->_baseDir;
        foreach($pathCut as $part_path){
            if($part_path){
                if(!in_array($path_info.'/'.$part_path, $this->filelist($path_info ? $path_info : '/'))){
                    $path_info .= '/'.$part_path;
                    $this->mkdir($path_info);
                }else{
                    $path_info .= '/'.$part_path;
                }
            }
        }
    }
     
    /**
     * 下载
     *
     * @access  public
     * @param   string  远程目录标识(ftp)
     * @param   string  本地目录标识
     * @param   string  下载模式 auto || ascii  
     * @return  boolean
     */
    public function download($remotepath, $localpath, $mode = 'auto') {
        if( ! $this->_isconn()) {
            return false;
        }
         
        if($mode == 'auto') {
            $ext = $this->_getext($remotepath);
            $mode = $this->_settype($ext);
        }
         
        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;
         
        $result = ftp_get($this->_conn_id, $localpath, $remotepath, $mode);
         
        if($result === false) {
            $this->_error("ftp_unable_to_download:localpath[".$localpath."]-remotepath[".$remotepath."]");
            return false;
        }
         
        return true;
    }
     
    /**
     * 移动
     *
     * @access  public
     * @param   string  远程目录标识(ftp)
     * @param   string  新目录标识
     * @return  boolean
     */
    public function move($oldname, $newname) {
        if( ! $this->_isconn()) {
            return false;
        }
         
        $result = ftp_rename($this->_conn_id, $oldname, $newname);
         
        if($result === false) {
            $msg = 'move failed';
            $this->_error($msg);
            return false;
        }
         
        return true;
    }
     
    /**
     * 删除文件
     *
     * @access  public
     * @param   string  文件标识(ftp)
     * @return  boolean
     */
    public function delete_file($file) {
        if( ! $this->_isconn()) {
            return false;
        }
         
        $result = ftp_delete($this->_conn_id, $file);
         
        if($result === false) {
            $this->_error("ftp_unable_to_delete_file:file[".$file."]");
            return false;
        }
         
        return true;
    }
     
    /**
     * 删除文件夹
     *
     * @access  public
     * @param   string  目录标识(ftp)
     * @return  boolean
     */
    public function delete_dir($path) {
        if( ! $this->_isconn()) {
            return false;
        }
         
        //对目录宏的'/'字符添加反斜杠'\'
        $path = preg_replace("/(.+?)\/*$/", "\\1/", $path);
     
        //获取目录文件列表
        $filelist = $this->filelist($path);
         
        if($filelist !== false AND count($filelist) > 0) {
            foreach($filelist as $item) {
                //如果我们无法删除,那么就可能是一个文件夹
                //所以我们递归调用delete_dir()
                if( ! delete_file($item)) {
                    $this->delete_dir($item);
                }
            }
        }
         
        //删除文件夹(空文件夹)
        $result = ftp_rmdir($this->_conn_id, $path);
         
        if($result === false) {
            $this->_error("ftp_unable_to_delete_dir:dir[".$path."]");
            return false;
        }
         
        return true;
    }
     
    /**
     * 修改文件权限
     *
     * @access  public
     * @param   string  目录标识(ftp)
     * @return  boolean
     */
    public function chmod($path, $perm) {
        if( ! $this->_isconn()) {
            return false;
        }
         
        $result = ftp_chmod($this->_conn_id, $perm, $path);
        if($result === false) {
            $this->_error("ftp_unable_to_chmod:path[".$path."]-chmod[".$perm."]");
            return false;
        }
        return true;
    }
     
    /**
     * 获取目录文件列表
     *
     * @access  public
     * @param   string  目录标识(ftp)
     * @return  array
     */
    public function filelist($path = '.') {
        if( ! $this->_isconn()) {
            return false;
        }
         
        return ftp_nlist($this->_conn_id, $path);
    }
     
    /**
     * 关闭FTP
     *
     * @access  public
     * @return  boolean
     */
    public function close() {
        if( ! $this->_isconn()) {
            return false;
        }
        return ftp_close($this->_conn_id);
    }
     
    /**
     * 判断con_id
     *
     * @access  private
     * @return  boolean
     */
    private function _isconn() {
        if( ! is_resource($this->_conn_id)) {
            $this->_error("ftp_no_connection");
            return false;
        }
        return true;
    }
     
    /**
     * 从文件名中获取后缀扩展
     *
     * @access  private
     * @param   string  目录标识
     * @return  string
     */
    private function _getext($filename) {
        if(false === strpos($filename, '.')) {
            return 'txt';
        }
         
        $extarr = explode('.', $filename);
        return end($extarr);
    }
     
    /**
     * 从后缀扩展定义FTP传输模式  ascii 或 binary
     *
     * @access  private
     * @param   string  后缀扩展
     * @return  string
     */
    private function _settype($ext) {
        $text_type = array (
            'txt',
            'text',
            'php',
            'phps',
            'php4',
            'js',
            'css',
            'htm',
            'html',
            'phtml',
            'shtml',
            'log',
            'xml'
        );
        return (in_array($ext, $text_type)) ? 'ascii' : 'binary';
    }
     
    /**
     * 错误日志记录
     *
     * @access  prvate
     * @return  boolean
     */
    private function _error($msg) {
        $data = "date[".date("Y-m-d H:i:s")."]\nhostname[".$this->hostname."]\nusername[".$this->username."]\npassword[".$this->password."]\nmsg[".$msg."]";
        return utils_file::writeLog(date('d').'.log', $data, 'ftp_error/'.date('Y_m'));
    }
}