<?php

function smarty_function_twitter_acct_input($params, &$smartest_engine){
    
    if(isset($params['name'])){
        
        $input = new SmartestParameterHolder('Input Parameters: '.$params['name']);
        $input->setParameter('name', $params['name']);
        
        if(isset($params['id'])){
            $input->setParameter('id', $params['id']);
        }else{
            $input->setParameter('id', SmartestStringHelper::toSlug($params['name']));
        }
        
        if(isset($params['value'])){
            $input->setParameter('value', $params['value']);
        }else{
            $input->setParameter('value', null);
        }
        
        $smartest_engine->assign('_input_data', $input);
        $smartest_engine->run(SM_ROOT_DIR.'System/Presentation/InterfaceBuilder/Inputs/twitter_acct.tpl', array());
        
    }else{
        
    }
    
}