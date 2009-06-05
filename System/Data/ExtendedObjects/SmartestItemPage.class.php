<?php

class SmartestItemPage extends SmartestPage{
    
    protected $_identifying_field_name = null;
    protected $_identifying_field_value = null;
    protected $_url_variables = array();
    protected $_principal_item = null;
    protected $_simple_item = null;
    protected $_dataset = null;
    
    public function getDataSet(){
        
        if(!$this->_dataset){
            
            $this->_dataset = new SmartestCmsItemSet;
            
            if($this->_dataset->hydrate($this->getDatasetId())){
                $this->_dataset->getMembers();
            }
        }
        
        return $this->_dataset;
    }
    
    public function getSimpleItem(){
        return $this->_simple_item;
    }
    
    public function setSimpleItem(SmartestItem $item){
        $this->_simple_item = $item;
    }
    
    public function getPrincipalItem(){
        return $this->_principal_item;
    }
    
    public function setPrincipalItem($item){
        $this->_principal_item = $item;
        $this->_simple_item = $item->getItem();
        $this->_identifying_field_name = 'id';
        $this->_identifying_field_value = $item->getItem()->getId();
    }
    
    public function assignPrincipalItem(){
        
        if($item = SmartestCmsItem::retrieveByPk($this->_simple_item->getId())){
            $this->_principal_item = $item;
            $this->_principal_item->setDraftMode($this->getDraftMode());
        }else{
            return false;
        }
    }
    
    public function addHit(){
        $num_hits = $this->_simple_item->getNumHits();
        $new_item = $this->_simple_item->copy();
        $new_item->setNumHits($num_hits + 1);
        $new_item->save();
        unset($new_item);
    }
    
    public function getTags(){
	    
	    return $this->_simple_item->getTags();
	    
	}
	
	public function getTagsAsArrays(){
	    
	    return $this->_simple_item->getTagsAsArrays();
	    
	}
    
    public function setIdentifyingFieldName($field_name){
        if(!isset($this->_identifying_field_name)){
            $this->_identifying_field_name = $field_name;
        }
    }
    
    public function getIdentifyingFieldName(){
        return $this->_identifying_field_name;
    }
    
    public function setIdentifyingFieldValue($field_name){
        if(!isset($this->_identifying_field_value)){
            $this->_identifying_field_value = $field_name;
        }
    }
    
    public function getIdentifyingFieldValue(){
        return $this->_identifying_field_value;
    }
    
    public function setUrlNameValuePair($name, $value){
        $this->_url_variables[$name] = $value;
    }
    
    public function isAcceptableItem(){
        
        if($this->_identifying_field_name && $this->_identifying_field_value){
            
            if(is_object($this->_simple_item)){
                
                if($this->getDatasetId() == $this->_simple_item->getItemclassId()){
                    return true;
                }else{
                    return false;
                }
                
            }else{
                
                if($this->getType() == 'ITEMCLASS' || $this->getType() == 'SM_PAGETYPE_ITEMCLASS'){
                
                    $sql = "SELECT * FROM Items WHERE item_".$this->_identifying_field_name."='".$this->_identifying_field_value."'";
            
                    // if($this->getType() == 'ITEMCLASS'){
                        $sql .= " AND item_itemclass_id='".$this->getDataSetId()."'";
                    // }
            
                    $sql .= " AND (item_shared = '1' OR item_site_id = '".$this->getSiteId()."')";
            
                    if(!$this->getDraftMode()){
                        $sql .= " AND item_public='TRUE'";
                    }
        
                    $sql .= " AND item_deleted !='1' LIMIT 1";
            
                    $result = $this->database->queryToArray($sql);
            
                    if(count($result)){
                
                        $i = new SmartestItem;
                        $i->hydrate($result[0]);
                
                        if($this->getDatasetId() == $result[0]['item_itemclass_id']){
                            $this->_simple_item = $i;
                            return true;
                        }else{
                            return false;
                        }
            
                    }else{
                        return false;
                    }
                
                }else if($this->getType() == 'SM_PAGETYPE_DATASET'){
                    
                }
            
            }
            
        }else{
            
            // name and value not set
            
        }
    }
    
    public function getTitle($force_static=false){
        if($this->_properties['force_static_title'] || $force_static){
            return $this->_properties['title'];
        }else{
            return $this->_simple_item->getName();
        }
    }
    
    public function getRelatedContentForRender(){
	    
	    // $content = array();
	    $data = new SmartestParameterHolder('Related Content');
	    
	    $du = new SmartestDataUtility;
        $models = $du->getModels();
    
        foreach($models as $m){
            $key = SmartestStringHelper::toVarName($m->getPluralName());
            
            if($m->getId() == $this->_simple_item->getModelId()){
                // $content[$key] = $this->_simple_item->getRelatedItems($this->getDraftMode());
                $data->setParameter($key, $this->_simple_item->getRelatedItems($this->getDraftMode()));
            }else{
                // $content[$key] = $this->_simple_item->getRelatedForeignItems($this->getDraftMode(), $m->getId());
                $data->setParameter($key, $this->_simple_item->getRelatedForeignItems($this->getDraftMode(), $m->getId()));
            }
        }
        
        // $content['pages'] = $this->_simple_item->getRelatedPages($this->getDraftMode());
        $data->setParameter('pages', $this->_simple_item->getRelatedPages($this->getDraftMode()));
        
        // return $content;
        return $data;
        
	}
	
	public function loadAssetClassDefinitions(){
	    
	    parent::loadAssetClassDefinitions();
	    
	    if($this->getDraftMode()){
	        $sql = "SELECT * FROM Assets, AssetClasses, AssetIdentifiers WHERE AssetIdentifiers.assetidentifier_assetclass_id=AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_page_id='".$this->_properties['id']."' AND AssetIdentifiers.assetidentifier_item_id='".$this->_simple_item->getId()."' AND AssetIdentifiers.assetidentifier_draft_asset_id=Assets.asset_id";
        }else{
            $sql = "SELECT * FROM Assets, AssetClasses, AssetIdentifiers WHERE AssetIdentifiers.assetidentifier_assetclass_id=AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_page_id='".$this->_properties['id']."' AND AssetIdentifiers.assetidentifier_item_id='".$this->_simple_item->getId()."' AND AssetIdentifiers.assetidentifier_live_asset_id=Assets.asset_id";
        }
        
        $result = $this->database->queryToArray($sql);
        
        // print_r($sql);
        
        foreach($result as $def_array){
            if($def_array['assetclass_type'] == 'SM_ASSETCLASS_CONTAINER'){
                $def = new SmartestContainerDefinition;
                $def->hydrateFromGiantArray($def_array);
                $this->_containers[$def_array['assetclass_name']] = $def;
            }else{
                $def = new SmartestPlaceholderDefinition;
                $def->hydrateFromGiantArray($def_array);
                $this->_placeholders[$def_array['assetclass_name']] = $def;
            }
        }
        
        // print_r($this->_containers['stage_right']->getTemplateFilePath());
        
        // print_r(array_keys($this->_containers));
	    
	}
	
	public function offsetGet($offset){
	    
	    switch($offset){
	        
	        case "item":
	        case "principal_item":
	        return $this->_principal_item;
	        
	        /* case "model_name":
	        return $this->_simple_item->getModel()->getName();
	        
	        case "model_plural_name":
	        return $this->_simple_item->getModel()->getPluralName(); */
	        
	        case "model":
	        return $this->_simple_item->getModel();
	        
	    }
	    
	    return parent::offsetGet($offset);
	    
	}
	
	public function getAuthors(){
	    return $this->_simple_item->getAuthors();
	}
	
	public function getAuthorsAsArrays(){
	    return $this->_simple_item->getAuthorsAsArrays();
	}
    
}