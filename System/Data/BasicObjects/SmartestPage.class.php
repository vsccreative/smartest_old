<?php

class SmartestPage extends SmartestBasePage implements SmartestSystemUiObject, SmartestGenericListedObject, SmartestDualModedObject, SmartestStorableValue{

	protected $_save_url = true;
	protected $_fields_retrieval_attempted = false;
	protected $_child_pages = array();
	protected $_child_pages_retrieved = false;
	protected $_child_web_pages = array();
	protected $_child_web_pages_retrieved = false;
	protected $_grandparent_page;
	protected $_parent_page;
	protected $_section_page;
	protected $_urls = array();
	
	protected $_draft_mode = false;
	protected $_level = 0;
	
	protected $_fields = array();
	protected $_field_definitions = array();
	protected $_containers = array();
	protected $_placeholders;
	protected $_itemspaces = array();
	
	protected $_new_urls = array();
	protected $displayPagesIndex = 0;
	protected $displayPages = array();
	protected $_site;
	protected $_parent_site;
	
	const NOT_CHANGED = 100;
	const AWAITING_APPROVAL = 101;
	const CHANGES_APPROVED = 102;
	const NOT_PUBLIC = 103;
	
	const HIERARCHY_DEPTH_LIMIT = 32;
	
	public function hydrate($id){
		// determine what kind of identification is being used
		
		if(is_array($id)){
		    
		    return parent::hydrate($id);
		
		}else{
		    
		    $this->_save_url = false;
		    
		    if(is_numeric($id)){
    			// numeric_id
    			$field = 'page_id';
    		}else if(preg_match('/[a-zA-Z0-9\$-]{32}/', $id)){
    			// 'webid'
    			$field = 'page_webid';
    		}else if(preg_match('/[a-zA-Z0-9_-]+/', $id)){
    			// name
    			$field = 'page_name';
    		}
    		
    		if($field && $id){
		    
    		    $sql = "SELECT * FROM Pages WHERE $field='$id'";
		
        		$result = $this->database->queryToArray($sql);
		
        		if(count($result)){
			
        			foreach($result[0] as $name => $value){
        				if (substr($name, 0, 5) == $this->_table_prefix) {
        					$this->_properties[substr($name, 5)] = $value;
        					$this->_properties_lookup[SmartestStringHelper::toCamelCase(substr($name, 5))] = substr($name, 5);
        				}
        			}
			
        			$this->_came_from_database = true;
			
        			return true;
        		}else{
        			return false;
        		}
        		
		    }else{
		        
		        // var_dump($id);
		        // echo 'empty ';
		        // throw new SmartestException('Attempted page hydration without valid page id, web_id, or name field.');
		        
		    }
	    }
	}
	
	public function save(){
		
		parent::save();
		
		if($this->_properties['id']){
		    $sql = "SELECT pageurl_id FROM PageUrls WHERE pageurl_page_id='".$this->_properties['id']."'";
		    $result = $this->database->queryToArray($sql);
		    $num_existing_urls = count($result);
	    }else{
	        $num_existing_urls = 0;
	    }
		
		$i = 0;
		
		// Add any new URLs
		foreach($this->_new_urls as $url_string){
		    
		    $url = new SmartestPageUrl;
    	    $url->setUrl($url_string);
    	    $url->setPageId($this->_properties['id']);
    	    
    	    if($i < 1){
    	        if($num_existing_urls == 0){
    	            $url->setIsDefault(1);
	            }
	        }
	        
    	    $url->save();
    	    $i++;
		}
		
		$this->_new_urls = array();
		
	}
	
	public function delete($remove=false){
	    if($remove){
		    
		    $this->removeUrls();
		    
		    $sql = "DELETE FROM PagePropertyValues WHERE pagepropertyvalue_page_id='".$this->_properties['id']."'";
		    $this->database->rawQuery($sql);
		    
		    $sql = "DELETE FROM AssetIdentifiers WHERE assetidentifier_page_id='".$this->_properties['id']."'";
		    $this->database->rawQuery($sql);
		    
		    $sql = "DELETE FROM ".$this->_table_name." WHERE ".$this->_table_prefix."id='".$this->_properties['id']."' LIMIT 1";
		    $this->database->rawQuery($sql);
		    
		    $this->_properties['id'] = null;
		    $this->_came_from_database = false;
		    
	    }else{
	        
	        $this->setField('deleted', 'TRUE');
	        $this->save();
	    }
	}
	
	public function removeUrls(){
	    $sql = "DELETE FROM PageUrls WHERE pageurl_page_id='".$this->_properties['id']."'";
	    $this->database->rawQuery($sql);
	}
	
	public function getDraftMode(){
	    return $this->_draft_mode;
	}
	
	public function setDraftMode($mode){
	    $this->_draft_mode = (bool) $mode;
	}
	
	public function getMasterTemplate(){
	    $template = new SmartestTemplateAsset;
	    $filename = $this->getDraftMode() ? $this->getDraftTemplate() : $this->getLiveTemplate();
	    $template->findBy('url', $filename);
	    return $template;
	}
	
	public function addUrl($url_string, $is_forward=false){
	    if(!in_array($url_string, $this->_new_urls)){
	        $this->_new_urls[] = $url_string;
	    }
	}
	
	public function clearDefaultUrl(){
	    $sql = "UPDATE PageUrls SET pageurl_is_default='0' WHERE pageurl_page_id='".$this->_properties['id']."'";
	    $this->database->rawQuery($sql);
	}
	
	public function setDefaultUrl($url){
	    if($url == (int) $url){
	        // we are dealing with a url id
	        $u = new SmartestPageUrl;
	        if($u->hydrate($url)){
	            $u->setIsDefault(1);
	            $u->setType('SM_PAGEURL_NORMAL');
	            $this->clearDefaultUrl();
                $u->save();
                return true;
	        }else{
	            return false;
	        }
	    }else{
	        $sql = "SELECT PageUrls.*, Pages.page_id FROM PageUrls, Pages WHERE PageUrls.pageurl_page_id=Pages.page_id AND PageUrls.pageurl_url='".SmartestStringHelper::sanitize($url)."' AND Pages.page_site_id='".$this->getParentSite()->getId()."'";
	        $result = $this->database->queryToArray();
	        if(count($result)){
	            // url exists
	            $url_record = $result[0];
	            
	            if($url_record['page_id'] == $this->_properties['id']){
	                // the url is already in use for this page - just make it the default
	                $u = new SmartestPageUrl;
	                $u->hydrate($result[0]);
	                $u->setIsDefault(1);
	                $this->clearDefaultUrl();
	                $u->save();
	                return true;
	            }else{
	                // the url is in use for another page
	                // record to log
	                return false;
	            }
	            
	        }else{
	            // url doesn't exist
	            $u = new SmartestPageUrl;
	            $u->setUrl(SmartestStringHelper::sanitize($url));
	            $u->setPageId($this->_properties['id']);
	            $u->setIsDefault(1);
	            $this->clearDefaultUrl();
	            $u->save();
	            return true;
	        }
	    }
	}
	
	public function getAssetIdentifiers($item_id=false, $item_only=false){
	    
	    $sql = "SELECT * FROM AssetIdentifiers, AssetClasses WHERE assetidentifier_page_id='".$this->_properties['id']."' AND assetidentifier_assetclass_id=assetclass_id";
	    
	    if(is_numeric($item_id)){
	        if($item_only){
	            $sql .= " AND assetidentifier_item_id='".$item_id."'";
	        }else{
	            $sql .= " AND (assetidentifier_item_id='".$item_id."' OR assetidentifier_item_id IS NULL)";
	        }
	    }else{
	        $sql .= " AND assetidentifier_item_id IS NULL";
	    }
	    
	    $result = $this->database->queryToArray($sql);
    
	    $ais = array();
    
	    foreach($result as $r){
        
	        $ai = new SmartestAssetIdentifier;
	        $ai->hydrateFromGiantArray($r);
	        $ais[$r['assetclass_id']] = $ai;
        
	    }
	    
	    /* if($item_id !== false){
	        
	        $sql = "SELECT * FROM AssetIdentifiers, AssetClasses WHERE assetidentifier_page_id='".$this->_properties['id']."' AND assetidentifier_assetclass_id=assetclass_id AND assetidentifier_item_id='".$item_id."'";
            $result = $this->database->queryToArray($sql);
    	    
    	    foreach($result as $r){

    	        $ai = new SmartestAssetIdentifier;
    	        $ai->hydrateFromGiantArray($r);
    	        $ais[$r['assetclass_id']] = $ai;

    	    }
    	    
	    } */
	    
	    return $ais;
	    
	}
	
	public function clearCachedCopies(){
	    
	    $cache_files = SmartestFileSystemHelper::load(SM_ROOT_DIR."System/Cache/Pages/");
		
		// removes all cache versions related to this page to keep the cache nice and tidy
		$cf_start = "site".$this->_properties['site_id']."_cms_page_".$this->_properties['id'];
		
		$result = false;
		$len = strlen($cf_start);
		$log = SmartestLog::getInstance('system');
		
		foreach($cache_files as $f){
		    if(substr($f, 0, $len) == $cf_start){
		        if($result = unlink(SM_ROOT_DIR."System/Cache/Pages/".$f)){
		            $log->log('Cached copy of page \''.$this->_properties['title'].'\' was removed from the cache.');
		        }
		    }
		}
		
		return $result;
	    
	}
	
	public function publish($item_id=false, $item_only=null){
	    
	    // update database defs
		$sql = "UPDATE Lists SET list_live_set_id=list_draft_set_id, list_live_template_file=list_draft_template_file, list_live_header_template=list_draft_header_template, list_live_footer_template=list_draft_footer_template WHERE list_page_id='".$this->_properties['id']."'";
		$this->database->rawQuery($sql);
		
		// delete files in page cache
		$this->clearCachedCopies();
		
		// Update placeholders and containers
		$this->publishAssetClasses($item_id, $item_only);
		
		// Update fields
		$this->publishFields();
		
		$this->setLiveTemplate($this->getDraftTemplate());
		$this->setLastPublished(time());
		$this->setIsPublished('TRUE');
		$this->save();
		SmartestLog::getInstance('system')->log('SmartestPage: Page \''.$this->_properties['title'].'\' was published.');
		
		SmartestCache::clear('site_pages_tree_'.$this->getSiteId(), true);
		
		// publish all textfragments on the page
		foreach($this->getParsableTextFragments($item_id) as $tf){
		    $tf->publish();
		}
		
	}
	
	public function publishAssetClasses($item_id=false, $item_only=false){
	    
	    $asset_identifiers = $this->getAssetIdentifiers($item_id, $item_only);
		
		foreach($asset_identifiers as $ai){
		    
		    if($ai->getAssetClass()->getType() == 'SM_ASSETCLASS_ITEM_SPACE'){
		        
		        $item = $ai->getSimpleItem(true);
		        
		        if($item){
		            
		            $item_published = ($item->getPublic() == 'TRUE') ? true : false;
		            
		            if($item_published){
		                // if the item selected as the draft def for the itemspace is published
		                $ai->publish(true);
		            }
		            
		        }else{
		            SmartestLog::getInstance("system")->log('Item ID '.$ai->getItemId(true).' chosen as draft definition for itemspace ID '.$ai->getItemSpaceId().' could not be found.');
		        }
		        
		        if($ai->getAssetClass()->getUpdateOnPagePublish() == 1 || $item_published){

    		    }
		        
	        }else{
	            $ai->publish(true);
	        }
		    
		}
	    
	}
	
	public function publishFields(){
	    
	    $sql = "UPDATE PagePropertyValues SET pagepropertyvalue_live_value=pagepropertyvalue_draft_value WHERE pagepropertyvalue_page_id='".$this->_properties['id']."'";
		$this->database->rawQuery($sql);
	    
	}
	
	public function unpublish($auto_save=true){
		$this->setIsPublished('FALSE');
		
		if($auto_save){
		    $this->save();
		    SmartestCache::clear('site_pages_tree_'.$this->getSiteId(), true);
		    SmartestLog::getInstance('system')->log('SmartestPage: Page \''.$this->_properties['title'].'\' was un-published.');
	    }else{
	        SmartestLog::getInstance('system')->log('SmartestPage: Page \''.$this->_properties['title'].'\' published was set to \'FALSE\'.');
	    }
	    
	}
	
	public function getTextFragments(){
	    
	    $sql = "SELECT TextFragments.* FROM Pages, Assets, AssetIdentifiers, TextFragments WHERE Pages.page_id=AssetIdentifiers.assetidentifier_page_id AND Assets.asset_id=AssetIdentifiers.assetidentifier_live_asset_id AND TextFragments.textfragment_id=Assets.asset_fragment_id AND Pages.page_id='".$this->_properties['id']."'";
		$result = $this->database->queryToArray($sql);
		$objects = array();
		
		foreach($result as $tfarray){
		    $tf = new SmartestTextFragment;
		    $tf->hydrate($tfarray);
		    $objects[] = $tf;
		}
		
		return $objects;
	}
	
	public function getParsableTextFragments($item_id=false){
	    
	    $helper = new SmartestAssetsLibraryHelper;
		$codes = $helper->getParsableAssetTypeCodes();
		
		$sql = "SELECT TextFragments.* FROM Pages, Assets, AssetIdentifiers, TextFragments WHERE Pages.page_id=AssetIdentifiers.assetidentifier_page_id AND Assets.asset_id=AssetIdentifiers.assetidentifier_live_asset_id AND TextFragments.textfragment_id=Assets.asset_fragment_id AND Pages.page_id='".$this->_properties['id']."' AND Assets.asset_type IN ('".implode("', '", $codes)."')";
		
		if(is_numeric($item_id)){
            $sql .= " AND assetidentifier_item_id='".$item_id."'";
        }else{
            $sql .= " AND assetidentifier_item_id IS NULL";
        }
		
		$result = $this->database->queryToArray($sql);
		$objects = array();
		
		foreach($result as $tfarray){
		    $tf = new SmartestTextFragment;
		    $tf->hydrate($tfarray);
		    $objects[] = $tf;
		}
		
		return $objects;
		
	}
	
	/* public function getTemplate(){
	    
	    
	    
	} */
	
	public function getStylesheets(){
	    
	    $t = $this->getMasterTemplate();
	    $hct = is_object($t) ? $t->getImportedStylesheets() : array();
	    $hard_coded_templates = array();
	    
	    foreach($hct as $a){
	        $hard_coded_templates[$a->getStringId()] = $a;
	    }
	    
	    $h = new SmartestAssetsLibraryHelper;
	    
	    // 1. Get placeholder types that accept stylesheets
	    $codes = $h->getAssetClassCodesThatAcceptType('SM_ASSETTYPE_STYLESHEET');
	    
	    // 2. Get placeholders of those types that are defined on this page
	    $sql = "SELECT Assets.*, AssetIdentifiers.* FROM Assets, AssetIdentifiers, AssetClasses, Pages WHERE AssetIdentifiers.assetidentifier_page_id = Pages.page_id AND AssetIdentifiers.assetidentifier_assetclass_id = AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_draft_asset_id = Assets.asset_id AND Pages.page_id = '".$this->getId()."' AND AssetClasses.assetclass_type IN ('".implode("','", $codes)."')";
	    $result = $this->database->queryToArray($sql);
	    
	    foreach($result as $ra){
	        $a = new SmartestAsset;
	        $a->hydrate($ra);
	        $hard_coded_templates[$a->getStringId()] = $a;
	    }
	    
	    ksort($hard_coded_templates);
	    
	    return $hard_coded_templates;
	    
	}
	
	public function getNextChildOrderIndex(){
	    
	    $children = $this->getPageChildren();
	    
	    if(count($children)){
	        $bottom_child_array = end($children);
	        $bottom_child = new SmartestPage;
	        $bottom_child->hydrate($bottom_child_array);
	        $oi = $bottom_child->getOrderIndex();
	        $index = (int) $oi;
	        return $index+1;
        }else{
            return 0;
        }
	}
	
	public function fixChildPageOrder($recursive=false){
	    
	    $children = $this->getPageChildren();
	    
	    // print_r(count($children));
	    
	    if(count($children)){
	        
	        $order_index = 0;
	        
	        foreach($children as $p){
	            
	            if(!SmartestStringHelper::toRealBool($p->getDeleted())){
	                
	                // echo $order_index;
	                
	                $p->setOrderIndex($order_index);
	                $p->save();
	                
	                $order_index++;
	                
	                if($recursive){
	                    $p->fixChildPageOrder(true);
	                }
                }
	        }
	    }
	}
	
	public function moveUp(){
	    
	    $this->getParentPage()->fixChildPageOrder();
	    
	    $order_index = $this->getOrderIndex();
	    $order_index = (int) $order_index;
	    
	    if($pp = $this->getPreviousPage()){
	        $this->setOrderIndex($pp->getOrderIndex());
            $this->save();
            $pp->setOrderIndex($order_index);
            $pp->save();
	    }
	}
	
	public function moveDown(){
	    
	    $this->getParentPage()->fixChildPageOrder();
	    
	    $order_index = $this->getOrderIndex();
	    $order_index = (int) $order_index;
	    
	    if($np = $this->getNextPage()){
	        $this->setOrderIndex($np->getOrderIndex());
            $this->save();
            $np->setOrderIndex($order_index);
            $np->save();
	    }
	    
	}
	
	public function getPreviousPage(){
	    $sql  = "SELECT * FROM Pages WHERE page_order_index < '".$this->getOrderIndex()."' AND page_parent='".$this->getParent()."' AND page_id !='".$this->_properties['id']."' AND page_deleted !='TRUE' ORDER BY page_order_index DESC LIMIT 1";
	    $result = $this->database->queryToArray($sql);
	    if(count($result)){
	        $pp = $result[0];
	        $page = new SmartestPage;
	        $page->hydrate($pp);
	        return $page;
	    }else{
	        return null;
	    }
	}
	
	public function getNextPage(){
	    $sql  = "SELECT * FROM Pages WHERE page_order_index > '".$this->getOrderIndex()."' AND page_parent='".$this->getParent()."' AND page_id !='".$this->_properties['id']."' AND page_deleted !='TRUE' ORDER BY page_order_index ASC LIMIT 1";
	    $result = $this->database->queryToArray($sql);
	    if(count($result)){
	        $np = $result[0];
	        $page = new SmartestPage;
	        $page->hydrate($np);
	        return $page;
	    }else{
	        return null;
	    }
	}
	
	public function getElementsAsList(){
		
	}

	public function getElementsAsTree(){
		
	}
	
	public function getPagesSubTree($level=1){
	
		$working_array = array();
		$index = 0;
		
		$_children = $this->getPageChildren();
		
		$int_level = (int) $level;
		
		foreach($_children as $child){
			
			/* if($child_page_record['page_type'] == 'ITEMCLASS'){
			    
			    $child = new SmartestItemPage;
			    
		    }else{
		        
		        $child = new SmartestPage;
		        
		    } */
		    
		    // var_dump($child_page_record);
		    
		    // $child->hydrate($child_page_record);
			
			$working_array[$index]["info"] = $child;
			$working_array[$index]["treeLevel"] = $int_level;
			$new_level = $int_level + 1; 
			$working_array[$index]["children"] = $child->getPagesSubTree($new_level);
			
			/* if($child->getType() == "ITEMCLASS" && $get_items){
			    $set = $child->getDataSet();
			    $working_array[$index]["child_items"] = $set->getMembersAsArrays();
			}else{
			    $working_array[$index]["child_items"] = array();
			} */
			
			$index++;
			
		}
		
		return $working_array;
	}
	
	public function getSerializedPageTree($level=1, $get_items=false, $use_passed_array=false, $passed_array=''){
		
		if($use_passed_array && is_array($passed_array)){
		    $pagesArray = $passed_array;
		}else{
		    $pagesArray = $this->getPagesSubTree($level, $get_items);
	    }
		
		$new_level = (int) $level + 1;
		
		foreach($pagesArray as $page){
			
			$this->displayPages[$this->displayPagesIndex]['info'] = $page['info'];
			$this->displayPages[$this->displayPagesIndex]['treeLevel'] = $page['treeLevel'];
			$children = $page['children'];
			
			$this->displayPagesIndex++;
			
			if(count($children) > 0){
			    
			    foreach($children as $child_array){
				    
				    $this->getSerializedPageTree($new_level, $get_items, true, array($child_array));
			        
		        }
		        
			}
	
		}
		
		return $this->displayPages;
		
	}
	
	public function getAvailableIconImageFilenames(){
	    
	    $sql = "SELECT * FROM Assets WHERE asset_type IN ('SM_ASSETTYPE_JPEG_IMAGE', 'SM_ASSETTYPE_GIF_IMAGE', 'SM_ASSETTYPE_PNG_IMAGE') AND (asset_site_id='".$this->_properties['site_id']."' OR asset_shared=1) AND asset_deleted!=1 ORDER BY asset_url";
	    $result = $this->database->queryToArray($sql);
	    
	    $filenames = array();
	    
	    foreach($result as $a){
	        $filenames[] = $a['asset_url'];
	    }
	    
	    return $filenames;
	}
	
	public function getSlug(){
	    return $this->getName();
	}
	
	public function getDate(){
	    if($this->getDraftMode()){
            return $this->getCreated();
        }else{
            return $this->getLastPublished();
        }
	}
	
	public function getLinkContents(){
	    if($this->getType() == 'ITEMCLASS'){
	        if(is_object($this->getSimpleItem())){
	            return 'metapage:'.$this->getName().':id='.$this->getSimpleItem()->getId();
            }else{
                return 'page:'.$this->getName();
            }
	    }else{
	        return 'page:'.$this->getName();
        }
	}
	
	public function getDefaultUrl(){
	    
	    $urls = $this->getUrls();
	    
        if(count($urls)){
	        // If there are actually urls for this page:
	        foreach($urls as $u){
	            if($u->getIsDefault()){
	                return $u;
	            }
	        }
            
            if($this->isHomePage()){
                return '';
            }else{
                return $urls[0];
            }
    
	    }else{
	        // No urls have been defined.
	        if($this->isHomePage()){
	            // Return "/"
	            $url = '';
	        }else{
	            // Return a dynamic one.
	            $url = 'website/renderPageFromId?page_id='.$this->getWebid();
            }
	    }

	    return $url;
	}
	
	public function getJsonInfoUrl($complete=false){
	    
	    if($complete){
	        return $this->getSite()->getDomain().$this->_request->getDomain().'ajax:website/pageInfo?page_id='.$this->getWebid();
        }else{
            return $this->_request->getDomain().'ajax:website/pageInfo?page_id='.$this->getWebId();
        }
	    
	}
	
	public function getUrls(){
		
		if(!count($this->_urls)){
		
		    $sql = "SELECT * FROM PageUrls WHERE pageurl_page_id ='".$this->_properties['id']."'";
		    $pageUrls = $this->database->queryToArray($sql);
		
		    foreach($pageUrls as $key => $url){
		        
		        $urlObj = new SmartestPageUrl;
		        $urlObj->hydrate($url);
		        $this->_urls[$key] = $urlObj;
		        
		    }
		
	    }
	    
	    return $this->_urls;

	}
	
	public function getUrlsAsArrays(){
	    
	    $urls = $this->getUrls();
	    $urls_array = array();
	    
	    foreach($urls as $u){
	        $urls_array[] = $u->__toArray();
	    }
	    
	    return $urls_array;
	    
	}
	
	public function getParentPage($get_item_page=true, $draft_mode='AUTO'){
	    
	    if($this->isHomePage()){
	        throw new SmartestException("Tried to get Parent of the top (home) page.");
	    }
	    
	    if(!$this->_parent_page || $get_item_page){
	        
	        $helper = new SmartestPageManagementHelper;
    		$type_index = $helper->getPageTypesIndex($this->getParentSite()->getId());
	        
	        if($type_index[$this->getParent()] == 'ITEMCLASS' && $get_item_page){
                
                if($this instanceof SmartestItemPage){
                    
	                if($this->getParentMetaPageReferringPropertyId()){
	                    // get the value of that property for the principal_item, which should also be an item_id
	                    $property = new SmartestItemPropertyValueHolder;
	                    
	                    if($property->hydrate($this->getParentMetaPageReferringPropertyId())){
	                        
	                        $property->setContextualItemId($this->getSimpleItem()->getId());
	                        
	                        if($this->getDraftMode()){
	                            $parent_item_id = $property->getData()->getDraftContent();
	                        }else{
	                            $parent_item_id = $property->getData()->getContent();
	                        }
	                        
	                        if($parent_item_id){
	                            
	                            // build the item that has that ID
	                            if($parent_item = SmartestCmsItem::retrieveByPk($parent_item_id)){
	                                $parent = new SmartestItemPage;
                    	            $parent->hydrate($this->getParent());
                    	            // give the item to the parent page
                    	            $parent->setPrincipalItem($parent_item);
	                            }else{
	                                $parent = new SmartestPage;
                    	            $parent->find($this->getParent());
	                            }
                                
	                        }else{
	                            $parent = new SmartestPage;
                	            $parent->find($this->getParent());
	                        }
	                        
	                        
                        }else if($this->getParentMetaPageReferringPropertyId() == '_SELF'){
                            
                            // self-referential property
                            $parent = new SmartestItemPage;
        	                $parent->hydrate($this->getParent());
                            $parent->setPrincipalItem($this->getPrincipalItem());
                            
                        }else{
                            
                            throw new SmartestException("Parent data source property for parent meta-page not found with ID '".$this->getParentMetaPageReferringPropertyId()."'.");
                            
                        }
                        
	                }else{
	                    
	                    throw new SmartestException("Parent data source property ID for parent meta-page not defined for meta-page '".$this->_properties['title']."'.");
	                    
	                }
	                
	            }else{
	                // the current page is not a SmartestItemPage instance, so has no prinipal item
	                $parent = new SmartestPage;
    	            $parent->hydrate($this->getParent());
	            }
	            
            }else{
	            $parent = new SmartestPage;
	            $parent->find($this->getParent());
	        }
	        
	        if(is_bool($draft_mode)){
	            $parent->setDraftMode($draft_mode);
	        }else if($draft_mode == 'AUTO'){
	            $parent->setDraftMode($this->getDraftMode());
            }
            
            // if($get_item_page || $parent->getType() != 'ITEMCLASS'){
	            $this->_parent_page = $parent;
	        // }
	        
	        return $this->_parent_page;
	        
        }
        
        return $this->_parent_page;
        
	}
	
	public function getGrandParentPage(){
	    
	    if(!$this->_grandparent_page){
	        
	        if(is_object($this->getParentPage())){
	            
	            $this->_grandparent_page = $this->getParentPage()->getParentPage();
	        
            }
	        
        }
        
        return $this->_grandparent_page;
        
	}

	public function getPageChildren($sections_only=false){
	    
	    $sql = "SELECT DISTINCT * FROM Pages WHERE page_parent='".$this->_properties['id']."' AND page_site_id='".$this->_properties['site_id']."' AND page_deleted != 'TRUE'";
		
		if(!$this->getDraftMode()){
		    $sql .= " AND page_is_published = 'TRUE'";
		}
		
		if($sections_only){
		    $sql .= " AND page_is_section = '1'";
		}
		
		$sql .= " ORDER BY page_order_index, page_id ASC";
		
		$result = $this->database->queryToArray($sql);
	    $i = 0;
	    
	    if(is_array($result)){
	    
	        foreach($result as $page_record){
	            $child_page = new SmartestPage;
	            $child_page->hydrate($page_record);
	            $child_page->setDraftMode($this->getDraftMode());
	            $this->_child_pages[$i] = $child_page;
	            $i++;
	        }
	    
	        $this->_child_pages_retrieved = true;
	    
        }
	        
	    return $this->_child_pages;
	}
	
	public function getPageChildrenAsArrays($sections_only=false){
	    
	    $children = $this->getPageChildren($sections_only);
	    $array = array();
	    
	    foreach($children as $child_page){
	        $array[] = $child_page->__toArray(false);
	    }
	    
	    return $array;
	    
	}
	
	public function getPageChildrenForWeb($sections_only=false){
	    // echo $this->getId();
	    $special_page_ids = array();
	    
	    if($this->getParentSite()->getTagPageId()){
	        $special_page_ids[] = $this->getParentSite()->getTagPageId();
	    }
        
        // these values should not be the same as the ids of the other special pages, 
        // but just in case they are, prevent them from being in the SQL query twice:
        if($this->getParentSite()->getErrorPageId() && !in_array($this->getParentSite()->getErrorPageId(), $special_page_ids)){
            $special_page_ids[] = $this->getParentSite()->getErrorPageId();
        }
        
        if($this->getParentSite()->getSearchPageId() && !in_array($this->getParentSite()->getSearchPageId(), $special_page_ids)){
            $special_page_ids[] = $this->getParentSite()->getSearchPageId();
        }
        
        $sql = "SELECT DISTINCT * FROM Pages WHERE page_parent='".$this->_properties['id']."' AND page_site_id='".$this->_properties['site_id']."' AND page_deleted != 'TRUE'";
		
		if(!$this->getDraftMode()){
		    $sql .= " AND page_is_published = 'TRUE'";
		}
		
		if($sections_only){
		    $sql .= " AND page_is_section = '1'";
		}
		
		$sql .= " AND page_type = 'NORMAL'";
		
		if(count($special_page_ids)){
		    $sql .= " AND page_id NOT IN('".implode("', '", $special_page_ids)."')";
	    }
		
		$sql .= " ORDER BY page_order_index, page_id ASC";
		
		$result = $this->database->queryToArray($sql);
	    $i = 0;
	    
	    if(is_array($result)){
	    
	        foreach($result as $page_record){
	            // if($page_record['page_type'] == 'NORMAL'){
	                $child_page = new SmartestPage;
	                $child_page->hydrate($page_record);
	                $this->_child_web_pages[$i] = $child_page;
	                $i++;
                // }
                
                /* else if($page_record['page_type'] == 'ITEMCLASS'){
                    
                    $model = new SmartestModel;
                    
                    if($model->hydrate($page_record['page_dataset_id'])){
                        foreach($model->getSimpleItems() as $item){
                            
                            $child_page = new SmartestItemPage;
                            $child_page->hydrate($page_record);
                            $child_page->setSimpleItem($item);
                            $child_page->setIdentifyingFieldName('id');
                            $child_page->setIdentifyingFieldValue($item->getId());
                            $child_page->assignPrincipalItem();
                            
                            $is_acceptable = $child_page->isAcceptableItem($this->getDraftMode());
                            
                            // var_dump($is_acceptable);
                            
                            if($is_acceptable){
                                $this->_child_web_pages[$i] = $child_page;
        	                    $i++;
    	                    }
                        }
                    }
                } */
	        }
	    
	        $this->_child_web_pages_retrieved = true;
	    
        }
        
        return $this->_child_web_pages;
        
	}
	
	
	public function getPageChildrenForWebAsArrays($sections_only=false){
	    
	    $children = $this->getPageChildrenForWeb($sections_only);
	    $array = array();
	    
	    foreach($children as $child_page){
	        $array[] = $child_page->__toArray(false);
	    }
	    
	    return $array;
	    
	}
	
	public function getPageFields(){
	    
	    $sql = "SELECT * FROM `PageProperties` WHERE pageproperty_site_id='".$this->_properties['site_id']."'";
	    $result = $this->database->queryToArray($sql);
        
        foreach($result as $p){
	        $property = new SmartestPageField;
	        $property->hydrate($p);
	        $this->_fields[$property->getId()] = $property;
	    }
    
	    $sql = "SELECT * FROM `PagePropertyValues` WHERE pagepropertyvalue_page_id='".$this->_properties['id']."'";
	    $result = $this->database->queryToArray($sql);
        
        foreach($result as $pfda){
            $fid = $pfda['pagepropertyvalue_pageproperty_id'];
            
            if(is_object($this->_fields[$fid])){
                $this->_fields[$fid]->setContextualPageId($this->_properties['id']);
                $this->_fields[$fid]->hydrateValueFromPpdArray($pfda);
            }
        }
        
        $this->_fields_retrieval_attempted = true;
        
        return $this->_fields;
	    
	}
	
	public function getPageFieldDefinitions(){
	    
	    if(!count($this->_field_definitiones)){
	    
    	    $sql = "SELECT * FROM `PageProperties` WHERE pageproperty_site_id='".$this->_properties['site_id']."'";
    	    $result = $this->database->queryToArray($sql);
    	    $fields = array();
    	    
    	    $definitions = new SmartestParameterHolder("Page field definitions for page '".$this->_properties['title']."'");
        
            foreach($result as $p){
    	        $property = new SmartestPageField;
    	        $property->hydrate($p);
    	        $fields[$property->getId()] = $property;
    	        $definitions->setParameter($p['pageproperty_name'], null);
    	    }
	    
    	    $sql = "SELECT * FROM `PagePropertyValues` WHERE pagepropertyvalue_page_id='".$this->_properties['id']."'";
    	    $result = $this->database->queryToArray($sql);
	    
    	    foreach($result as $pfda){
    	        if(isset($fields[$pfda['pagepropertyvalue_pageproperty_id']])){
    	            if($this->getDraftMode()){
    	                // echo $pfda['pagepropertyvalue_draft_value'];
    	                $value = SmartestDataUtility::objectize($pfda['pagepropertyvalue_draft_value'], $fields[$pfda['pagepropertyvalue_pageproperty_id']]->getType());
                    }else{
                        $value = SmartestDataUtility::objectize($pfda['pagepropertyvalue_live_value'], $fields[$pfda['pagepropertyvalue_pageproperty_id']]->getType());
                    }
                    $definitions->setParameter($fields[$pfda['pagepropertyvalue_pageproperty_id']]->getName(), $value);
    	        }
    	    }
	    
    	    $this->_field_definitiones = $definitions;
        
        }
	    
	    return $this->_field_definitiones;
    
	    /* 
        
        foreach($result as $pfda){
            $fid = $pfda['pagepropertyvalue_pageproperty_name'];
            
            if(is_object($this->_fields[$fid])){
                $this->_fields[$fid]->setContextualPageId($this->_properties['id']);
                $this->_fields[$fid]->hydrateValueFromPpdArray($pfda);
            }
        }
        
        $this->_fields_retrieval_attempted = true;
        
        return $this->_fields; */
	    
	}
	
	public function getPageFieldsAsArrays($numeric_keys=false){
	    
	    $fields = $this->getPageFields();
	    $arrays = array();
	    
	    foreach($fields as $id => $field){
	        
	        if($numeric_keys){
	            $key = $id;
	        }else{
	            $key = $field->getName();
	        }
	        
	        $arrays[$key] = $field->__toArray();
	        
	    }
	    
	    return $arrays;
	    
	}
	
	public function getPageFieldValuesAsAssociativeArray(){
	    
	    $fields = $this->getPageFields();
	    $array = array();
	    
	    // print_r($fields);
	    
	    foreach($fields as $f){
	        
	        $key = $f->getName();
	        
	        if($this->getDraftMode()){
	            $data = $f->getData()->getDraftValue();
	        }else{
	            $data = $f->getData()->getLiveValue();
	        }
	        
	        $array[$key] = $data;
	        
	    }
	    
	    // print_r($array);
	    return $array;
	    
	}
	
	public function getParentMetaPageReferringPropertyId(){
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PARENT_METAPAGE_RPID');
	    $q->setTargetEntityByIndex(2);
        $q->addQualifyingEntityByIndex(1, $this->getId());
        $result = array_values($q->retrieve());
        
        if(count($result)){
            return $result[0]->getPropertyId();
        }else if($d = SmartestSystemSettingHelper::load('metapage_'.$this->_properties['id'].'_parent_item_property_id')){
	        return $d;
	    }
	    
	}
	
	public function setParentMetaPageReferringPropertyId($id){
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PARENT_METAPAGE_RPID');
	    $q->setTargetEntityByIndex(2);
        $q->addQualifyingEntityByIndex(1, $this->getId());
        $result = array_values($q->retrieve());
        
        if(count($result)){
            $ref = $result[0];
        }else{
            $ref = new SmartestParentMetaPagePropertyReference;
            $ref->setPageId($this->getId());
        }
	    
	    $ref->setPropertyId($id);
	    $ref->save();
	    
	    return true;
	}
	
	public function fetchRenderingData(){
	    
	    $data = new SmartestParameterHolder('Page Rendering Data for page');
	    
	    $data->setParameter('page', $this);
	    $data->setParameter('tags', $this->getTags());
	    
	    if($this instanceof SmartestItemPage){
	        if($this->getPrincipalItem()){
	            
	            $data->setParameter('principal_item', $this->getPrincipalItem());
	            $data->setParameter('has_item', true);
	            $data->setParameter('is_item', true);
	            
            }else{
                
                $data->setParameter('principal_item', array());
                $data->setParameter('has_item', false);
                $data->setParameter('is_item', true);
                
            }
            
	    }else{
	        $data->setParameter('has_item', false);
	        $data->setParameter('is_item', false);
	    }
	    
	    $du = new SmartestDataUtility;
	    $tags = $du->getTags();
	    
	    $data->setParameter('all_tags', $tags);
	    $data->setParameter('authors', array_values($this->getAuthors()));
	    // $data->setParameter('fields', $this->getPageFieldValuesAsAssociativeArray());
	    $data->setParameter('fields', $this->getPageFieldDefinitions());
	    $data->setParameter('navigation', $this->getNavigationStructure());
	    $data->setParameter('site', $this->getSite());
	    $data->setParameter('request', SmartestPersistentObject::get('request_data'));
	    
	    // print_r(SmartestPersistentObject::get('request_data'));
	    
	    return $data;
	}
	
	public function isTagPage(){
	    return ($this->getParentSite()->getTagPageId() == $this->_properties['id']);
	}
	
	public function isSearchPage(){
	    return ($this->getParentSite()->getSearchPageId() == $this->_properties['id']);
	}
	
	public function isHomePage(){
	    return ($this->getParentSite()->getTopPageId() == $this->_properties['id']);
	}
	
	public function isApproved(){
	    return (bool) $this->_properties['changes_approved'];
	}
	
	public function isEditableByUserId($user_id){
	    if($this->getIsHeld() == '0' || ($this->getIsHeld() == '1' && $this->getHeldBy() == $user_id)){
            // The page isn't held, or it's held by the user
            return true;
        }else{
            // check if they have 'edit_held_pages' token
            $sql = "SELECT utlookup_id FROM UsersTokensLookup WHERE utlookup_user_id='".$user_id."' AND (utlookup_site_id='".$this->getSiteId()."' OR utlookup_is_global='1') AND (utlookup_token_id='38' OR utlookup_token_id='0')";
            $result = $this->database->queryToArray($sql);
            
            if(count($result)){
                return true;
            }else{
                return false;
            }
        }
	}
	
	public function touch(){
	    if($this->_came_from_database == true){
	        $sql = "UPDATE Pages SET page_modified = '".time()."' WHERE Pages.page_id = '".$this->_properties['id']."'";
	        $this->database->rawQuery($sql);
	    }
	}
	
	public function __toArray($getChildren=false){
	    
	    // SmartestLog::getInstance('system')->log('Deprecated API function used: '.get_class($this).'->__toArray()');
	    
	    $array = parent::__toArray();
	    
	    $array['title'] = $this->getTitle();
	    $array['url'] = $this->getDefaultUrl();
	    $array['formatted_title'] = $this->getFormattedTitle();
	    $array['formatted_static_title'] = $this->getFormattedTitle();
	    $array['static_title'] = $this->_properties['title'];
	    $array['is_tag_page'] = $this->isTagPage();
	    
	    if($this->getType() == 'ITEMCLASS'){
	        if(is_object($this->_principal_item)){
	            $array['link_contents'] = 'metapage:'.$this->getName().':id='.$this->_principal_item->getId();
            }else{
                $array['link_contents'] = 'page:'.$this->getName();
            }
	    }else{
	        $array['link_contents'] = 'page:'.$this->getName();
        }
	    
	    if($getChildren){
	        $array['_child_pages'] = $this->getPageChildrenAsArrays();
        }
        
        return $array;
        
	}
	
	public function __toSimpleObject(){
	    
	    $obj = parent::__toSimpleObject();
	    return $obj;
	    
	}
	
	public function __toJson(){
	    
	    $obj = $this->__toSimpleObject();
	    
	    // The data need moulding slightly
	    unset($obj->is_held);
	    unset($obj->held_by);
	    unset($obj->url);
	    unset($obj->dataset_id);
	    unset($obj->cache_interval);
	    unset($obj->cache_as_html);
	    unset($obj->changes_approved);
	    unset($obj->draft_template);
	    
	    $obj->is_section = (bool) $this->getIsSection();
	    $obj->is_published = SmartestStringHelper::toRealBool($this->getIsPublished());
	    $obj->deleted = SmartestStringHelper::toRealBool($this->getDeleted());
	    $obj->created = (int) $this->getCreated();
	    $obj->modified = (int) $this->getModified();
	    $obj->last_published = (int) $this->getLastPublished();
	    $obj->tags = $this->getTagNames();
	    $obj->fields = $this->getPageFieldDefinitions()->__toSimpleObject();
	    
	    $obj->formatted_title = $this->getFormattedTitle();
	    if($this->isHomePage()){
	        $obj->is_home = true;
	        unset($obj->parent);
	    }else{
	        $obj->is_home = false;
	        $obj->parent_webid = $this->getParentPage()->getWebid();
	        $obj->parent_id = $this->getParentPage()->getId();
	        $obj->parent_title = $this->getParentPage()->getTitle();
	        $obj->parent_formatted_title = $this->getParentPage()->getFormattedTitle();
	    }
	    
	    return json_encode($obj);
	    
	}
	
	public function offsetGet($offset){
	    
	    $offset = strtolower($offset);
	    
	    switch($offset){
	        
	        case "title":
	        return new SmartestString($this->getTitle());
	        
	        case "url":
	        return $this->getDefaultUrl();
	        
	        case "permalink":
	        return new SmartestExternalUrl('http://'.$this->getSite()->getDomain().$this->_request->getDomain().$this->getDefaultUrl());
	        
	        case "fallback_url":
	        return "website/renderPageFromId?page_id=".$this->getWebid();
	        
	        case 'date':
            return $this->getDate();
            break;
	        
	        case "urls":
	        return $this->getUrls();
	        
	        case "formatted_title":
	        return new SmartestString($this->getFormattedTitle());
	        
	        case "static_title":
	        return $this->_properties['title'];
	        
	        case "link_contents":
	        return $this->getLinkContents();
	        break;
	        
	        case "link_code":
	        return "[[page:".$this->getName()."]]";
	        
	        case "is_tag_page":
	        return $this->isTagPage();
	        
	        case "child_pages":
	        return $this->getPageChildrenForWeb();
	        
	        case "sibling_level_pages":
	        return $this->getParentPage()->getPageChildrenForWeb();
	        
	        case "parent":
			return $this->getParentPage();
	        
	        case "parent_level_pages":
			return $this->getGrandParentPage()->getPageChildrenForWeb();
	        
	        case "level":
	        return $this->getTreeLevel();
	        
	        case "tags":
	        return $this->getTags();
	        
	        case "tag_names":
	        return $this->getTagNames();
	        
	        case "tags_list":
	        return implode(', ', $this->getTags());
	        
	        case "authors":
	        return $this->getAuthors();
	        
	        case "authors_list":
	        return implode(', ', $this->getAuthors());
	        
	        case "cache_file":
	        return $this->getCacheFileName();
	        
	        case "small_icon":
            return $this->getSmallIcon();

            case "large_icon":
            return $this->getLargeIcon();

            case "label":
            return $this->getLabel();
            
            case "meta_keywords":
            return $this->getKeywords();
            
            case "is_search":
            return false;

            case "action_url":
            return $this->getActionUrl();
            
            case "modified":
            return new SmartestDateTime($this->_properties['modified']);
            
            case "placeholders":
            if(!$this->_placeholders) $this->loadAssetClassDefinitions();
            return $this->_placeholders;
            
            case "section":
            return $this->getSectionPage();
            
            case "empty":
            return !$this->getId();
            
            case "absolute_json_info_uri":
            case "absolute_json_info_url":
            return $this->getJsonInfoUrl(true);
            
            case "json_info_uri":
            case "json_info_url":
            return $this->getJsonInfoUrl();
            
	        
	    }
	    
	    if($this->getPageFieldDefinitions()->hasParameter($offset)){
	        return $this->getPageFieldDefinitions()->getParameter($offset);
	    }
	    
	    return parent::offsetGet($offset);
	    
	}
	
	public function getWorkflowStatus(){
	    
	    if($this->getIsPublished() == 'TRUE'){
	    
	        if($this->getModified() > $this->getLastPublished()){
	        
	            // page has changed since it was last published
	            if($this->getChangesApproved()){
	                return self::CHANGES_APPROVED;
	            }else{
	                return self::AWAITING_APPROVAL;
	            }
	        
	        }else{
	            // page hasn't been modified
	            return self::NOT_CHANGED;
	        }
	    
        }else{
            return self::NOT_PUBLISHED;
        }
	}
	
	public function getIsPublishedAsBoolean(){
	    
	    return ($this->getIsPublished() == 'TRUE') ? true : false;
	    
	}

	public function getOkParentPages(){
		
		//// CODE TO GET LIST OF PAGES THAT ARE ACCEPTABLE AS PARENTS
		//// FOR THE CURRENT PAGE. I.E. NOT ITSELF OR ANY OF ITS CHILDREN
		$site_id = $this->_properties['site_id'];
		
		// FIRST GET A LIST OF ALL PAGES
		$all_pages = $this->getParentSite()->getPagesList(true);
		
		$this->displayPages = array();
		$this->displayPagesIndex = 0;
		
		// THEN GET A LIST OF ALL CHILD PAGES
		
		$sub_pages_list = $this->getSerializedPageTree(1, false, false, '', true);
		
		$all_page_ids = array();
		$sub_page_ids = array();
		
		// MAKE A SIMPLE ARRAY OF ALL THE CHILD PAGE IDS
		foreach($sub_pages_list as $child_page_array){
			if(!in_array($child_page_array["info"]["id"], $sub_page_ids)){
				$sub_page_ids[] = $child_page_array["info"]["id"];
				// print_r($child_page_array);
			}
		}
		
		// REMOVE THOSE PAGES FROM THE MAIN LIST
		foreach($all_pages as $key=>$page_array){
			if(in_array($page_array["info"]["id"], $sub_page_ids) || $page_array["info"]["id"] == $this->_properties['id']){
				unset($all_pages[$key]);
			}
		}
		
		return $all_pages;
	}
	
	public function clearTags(){
	    $sql = "DELETE FROM TagsObjectsLookup WHERE taglookup_object_id='".$this->_properties['id']."' AND taglookup_type='SM_PAGE_TAG_LINK'";
	    $this->database->rawQuery($sql);
	}
	
	public function getTagIdsArray(){
	    
	    $sql = "SELECT taglookup_tag_id FROM TagsObjectsLookup WHERE taglookup_object_id='".$this->_properties['id']."' AND taglookup_type='SM_PAGE_TAG_LINK'";
	    $result = $this->database->queryToArray($sql);
	    $ids = array();
	    
	    foreach($result as $tl){
	        if(!in_array($ta['taglookup_tag_id'], $ids)){
	            $ids[] = $tl['taglookup_tag_id'];
	        }
	    }
	    
	    return $ids;
	    
	}
	
	public function getTags(){
	    
	    $sql = "SELECT * FROM Tags, TagsObjectsLookup WHERE TagsObjectsLookup.taglookup_tag_id=Tags.tag_id AND TagsObjectsLookup.taglookup_object_id='".$this->_properties['id']."' AND TagsObjectsLookup.taglookup_type='SM_PAGE_TAG_LINK' ORDER BY Tags.tag_name";
	    $result = $this->database->queryToArray($sql);
	    $ids = array();
	    $tags = array();
	    
	    foreach($result as $ta){
	        if(!in_array($ta['taglookup_tag_id'], $ids)){
	            $ids[] = $ta['taglookup_tag_id'];
	            $tag = new SmartestTag;
	            $tag->hydrate($ta);
	            $tags[] = $tag;
	        }
	    }
	    
	    // print_r($tags);
	    return $tags;
	    
	}
	
	public function getTagNames(){
	    
	    $tags = $this->getTags();
	    $names = array();
	    
	    foreach($tags as $t){
	        $names[] = $t->getName();
	    }
	    
	    return $names;
	    
	}
	
	public function getTagsAsArrays(){
	    
	    $arrays = array();
	    $tags = $this->getTags();
	    
	    foreach($tags as $t){
	        $arrays[] = $t->__toArray();
	    }
	    
	    return $arrays;
	    
	}
	
	public function tag($tag_identifier){
	    
	    if(is_numeric($tag_identifier)){
	        
	        $tag = new SmartestTag;
	        
	        if(!$tag->find($tag_identifier)){
	            // kill it of if they are supplying a numeric ID which doesn't match a tag
	            return false;
	        }
	        
	    }else{
	        
	        $tag_name = SmartestStringHelper::toSlug($tag_identifier);
	        
	        $tag = new SmartestTag;

    	    if(!$tag->hydrateBy('name', $tag_name)){
                // create tag
    	        $tag->setLabel($tag_identifier);
    	        $tag->setName($tag_name);
    	        $tag->save();
    	    }
	    }
	    
	    $sql = "INSERT INTO TagsObjectsLookup (taglookup_tag_id, taglookup_object_id, taglookup_type) VALUES ('".$tag->getId()."', '".$this->_properties['id']."', 'SM_PAGE_TAG_LINK')";
	    $this->database->rawQuery($sql);
	    return true;
	    
	}
	
	public function untag($tag_identifier){
	    
	    if(is_numeric($tag_identifier)){
	        
	        $tag = new SmartestTag;
	        
	        if(!$tag->hydrate($tag_identifier)){
	            // kill it off if they are supplying a numeric ID which doesn't match a tag
	            return false;
	        }
	        
	    }else{
	        
	        $tag_name = SmartestStringHelper::toSlug($tag_identifier);
	        
	        $tag = new SmartestTag;

    	    if(!$tag->hydrateBy('name', $tag_name)){
                return false;
    	    }
	    }
	    
	    $sql = "DELETE FROM TagsObjectsLookup WHERE taglookup_object_id='".$this->_properties['id']."' AND taglookup_tag_id='".$tag->getId()."' AND taglookup_type='SM_PAGE_TAG_LINK'";
	    $this->database->rawQuery($sql);
	    return true;
	    
	}
	
	public function isRelatedToPage($page_id){
	    
	}
	
	public function getRelatedPages(){
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_RELATED_PAGES');
	    $q->setCentralNodeId($this->_properties['id']);
	    $q->addSortField('Pages.page_title');
	    
	    $q->addForeignTableConstraint('Pages.page_type', 'NORMAL');
	    $q->addForeignTableConstraint('Pages.page_deleted', 'FALSE');
	    
	    if(!$this->getDraftMode()){
	        $q->addForeignTableConstraint('Pages.page_is_published', 'TRUE');
	    }
	    
	    $related_pages = $q->retrieve();
	    
	    return $related_pages;
	}
	
	public function getRelatedPagesAsArrays(){
	    
	    $pages = $this->getRelatedPages();
	    $arrays = array();
	    
	    foreach($pages as $page){
	        $arrays[] = $page->__toArray();
	    }
	    
	    return $arrays;
	    
	}
	
	public function getRelatedPageIds(){
	    
	    $pages = $this->getRelatedPages();
	    $ids = array();
	    
	    foreach($pages as $page){
	        $ids[] = $page->getId();
	    }
	    
	    return $ids;
	    
	}
	
	public function addRelatedPage($page_id){
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_RELATED_PAGES');
	    $q->createNetworkLinkBetween($this->_properties['id'], $page_id);
	}
	
	public function removeRelatedPage($page_id){
	    $page_id = (int) $page_id;
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_RELATED_PAGES');
	    $q->deleteNetworkLinkBetween($this->_properties['id'], $page_id);
	}
	
	public function removeAllRelatedPages(){
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_RELATED_PAGES');
	    $q->deleteNetworkNodeById($this->_properties['id']);
	}
	
	public function isRelatedToItem($page_id){
	    
	}
	
	public function getRelatedItems($model_id){
	    
	    $ids_array = $this->getRelatedItemIds($model_id);
	    
	    $model = new SmartestModel;
	    
	    if($model->find($model_id)){
	    
	        $ds = new SmartestSortableItemReferenceSet($model, $this->getDraftMode());
	    
	        foreach($ids_array as $item_id){
		        $ds->insertItemId($item_id);
		    }
	    
	        return new SmartestArray($ds->getItems());
	    
        }else{
            return array();
        }
	    
	}
	
	public function getRelatedSimpleItems($model_id=''){
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PAGES_ITEMS');
	    $q->setTargetEntityByIndex(1);
	    $q->addQualifyingEntityByIndex(2, $this->_properties['id']);
	    
	    $q->addForeignTableConstraint('Items.item_deleted', 1, SmartestQuery::NOT_EQUAL);
	    
	    if($model_id && (int) $model_id == $model_id){
	        $q->addForeignTableConstraint('Items.item_itemclass_id', $model_id);
	    }
	    
	    if(!$this->getDraftMode()){
	        $q->addForeignTableConstraint('Items.item_public', 'TRUE');
	    }
	    
	    $q->addSortField('Items.item_created');
	    
	    $result = $q->retrieve();
	    
	    return $result;
	    
	}
	
	public function getRelatedItemsAsArrays($model_id=''){
	    
	    $items = $this->getRelatedSimpleItems($model_id);
	    $arrays = array();
	    
	    foreach($items as $i){
	        $arrays[] = $i->__toArray(true);
	    }
	    
	    return $arrays;
	    
	}
	
	public function getRelatedItemIds($model_id=''){
	    
	    $items = $this->getRelatedSimpleItems($model_id);
	    $ids = array();
	    
	    foreach($items as $i){
	        $ids[] = $i->getId();
	    }
	    
	    return $ids;
	    
	}
	
	public function addRelatedItem($item_id){
	    
	    $item_id = (int) $item_id;
	    
	    $link = new SmartestManyToManyLookup;
	    $link->setEntityForeignKeyValue(2, $this->_properties['id']);
	    $link->setEntityForeignKeyValue(1, $item_id);
	    $link->setType('SM_MTMLOOKUP_PAGES_ITEMS');
	    
	    $link->save();
	}
	
	public function removeRelatedItem($item_id){
	    
	    $item_id = (int) $item_id;
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PAGES_ITEMS');
	    $q->setTargetEntityByIndex(1);
	    $q->addQualifyingEntityByIndex(2, $this->_properties['id']);
	    $q->addForeignTableConstraint('Items.item_id', $item_id);
	    
	    $q->delete();
	}
	
	public function removeAllRelatedItems($model_id=''){
	    
	    $model_id = (int) $model_id;
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PAGES_ITEMS');
	    $q->setTargetEntityByIndex(1);
	    $q->addQualifyingEntityByIndex(2, $this->_properties['id']);
	    
	    if($model_id > 0){
	        $q->addForeignTableConstraint('Items.item_itemclass_id', $model_id);
        }
        
	    $q->delete();
	}
	
	public function getNavigationStructure(){
		// echo $this->getId();
		$home_page_id = $this->getParentSite()->getTopPageId();
		$home_page = new SmartestPage;
		$home_page->hydrate($home_page_id);
		$home_page->setDraftMode($this->getDraftMode());
		
		/* if(!$this->isHomePage()){
		    $this->getGrandParentPage();
		} */
		
		$data = new SmartestParameterHolder('Page Navigation Structure');
		
		if($this->isHomePage()){
		    $data->setParameter('parent', null);
		    $data->setParameter('section', $this);
		    $data->setParameter('_breadcrumb_trail', array($this));
		    $data->setParameter('sibling_level_pages', array());
		    $data->setParameter('parent_level_pages', array());
		}else{
		    $data->setParameter('parent', $this->getParentPage());
    		$data->setParameter('section', $this->getSectionPage());
    		$data->setParameter('_breadcrumb_trail', $this->getPageBreadCrumbs());
		    $data->setParameter('sibling_level_pages', $this->getParentPage($this->getDraftMode())->getPageChildrenForWeb());
		    
		    if($this->getParentPage()->isHomePage()){
		        $data->setParameter('parent_level_pages', array($this->getParentPage($this->getDraftMode())));
		    }else{
		        $data->setParameter('parent_level_pages', $this->getGrandParentPage($this->getDraftMode())->getPageChildrenForWeb());
	        }
	    }
	    
		$data->setParameter('child_pages', $this->getPageChildrenForWeb());
		$data->setParameter('main_sections', $home_page->getPageChildrenForWeb(true, $this->getDraftMode()));
		$data->setParameter('related', $this->getRelatedContentForRender());
		
		return $data;
		
	}
	
	public function loadAssetClassDefinitions(){
	    
	    if($this->getDraftMode()){
	        $sql = "SELECT * FROM Assets, AssetClasses, AssetIdentifiers WHERE AssetIdentifiers.assetidentifier_assetclass_id=AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_item_id IS NULL AND AssetIdentifiers.assetidentifier_page_id='".$this->_properties['id']."' AND AssetIdentifiers.assetidentifier_draft_asset_id=Assets.asset_id";
        }else{
            $sql = "SELECT * FROM Assets, AssetClasses, AssetIdentifiers WHERE AssetIdentifiers.assetidentifier_assetclass_id=AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_item_id IS NULL AND AssetIdentifiers.assetidentifier_page_id='".$this->_properties['id']."' AND AssetIdentifiers.assetidentifier_live_asset_id=Assets.asset_id";
        }
        
        $this->_placeholders = new SmartestParameterHolder("Placeholder definitions for page '".$this->getTitle()."'");
        
        $result = $this->database->queryToArray($sql);
        
        foreach($result as $def_array){
            if($def_array['assetclass_type'] == 'SM_ASSETCLASS_CONTAINER'){
                $def = new SmartestContainerDefinition;
                $def->hydrateFromGiantArray($def_array);
                $this->_containers[$def_array['assetclass_name']] = $def;
            }else{
                $def = new SmartestPlaceholderDefinition;
                $def->hydrateFromGiantArray($def_array);
                // $this->_placeholders[$def_array['assetclass_name']] = $def;
                $this->_placeholders->setParameter($def_array['assetclass_name'], $def);
            }
        }
	    
	}
	
	public function loadItemSpaceDefinitions(){
	    
	    if($this->getDraftMode()){
	        $sql = "SELECT * FROM Items, AssetClasses, AssetIdentifiers WHERE AssetIdentifiers.assetidentifier_assetclass_id=AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_page_id='".$this->_properties['id']."' AND AssetIdentifiers.assetidentifier_draft_asset_id=Items.item_id";
	    }else{
	        $sql = "SELECT * FROM Items, AssetClasses, AssetIdentifiers WHERE AssetIdentifiers.assetidentifier_assetclass_id=AssetClasses.assetclass_id AND AssetIdentifiers.assetidentifier_page_id='".$this->_properties['id']."' AND AssetIdentifiers.assetidentifier_live_asset_id=Items.item_id";
	    }
	    
	    $result = $this->database->queryToArray($sql);
	    
	    foreach($result as $def_array){
            $def = new SmartestItemSpaceDefinition;
            $def->hydrateFromGiantArray($def_array);
            $this->_itemspaces[$def_array['assetclass_name']] = $def;
        }
	    
	}
	
	public function hasContainerDefinition($container_name){
	    
	    return array_key_exists($container_name, $this->_containers);
	    
	}
	
	public function hasPlaceholderDefinition($placeholder_name){
	    
	    // return array_key_exists($placeholder_name, $this->_placeholders);
	    return $this->_placeholders->hasParameter($placeholder_name);
	    
	}
	
	public function hasItemSpaceDefinition($itemspace_name){
	    
	    return array_key_exists($itemspace_name, $this->_itemspaces);
	    
	}
	
	public function getItemSpaceDefinitionNames(){
	    return array_keys($this->_itemspaces);
	}
	
	//// Authors and page credit
	
	public function getAuthors(){
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PAGE_AUTHORS');
	    $q->setTargetEntityByIndex(1);
	    $q->addQualifyingEntityByIndex(2, $this->_properties['id']);
	    
	    $q->addSortField('Users.user_lastname');
	    
	    $result = $q->retrieve();
	    
	    return $result;
	    
	}
	
	public function getAuthorsAsArrays(){
	    
	    $authors = $this->getAuthors();
	    $arrays = array();
	    
	    foreach($authors as $a){
	        $arrays[] = $a->__toArray();
	    }
	    
	    return $arrays;
	    
	}
	
	public function getAuthorIds(){
	    
	    $authors = $this->getAuthors();
	    $ids = array();
	    
	    foreach($authors as $a){
	        $ids[] = $a->getId();
	    }
	    
	    return $ids;
	    
	}
	
	public function addAuthorById($user_id){
	    
	    $user_id = (int) $user_id;
	    
	    $link = new SmartestManyToManyLookup;
	    $link->setEntityForeignKeyValue(2, $this->_properties['id']);
	    $link->setEntityForeignKeyValue(1, $user_id);
	    $link->setType('SM_MTMLOOKUP_PAGE_AUTHORS');
	    
	    $link->save();
	    
	}
	
	public function removeAuthorById($user_id){
	    
	    $user_id = (int) $user_id;
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PAGE_AUTHORS');
	    $q->setTargetEntityByIndex(1);
	    $q->addQualifyingEntityByIndex(2, $this->_properties['id']);
	    $q->addForeignTableConstraint('Users.user_id', $user_id);
	    
	    $q->delete();
	    
	}
	
	public function getContainerDefinition($container_name){
	    
	    if(array_key_exists($container_name, $this->_containers)){
	        
	        $container = $this->_containers[$container_name];
	        return $container;
	        
	    }else{
	    
	        $container = new SmartestContainerDefinition;
            $container->load($container_name, $this, $this->getDraftMode());
            return $container;
        
        }
	    
	}
	
	public function getContainerDefinitionNames(){
	    return array_keys($this->_containers);
	}
	
	public function getContainerDefinitions(){
	    return $this->_containers;
	}
	
	public function getPlaceholderDefinition($placeholder_name){
	    
	    if($this->_placeholders->getParameter($placeholder_name)){
	        
	        // $placeholder = $this->_placeholders[$placeholder_name];
	        return $this->_placeholders->getParameter($placeholder_name);
	        
	    }else{
	    
	        $placeholder = new SmartestPlaceholderDefinition;
            $placeholder->load($placeholder_name, $this, $this->getDraftMode());
            return $placeholder;
        
        }
	    
	}
	
	public function getPlaceholderDefinitionNames(){
	    return array_keys($this->_placeholders);
	}
	
	public function getItemSpaceDefinition($itemspace_name){
	    
	    if(array_key_exists($itemspace_name, $this->_itemspaces)){
	        
	        $itemspace = $this->_itemspaces[$itemspace_name];
	        return $itemspace;
	        
	    }else{
	    
	        $itemspace = new SmartestItemSpaceDefinition;
            $itemspace->load($itemspace_name, $this, $this->getDraftMode());
            return $itemspace;
        
        }
	    
	}
	
	public function getRelatedContentForRender(){
	    
	    $data = new SmartestParameterHolder('Related Content');
	    
	    $du = new SmartestDataUtility;
        $models = $du->getModels(false, $this->_properties['site_id']);
    
        foreach($models as $m){
            $key = SmartestStringHelper::toVarName($m->getPluralName());
            $data->setParameter($key, $this->getRelatedItems($m->getId()));
        }
        
        $data->setParameter('pages', $this->getRelatedPages());
        
        return $data;
	}
	
	public function getPageBreadCrumbs(){
		
		$helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getParentSite()->getId());
		
		$home_page = $this->getParentSite()->getHomePage($this->getDraftMode());
		$breadcrumbs = array();
		
		$limit = self::HIERARCHY_DEPTH_LIMIT;
		
		$page = &$this;
		
		$breadcrumb_index = 0;
		
		while($home_page->getId() != $page->getId() && $limit > 0){
		    
		    $page->setDraftMode($this->getDraftMode());
		    $breadcrumbs[] = $page;
			$page = $page->getParentPage();
			
			$limit--;
			$breadcrumb_index++;
			
		}
		
		$this->_level = $breadcrumb_index;
		
		$breadcrumbs[] = $home_page;
		
		krsort($breadcrumbs);
		$result = array_values($breadcrumbs);
		
		return $result;
		
	}
	
	public function getPageBreadCrumbsAsArrays(){
		
		$home_page = $this->getParentSite()->getHomePage($this->getDraftMode());
		$breadcrumbs = array();
		
		$limit = self::HIERARCHY_DEPTH_LIMIT;
		
		$page_id = $this->_properties['id'];
		
		while($home_page->getId() != $page_id && $limit > 0){
			$page = new SmartestPage;
			$page->hydrate($page_id);
			$breadcrumbs[] = $page->__toArray();
			$page_id = $page->getParent();
			$limit--;
		}
		
		$breadcrumbs[] = $home_page->__toArray();
		
		krsort($breadcrumbs);
		$result = array_values($breadcrumbs);
		
		return $result;
		
	}
	
	public function getStrictUrl(){
	    
	    $home_page = $this->getParentSite()->getHomePage(true);
	    
		$breadcrumbs = array();
		$bits = array();
		
		$limit = self::HIERARCHY_DEPTH_LIMIT;
		
		$page = $this;
		
		if($this->getType() == 'ITEMCLASS'){
		    $bits[] = ':name';
		    
		    $model = new SmartestModel;
		    if($model->find($this->getDatasetId())){
		        // SmartestSession::get('__newPage')->setDatasetId($this->getRequestParameter('page_model'));
	        }
		    
		}
		
		while($limit > 0){
		    
		    $pp = $page->getParentPage();
		    $bits[] = SmartestStringHelper::toSlug($page->getTitleForStrictUrl());
		    
		    if($pp->getId() != $home_page->getId()){
		    
    		    $page = $pp;
			
		    }else{
		        
		        break;
		        
		    }
		    
		    $limit--;
			
		}
		
		krsort($bits);
		$result = array_values($bits);
	    
	    return implode('/', $bits).'.html';
	    
	}
	
	public function getTreeLevel(){
	    return $this->_level;
	}
	
	public function getSectionPage(){
	    
	    if(SmartestStringHelper::isFalse($this->getIsSection()) && !$this->isHomePage()){
	        
	        if(!$this->_section_page){
	            
	            $page = $this->getParentPage();
	            
	            $limit = self::HIERARCHY_DEPTH_LIMIT;
	            
	            while($limit > 0){
	                
	                if(SmartestStringHelper::toRealBool($page->isHomePage()) || SmartestStringHelper::toRealBool($page->getIsSection())){
	                    $section_page = $page;
	                    break;
	                }else{
	                    $page = $page->getParentPage();
	                }
	                
	                $limit--;
	            }
	            
	            $section_page->setDraftMode($this->getDraftMode());
	            $this->_section_page = $section_page;
	            return $section_page;
	            
	        }else{
	            return $this->_section_page;
	        }
        }else{
            return $this;
        }
        
	}
	
	public function getSite(){
	    
	    if(!SmartestPersistentObject::get('__current_host_site')){
	        $sql = "SELECT * FROM Sites WHERE site_id='".$this->_properties['site_id']."'";
	        $result = $this->database->queryToArray($sql);
	        $s = new SmartestSite;
	        $s->hydrate($result[0]);
	        SmartestPersistentObject::set('__current_host_site', $s);
	    }
	    
	    return SmartestPersistentObject::get('__current_host_site');
	    
	}
	
	public function getParentSite(){
	    
	    if(!$this->getSiteId()){
	        // echo "getParentSite() called without hydration.";
	        $e = new Exception;
	        echo nl2br($e->getTraceAsString());
	    }
	    
	    if(!$this->_parent_site){
	        
	        $sql = "SELECT * FROM Sites WHERE Sites.site_id='".$this->getSiteId()."'";
	        $result = $this->database->queryToArray($sql);
	        
	        if(count($result)){
                $s = new SmartestSite;
                $s->hydrate($result[0]);
                $this->_parent_site = $s;
            }
        }
        
        return $this->_parent_site;
        
	}
	
	public function getTitle(){
        return $this->_properties['title'];
    }
    
    public function getTitleForStrictUrl(){
        if($this->_properties['type'] == 'ITEMCLASS'){
            if(is_numeric($this->_properties['dataset_id'])){
                $model = new SmartestModel;
                if($model->find($this->_properties['dataset_id'])){
                    return $model->getName();
                }else{
                    return $this->_properties['title'];
                }
            }else{
                return $this->_properties['title'];
            }
        }else{
            return $this->_properties['title'];
        }
    }
	
	public function getFormattedTitle(){
		
		$t = $this->getTitle();
		
		$format = $this->getParentSite()->getTitleFormat();
		
		$title = str_replace('$page', $t, $format);
		
		$separator = $this->getParentSite()->getTitleFormatSeparator();
		
		if(SmartestStringHelper::isFalse($this->getIsSection())){
		    
		    // if the page is not a section page
		    $section_page = $this->getSectionPage();
		    
		    if($section_page->isHomePage()){
		        $title = preg_replace(SmartestStringHelper::toRegularExpression($separator.' $section', true), '', $title);
		    }else{
		        $title = preg_replace(SmartestStringHelper::toRegularExpression($separator.' $section', true), $separator.' '.$section_page->getTitle(), $title);
	        }
	        
		}else{
		    $title = preg_replace(SmartestStringHelper::toRegularExpression($separator.' $section', true), '', $title);
		}
		
		if($this->isTagPage() && is_object($this->_tag)){
		    $title .= ' '.$separator.' '.$this->_tag->getLabel();
	    }
	    
	    $title = str_replace('$site', $this->getParentSite()->getName(), $title);
	    
	    return $title;
	}
	
	public function getCacheFileName(){
	    
	    switch($this->getCacheInterval()){
	        
			case "MONTHLY":
			$page_cache_name = "site".$this->_properties['site_id']."_cms_page_".$this->_properties['id']."_m".date("m");
			break;
			
			case "DAILY":
			$page_cache_name = "site".$this->_properties['site_id']."_cms_page_".$this->_properties['id']."_m".date("m")."_d".date("d");
			break;
			
			case "HOURLY":
			$page_cache_name = "site".$this->_properties['site_id']."_cms_page_".$this->_properties['id']."_m".date("m")."_d".date("d")."_H".date("H");
			break;
			
			case "MINUTE":
			$page_cache_name = "site".$this->_properties['site_id']."_cms_page_".$this->_properties['id']."_m".date("m")."_d".date("d")."_H".date("H")."_i".date("i");
			break;
			
			case "SECOND":
			$page_cache_name = "site".$this->_properties['site_id']."_cms_page_".$this->_properties['id']."_m".date("m")."_d".date("d")."_H".date("H")."_i".date("i")."_s".date("s");
			break;
			
			case "PERMANENT":
			default:
			$page_cache_name = "site".$this->_properties['site_id']."_cms_page_".$this->_properties['id'];
			break;
			
		}
		
		if($this->getType() == "ITEMCLASS" && $this->_principal_item){
			$page_cache_name .= "__id".$this->_principal_item->getId();
		}
		
		return $page_cache_name.'.html';
		
	}
	
	public function hasPrincipalItem(){
	    return (($this instanceof SmartestItemPage) && is_object($this->_principal_item));
	}
	
	public function hasSimpleItem(){
	    return (($this instanceof SmartestItemPage) && is_object($this->_simple_item));
	}
	
	public function clearRecentlyEditedInstances($site_id, $user_id=''){
	    
	    $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_RECENTLY_EDITED_PAGES');
	    
	    $q->setTargetEntityByIndex(1);
	    
        $q->addQualifyingEntityByIndex(1, $this->getId());
        $q->addQualifyingEntityByIndex(3, (int) $site_id);
        
        if(is_numeric($user_id)){
            $q->addQualifyingEntityByIndex(2, $user_id);
        }
        
        $q->delete();
	    
	}
	
	// SmartestStorableValue methods
	
	public function getStorableFormat(){
	    return $this->getId();
	}
	
    public function hydrateFromStorableFormat($v){
        return $this->find($v);
    }
    
    // SmartestSubmittableValue methods
    
    public function hydrateFromFormData($v){
        return $this->find((int) $v);
    }
    
    public function renderInput($params){
        // TODO
    }
	
	// System UI calls
	
	public function getSmallIcon(){
	    
	    if($this->getType() == 'ITEMCLASS'){
	        return $this->_request->getDomain().'Resources/Icons/page_gear.png';
        }else{
            return $this->_request->getDomain().'Resources/Icons/page.png';
        }
	    
	}
	
	public function getLargeIcon(){
	    
	    
	    
	}
	
	public function getLabel(){
	    
	    return $this->getTitle();
	    
	}
	
	public function getActionUrl(){
	    
	    return $this->_request->getDomain().'websitemanager/openPage?page_id='.$this->getId();
	    
	}

}