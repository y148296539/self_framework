<?php
/**
 * 域名管理
 *
 * @author cao_zl
 */
class utils_uri {
    
    public static function getDomain($domainFlag , $protocol='http'){
        $domain = utils_config::getFile('domain')->get($domainFlag);
        if($domain){
            return $protocol.'://'.$domain;
        }
        return '';
    }
    
    
    public static function createUrl($mca){
        
    }
    
}
