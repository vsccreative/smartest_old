<?php

/**
 * Smarty plugin
 * @package Smartest CMS Smarty Plugins
 * @subpackage page manager
 */

function smarty_function_container($params, &$smartest_engine){
    // print_r($params);
	if(isset($params['name'])){
	    // if($smartest_engine instanceof SmartestWebPageBuilder){
	        return $smartest_engine->renderContainer($params['name'], $params, $smartest_engine->getPage());
        // }else if(isset($GLOBALS['user_action_has_page']) && $GLOBALS['user_action_has_page'] == true && is_object($GLOBALS['user_action_page'])){
            
        // }
	}else{
		return null;
	}
}
