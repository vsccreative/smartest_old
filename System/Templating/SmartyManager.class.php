<?php
/**
  * PHP Controller
  *
  * PHP versions 5
  *
  * LICENSE: This source file is subject to version 3.0 of the PHP license
  * that is available through the world-wide-web at the following URI:
  * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
  * the PHP License and are unable to obtain it through the web, please
  * send a note to license@php.net so we can mail you a copy immediately.
  *
  *
  * @category   Presentation
  * @package    SmartyManager
  * @author     Marcus Gilroy-Ware <marcus@vsccreative.com>
  * @copyright  2011 VSC Creative Ltd.
  * @version    0.4.3
  */
  
if(!class_exists('SmartestEngine')){
	require_once(SM_ROOT_DIR.'System/Templating/SmartestEngine.class.php');
}


class SmartyManager{

	private $options = array();
	private $context = null;
	private $smartyObj;
	
	public function __construct($context=null){
		if($options = SmartestYamlHelper::fastLoad(SM_ROOT_DIR."System/Core/Info/system.yml")){
		    $this->options = $options['system']['smarty_config'];
			if($context){
			    $this->context = $context;
			}
		}else{
			throw new SmartestException("Config file ".SM_ROOT_DIR.'System/Core/Info/system.yml'." could not be parsed.", 104);
		}
	}

	public function &initialize($pid='_main') {
		
		//detect if the proper directories exist
		if(!is_dir($this->options['default_templates_dir']) ){
			throw new SmartestException("SmartyManager::smartyInitialize Error: create templates directory", 104);
			// die("SmartyManager::smartyInitialize Error: create templates directory");
		}
		
		if(!is_dir( $this->options['templates_cache']) ){
			throw new SmartestException("SmartyManager::smartyInitialize Error: create template_c directory", 104);
			// die("SmartyManager::smartyInitialize Error: create template_c directory");
		}
		
		if(!is_dir( $this->options['cache']) ){
			throw new SmartestException("SmartyManager::smartyInitialize Error: create cache directory", 104);
			// die("SmartyManager::smartyInitialize Error: create cache directory");
		}
		
		if(!is_dir( $this->options['config']) ){
			throw new SmartestException("SmartyManager::smartyInitialize Error: create config directory", 104);
			// die("SmartyManager::smartyInitialize Error: create config directory");
		}
		
		if(!is_writeable($this->options['templates_cache']) ){
      
			if(!@chmod($this->options['templates_cache'], "ug+w")){
				throw new SmartestException("SmartyManager::smartyInitialize Error: directory ".$this->options['templates_cache']." needs to writable", 104);
				// die("SmartyManager::smartyInitialize Error: directory ".$this->options['templates_cache']." needs to writable");
			}
		}
    
        if($this->context == 'InterfaceBuilder'){
		    $smartyObj = new SmartestInterfaceBuilder('_main');
		}else if($this->context == 'WebPageBuilder'){
		    $smartyObj = new SmartestWebPageBuilder('_main');
		}else if($this->context == 'BasicRenderer'){
    	    $smartyObj = new SmartestBasicRenderer($pid);
    	}else if($this->context == 'SingleItemTemplateRenderer'){
        	$smartyObj = new SmartestSingleItemTemplateRenderer($pid);
        }else if($this->context == 'UserAppBuilder'){
    	    $smartyObj = new SmartestBasicRenderer('_main');
    	}else{
		    $smartyObj = new SmartestEngine('_main');
		}
		
		$smartyObj->template_dir = SM_ROOT_DIR.$this->options['default_templates_dir'];
		$smartyObj->compile_dir = SM_ROOT_DIR.$this->options['templates_cache'];
		$smartyObj->cache_dir = SM_ROOT_DIR.$this->options['cache'];
		$smartyObj->config_dir = SM_ROOT_DIR.$this->options['config'];
    
		return $smartyObj;
		
	}
}
