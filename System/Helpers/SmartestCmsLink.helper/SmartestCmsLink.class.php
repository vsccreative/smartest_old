<?php

class SmartestCmsLink extends SmartestHelper{
    
    protected $_host_page;
    protected $_error_message;
    protected $_has_error = false;
    protected $_draft_mode = false;
    protected $_preview_mode = false;
    protected $database;
    protected $_destination_properties;
    protected $_destination;
    protected $_markup_attributes;
    protected $_render_data;
    protected $_request;
    protected $_hash = '';
    
    const PAGE = 1;
    const METAPAGE = 2;
    const IMAGE = 4;
    const DOWNLOAD = 8;
    const TAG = 16;
    const AUTHOR = 32;
    const EXTERNAL = 256;
    const MAILTO = 512;
    
    const ERROR_PAGE_NOT_FOUND = 1;
    const ERROR_ITEM_NOT_FOUND = 2;
    const ERROR_INVALID_DESTINATION = 4;
    
    public function __construct($destination_properties, $markup_attributes){
        
        $this->database = SmartestPersistentObject::get('db:main');
        $this->_request = SmartestPersistentObject::get('controller')->getCurrentRequest();
        
        if(is_array($destination_properties)){
            $ph = new SmartestParameterHolder("Link destination properties: ".$destination_properties['to']);
            $ph->loadArray($destination_properties);
            $this->_destination_properties = $ph;
        }else if($destination_properties instanceof SmartestParameterHolder){
            $this->_destination_properties = $destination_properties;
        }
        
        $this->_markup_attributes = $this->getSeparatedAttributes($markup_attributes)->getParameter('html');
        $this->_render_data = $this->getSeparatedAttributes($markup_attributes)->getParameter('other');
        
        // Give any HTML attributes passed by SmartestCmsLinkHelper a chance to be included
        $extra_markup_attributes = $this->getSeparatedAttributes($this->_destination_properties)->getParameter('html');
        $this->_markup_attributes->absorb($extra_markup_attributes);
        
        if($this->_destination_properties->hasParameter('hash')){
            $this->_hash = $this->_destination_properties->getParameter('hash');
        }
        
        if($this->_markup_attributes->hasParameter('hash')){
            $this->_hash = $this->_markup_attributes->getParameter('hash');
        }
        
        if($this->_destination_properties->getParameter('from_item')){
            
            $this->setDestinationFromProvidedItem($this->_destination_properties->getParameter('item'));
        
        }else if($this->_destination_properties->getParameter('from_page')){
            
            $this->setDestinationFromProvidedPage($this->_destination_properties->getParameter('page'));
            
        }else if($this->_destination_properties->getParameter('from_tag')){
            
            $this->setDestinationFromProvidedTag($this->_destination_properties->getParameter('tag'));
            
        }else if($this->_destination_properties->getParameter('from_author')){
          
            $this->setDestinationFromProvidedAuthor($this->_destination_properties->getParameter('author'));
        
        }else{
        
            $this->setTypeFromNameSpace($this->_destination_properties->getParameter('namespace'));
            $this->_loadDestination();
        
        }
        
    }
    
    ///// NEW API FUNCTIONS /////
    
    public function setTypeFromNamespace($ns){
        
        $ns = strtolower($ns);
        
        if(strlen($this->_destination_properties->getParameter('destination'))){
        
            $du = new SmartestDataUtility;
            $model_names = array_keys($du->getModelNamesLowercase());
        
            if(in_array($ns, array('page', 'metapage', 'item', 'image', 'img', 'download', 'dl', 'tag', 'tag_page', 'user', 'author', 'mailto'))){
            
                switch($ns){
                
                    case "page":
                    $this->setType(SM_LINK_TYPE_PAGE);
                    $this->addClass('sm-link-internal');
                    break;
                
                    case "metapage":
                    case "item":
                    $this->setType(SM_LINK_TYPE_METAPAGE);
                    $this->_destination_properties->setParameter('format', SM_LINK_FORMAT_AUTO);
                    $this->addClass('sm-link-internal');
                    break;
                
                    case "image":
                    case "img":
                    $this->setType(SM_LINK_TYPE_IMAGE);
                    break;
                
                    case "download":
                    case "dl":
                    $this->setType(SM_LINK_TYPE_DOWNLOAD);
                    $this->addClass('sm-link-download');
                    break;
                
                    case "tag":
                    case "tag_page":
                    $this->setType(SM_LINK_TYPE_TAG);
                    break;
                    
                    case "user":
                    case "author":
                    $this->setType(SM_LINK_TYPE_AUTHOR);
                    break;
                    
                    case "mailto":
                    $this->setType(SM_LINK_TYPE_MAILTO);
                    $this->addClass('sm-link-mailto');
                    break;
                    
                }
                
                $this->setNamespace($ns);
            
                return true;
            
            }else if(in_array($ns, $model_names)){

                $this->setType(SM_LINK_TYPE_METAPAGE);
                $this->setNamespace($ns);
                $this->addClass('sm-link-internal');
                $this->_destination_properties->setParameter('format', SM_LINK_FORMAT_USER);
            
                return true;
            
            }else{
            
                if(substr($this->_destination_properties->getParameter('destination'), 0, 4) == 'http'){
                    $this->setType(SM_LINK_TYPE_EXTERNAL);
                    $this->addClass('sm-link-external');
                    $this->_destination_properties->setParameter('format', SM_LINK_FORMAT_URL);
                    return true;
                }
            
            }
        
        }else{
            $this->error("Link could not be built. No destination given.");
            $this->setType(SM_LINK_TYPE_DUD);
            return false;
        }
        
    }
    
    public function getSeparatedAttributes($markup_attributes){
        
        if(is_array($markup_attributes)){
            $data = $markup_attributes;
        }else if($markup_attributes instanceof SmartestParameterHolder){
            $data = $markup_attributes->getParameters();
        }
        
        $allowed_attributes = array('title', 'id', 'name', 'style', 'class', 'target', 'rel', 'dir', 'accesskey', 'tabindex', 'lang', 'download');
        $deprecated_javascript_attributes = array('onclick', 'ondblclick', 'onmouseover', 'onmouseout');
        $html_attributes_array = array();
        $other_attributes_array = array();
        
        foreach($data as $name => $value){
            
            if(in_array($name, $deprecated_javascript_attributes)){
                $html_attributes_array[$name] = $value;
            }else if(in_array($name, $allowed_attributes)){
                // Make sure attributed supplied for display are XML friendly
                $html_attributes_array[$name] = SmartestStringHelper::toXmlEntities($value);
            }else if(substr($name, 0, 5) == 'data-'){
                // Custom non-visible HTML5 attributes
                $html_attributes_array[$name] = SmartestStringHelper::toXmlEntities($value);
            }else{
                $other_attributes_array[$name] = $value;
            }
            
        }
        
        $html_attributes = new SmartestParameterHolder("Link HTML Attributes");
        $html_attributes->loadArray($html_attributes_array);
        
        $other_attributes = new SmartestParameterHolder("Link Non-HTML Attributes");
        $other_attributes->loadArray($other_attributes_array);
        
        $ph = new SmartestParameterHolder("Separated Attributes");
        $ph->setParameter('html', $html_attributes);
        $ph->setParameter('other', $other_attributes);
        
        return $ph;
        
    }
    
    public function getMarkupAttributes(){
        return $this->_markup_attributes;
    }
    
    public function getMarkupAttribute($attribute_name){
        return $this->_markup_attributes->getParameter($attribute_name);
    }
    
    public function setMarkupAttribute($attribute_name, $attribute_value){
        return $this->_markup_attributes->setParameter($attribute_name, $attribute_value);
    }
    
    public function clearMarkupAttribute($attribute_name){
        return $this->_markup_attributes->clearParameter($attribute_name);
    }
    
    public function getDestinationProperties(){
        return $this->_destination_properties;
    }
    
    public function getDestinationProperty($property_name){
        return $this->_destination_properties->getParameter($property_name);
    }
    
    public function getRenderData(){
        return $this->_render_data;
    }
    
    public function getScope(){
        return $this->_destination_properties->getParameter('scope');
    }
    
    public function setType($type){
        return $this->_destination_properties->setParameter('type', (int) $type);
    }
    
    public function getType(){
        return $this->_destination_properties->getParameter('type');
    }
    
    public function setNamespace($ns){
        return $this->_destination_properties->setParameter('namespace', $ns);
    }
    
    public function getNamespace(){
        return $this->_destination_properties->getParameter('namespace');
    }
    
    public function setDestinationString($s){
        return $this->_destination_properties->setParameter('destination', $s);
    }
    
    public function getDestinationString(){
         return $this->_destination_properties->getParameter('destination');
    }
    
    public function setDestinationFromProvidedItem(SmartestCmsItem $item){
        
        $this->setType(SM_LINK_TYPE_METAPAGE);
        $this->setNamespace('metapage');
        $this->_destination_properties->setParameter('format', SM_LINK_FORMAT_AUTO);
        
        if(is_object($item->getMetaPage()) && $item->getMetaPage()->getId()){
            
            $d = $item->getMetaPage();
            $d->setPrincipalItem($item);
            $this->addClass('sm-link-internal');
        
            $this->_destination = $d;
        
        }else{
            return $this->error("A metapage was not found for the ".strtolower($item->getModel()->getName()).": '".$item->getName().'\'');
        }
        
    }
    
    public function setDestinationFromProvidedPage(SmartestPage $page){
        
        $this->setType(SM_LINK_TYPE_PAGE);
        $this->setNamespace('page');
        $this->_destination_properties->setParameter('format', SM_LINK_FORMAT_AUTO);
        $this->addClass('sm-link-internal');
        
        $this->_destination = $page;
        
    }
    
    public function setDestinationFromProvidedTag(SmartestTag $tag){
        
        $this->setType(SM_LINK_TYPE_TAG);
        $this->setNamespace('tag');
        $this->_destination_properties->setParameter('format', SM_LINK_FORMAT_AUTO);
        $this->addClass('sm-link-internal');
        
        $this->_destination = $tag;
        
    }
    
    public function setDestinationFromProvidedAuthor(SmartestUser $user){
        
        $this->setType(SM_LINK_TYPE_AUTHOR);
        $this->setNamespace('author');
        $this->_destination_properties->setParameter('format', SM_LINK_FORMAT_AUTO);
        $this->addClass('sm-link-internal');
        
        $this->_destination = $user;
        
    }
    
    public function getSiteId(){
        
        if(defined('SM_CMS_PAGE_SITE_ID')){
            return SM_CMS_PAGE_SITE_ID;
        }else if(SmartestSession::hasData('current_open_project')){
            return SmartestSession::get('current_open_project')->getId();
        }
        
    }
    
    public function getSite(){
        
        $s = new SmartestSite;
        
        if($s->find($this->getSiteId())){
            return $s;
        }
        
    }
    
    public function addClass($class_name){
        
        $classes = explode(' ', $this->getMarkupAttribute('class'));
        
        if(in_array($class_name, $classes)){
            // class was already applied
            return false;
        }else{
            // class was added
            $classes[] = $class_name;
            $this->setMarkupAttribute('class', trim(implode(' ', $classes)));
            return true;
        }
        
    }
    
    public function removeClass($class_name){
        
        $classes = explode(' ', $this->getMarkupAttribute('class'));
        
        if(in_array($class_name, $classes)){
            
            $key = array_search($class_name, $classes);
            unset($classes[$key]);
            if(count($classes)){
                $this->setMarkupAttribute('class', trim(implode(' ', $classes)));
                return true;
            }else{
                $this->clearMarkupAttribute('class');
            }
            
        }else{
            // class not applied
            return false;
        }
        
    }
    
    protected function _loadDestination(){
        
        switch($this->getType()){
            
            case SM_LINK_TYPE_PAGE:
            $d = new SmartestPage;
            
            $sql = "SELECT * FROM Pages WHERE page_".$this->_destination_properties->getParameter('page_ref_field_name')."='".$this->_destination_properties->getParameter('page_ref_field_value')."' AND page_site_id='".$this->getSiteId()."' AND page_type='NORMAL' AND page_deleted != 'TRUE'";
            $result = $this->database->queryToArray($sql);
            
            if(count($result)){
                $d->hydrate($result[0]);
                $this->_destination = $d;
            }else{
                return $this->error("The requested page was not found. (Link destination: ".$this->_destination_properties->getParameter('destination').')');
            }
            
            break;
            
            case SM_LINK_TYPE_METAPAGE:
            $d = new SmartestItemPage;
            
            if($this->_destination_properties->getParameter('format') == SM_LINK_FORMAT_AUTO){
                
                $sql = "SELECT * FROM Pages WHERE page_".$this->_destination_properties->getParameter('page_ref_field_name')."='".$this->_destination_properties->getParameter('page_ref_field_value')."' AND page_site_id='".$this->getSiteId()."' AND page_type='ITEMCLASS' AND page_deleted != 'TRUE'";
                $result = $this->database->queryToArray($sql);
                
                if(count($result)){
                    $d->hydrate($result[0]);
                    
                    if($this->_destination_properties->getParameter('item_ref_field_name') == 'name'){
                        $this->_destination_properties->setParameter('item_ref_field_name', 'slug');
                    }
                    
                    $sql = "SELECT * FROM Items WHERE item_".$this->_destination_properties->getParameter('item_ref_field_name')."='".$this->_destination_properties->getParameter('item_ref_field_value')."' AND item_site_id='".$this->getSiteId()."' AND item_itemclass_id='{$d->getDatasetId()}' AND item_deleted != '1'";
                    $result = $this->database->queryToArray($sql);
                    
                    if(count($result)){
                        $d->setPrincipalItem(SmartestCmsItem::retrieveByPk($result[0]['item_id']));
                        $this->_destination = $d;
                    }else{
                        return $this->error("The requested item was not found. (Link destination: ".$this->_destination_properties->getParameter('destination').')');
                    }
                
                }else{
                    
                    return $this->error("The requested page was not found. (Link destination: ".$this->_destination_properties->getParameter('destination').')');
                }
            
            }else if($this->_destination_properties->getParameter('format') == SM_LINK_FORMAT_USER){
                
                $du = new SmartestDataUtility;
                $model_names = $du->getModelNamesLowercase();
                $model_id = $model_names[$this->_destination_properties->getParameter('namespace')];
                
                // user-formatted wikipedia style links. start with the item, figure out the metapage, and go from there
                $sql = "SELECT Items.item_id, Items.item_slug, Items.item_webid, Items.item_itemclass_id, Items.item_site_id, Items.item_deleted, ItemClasses.itemclass_varname, ItemClasses.itemclass_name, ItemClasses.itemclass_id FROM Items, ItemClasses WHERE item_".$this->_destination_properties->getParameter('item_ref_field_name')."='".$this->_destination_properties->getParameter('item_ref_field_value')."' AND ItemClasses.itemclass_id='".$model_id."' AND Items.item_itemclass_id=ItemClasses.itemclass_id AND item_site_id='".constant('SM_CMS_PAGE_SITE_ID')."' AND item_deleted != '1'";
                $result = $this->database->queryToArray($sql);
                
                if(count($result)){
                    
                    $item = SmartestCmsItem::retrieveByPk($result[0]['item_id']);
                    
                    $sql = "SELECT * FROM Pages WHERE page_id='".$item->getMetaPageId()."' AND page_site_id='".constant('SM_CMS_PAGE_SITE_ID')."' AND page_type='ITEMCLASS' AND page_deleted != 'TRUE'";
                    $result = $this->database->queryToArray($sql);
                    
                    if(count($result)){
                    
                        $d->hydrate($result[0]);
                        $d->setPrincipalItem($item);
                    
                        $this->_destination = $d;
                    
                    }else{
                        return $this->error("The requested page was not found. (Link destination: ".$this->_destination_properties->getParameter('destination').')');
                    }
                    
                }else{
                    return $this->error("The requested item was not found. (Link destination: ".$this->_destination_properties->getParameter('destination').')');
                }
                
            }
            
            break;
            
            case SM_LINK_TYPE_IMAGE:
            $d = new SmartestAsset;
            $d->hydrateBy('url', $this->_destination_properties->getParameter('filename'));
            $this->_markup_attributes->setParameter('type', $d->getMimeType());
            $this->_destination = $d;
            break;
            
            case SM_LINK_TYPE_DOWNLOAD:
            $d = new SmartestAsset;
            $d->hydrateBy('url', $this->_destination_properties->getParameter('filename'));
            $mime_type = $d->getMimeType();
            
            if($mime_type){
                $this->_markup_attributes->setParameter('type', $mime_type);
            }
            
            $this->_destination = $d;
            break;
            
            case SM_LINK_TYPE_TAG:
            $d = new SmartestTag;
            $d->hydrateBy('name', $this->_destination_properties->getParameter('tag_name'));
            $this->_destination = $d;
            break;
            
            case SM_LINK_TYPE_AUTHOR:
            $d = new SmartestUser;
            $d->hydrateBy('username', $this->_destination_properties->getParameter('username'));
            $this->_destination = $d;
            break;
            
            case SM_LINK_TYPE_MAILTO:
            $d = new SmartestString($this->_destination_properties->getParameter('destination'));
            $this->_destination = $d;
            break;
            
        }
        
    }
    
    public function getDestination(){
        return $this->_destination;
    }
    
    public function setHostPage($p){
        $this->_host_page = $p;
    }
    
    public function getHostPage(){
        return $this->_host_page;
    }
    
    public function hasHostPage(){
        return (($this->_host_page instanceof SmartestPage) && is_numeric($this->_host_page->getId()));
    }
    
    public function error($message){
        $this->_has_error = true;
        $this->_error_message = $message;
    }
    
    public function hasError(){
        return $this->_has_error;
    }
    
    public function getError(){
        return $this->_error_message;
    }
    
    ///// END NEW API FUNCTIONS /////
    
    public function shouldOmitAnchorTag($draft_mode=false){
        // return !$this->_preview_mode && ($this->isInternalPage() && $this->shouldGoCold() && is_object($this->_host_page) && $this->_page->getId() == $this->_host_page->getId());
        if(!$this->_destination_properties->getParameter('from_item') && !$this->_destination_properties->getParameter('from_page') && !$this->_destination_properties->getParameter('from_tag') && !$this->_destination_properties->getParameter('from_author') && (!$this->_destination_properties->getParameter('destination') || $this->_destination_properties->getParameter('destination') == '#')){
            return true;
        }else{
            if($this->getHostPage()){
                if($this->getType() == SM_LINK_TYPE_PAGE){
                    if(($this->_destination->getId() == $this->getHostPage()->getId()) && $this->shouldGoCold()){
                        return true;
                    }else{
                        if($draft_mode || $this->_destination->getIsPublished()){
                            return false;
                        }else{
                            return true;
                        }
                    }
                }else if($this->getType() == SM_LINK_TYPE_METAPAGE){
                    if(($this->_destination->getId() == $this->getHostPage()->getId()) && ($this->_destination->getSimpleItem()->getId() == $this->getHostPage()->getSimpleItem()->getId()) && $this->shouldGoCold()){
                        return true;
                    }else{
                        if($draft_mode || ($this->_destination->getIsPublished() && $this->_destination->getSimpleItem()->getIsPublished())){
                            return false;
                        }else{
                            return true;
                        }
                    }
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }
    }
    
    public function shouldGoCold(){
        return (isset($this->_render_data['goCold']) && !SmartestStringHelper::isFalse($this->_render_data['goCold']));
    }
    
    public function shouldUseId(){
        return (isset($this->_render_data['byId']) && !SmartestStringHelper::isFalse($this->_render_data['byId']));
    }
    
    public function isInternalPage(){
        SmartestLog::getInstance('system')->log('Deprecated function used: SmartestCmsLink::isInternalPage()', SM_LOG_DEBUG);
        return in_array($this->getType(), array(SM_LINK_TYPE_PAGE, SM_LINK_TYPE_METAPAGE));
    }
    
    public function isInternal(){
        return !in_array($this->getType(), array(SM_LINK_TYPE_EXTERNAL));
    }
    
    public function getErrorMessage(){
        return $this->_error_message;
    }
    
    public function setImageAsContent(SmartestImage $img){
        
        $this->_render_data->setParameter('with', $img);
        
    }
    
    public function getContent($draft_mode=false){
        
        if($this->_render_data->hasParameter('with')){
            // if the with="" attribute is specified
            
            if($this->_render_data->getParameter('with') instanceof SmartestImage || ($this->_render_data->getParameter('with') instanceof SmartestAsset && $this->_render_data->getParameter('with')->isImage()) || substr($this->_render_data->getParameter('with'), 0, 6) == 'image:'){
                // return $this->_render_data->getParameter('with')->render();
                
                /* $a = new SmartestRenderableAsset;
                $a->findBy('url', substr($this->_render_data->getParameter('with'), 6));
                
                if($this->_render_data->hasParameter('alt')){
                    $a->setAdditionalRenderData(array('alt_text'=>$this->_render_data->getParameter('alt')));
                } */
                
                // return $a->render($draft_mode);
								
								if($this->_render_data->getParameter('with') instanceof SmartestImage){
                    
                    $img = $this->_render_data->getParameter('with');
                
                }else if($this->_render_data->getParameter('with') instanceof SmartestAsset){
                    
                    $img = $this->_render_data->getParameter('with')->getImage();
                
                }else{
                
                    $img = new SmartestImage;
                
                    if(!$img->loadFile(SM_ROOT_DIR.'Public/Resources/Images/'.substr($this->_render_data->getParameter('with'), 6))){
                    
                        // Image not recognised - error
                        return;
                    
                    }
                    
                }
                
				// print_r($this->_render_data->getParameter('img_scale_ratio'));
				
                if(is_numeric($this->_render_data->getParameter('img_width')) && is_numeric($this->_render_data->getParameter('img_height'))){
                    
					if(!$this->_render_data->hasParameter('create_resized_image') || SmartestStringHelper::toRealBool($this->_render_data->getParameter('create_resized_image'))){
	                    
						if($this->_render_data->hasParameter('img_scale') && !SmartestStringHelper::toRealBool($this->_render_data->getParameter('img_scale'))){
	                        $img = $img->getResizedVersionNoScale($this->_render_data->getParameter('img_width'), $this->_render_data->getParameter('img_height'));
	                    }else if($this->_render_data->hasParameter('img_scale') && $this->_render_data->getParameter('img_scale') == 'constrain'){
	                        $img = $img->getConstrainedVersionWithin($this->_render_data->getParameter('img_width'), $this->_render_data->getParameter('img_height'));
	                    }else{
	                        $img = $img->resizeAndCrop($this->_render_data->getParameter('img_width'), $this->_render_data->getParameter('img_height'));
	                    }
						
					}else{
						
						// if($this->_render_data->hasParameter('img_width')){
							$img->setMarkupRenderWidth($this->_render_data->getParameter('img_width'));
						// }
						
						// if($this->_render_data->hasParameter('img_height')){
							$img->setMarkupRenderHeight($this->_render_data->getParameter('img_height'));
						// }
					}
                    
                }else if(is_numeric($this->_render_data->getParameter('img_width'))){
                    
					if(!$this->_render_data->hasParameter('create_resized_image') || SmartestStringHelper::toRealBool($this->_render_data->getParameter('create_resized_image'))){
					
                    	if($this->_render_data->getParameter('img_square') && SmartestStringHelper::toRealBool($this->_render_data->getParameter('img_square'))){
                    	    $img = $img->getSquareVersion($this->_render_data->getParameter('img_width'));
                    	}else{
                    	    $img = $img->restrictToWidth($this->_render_data->getParameter('img_width'));
                    	}
					
					}else{
						
						$actual_height = $img->getHeight();
						$new_height = (int) ($actual_height*$this->_render_data->getParameter('img_width')/$img->getWidth());
						$img->setMarkupRenderWidth($this->_render_data->getParameter('img_width'));
						$img->setMarkupRenderHeight($new_height);
						
					}
                    
                }else if(is_numeric($this->_render_data->getParameter('img_height'))){
                    
					if(!$this->_render_data->hasParameter('create_resized_image') || SmartestStringHelper::toRealBool($this->_render_data->getParameter('create_resized_image'))){
					
	                    if($this->_render_data->getParameter('img_square') && SmartestStringHelper::toRealBool($this->_render_data->getParameter('img_square'))){
	                        $img = $img->getSquareVersion($this->_render_data->getParameter('img_height'));
	                    }else{
	                        $img = $img->restrictToHeight($this->_render_data->getParameter('img_height'));
	                    }
					
					}else{
						
						$actual_width = $img->getWidth();
						$new_width = (int) ($actual_width*$this->_render_data->getParameter('img_height')/$img->getHeight());
						$img->setMarkupRenderHeight($this->_render_data->getParameter('img_height'));
						$img->setMarkupRenderWidth($new_width);
						
					}
					
                }else if($this->_render_data->hasParameter('img_scale_ratio')){
                	
					if(strpos($this->_render_data->getParameter('img_scale_ratio'), '%') !== false){
						preg_match('/^(\d+)%$/', $this->_render_data->getParameter('img_scale_ratio'), $scalematches);
						$percentage = $scalematches[1];
						$ratio = $percentage/100;
					}else if(is_numeric($this->_render_data->hasParameter('img_scale_ratio'))){
						$ratio = $this->_render_data->getParameter('img_scale_ratio');
					}
					
					$actual_height = $img->getHeight();
					$actual_width = $img->getWidth();
					
					$new_width = $actual_width*$ratio;
					$new_height = $actual_height*$ratio;
					
					if(!$this->_render_data->hasParameter('create_resized_image') || SmartestStringHelper::toRealBool($this->_render_data->getParameter('create_resized_image'))){
						
						$img = $img->resizeAndCrop($new_width, $new_height);
						
					}else{
						
						$img->setMarkupRenderWidth($new_width);
						$img->setMarkupRenderHeight($new_height);
						
					}
					
                }
                
                if($this->_render_data->hasParameter('img_alt')){
                    $img->setAltText($this->_render_data->getParameter('img_alt'));
                }
                
                if($this->_render_data->hasParameter('alt')){
                    $img->setAltText($this->_render_data->getParameter('alt'));
                }
            
                return $img->render();
                
            }else{
							
				$fa_prefix = $this->_render_data->hasParameter('fa_iconname') ? '<i class="fa fa-'.SmartestStringHelper::toSlug($this->_render_data->getParameter('fa_iconname')).'"> </i>' : '';
				return $fa_prefix.$this->_render_data->getParameter('with');
                
            }
            
        }else if($this->_destination_properties->getParameter('text') && ($this->_destination_properties->getParameter('text') != SmartestLinkParser::LINK_TARGET_TITLE)){
            // if the text is given in the link via a pipe (|)
            return $this->_destination_properties->getParameter('text');
        }else{
            // otherwise guess
            
			$fa_prefix = $this->_render_data->hasParameter('fa_iconname') ? '<i class="fa fa-'.SmartestStringHelper::toSlug($this->_render_data->getParameter('fa_iconname')).'"> </i>' : '';
						
            if($this->getType() == SM_LINK_TYPE_EXTERNAL){
                
				if($this->_render_data->hasParameter('hide_protocol') && SmartestStringHelper::toRealBool($this->_render_data->hasParameter('hide_protocol'))){
                    return $fa_prefix.SmartestStringHelper::toUrlStringWithoutProtocol($this->_destination_properties->getParameter('destination'));
                }else{
                    return $fa_prefix.$this->_destination_properties->getParameter('destination');
                }
                
            }else{
                
                if($this->hasError()){
                    
                    return null;
                    
                }else{
                    
                    switch($this->getType()){

                        case SM_LINK_TYPE_PAGE:
                        return $fa_prefix.SmartestStringHelper::toXmlEntities($this->_destination->getTitle());
                        break;

                        case SM_LINK_TYPE_METAPAGE:
                
                        if($this->_destination->getForceStaticTitle() == 1){
                            return $fa_prefix.SmartestStringHelper::toXmlEntities($this->_destination->getTitle(true));
                        }else{
                            return $fa_prefix.SmartestStringHelper::toXmlEntities($this->_destination->getTitle());
                        }
                
                        break;

                        case SM_LINK_TYPE_IMAGE:
                        return $this->_destination;
                        break;
                
                        case SM_LINK_TYPE_TAG:
                        return $fa_prefix.SmartestStringHelper::toXmlEntities($this->_destination->getLabel());
                        break;
                        
                        case SM_LINK_TYPE_AUTHOR:
                        return $fa_prefix.SmartestStringHelper::toXmlEntities($this->_destination->getFullName());
                        break;
                
                        case SM_LINK_TYPE_DOWNLOAD:
                        return $fa_prefix.$this->_destination->getUrl();
                        break;
                        
                        case SM_LINK_TYPE_MAILTO:
                        return $fa_prefix.$this->_destination->toHtmlEncoded();
                        break;
                        
                    }
                
                }
            
            }
            
        }
        
    }
    
    public function getAbsoluteUrlObject(){
        // Returns a SmartestExternalUrl object pointing to the absolute uri of the link
        $url = 'http://'.$this->getSite()->getDomain().$this->getUrl(false, true);
        return new SmartestExternalUrl($url);
    }
    
    public function getUrl($draft_mode=false, $ignore_status=false){
        
        switch($this->getType()){

            case SM_LINK_TYPE_PAGE:
            
            if($draft_mode){
                if($this->_request->getRequestParameter('hide_newwin_link')){
                    $url = $this->_request->getDomain().'website/renderEditableDraftPage?page_id='.$this->_destination->getWebId().'&amp;hide_newwin_link=true';
                    if(strlen($this->_hash)){
                        $url .= '#'.$this->_hash;
                    }
                }else{
                    $url = $this->_request->getDomain().'websitemanager/preview?page_id='.$this->_destination->getWebId();
                    if(strlen($this->_hash)){
                        $url .= '&amp;hash='.$this->_hash;
                    }
                }
                return $url;
            }else{
                if($this->_destination->getIsPublishedAsBoolean() || $ignore_status){
                    /* if(defined('SM_LINK_URLS_ABSOLUTE') && constant('SM_LINK_URLS_ABSOLUTE')){
                        'http://'.$this->getSite()->getDomain().$this->_request->getDomain().$this->_destination->getDefaultUrl();
                    }else{ */
                        $url = $this->_request->getDomain().$this->_destination->getDefaultUrl();
                    // }
                    // var_dump($this->_hash);
                    if(strlen($this->_hash)){
                        $url .= '#'.$this->_hash;
                    }
                    return $url;
                }else{
                    return '#';
                }
            }
            
            break;

            case SM_LINK_TYPE_METAPAGE:
            
            if($draft_mode){ 
                if($this->_request->getRequestParameter('hide_newwin_link')){
                    $url = $this->_request->getDomain().'website/renderEditableDraftPage?page_id='.$this->_destination->getWebId().'&amp;hide_newwin_link=true&amp;item_id='.$this->_destination->getPrincipalItem()->getId();
                    if(strlen($this->_hash)){
                        $url .= '#'.$this->_hash;
                    }
                }else{
                    $url = $this->_request->getDomain().'websitemanager/preview?page_id='.$this->_destination->getWebId().'&amp;item_id='.$this->_destination->getPrincipalItem()->getId();
                    if(strlen($this->_hash)){
                        $url .= '&amp;hash='.$this->_hash;
                    }
                }
                
                return $url;
            }else{
                if(($this->_destination->getIsPublishedAsBoolean() && $this->_destination->getPrincipalItem()->isPublished()) || $ignore_status){
                    
                    $template_url = $this->_request->getDomain().$this->_destination->getDefaultUrl();
                    $url = str_replace(':id', $this->_destination->getPrincipalItem()->getId(), $template_url);
                    $url = str_replace(':long_id', $this->_destination->getPrincipalItem()->getWebid(), $url);
                    $url = str_replace(':name', $this->_destination->getPrincipalItem()->getSlug(), $url);
                    
                    if(strlen($this->_hash)){
                        $url .= '#'.$this->_hash;
                    }
                    
                    return $url;
                    
                }else{
                    return '#';
                }
            }
    
            break;

            case SM_LINK_TYPE_IMAGE:
            return $this->_destination->getWebUrl();
            break;
    
            case SM_LINK_TYPE_TAG:
            
            if($draft_mode){
                if($this->_request->getRequestParameter('hide_newwin_link')){
                    return $this->_request->getDomain().'website/renderEditableDraftPage?page_id='.$this->getSite()->getTagPage()->getWebId().'&amp;hide_newwin_link=true&amp;tag_name='.$this->_destination->getName();
                }else{
                    return $this->_request->getDomain().'websitemanager/preview?page_id='.$this->getSite()->getTagPage()->getWebId().'&amp;tag='.$this->_destination->getName();
                }
            }else{
                if($this->_destination){
                    return $this->_request->getDomain().'tags/'.$this->_destination->getName().'.html';
                }else{
                    return '#';
                }
            }
            
            break;
            
            case SM_LINK_TYPE_AUTHOR:
            
            if($draft_mode){
                if($this->_request->getRequestParameter('hide_newwin_link')){
                    return $this->_request->getDomain().'website/renderEditableDraftPage?page_id='.$this->getSite()->getUserPage()->getWebId().'&amp;hide_newwin_link=true&amp;author_id='.$this->_destination->getId();
                }else{
                    return $this->_request->getDomain().'websitemanager/preview?page_id='.$this->getSite()->getUserPage()->getWebId().'&amp;author_id='.$this->_destination->getId();
                }
            }else{
                if($this->_destination){
                    return $this->_request->getDomain().'author/'.$this->_destination->getUserName();
                }else{
                    return '#';
                }
            }
            
            break;
    
            case SM_LINK_TYPE_DOWNLOAD:
            // return $this->_request->getDomain().'download/'.urlencode($this->_destination->getUrl()).'?key='.$this->_destination->getWebid();
            return $this->_destination->getAbsoluteDownloadUri();
            break;
            
            case SM_LINK_TYPE_EXTERNAL:
            
            // TODO: Make this an option
            
            // if($this->_destination_properties->getParameter('newwin')){
            //    return "javascript:window.open('".$this->_destination_properties->getParameter('destination')."');";
            //}else{
                return $this->_destination_properties->getParameter('destination');
            // }
            
            break;
            
            case SM_LINK_TYPE_MAILTO:
            return "&#109;&#97;&#105;&#108;&#116;&#111;&#58;".$this->_destination->toHexUrlEncoded();
            
        }
        
    }
    
    public function render($draft_mode=false, $ama=''){
        
        if($this->getType() == SM_LINK_TYPE_EXTERNAL){
            
            if($draft_mode && !SmartestStringHelper::toRealBool($this->_destination_properties->getParameter('newwin'))){
                $this->_markup_attributes->setParameter('target', '_top');
                $this->_markup_attributes->setParameter('onclick', "return confirm('You will be taken to an external page. Continue?')");
            }else{
                if($this->_markup_attributes->getParameter('target') == '_blank' || $this->_markup_attributes->getParameter('target') == '_new'){
                    $this->_destination_properties->setParameter('newwin', true);
                }
            }
            
            if($this->_destination_properties->getParameter('newwin')){
                $this->_markup_attributes->setParameter('target', '_blank');
            }
            
            $this->_markup_attributes->setParameter('rel', 'external');
            
        }else if($this->getType() == SM_LINK_TYPE_TAG){
            
            $this->_markup_attributes->setParameter('rel', 'tag');
            
        }else if($this->getType() == SM_LINK_TYPE_AUTHOR){
            
            $this->_markup_attributes->setParameter('rel', 'author');
            
        }
        
        $url = $this->getUrl($draft_mode);
        $contents = $this->getContent();
        
        if($draft_mode && ($this->getType() == SM_LINK_TYPE_PAGE || $this->getType() == SM_LINK_TYPE_METAPAGE || $this->getType() == SM_LINK_TYPE_TAG || $this->getType() == SM_LINK_TYPE_AUTHOR) && $url != '#'){
            $this->_markup_attributes->setParameter('target', '_top');
        }
        
        $sm = new SmartyManager('BasicRenderer');
        $r = $sm->initialize($this->getDestinationString());
        $r->setDraftMode($draft_mode);
        
        if(is_array($ama)){
           $additional_markup_attributes = $this->getSeparatedAttributes($ama)->getParameter('html');
           $this->_markup_attributes->loadArray($additional_markup_attributes);
        }
        
        if(($this->getType() == SM_LINK_TYPE_PAGE || $this->getType() == SM_LINK_TYPE_METAPAGE) && !$this->_markup_attributes->hasParameter('title')){
            // Make sure that any title added automatically won't break well-formed markup
            $this->_markup_attributes->setParameter('title', SmartestStringHelper::toXmlEntities($this->_destination->getTitle()));
        }
        
        $content = $r->renderLink($this);
	    
	    return $content;
        
    }
    
}