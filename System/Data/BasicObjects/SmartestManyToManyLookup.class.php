<?php

class SmartestManyToManyLookup extends SmartestBaseManyToManyLookup{

    public function getEntityForeignKeyValue($entity_num){
	    if(ceil($entity_num) > 0 && ceil($entity_num) < 5){
	        $field = 'entity_'.$entity_num.'_foreignkey';
	        return $this->_properties[$field];
	    }
	}
	
	public function setEntityForeignKeyValue($entity_num, $value){
	    if(ceil($entity_num) > 0 && ceil($entity_num) < 5){
	        $field = 'entity_'.$entity_num.'_foreignkey';
	        $this->_properties[$field] = $value;
			$this->_modified_properties[$field] = $value;
	    }
	}
	
	//// URL Encoding is being used to work around a bug in PHP's serialize/unserialize. No actual URLS are necessarily in use here
	public function setContextDataField($field, $new_data){
	    
	    $field = SmartestStringHelper::toVarName($field);
	    $data = $this->getContextData();
	    $data[$field] = rawurlencode(utf8_decode($new_data));
	    
	    $this->setContextData($data);
	    
	}
	
	public function getContextDataField($field){
	    
	    $data = $this->getContextData();
	    
	    $field = SmartestStringHelper::toVarName($field);
	    
	    if(isset($data[$field])){
	        return utf8_encode(stripslashes(rawurldecode($data[$field])));
	    }else{
	        return null;
	    }
	}
	
	public function getContextData(){
	    
	    if($data = unserialize($this->_getContextData())){
	        
	        if(is_array($data)){
	            return $data;
            }else{
                return array($data);
            }
	    }else{
	        return array();
	    }
	}
	
	public function setContextData($data){
	    
	    if(!is_array($data)){
	        $data = array($data);
	    }
	    
	    $this->_setContextData(serialize($data));
	    
	}
	
	public function _getContextData(){
	    return $this->_properties['context_data'];
	}
	
	protected function _setContextData($serialized_data){
	    $this->_properties['context_data'] = $serialized_data;
		$this->_modified_properties['context_data'] = $serialized_data;
	}
	
	public function publish(){
	    if(isset($this->_properties['status_flag'])){
	        if($this->getStatusFlag() == 'SM_MTMLOOKUPSTATUS_OLD'){
	            // delete
	            $this->delete();
	        }else if($this->getStatusFlag() == 'SM_MTMLOOKUPSTATUS_DRAFT'){
	            $this->setStatusFlag('SM_MTMLOOKUPSTATUS_LIVE');
	            $this->save();
	        }
	        
	    }
	}
   
}