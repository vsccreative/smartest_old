<?php

/**
* @package Smartest
* @subpackage CMS Object Model
* @author Marcus Gilroy-Ware <marcus@mjgw.com>
* SmartestCmsItem is the underlying class that is extended to create the objects that are created and edited in the CMS
* It is also used
*/

class SmartestCmsItem implements ArrayAccess, SmartestGenericListedObject, SmartestStorableValue, SmartestDualModedObject{
	
	/** 
	* Description
	* @access protected
	* @var mixed
	*/
	protected $_item;
	
	/** 
	* Description
	* @access protected
	* @var SmartestModel
	*/
	protected $_model = null;
	
	/** 
	* A list of the actual properties of the loaded object. The numeric keys are the primary keys of the properties in the Properties table.
	* @access protected
	* @var array
	*/
	protected $_properties = array();
	
	/** 
	* A list of all those properties that have been modified which is generated and updated automatically so that when the object is saved, only the properties in this list will be updated.
	* @access protected
	* @var array
	*/
	protected $_modified_properties = array();
	
	/** 
	* A list of any properties that are referred to by the user's code, but aren't linked to actual properties in the structure of the model.
	* @access protected
	* @var array
	*/
	protected $_overloaded_properties = array();
	
	/** 
	* A mapping of the items' property names to the ids of the properties.
	* @access protected
	* @var array
	*/
	protected $_properties_lookup = array();
	
	/** 
	* A mapping of the varnames of the properties to the ids of the properties, for speed.
	* @access protected
	* @var array
	*/
	protected $_varnames_lookup = array();
	
	/** 
	* Description
	* @access protected
	* @var array
	*/
	protected $_property_values_lookup = array();
	
	/** 
	* Description
	* @access protected
	* @var boolean
	*/
	
	protected $_came_from_database = false;
	protected $_model_built = false;
	protected $_lookups_built = false;
	protected $_save_errors = array();
	protected $_draft_mode = false;
	protected $_request;
	protected $_disabled_template_properties = array();
	protected $_item_chain_data;
	
	/** 
	* Description
	* @access protected
	* @var SmartestMysql
	*/
	protected $database;
	
	const NAME = '_SMARTEST_ITEM_NAME';
	const ID = '_SMARTEST_ITEM_ID';
	const WEB_ID = '_SMARTEST_ITEM_WEBID';
	const WEBID = '_SMARTEST_ITEM_WEBID';
	const LONG_ID = '_SMARTEST_ITEM_WEBID';
	const NUM_COMMENTS = '_SMARTEST_ITEM_NUM_COMMENTS';
	const NUM_HITS = '_SMARTEST_ITEM_NUM_HITS';
	const AVERAGE_RATING = '_SMARTEST_ITEM_AVG_RATING';
	
	const NOT_CHANGED = 100;
	const AWAITING_APPROVAL = 101;
	const CHANGES_APPROVED = 102;
	
	public function __construct(){
		
		$this->database = SmartestPersistentObject::get('db:main');
		$this->_item = new SmartestItem;
		
		$this->generateModel();
		// $this->generatePropertiesLookup();
		$this->_request = SmartestPersistentObject::get('controller')->getCurrentRequest();
		
		/* if(get_class($this) == 'SmartestCmsItem'){
		    throw new SmartestException('here');
		} */
		
	}
	
	private function generateModel(){
		
		if(isset($this->_model_id) && !$this->_model_built){
		
		    if(SmartestCache::hasData('model_properties_'.$this->_model_id, true)){
		        $result = SmartestCache::load('model_properties_'.$this->_model_id, true);
		    }else{
			    // gotta get that from the database too
			    $sql = "SELECT * FROM ItemProperties WHERE itemproperty_itemclass_id='".$this->_model_id."'";
			    $result = $this->database->queryToArray($sql);
			    SmartestCache::save('model_properties_'.$this->_model_id, $result, -1, true);
		    } 
		
		    $properties = array();
			
		    foreach($result as $key => $raw_property){
		        
		        $property = new SmartestItemPropertyValueHolder;
		        $property->hydrate($raw_property);
		        
		        if($property->getDatatype() == 'SM_DATATYPE_CMS_ITEM'){
		            // print_r($property->getOriginalDbRecord());
		        }
		        
		        $this->_properties[$raw_property['itemproperty_id']] = $property;
		        
		    }
		    
		    $this->_model_built = true;
		
	    }
		
	}
	
	public function __call($name, $args){
		  throw new SmartestException("Call to undefined function: ".get_class($this).'->'.$name.'()');
	}
	
	public function getPropertyVarNames(){
	    return array_keys($this->_varnames_lookup);
	}
	
	public function setDraftMode($mode){
	    $this->_draft_mode = (bool) $mode;
	}
	
	public function getDraftMode(){
	    return $this->_draft_mode;
	}
	
	// The next three methods are for the SmartestStorableValue interface
    public function getStorableFormat(){
        return $this->_item->getId();
    }
    
    public function hydrateFromStorableFormat($v){
        if(is_numeric($v)){
            return $this->find($v);
        }
    }
    
    public function hydrateFromFormData($v){
        $r = $this->find((int) $v);
        return $r;
    }
    
    // Convenience function to provide controller instance internally
    protected function getController(){
        return SmartestPersistentObject::get('controller');
    }
    
    public function disableTemplateProperty($property_id){
        $this->_disabled_template_properties[$property_id] = true;
    }
	
	public function offsetExists($offset){
	    return ($this->_item->offsetExists($offset) || isset($this->_varnames_lookup[$offset]) || in_array($offset, array('_workflow_status', '_model', '_properties')));
	}
	
	public function offsetGet($offset){
	    
	    if(defined('SM_CMS_PAGE_CONSTRUCTION_IN_PROGRESS') && constant('SM_CMS_PAGE_CONSTRUCTION_IN_PROGRESS') && defined('SM_CMS_PAGE_ID')){
		    $dah = new SmartestDataAppearanceHelper;
            $dah->setItemAppearsOnPage($this->getId(), constant('SM_CMS_PAGE_ID'));
		}
	    
	    if($offset == 'name'){
	        return new SmartestString($this->getName());
	    }
	    
	    if($this->_item->offsetExists($offset)){
	        
	        return $this->_item->offsetGet($offset);
	        
	    }else if(isset($this->_varnames_lookup[$offset])){
	        
	        if(isset($this->_disabled_template_properties[$this->_varnames_lookup[$offset]])){
	            return "Recursion disallowed";
	        }else{
	            $v = $this->getPropertyValueByNumericKey($this->_varnames_lookup[$offset], $this->getDraftMode(), true);
	            if(is_null($v)){
	                return new SmartestString('');
	            }else{
	                return $v;
                }
            }
	        
	    }else{
	        
	        switch($offset){
	            
	            case "_workflow_status":
	            
	            switch($this->getWorkflowStatus()){
            	    
            	    case self::NOT_CHANGED:
            	    return 'Not changed';
            	    break;
            	    
            	    case self::CHANGES_APPROVED:
            	    return 'Approved and ready for publishing';
            	    break;
            	    
            	    default:
            	    return 'Awaiting approval';
            	    break;
            	}
            	
	            break;
	            
	            case 'label':
	            return $this->getItem()->getName();
	            
	            case 'related':
	            // Sergiy - Moved your if statement to here: (Marcus)
	            return $this->getItem()->getRelatedContentForRender($this->getDraftMode());
	            
	            case 'comments':
	            return $this->getItem()->getPublicComments();
	            
	            case 'num_comments':
	            return $this->getItem()->getNumApprovedPublicComments();
	            
	            case '_php_class':
	            return get_class($this);
	            
	            case 'url':
	            case 'permalink':
	            return $this->getUrl();
	            
	            case 'tags_cs_string':
	            return $this->_item->getTagsAsCommaSeparatedString();
	            
	            case 'absolute_uri':
	            case 'absolute_url':
	            return $this->getAbsoluteUri();
	            
	            case 'description':
	            case '_description':
	            return $this->getDescriptionFieldContents();
	            
	            case '_auto_date':
	            return $this->getDate();
	            
	            case '_is_published':
	            return new SmartestBoolean($this->isPublished());
	            
	            case '_byline':
	            return SmartestStringHelper::toCommaSeparatedList($this->getItem()->getAuthors());
	            
	            case '_model':
	            return $this->getModel();
	            
	            case '_properties':
	            return $this->getProperties();
	            
	            case '_editable_properties':
	            return $this->getProperties();
	            
	            case '_draft_mode':
	            return new SmartestBoolean($this->_draft_mode);
	            
	            case '_meta_page':
	            if($p = $this->getMetaPage()){
	                return $p;
                }else{
                    break;
                }
                
                case '_thumbnail':
                return $this->getDefaultThumbnailImage();
                
                case '_site':
                return $this->getSite();
                
                case 'empty':
                return !is_numeric($this->_item->getId());
	            
	        }
	        
	        $infn = SmartestStringHelper::toVarName($this->getModel()->getItemNameFieldName());
	        if($offset == $infn){
	            return new SmartestString($this->getName());
	        }
	        
	    }
	    
	}
	
	public function offsetSet($offset, $value){
	    // read only
	}
	
	public function offsetUnset($offset){
	    // read only
	}
	
	public function getCacheFiles(){
	    return $this->getItem()->getCacheFiles();
	}
	
	private function getField($field_name, $draft=false){
	    
	    $t = $this->_properties[$this->_properties_lookup[$field_name]]->getTypeInfo();
        
        if(array_key_exists($field_name, $this->_properties_lookup)){
		    if($this->_properties[$this->_properties_lookup[$field_name]] instanceof SmartestItemPropertyValueHolder){
			    
			    // return $this->_properties[$this->_properties_lookup[$field_name]];
			    if($this->_properties[$this->_properties_lookup[$field_name]]->getData() instanceof SmartestItemPropertyValue){
			        
			        if($draft){
		                return $this->_properties[$this->_properties_lookup[$field_name]]->getData()->getDraftContent();
		            }else{
		                return $this->_properties[$this->_properties_lookup[$field_name]]->getData()->getContent();
		            }
		        }else{
		            
		            if($t['valuetype'] != 'auto'){
		            
    		            // no value found, so create one
    		            $ipv = new SmartestItemPropertyValue;
        	            $ipv->setPropertyId($this->_properties[$this->_properties_lookup[$field_name]]->getId());
        	            $ipv->setItemId($this->getItem()->getId());
        	            $ipv->setDraftContentId($this->_properties[$this->_properties_lookup[$field_name]]->getDefaultValue());
        	            // var_dump($this->_properties[$this->_properties_lookup[$field_name]]->getDefaultValue());
        	            $ipv->save();
    	            
        	            if($draft){
        	                return $ipv->getDraftContent();
        	            }else{
        	                return null;
        	            }
    	            
	                }
		        }
		    }
		}else if(array_key_exists($field_name, $this->_overloaded_properties)){
			return $this->_overloaded_properties[$field_name];
		}else{
			return null;
		}
	}
	
	private function setField($field_name, $value){
		if(array_key_exists($field_name, $this->_properties_lookup)){
			// field being set is part of the model and corresponds to a column in the db table
			
			// $this->_properties[$this->_properties_lookup[$field_name]]->setDraftContent($value);
			$this->setPropertyValueByNumericKey($this->_properties_lookup[$field_name], $value);
			
			// $this->_modified_properties[$this->_properties_lookup[$field_name]] = $value;
		}else{
			// field being set is an overloaded property, which is stored, but not retrieved from or stored in the db
			$this->_overloaded_properties[$field_name] = $value;
			
		}
		
		return true;
	}
	
	public function setModelId($id){
	    
	    $id = (int) $id;
	    
	    if($this instanceof SmartestCmsItem && !$this->_model_built){
	        
	        $this->_model_id = $id;
	        $this->_model = new SmartestModel;
	        
	        if(!$this->_model->find($this->_model_id)){
	            throw new SmartestException('The model ID '.$this->_model_id.' doesn\'t exist.');
	        }
	        
	        if(!$this->_model_built){
    	        $this->generateModel();
    	    }
	        
	    }
	    
	}
	
	public function getRequest(){
	    return $this->_request;
	}
	
	public function setSiteId($id){
	    if(is_object($this->_item)){
	        $this->_item->setSiteId($id);
        }
	}
	
	public function getSite(){
	    return $this->_item->getHomeSite();
	}
    
    public function getMetapageId(){
	    
	    return $this->_item->getMetapageId();
	    
	}
	
	public function getMetaPage(){
	    
	    return $this->_item->getMetapage();
	    
	}
	
	public function getDefaultThumbnailImage(){
	    if($propertyid = $this->getModel()->getDefaultThumbnailPropertyId()){
	        return $this->getPropertyValueByNumericKey($propertyid, true);
	    }
	}
	
	public function getItemSpaceDefinitions($draft=false){
	    
	    return $this->_item->getItemSpaceDefinitions($draft);
	    
	}
	
	public function hydrateNewFromRequest($request_data, $site_id=''){
	    
	    if(is_array($request_data)){
	            
		    $this->_item->setName(SmartestStringHelper::sanitize($request_data['_name']));
		    $this->_item->setLanguage(SmartestStringHelper::sanitize($request_data['_language']));
            
            $this->_item->setPublic('FALSE');
            
            $this->_item->setItemclassId($this->_model_id);
            $this->_item->setSlug(SmartestStringHelper::toSlug($this->_item->getName(), true), $site_id);
            $this->_item->setCreated(time());
            $this->_item->setModified(time()+2); // this is to make it show up on the approval todo list
            
            if(SmartestPersistentObject::get('user') instanceof SmartestUser){
                $this->_item->setCreatedbyUserid(SmartestPersistentObject::get('user')->getId());
            }
	        
	        /* foreach($request_data as $key => $value){
	        
	            if(isset($this->_properties[$key]) && !in_array($key, array('_name', '_is_public')) && is_object($this->_properties[$key])){
	                
	                $this->setPropertyValueByNumericKey($key, $value);
                    
	            }else{
	                // echo "property value object not found<br />";
	                // property object doesn't exist
	                // $this->_save_errors[$key] = $value;
	                // TODO: decide what to do here and implement it here
	            }
	        } */
	        
	        switch($this->getModel()->getLongIdFormat()){
	            
	            case '_STD':
	            case '':
	            $webid = SmartestStringHelper::random(32);
	            break;
	            
	            case '_UUID':
	            $webid = SmartestStringHelper::generateUUID();
	            break;
	            
	            default:
	            $webid = SmartestStringHelper::randomFromFormat($this->getModel()->getLongIdFormat());
	            
	        }
	        
	        $this->_item->setWebid($webid);
	        
	        foreach($this->getModel()->getProperties() as $p){
                if(isset($request_data[$p->getId()])){
                    $this->setPropertyValueByNumericKey($p->getId(), $request_data[$p->getId()]);
                }
            }
	        
	        if(!count($this->_save_errors)){
	            return true;
	        }else{
	            return false;
	        }
	        
	    }else{
	        
	        // error - expecting data in associative array
	        
	    }
	}
	
	public function find($id, $draft=false){
		
		if($this->_item->find($id)){
		    
		    $this->_runPostSimpleItemFind($id, $draft);
		    return true;
		
	    }else{
	        
	        return false;
	        
	    }
		
	}
	
	public function findBy($field, $id, $draft=false){
		
		if($this->_item->findBy($field, $id)){
		    
		    $this->_runPostSimpleItemFind($this->_item->getId(), $draft);
		    return true;
		
	    }else{
	        
	        return false;
	        
	    }
		
	}
	
	private function _runPostSimpleItemFind($id, $draft){
	    
	    $this->_came_from_database = true;
	    
	    if(!$this->_model_built){
	        $this->_model_id = $this->_item->getItemclassId();
	        $this->generateModel();
	    }
	    
	    if(SmartestCache::hasData('model_properties_'.$this->_model_id, true)){
		    $properties_result = SmartestCache::load('model_properties_'.$this->_model_id, true);
	    }else{
		    // gotta get that from the database too
		    $properties_sql = "SELECT * FROM ItemProperties WHERE itemproperty_itemclass_id='".$this->_model_id."' AND itemproperty_varname !='hydrate'";
		    $properties_result = $this->database->queryToArray($sql);
		    SmartestCache::save('model_properties_'.$this->_model_id, $result, -1, true);
	    }
	    
	    // loop through properties first time, just setting up empty holder items
	    foreach($properties_result as $property){
	        
	        if(!isset($this->_properties[$property['itemproperty_id']]) || !is_object($this->_properties[$property['itemproperty_id']])){
	            SmartestCache::clear('model_properties_'.$this->_model_id, true);
	            $this->_properties[$property['itemproperty_id']] = new SmartestItemPropertyValueHolder;
	            
	        }
	        
		    $this->_properties[$property['itemproperty_id']]->hydrate($property);
		    $this->_properties[$property['itemproperty_id']]->setItem($this);// Sergiy: &$this=>$this for PHP 5.4
		    // $this->_properties[$property['itemproperty_id']]->setContextualItemId($this->_item->getId());
	    }
	    
	    $values_sql = "SELECT * FROM ItemPropertyValues WHERE itempropertyvalue_item_id='$id'";
	    $result = $this->database->queryToArray($values_sql, true);
	    
	    // then loop through properties again, making sure all are given either a ipv from the last db query, or given a new one if none was found.
	    // these ifs and buts shouldn't run very often if everything is working as it should
		
		foreach($result as $propertyvalue){
		    
		    $ipv = new SmartestItemPropertyValue;
		    $ipv->hydrate($propertyvalue);
		    $ipv->setItem($this);
		    
            // if the property object does not exist, create and hydrate it
            
            if(!isset($this->_properties[$ipv->getPropertyId()]) || !is_object($this->_properties[$ipv->getPropertyId()])){
                $this->_properties[$ipv->getPropertyId()] = new SmartestItemPropertyValueHolder;
		    }
		    
		    if(!$this->_properties[$ipv->getPropertyId()]->hasData()){
		        $this->_properties[$ipv->getPropertyId()]->hydrateValueFromIpvObject($ipv);
            }
		    
		    // give the property the current item id, so that it knows which ItemPropertyValue record to retrieve in any future operations (though it isn't needed in this one)
		    // $this->_properties[$ipv->getPropertyId()]->setContextualItemId($this->_item->getId());
		    
		    // $this->_properties[$ipv->getPropertyId()]->hydrateValueFromIpvArray($propertyvalue);
		    
	    } 
	    
	    // all properties should now be represented.
	    // last jobs are:
	    //// 1. to make sure all property objects have value objects
	    //// 2. to give the value objects info about their properties, without doing more queries.
	    foreach($this->_properties as $pid=>$p){
	        // this function will automatically crate a value and save it
	        $p->getData()->hydratePropertyFromExteriorArray($p->getOriginalDbRecord());
	    }
	    
	}
	
	public function hydrate($id, $draft=false){
	    return $this->find($id, $draft);
	}
	
	public function isHydrated(){
	    // var_dump($this->getItem()->isHydrated());
	    // return $this->getItem()->isHydrated();
	    return $this->_came_from_database;
	}
	
	// Raw data from an SQL query that retrieves both Items and ItemPropertyValues can be passed to the item via this function
	public function hydrateFromRawDbRecord($record){
	    if($this->isHydrated()){
	        throw new SmartestException("Tried to hydrate an already-hydrated SmartestCmsItem object.");
	    }else{
	        
	        $item = new SmartestItem;
	        $item->hydrate(reset($record));
	        $this->_item = $item;
	        
	        if($this->_model_built){
	            foreach($this->_properties as &$p){
	                // print_r($record);
	                // $p is an itempropertyvalueholder object
	                $p->hydrateValueFromIpvArray($record[$p->getId()], $this); // Sergiy: &$this=>$this for PHP 5.4
	            }
	        }
	    }
	}
	
	public function getId(){
		return $this->getItem()->getId();
	}
	
	public function getName(){
		$n = $this->getItem()->getName();
		if($this->_draft_mode && $this->getItem()->getPublic() != 'TRUE' && $this->getRequest()->getAction() == "renderEditableDraftPage"){
		    $n = '*'.$n;
		}
		return $n;
	}
	
	public function setName($name){
		return $this->getItem()->setName($name);
	}
	
	public function setSlug($slug, $site_id=''){
	    return $this->getItem()->setSlug(SmartestStringHelper::toSlug($name), $site_id);
	}
	
	public function setIsPublic($p){
	    return $this->getItem()->setIsPublic($p);
	}
	
	public function setLanguage($lang_code){
		return $this->getItem()->setLanguage($lang_code);
	}
	
	// needed for compliance with SmartestGenericListedObject
	public function getTitle(){
	    return $this->getName();
	}
	
	public function getDate(){
	    if($this->getDraftMode()){
            return $this->getItem()->getCreated();
        }else{
            return $this->getItem()->getLastPublished();
        }
	}
	
	public function getDescription(){
	    return $this->getDescriptionFieldContents();
	}
	
	public function getSlug(){
		return $this->getItem()->getSlug();
	}
	
	public function getWebid(){
		return $this->getItem()->getWebid();
	}
	
	public function getIsPublic(){
		return ($this->getItem()->getPublic() == 'TRUE') ? 'TRUE' : 'FALSE';
	}
	
	public function isPublished(){
	  return ($this->getItem()->getPublic() == 'TRUE') ? true : false;
	}
	
	public function getItem(){
		return $this->_item;
	}
	
	public function getLinkContents(){
	    
	    if($this->getMetapageId()){
	        $page_id = $this->getMetapageId();
	    }else if($this->getModel()->getDefaultMetapageId()){
	        $page_id = $this->getModel()->getDefaultMetapageId();
	    }else{
	        return null;
	    }
	    
	    return 'metapage:id='.$page_id.':id='.$this->getId();
	    
	}
	
	public function getLinkObject(){
	    
	    // $link = SmartestCmsLinkHelper::createLink($this->getLinkContents(), array());
	    $link = SmartestCmsLinkHelper::createLinkFromCmsItem($this, array());
	    return $link;
	    
	}
	
	public function getUrl(){
	    
	    // $link = SmartestCmsLinkHelper::createLink('metapage:id='.$page_id.':id='.$this->getId(), 'Raw Link Params: '.'metapage:id='.$page_id.':id='.$this->getId());
	    $link = $this->getLinkObject();
	    
	    if($link->hasError()){
	        // echo $link->getError();
	        return '#';
	    }else{
	        return $link->getUrl(false, true);
        }
	    
	}
	
	public function getAbsoluteUri(){
	    
	    return $this->getLinkObject()->getAbsoluteUrlObject();
	    
	}
	
	public function getModel(){
	    
	    if(!$this->_model && is_object($this->_item) && $this->_item->getItemclassId()){
	        $model = new SmartestModel;
	        $model->find($this->_item->getItemclassId());
	        $this->_model = $model;
	    }else if(!$this->_model && $this->_model_id){
	        $model = new SmartestModel;
	        if($model->find($this->_model_id)){
	            $this->_model = $model;
	        }
	    }
	    
	    return $this->_model;
	    
	}
	
	public function getModelId(){
	    return $this->_item->getItemclassId();
	}
	
	public function getDescriptionField(){
	    
	    // default_description_property_id
	    if($this->getModel()->getDefaultDescriptionPropertyId()){
	        $property_id = $this->getModel()->getDefaultDescriptionPropertyId();
	        $property = $this->getPropertyByNumericKey($property_id);
	        return $property;
	    }else{
	        return null;
	    }
	    
	}
	
	public function getDescriptionFieldContents(){
	    
	    $property = $this->getDescriptionField();
	    
	    if(is_object($property)){
	        
	        $type_info = $property->getTypeInfo();
	        
	        if($property->getDatatype() == 'SM_DATATYPE_ASSET'){
	            $asset = new SmartestRenderableAsset;
	            
	            if($asset = $this->getPropertyValueByNumericKey($property->getId())){
	                // get asset content
	                return $asset;
	            }else{
	                // throw new SmartestException(sprintf("Asset with ID %s was not found.", $this->getPropertyValueByNumericKey($property_id)));
	                return null;
	            }
	            
	        }else{
	            return $this->getPropertyValueByNumericKey($property->getId());
	        }
	        
	    }else{
	        if($this->getModel()->getDefaultDescriptionPropertyId()){
	            throw new SmartestException(sprintf("Specified model description property with ID '%s' is not an object.", $property_id));
            }else{
                SmartestLog::getInstance('system')->log("Model '".$this->getModel()->getName().'\' does not have a description property, so no description can be given in content mixture.');
            }
	    }
	    
	}
	
	public function compile($draft=false, $numeric_keys=false){
	    return $this->__toArray($draft, $numeric_keys);
	}
	
	public function __toArray($draft=false, $numeric_keys=false, $get_all_fk_property_options=false){
		// return associative array of property names and values
		$result = array();
		
		$result = $this->_item->__toArray(true);
		
		foreach($this->_varnames_lookup as $vn => $id){
		    
		    if($numeric_keys){
		        $key = $id;
		    }else{
		        $key = $vn;
		    }
		    
		    if($draft){
		        if(isset($this->_properties[$id]) && is_object($this->_properties[$id]->getData())){
		            $result[$key] = $this->_properties[$id]->getData()->getDraftContent();
	            }
	        }else{
	            if(isset($this->_properties[$id]) && is_object($this->_properties[$id]->getData())){
	                $result[$key] = $this->_properties[$id]->getData()->getContent();
                }
	        }
		}
		
		switch($this->getWorkflowStatus()){
		    case self::NOT_CHANGED:
		    $result['_workflow_status'] = 'Not changed';
		    break;
		    case self::CHANGES_APPROVED:
		    $result['_workflow_status'] = 'Approved and ready for publishing';
		    break;
		    default:
		    $result['_workflow_status'] = 'Awaiting approval';
		    break;
		}
		
		if(is_object($this->getModel())){
		    $result['_model'] = $this->getModel()->__toArray();
	    }
	    
		$result['_properties'] = $this->getPropertiesAsArrays($numeric_keys, $get_all_fk_property_options);
		
		ksort($result);
		
		return $result;
	}
	
	public function __toSimpleObject($simple=false){
	    
	    $obj = new stdClass;
	    $obj->name = $this->getName();
	    $obj->id = $this->getId();
	    $obj->slug = $this->getSlug();
	    
	    if(!$simple){
	        foreach($this->getProperties() as $p){
	            $vn = $p->getVarname();
	            $obj->$vn = $p->getData()->getContent()->stdObjectOrScalar();
	        }
	    }
	    
	    return $obj;
	    
	}
	
	public function __toJson($simple=false){
	    
	    return json_encode($this->__toSimpleObject($simple));
	    
	}
	
	public function getProperties($numeric_keys=false){
	    
	    $result = array();
	    
	    foreach($this->_varnames_lookup as $vn => $id){
	    
	        if($numeric_keys){
	            $key = $id;
	        }else{
	            $key = $vn;
	        }
	    
	        $result[$key] = $this->_properties[$id];
	        
		}
		
	    return $result;
	    
	}
	
	public function getPropertyValueHolders(){
	    return $this->getProperties();
	}
	
	public function getPropertiesThatRequireDuplicationDecision(){
	    
	    $properties = array();
	    
	    foreach($this->getProperties() as $p){
	        $info = $p->getTypeInfo();
	        if($info['id'] == 'SM_DATATYPE_ASSET' || $info['id'] == 'SM_DATATYPE_TEMPLATE'){
	            $properties[] = $p;
	        }
	    }
	    
	    return $properties;
	    
	}
	
	public function getStringProperties(){
	    
	    $type_codes = SmartestDataUtility::getDataTypeCodesByValueType('string');
	    $sql = "SELECT itemproperty_id, itemproperty_varname FROM ItemProperties WHERE itemproperty_datatype IN ('".implode("','", $type_codes)."') AND itemproperty_itemclass_id='".$this->_item->getItemclassId()."' ORDER BY itemproperty_order_index ASC;";
	    // $result = $this->database->queryToArray($sql);
	    
	    $signifiers = $this->database->queryFieldsToArrays(array('itemproperty_varname', 'itemproperty_id'), $sql);
	    // print_r($signifiers['itemproperty_id']);
	    
	    // return $signifiers['itemproperty_varname'];
	    
	    /* $varnames = array();
	    $ids = array();*/
	    $properties = array();
	    
	    /* foreach($result as $r){
	        $varnames[] = $r['itemproperty_varname'];
	        $ids[] = $r['itemproperty_id'];
	    } */
	    
	    foreach($this->_properties as $key => $propertyvalueholder){
	        // echo $key.' ';
	        if(in_array($key, $signifiers['itemproperty_id'])){
	            $properties[] = $propertyvalueholder;
	        }
	    }
	    
	    return $properties;
	    
	}
	
	public function getDropdownMenuProperties(){
	    
	    $sql = "SELECT itemproperty_id, itemproperty_varname FROM ItemProperties WHERE itemproperty_datatype = 'SM_DATATYPE_DROPDOWN_MENU' AND itemproperty_itemclass_id='".$this->_item->getItemclassId()."' ORDER BY itemproperty_order_index ASC;";
	    $signifiers = $this->database->queryFieldsToArrays(array('itemproperty_varname', 'itemproperty_id'), $sql);
	    $properties = array();
	    
	    foreach($this->_properties as $key => $propertyvalueholder){
	        if(in_array($key, $signifiers['itemproperty_id'])){
	            $properties[] = $propertyvalueholder;
	        }
	    }
	    
	    return $properties;
	    
	}
	
	public function getPropertiesAsArrays($numeric_keys=false, $get_all_fk_property_options=false){
	    
	    $result = array();
	    
	    foreach($this->_varnames_lookup as $fn => $id){
	    
	        if($numeric_keys){
	            $key = $id;
	        }else{
	            $key = $vn;
	        }
	    
	        $result[$key] = $this->_properties[$id]->__toArray();
	        $result[$key]['_type_info'] = $this->_properties[$id]->getTypeInfo();
            
            if($this->_properties[$id]->isForeignKey() && $get_all_fk_property_options){
                $result[$key]['_options'] = $this->_properties[$id]->getPossibleValuesAsArrays();
            }
	        
		}
		
		return $result;
		
	}
	
	public function getTags(){
	    return $this->_item->getTags();
	}
	
	public function getTagsAsArrays(){
	    return $this->_item->getTagsAsArrays();
	}
	
	public function updateTagsFromStringsArray($strings_array){
	    $this->_item->updateTagsFromStringsArray($strings_array);
	}
	
	public function getTagsAsCommaSeparatedString(){
	    return $this->_item->getTagsAsCommaSeparatedString();
	}
	
	public function getAuthors(){
	    return $this->getItem()->getAuthors();
	}
	
	public function addAuthorById($user_id){
	    return $this->getItem()->addAuthorById($user_id);
	}
	
	public function getPropertyByNumericKey($key){
	    if(array_key_exists($key, $this->_properties)){
	        return $this->_properties[$key];
	    }else{
	        return null;
	    }
	}
	
	public function getPropertyValueByNumericKey($key, $draft=false){
	    
	    if(array_key_exists($key, $this->_properties)){
	        
	        // echo "test";
	        try{
	            
	            if(!$this->_properties[$key]->getData()->hasItem()){
	                $this->_properties[$key]->getData()->setItem($this);
	            }
    	        
    	        if($this->_properties[$key]->getDatatype() == 'SM_DATATYPE_TEMPLATE'){
    	            // var_dump($this->getDraftMode());
    	        }
    	        
    	        if($this->getDraftMode()){
    	            $raw_value = $this->_properties[$key]->getData()->getDraftContent();
    	            // $raw_value->setDraftMode(true);
                }else{
                    $raw_value = $this->_properties[$key]->getData()->getContent();
                }
                
                // echo get_class($raw_value);
            
            }catch(SmartestException $e){
                echo $e->getMessage();
            }
            
            $t = $this->_properties[$key]->getTypeInfo();
            
            if($t['valuetype'] == 'auto'){
                
                if($t['id'] == 'SM_DATATYPE_AUTO_ITEM_FK'){
                
                    $class = $t['class'];
                    
                    $ids = array();
                
                    $field = $draft ? 'itempropertyvalue_draft_content' : 'itempropertyvalue_content';
                
                    $sql = "SELECT item_id FROM Items, ItemProperties, ItemPropertyValues WHERE item_deleted !=1 AND item_itemclass_id=itemproperty_itemclass_id AND itempropertyvalue_item_id=item_id AND itempropertyvalue_property_id = itemproperty_id AND ".$field."='".$this->getId()."' AND itemproperty_id='".$this->_properties[$key]->getForeignKeyFilter()."'";
                    $result = $this->database->queryToArray($sql);
                    
                    foreach($result as $r){
                        $ids[] = $r['item_id'];
                    }
                    
                    $obj = new $class;
                    $obj->hydrateFromStoredIdsArray($ids, $draft);
                    return $obj;
                
                }
                
            }
            
            // var_dump();
            // echo "test";
            // var_dump($raw_value);
            if(is_object($raw_value)){
                $r = $raw_value;
                // echo get_class($raw_value);
                // echo "Object";
            }else if($value_ob = SmartestDataUtility::objectize($raw_value, $this->_properties[$key]->getDatatype(), $this->_properties[$key]->getForeignKeyFilter())){
                $r = $value_obj;
                // echo get_class($value_obj);
                // echo "Not Object";
            }else if(is_null($raw_value) && $c = SmartestDataUtility::getClassForDataType($this->_properties[$key]->getDatatype(), $this->_properties[$key]->getForeignKeyFilter())){
                $r = new $c;
            }
            
            return $r;
            
	    }else{
	        return null;
	    }
	}
	
	public function getPropertyRawValueByNumericKey($key){
	    
	    if(array_key_exists($key, $this->_properties)){
	        
	        if($this->getDraftMode()){
	            $raw_value = $this->_properties[$key]->getData()->getRawValue(true);
            }else{
                $raw_value = $this->_properties[$key]->getData()->getRawValue(false);
            }
            
            return $raw_value;
            
	    }else{
	        return null;
	    }
	}
	
	public function getPropertyValueByVarName($varname){
	    
	    if(array_key_exists($varname, $this->_varnames_lookup)){
	        /* if($this->getDraftMode()){
	            return $this->_properties[$this->_varnames_lookup[$varname]]->getData()->getDraftContent();
            }else{
                return $this->_properties[$this->_varnames_lookup[$varname]]->getData()->getContent();
            } */
            
            if($this->getDraftMode()){
	            $raw_value = $this->_properties[$this->_varnames_lookup[$varname]]->getData()->getDraftContent();
            }else{
                $raw_value = $this->_properties[$this->_varnames_lookup[$varname]]->getData()->getContent();
            }
            
            // print_r($raw_value);
            if(is_object($raw_value)){
                return $raw_value;
            }else if($value_ob = SmartestDataUtility::objectize($raw_value, $this->_properties[$this->_varnames_lookup[$varname]]->getDatatype())){
                // echo $value_obj;
                return $value_obj;
            }
            
	    }else{
	        return null;
	    }
	}
	
	public function setPropertyValueByNumericKey($key, $raw_value){
	    
	    if(array_key_exists($key, $this->_properties)){
	        
	        if(!$this->_properties[$key]->getData()->getPropertyId()){
	            $this->_properties[$key]->getData()->setPropertyId($key);
	        }
	        
	        if(!$this->_properties[$key]->getData()->getItemId()){
	            $this->_properties[$key]->getData()->setItemId($this->getId());
	        }
	        
	        // var_dump($raw_value);
	        
	        // var_dump(get_class($this->_properties[$key]->getData()));
	        // echo $key;
	        
	        $this->_properties[$key]->getData()->setContent($raw_value);
	        // print_r($this->_properties[$key]->getData()->getDraftContent());
	        
	        // return $this->_properties[$key]->getData()->setContent($raw_value);
	        
	        // echo $this->_properties[$key]->getDatatype();
	        
	        /* if($value_obj = SmartestDataUtility::objectizeFromRawFormData($value, $this->_properties[$key]->getDatatype())){
	            // echo $value_obj->getStorableFormat().' ';
	            // echo $value_obj;
	            return $this->_properties[$key]->getData()->setContent($value_obj->getStorableFormat());
            }else{
                // echo "failed";
            } */
	        
	    }else{
	        return null;
	    }
	}
	
	public function __toString(){
		// return item's built-in name
		return $this->getItem()->getName();
	}
	
	public function getWorkflowStatus(){
	    if($this->getItem()->getModified() > $this->getItem()->getLastPublished()){
	        
	        // page has changed since it was last published
	        if($this->getItem()->getChangesApproved()){
	            return self::CHANGES_APPROVED;
	        }else{
	            return self::AWAITING_APPROVAL;
	        }
	        
	    }else{
	        // page hasn't been modified
	        return self::NOT_CHANGED;
	    }
	}
	
	public function save(){
		
		$this->_save_errors = array();
		
		if(!$this->_came_from_database){
		    
		    if(!$this->_item->getWebId()){
		    
		        // create web id for SmartestItem object first
		        // $webid = SmartestStringHelper::random(32);
		        
		        switch($this->getModel()->getLongIdFormat()){

    	            case '_STD':
    	            case '':
    	            $webid = SmartestStringHelper::random(32);
    	            break;

    	            case '_UUID':
    	            $webid = SmartestStringHelper::generateUUID();
    	            break;

    	            default:
    	            $webid = SmartestStringHelper::randomFromFormat($this->getModel()->getLongIdFormat());

    	        }
		        
		        $this->_item->setWebId($webid);
		    
	        }
	        
	        // If the item is new, a site_id is also automatically assigned by SmartestItem::save()
	        
	    }
	    
	    if($this->_item->getName()){
	        
	        if(!$this->_item->getItemclassId()){
	            $this->_item->setItemclassId($this->_model_id);
	        }
	        
	        if(!strlen($this->_item->getSlug())){
	            $this->_item->setSlug(SmartestStringHelper::toSlug($this->_item->getName()));
	        }
	        
	        $this->_item->save();
            
            foreach($this->getModel()->getProperties() as $prop){
                
                $key = $prop->getId();
                $t = $prop->getTypeInfo();
                
                $this->_properties[$key]->setContextualItemId($this->_item->getId());
                $this->_properties[$key]->getData()->setItemId($this->_item->getId());
                
                if($this->_properties[$key]->getRequired() == 'TRUE' && !$this->_properties[$key]->getData()->getDraftContent()){
                    
                    // raise error
                    $this->_save_errors[] = $key; // SmartestItemPropertyValue::OMISSION_ERROR;
                    
                }
                
                if($t['valuetype'] != 'auto'){
                
                    // save a value object regardless if it is
                    $this->_properties[$key]->getData()->save();
                
                }
                
            }
            
        }else{
            // raise error - the item had no name
            $this->_save_errors[] = '_name';
            throw new SmartestException("Item saved without a name", SM_ERROR_USER);
        }
        
        if(count($this->_save_errors)){
            return false;
        }else{
            return true;
        }
        
	}
	
	public function saveAndPublish(){
	    
	    $this->save();
	    $this->publish();
	    
	}
	
	public function getSaveErrors(){
	    return $this->_save_errors;
	}
	
	public function delete(){
		// mark as deleted
		if($this->_item instanceof SmartestItem && $this->_item->isHydrated()){
		    
		    $sql = "SELECT AssetIdentifiers.assetidentifier_live_asset_id, AssetIdentifiers.assetidentifier_assetclass_id, AssetClasses.assetclass_id, AssetClasses.assetclass_name, Pages.page_title, Pages.page_id FROM AssetIdentifiers, AssetClasses, Pages WHERE AssetIdentifiers.assetidentifier_live_asset_id='".$this->getId()."' AND AssetClasses.assetclass_type='SM_ASSETCLASS_ITEM_SPACE' AND AssetIdentifiers.assetidentifier_assetclass_id=AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_page_id=Pages.page_id";
		    $result = $this->database->queryToArray($sql);
		    
		    if(count($result)){
		        SmartestLog::getInstance('system')->log("Item '{$this->getName()}' could not be deleted because it is currently the live, published item for the itemspace '{$result[0]['assetclass_name']}' on page '{$result[0]['page_title']}'");
		        return false;
		    }
		    
		    $this->_item->setDeleted(1);
		    $this->_item->save();
		    
		    return true;
		}
	}
	
	public function hardDelete(){
	    
	    if($this->_item instanceof SmartestItem && $this->_item->isHydrated()){
	        $this->_item->delete(true);
	    }
	    
	}
	
	public function duplicateFactory($name){
	    
	    $class = get_class($this);
	    $dupe = new $class;
	    $dupe->setItemForDuplicate($this->getItem()->duplicateWithoutSaving());
	    $dupe->setName($name);
	    
	    switch($this->getModel()->getLongIdFormat()){
            
            case '_STD':
            case '':
            $webid = SmartestStringHelper::random(32);
            break;
            
            case '_UUID':
            $webid = SmartestStringHelper::generateUUID();
            break;
            
            default:
            $webid = SmartestStringHelper::randomFromFormat($this->getModel()->getLongIdFormat());
            
        }
        
        $this->_item->setWebid($webid);
	    $dupe->getItem()->setWebId($webid);
	    $dupe->getItem()->setSlug($this->getItem()->getSlug());
	    $dupe->getItem()->save();
	    
	    $new_values = array();
	    
	    // print_r(array_merge(, SmartestDataUtility::getDataTypeCodesByValueType('foreignkey')));
	    
	    foreach($this->getStringProperties() as $p){
	        $new_values[$p->getId()] = $p->getData()->duplicateWithoutSaving(); // new SmartestItemPropertyValue object the same as the old one
	        $new_values[$p->getId()]->setItemId($dupe->getItem()->getId());
	    }
	    
	    foreach($this->getDropdownMenuProperties() as $p){
	        $new_values[$p->getId()] = $p->getData()->duplicateWithoutSaving(); // new SmartestItemPropertyValue object the same as the old one
	        $new_values[$p->getId()]->setItemId($dupe->getItem()->getId());
	    }
	    
	    $dupe->loadPropertiesForDuplication($new_values);
	    $dupe->save();
	    
	    foreach($this->getAuthors() as $a){
	        $dupe->addAuthorById($a->getId());
	    }
	    
	    return $dupe;
	    
	}
	
	public function loadPropertiesForDuplication($properties_from_other_item){
	    
	    if(is_array($properties_from_other_item)){
	        foreach($properties_from_other_item as $k => $p){ // These are ItemPropertyValueHolder objects
	            $this->_properties[$k]->replaceItemPropertyValueWith($properties_from_other_item[$k]);
	        }
	    }
	}
	
	public function setItemForDuplicate(SmartestItem $i){
	    $this->_item = $i;
	}
	
	public function publish(){
	    
	    // NOTE: the SmartestItemPropertyValue::publish() function checks the user's permission, so this one doesn't need to
	    foreach($this->_properties as $pid => $p){
	        
	        if($p instanceof SmartestItemPropertyValueHolder){
	            $p->getData()->publish();
	        }
	        
	    }
	    
	    $sql = "UPDATE TodoItems SET todoitem_is_complete='1' WHERE todoitem_type='SM_TODOITEMTYPE_PUBLISH_ITEM' AND todoitem_foreign_object_id='".$this->_item->getId()."'";
	    $this->database->rawQuery($sql);
	    
	    $this->_item->setChangesApproved(1);
	    $this->_item->setLastPublished(time());
	    $this->_item->setIsHeld(0);
	    $this->_item->setPublic('TRUE');
	    $this->_item->save();
	    
	    foreach($this->getCacheFiles() as $file){
	        
	        unlink($file);
	        
	    }
	    
	}
	
	public function unPublish(){
	    $this->_item->setPublic('FALSE');
	    $this->_item->save();
	}
	
	public function isApproved(){
	    return ($this->_item->getChangesApproved() == 1) ? true : false;
	}
	
	public function getRelatedPagesAsArrays($draft_mode=false){
	    return $this->_item->getRelatedPagesAsArrays($draft_mode);
	}
    
    public static function getModelClassName($item_id){
	    
	    $item = new SmartestItem;
	    $item->find($item_id);
	    $model_id = $item->getItemclassId();
	    
	    $model = new SmartestModel;
	    $model->find($model_id);
	    return $model->getClassName();
	    
    }
    
    // builds a fully populated object of the correct type from just the primary key or webid
    public static function retrieveByPk($item_id, $dont_bother_with_class=false){
        
        if(__CLASS__ == 'SmartestCmsItem'){
        
            if(!$dont_bother_with_class){
                $className = self::getModelClassName($item_id);
            }
        
            if(!$dont_bother_with_class && class_exists($className)){
                $object = new $className;
            }else{
                $object = new SmartestCmsItem;
            }
        
        }else{
            
            $className = __CLASS__;
            $object = new $className;
            
        }
        
        if($object->find($item_id)){
            return $object;
        }else{
            return null;
        }
    }
    
    public static function all($mode=9, $site_id=null){
        
        if(__CLASS__ == 'SmartestCmsItem'){
        
            // Error - all() must be called on a specific model
        
        }else{
            
            $className = __CLASS__;
            $object = new $className;
            $model = $object->getModel();
            return $model->getAllItems($site_id, $mode);
            
        }
        
    }
    
    // builds a fully populated object of the correct type from just the primary key or webid
    /* public static function retrieveByName($item_id, $dont_bother_with_class=false){
        
        if(__CLASS__ == 'SmartestCmsItem'){
        
            if(!$dont_bother_with_class){
                $className = self::getModelClassName($item_id);
            }
        
            if(!$dont_bother_with_class && class_exists($className)){
                $object = new $className;
            }else{
                $object = new SmartestCmsItem;
            }
        
        }else{
            
            $className = __CLASS__;
            $object = new $className;
            
        }
        
        if($object->find($item_id)){
            return $object;
        }else{
            return null;
        }
    } */
    
    public static function createNewByModelId($id){
        
        $m = new SmartestModel;
        
        if($m->find($id)){
            $class_name = $m->getClassName();
            if(class_exists($class_name)){
                return new $class_name;
            }else{
                // error - model's class name does not exist
            }
        }else{
            // error - model not found
        }
        
    }
    
    protected function getDataStore(){
        return SmartestPersistentObject::get('centralDataHolder');
    }
    
    public function initializeItemChainDataStorage(){
        if(!$this->_item_chain_data){
            $this->_item_chain_data = new SmartestParameterHolder('Item Chain information for item '.$this->_item->getName());
        }
    }
    
    public function setPositionInItemChain($set_id, $position){
        $this->initializeItemChainDataStorage();
        $this->_item_chain_data->setParameter('pos_'.$set_id, (int) $position);
    }
    
    public function setPreviousPrimaryKeyInItemChain($set_id, $id){
        $this->initializeItemChainDataStorage();
        $this->_item_chain_data->setParameter('prev_'.$set_id, (int) $id);
    }
    
    public function setNextPrimaryKeyInItemChain($set_id, $id){
        $this->initializeItemChainDataStorage();
        $this->_item_chain_data->setParameter('next_'.$set_id, (int) $id);
    }
    
    public function getPositionInItemChain($set_id){
        $this->_item_chain_data->getParameter('pos_'.$set_id);
    }
    
    public function getPreviousPrimaryKeyInItemChain($set_id){
        $this->_item_chain_data->getParameter('prev_'.$set_id);
    }
    
    public function getNextPrimaryKeyInItemChain($set_id){
        $this->_item_chain_data->getParameter('next_'.$set_id);
    }
	
}
