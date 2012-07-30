<?php

/**
 * undocumented class
 *
 * @package Smartest
 * @author Marcus Gilroy-Ware
 **/
 
class SmartestFile implements ArrayAccess, SmartestStorableValue{
    
    protected $_original_file_path;
    protected $_current_file_path;
    protected $_request_data;
    
    public function __construct(){
        $this->_request_data = SmartestPersistentObject::get('request_data');
    }
    
    public function loadFile($file_path){
        
        if(is_file($file_path)){
            
            $this->_original_file_path = realpath($file_path);
            $this->_current_file_path = realpath($file_path);
            return true;
            
        }else{
            SmartestLog::getInstance('system')->log('SmartestFile->loadFile(): '.$file_path.' does not exist or is not a valid file.');
            return false;
        }
    }
    
    public function delete(){
        return unlink($this->_current_file_path);
    }
    
    // The next three methods are for the SmartestStorableValue interface
    public function getStorableFormat(){
        return $this->_current_file_path;
    }
    
    public function hydrateFromStorableFormat($v){
        return $this->loadFile($v);
    }
    
    public function hydrateFromFormData($v){
        return $this->loadFile($v);
    }
    
    public function exists(){
        return file_exists($this->_current_file_path);
    }
    
    public function getPath(){
        return $this->_current_file_path;
    }
    
    public function getSmartestPath(){
        return substr($this->getPath(), strlen(SM_ROOT_DIR));
    }
    
    public function getShortHash(){
        return substr(md5(basename($this->getPath())), 0, 8);
    }
    
    public function getFileName(){
        return basename($this->getPath());
    }
    
    public function getOriginalPath(){
        return $this->_original_file_path;
    }
    
    public function isImage(){
        return ($this instanceof SmartestImage);
    }
    
    public function isPublic(){
        
        $path_start = SM_ROOT_DIR.'Public';
        $path_start_length = strlen($path_start);
        
        return substr($this->_current_file_path, 0, $path_start_length) == $path_start;
        
    }
    
    public function getPublicPath(){
        
        if($this->isPublic()){
            
            $path_start = SM_ROOT_DIR.'Public/';
            $path_start_length = strlen($path_start);
            return substr($this->_current_file_path, $path_start_length);
            
        }
        
    }
    
    public function getWebUrl(){
        if($this->isPublic() || $this->isImage()){
            return $this->_request_data->g('domain').$this->getPublicPath();
        }
    }
    
    public function rename($new_name, $force=false){
        // keeps the file in the same directory, just changes the name
    }
    
    public function moveTo($new_location, $force=false){
        // if $new_location is a directory, keep the file name as it is and just move it
        // otherwise, attempt to move it and change its name to whatever the new name was
    }
    
    public function send(){
        // send the file to the client by instantiating a new SmartestFileDownload object
    }
    
    public function getMimeType(){
        // use xml file to get mime type from dot suffix
    }
    
    public function getContent($binary_safe=false){
        return SmartestFileSystemHelper::load($this->_current_file_path, $binary_safe);
    }
    
    public function setContent($content, $binary_safe=false){
        return SmartestFileSystemHelper::save($this->_current_file_path, $template_content, $binary_safe);
    }
    
    public function getSize($formatted=true){
        if($formatted){
            return SmartestFileSystemHelper::getFileSizeFormatted($this->_current_file_path);
        }else{
            return SmartestFileSystemHelper::getFileSize($this->_current_file_path);
        }
    }
    
    public function offsetGet($offset){
        
        switch($offset){
	        
	        case "url":
	        return $this->getWebUrl();
	        break;
	        
	        case "file_path":
	        return $this->getPath();
	        break;
	        
	        case "smartest_path":
	        return $this->getSmartestPath();
	        break;
	        
	        case "public_file_path":
	        return $this->getPublicPath();
	        break;
	        
	        case "size":
	        return $this->getSize();
	        break;
	        
	        case "raw_size":
	        return $this->getSize(false);
	        break;
	        
	    }
        
    }
    
    public function offsetExists($offset){}
    public function offsetSet($offset, $value){}
    public function offsetUnset($offset){}

}