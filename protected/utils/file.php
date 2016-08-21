<?php
/**
 * 日志书写类
 *
 * @author cao_zl
 */
class utils_file {
    
    /**
     * 将内容写入文件，直接调用fopen\fwrite
     * 
     * @param string $filePath 文件名路径
     * @param string $string 写入的内容
     * @param string $mode 写入模式，与fwrite的参数相同，a/w/r等
     * 
     * @return boolean
     */
    public static function writeFile($filePath , $string , $mode='w'){
        self::preparePath(dirname($filePath));
        if (($handle = fopen($filePath , $mode)) && (fwrite($handle , $string) !== false)) {
            return true;
        }
        return false;
    }
    
    /**
     * 写文件日志
     * - 基于项目中 runtime/log 文件夹为基础目录
     * 
     * @param stirng $fileName 文件名
     * @param string $string 写入的内容，自行控制格式，换行符“\n”
     * @param string $path 文件路径
     */
    public static function writeLog($fileName , $string , $path=''){
        $basePath = APPLICATION_RUNTIME_PATH.'log'.DIRECTORY_SEPARATOR;
        $savePath = self::preparePath($basePath.$path);
        $string = "====================".date('Y-m-d H:i:s')."|".utils_http::ip()."======================\r\n".$string."\r\n\r\n\r\n";
        self::writeFile($savePath.DIRECTORY_SEPARATOR.$fileName , $string , 'a');
    }
    
    /**
     * 准备给定的路径(路径不存在则创建)
     * @param string $path
     * @return 实际有效路径
     */
    public static function preparePath($path){
        $path = strtr($path, array('\\' => '/'));
        $pathCut = explode('/', $path);
        $targetPath = '';
        if ($pathCut && is_array($pathCut)) {
            foreach ($pathCut as $part) {
                if ($part) {
                    if ($targetPath) {
                        $targetPath .= DIRECTORY_SEPARATOR . $part;
                    } else {
                        $targetPath = (strpos($part, ':') === false) ? DIRECTORY_SEPARATOR.$part : $part;
                    }
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath);
                        chmod($targetPath, 0777);
                    }
                }
            }
        }
        return $targetPath;
    }
    
}
