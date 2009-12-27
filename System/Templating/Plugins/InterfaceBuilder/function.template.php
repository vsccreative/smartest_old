<?php

function smarty_function_template($params, &$smarty){
	
	if(isset($params["name"]) && strlen($params["name"])){
	    
	    if(file_exists(SM_ROOT_DIR.'System/Presentation/InterfaceBuilder/'.$params["name"])){
	        $smarty->_smarty_include(array('smarty_include_tpl_file'=>SM_ROOT_DIR.'System/Presentation/InterfaceBuilder/'.$params["name"], 'smarty_include_vars'=>array()));
	    }else{
	        // error - file doesn't exist
	    }
	    
	}else{
	    // error - name not specified
	}
	
}