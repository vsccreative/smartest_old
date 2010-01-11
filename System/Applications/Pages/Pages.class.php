<?php

/**
 *
 * PHP versions 4,5
 *
 * @category   WebApplication
 * @package    Smartest
 * @subpackage Pages
 * @author     Marcus Gilroy-Ware <marcus@visudo.com>
 * @author     Eddie Tejeda <eddie@visudo.com>
 */
 
// include_once "Managers/AssetsManager.class.php";
include_once "Managers/SetsManager.class.php";
include_once "Managers/TemplatesManager.class.php";
include_once "System/Applications/MetaData/MetaDataManager.class.php";

class Pages extends SmartestSystemApplication{
	
	protected $setsManager;
	protected $templatesManager;
	protected $propertiesManager;
	
	function __moduleConstruct(){
		$this->setsManager = new SetsManager;
		$this->templatesManager = new TemplatesManager;
		// $this->propertiesManager = new PagePropertiesManager;
		// var_dump($this);
	}
	
	public function startPage(){
		// No code is needed here, just a function definition
		$this->setTitle("Welcome to Smartest");
	}
	
	public function openPage($get){
	    
	    if(@$get['page_id']){
	        
	        $page = new SmartestPage;
	        
	        if($page->hydrate($get['page_id'])){
	            
	            $page->setDraftMode(true);
	            
	            if($this->getUser()->hasToken('modify_page_properties')){
	            
	                if($page->getIsHeld() && $page->getHeldBy() && $page->getHeldBy() != $this->getUser()->getId() && !$this->getUser()->hasToken('edit_held_pages')){
    	                
    	                // page is already being edited by another user
    	                $editing_user = new SmartestUser;
	                    
    	                if($editing_user->hydrate($page->getHeldBy())){
    	                    $this->addUserMessageToNextRequest($editing_user->__toString().' is already editing this page.', SmartestUserMessage::ACCESS_DENIED);
    	                }else{
    	                    $this->addUserMessageToNextRequest('Another user is already editing this page.', SmartestUserMessage::ACCESS_DENIED);
    	                }
	                
    	                $this->redirect('/smartest/pages');
    	                
    	                $this->send($page->__toArray(), 'page');
    	                
    	            }else{
	                    
	                    // page is available to edit
			            SmartestSession::set('current_open_page', $page->getId());
			            
			            if($page->getIsHeld() && $this->getUser()->hasToken('edit_held_pages') && $page->getHeldBy() != $this->getUser()->getId()){
			                
			                $editing_user = new SmartestUser;
                            
                            if($editing_user->hydrate($page->getHeldBy())){
        	                    $this->addUserMessageToNextRequest('Careful: '.$editing_user->__toString().' has not yet released this page.', SmartestUserMessage::INFO);
        	                }else{
        	                    $this->addUserMessageToNextRequest('Careful: another user has not yet released this page.', SmartestUserMessage::INFO);
        	                }
        	                
			            }else{
			                // lock it against being edited by other people
    			            $page->setIsHeld(1);
    			            $page->setHeldBy($this->getUser()->getId());
    			            $page->save();
			            
    			            if(!$this->getUser()->hasTodo('SM_TODOITEMTYPE_RELEASE_PAGE', $page->getId())){
    			                $this->getUser()->assignTodo('SM_TODOITEMTYPE_RELEASE_PAGE', $page->getId(), 0);
    		                }
	                    }
			            
			            $page->clearRecentlyEditedInstances($this->getSite()->getId(), $this->getUser()->getId());
        			    $this->getUser()->addRecentlyEditedPageById($page->getId(), $this->getSite()->getId());
		            
			            $this->redirect('/'.SM_CONTROLLER_MODULE.'/editPage?page_id='.$page->getWebid());
    			        
    		        }
		        
	            }else{
	                
	                $this->addUserMessageToNextRequest('You don\'t have permission to edit pages.', SmartestUserMessage::ACCESS_DENIED);
	                
	                if(SmartestSession::hasData('current_open_project')){
	                    $this->redirect('/smartest/pages');
                    }else{
                        $this->redirect('/smartest');
                    }
	                
	            }
		        
		    }else{
		        $this->redirect('/smartest');
		    }
		}
	}
	
	public function closeCurrentPage($get){
	    
	    if(isset($get['release']) && $get['release'] == 1){
	        $page = new SmartestPage;
	        
	        if($page->hydrate(SmartestSession::get('current_open_page'))){
	            $page->setIsHeld(0);
	            $page->setHeldBy('');
	            $page->save();
	        }
	    }
	    
	    SmartestSession::clear('current_open_page');
	    $this->redirect('/smartest/pages');
	}
	
	public function releasePage($get){
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($get['page_id'])){
	        
	        $page->setDraftMode(true);
	        
	        if($page->getIsHeld() == '1'){
	            
	            if($page->getHeldBy() == $this->getUser()->getId()){
                    
                    $page->setIsHeld(0);
                    $page->setHeldBy('');
                    $page->save();
                    $this->addUserMessageToNextRequest("The page has been released.", SmartestUserMessage::SUCCESS);
                    
                    if($todo = $this->getUser()->getTodo('SM_TODOITEMTYPE_RELEASE_PAGE', $page->getId())){
		                $todo->complete();
	                }
	                
                }else{
                    //  the page is being edited by another user
                    $this->addUserMessageToNextRequest("You can't release this page because another user is editing it.", SmartestUserMessage::INFO);
                }
            }else{
                $this->addUserMessageToNextRequest("The page has been released.", SmartestUserMessage::SUCCESS);
                // $this->addUserMessageToNextRequest("The page is not currently held by any user.", SmartestUserMessage::INFO);
            }
            
        }
	    
	    // SmartestSession::clear('current_open_page');
	    
	    if(isset($get['from']) && $get['from'] == 'todoList'){
	        $this->redirect('/smartest/todo');
        }else{
            SmartestSession::clear('current_open_page');
            $this->redirect('/smartest/pages');
        }
	}
	
	public function clearPagesCache(){
	    
	    if($this->getSite() instanceof SmartestSite){
	        
	        if($this->getUser()->hasToken('clear_pages_cache')){
            
                $page_prefix = 'site'.$this->getSite()->getId().'_';
            
                $cache_files = SmartestFileSystemHelper::load(SM_ROOT_DIR.'System/Cache/Pages/');
            
                if(is_array($cache_files)){
                
                    $deleted_files = array();
                    $failed_files = array();
                    $untouched_files = array();
                
                    foreach($cache_files as $f){
                    
                        $path = SM_ROOT_DIR.'System/Cache/Pages/'.$f;
                    
                        if(strlen($f) && $page_prefix == substr($f, 0, strlen($page_prefix))){
                            // echo "deleting ".$path.'...<br />';
                            if(@unlink($path)){
                                $deleted_files[] = $f;
                            }else{
                                $failed_files[] = $f;
                            }
                        }else{
                            $untouched_files = $f;
                        }
                    }
                    
                    SmartestLog::getInstance('site')->log("{$this->getUser()} cleared the pages cache. ".count($deleted_files)." files were removed.", SmartestLog::USER_ACTION);
                    
                    $this->send(true, 'show_result');
                    $this->send($deleted_files, 'deleted_files');
                    $this->send(count($deleted_files), 'num_deleted_files');
                    $this->send($failed_files, 'failed_files');
                    $this->send($untouched_files, 'untouched_files');
                    $this->send(SM_ROOT_DIR.'System/Cache/Pages/', 'cache_path');
                
                }else{
                
                    $this->send(false, 'show_result');
                
                }
            
            }else{
                
                SmartestLog::getInstance('site')->log("{$this->getUser()} tried to cleared the pages cache but did not have permission.", SmartestLog::ACCESS_DENIED);
                $this->addUserMessageToNextRequest('You don\'t have permission to clear the page cache for this site.', SmartestUserMessage::ACCESS_DENIED);
                $this->redirect('/smartest/pages');

            }
            
        }else{
            $this->addUserMessageToNextRequest('No site selected.', SmartestUserMessage::ERROR);
            $this->redirect('/smartest');
        }
	    
	}
  
	public function editPage($get){
		
		// $this->addUserMessage('This is a really long test message with more than one line of text.');
		// $this->addUserMessage('You are on thin ice, Mr. Gilroy-Ware.');
		
		if(!isset($get['from'])){
		    $this->setFormReturnUri();
		}
		
		$page_webid = $get['page_id'];
		
		$helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());
		
		if(isset($type_index[$page_webid])){
		    if(($type_index[$page_webid] == 'ITEMCLASS' || $type_index[$page_webid] == 'SM_PAGETYPE_ITEMCLASS' || $type_index[$page_webid] == 'SM_PAGETYPE_DATASET') && isset($get['item_id']) && is_numeric($get['item_id'])){
		        $page = new SmartestItemPage;
		    }else{
		        $page = new SmartestPage;
		    }
		}else{
		    $page = new SmartestPage;
		}
    	
    	if($page->hydrate($page_webid)){
    	    
    	    if($page->getDeleted() == 'TRUE'){
                $this->send(true, 'show_deleted_warning');
            }
            
            $page->setDraftMode(true);
    	    
    	    if(($page->getType() == 'ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_DATASET') && (!isset($get['item_id']) || !is_numeric($get['item_id']))){
            
                $this->send(true, 'allow_edit');
            
                $model = new SmartestModel;
                
                if($model->hydrate($page->getDatasetId())){
                    $items = $model->getSimpleItems($this->getSite()->getId());
                    $this->send($items, 'items');
                    $this->send($model, 'model');
                    $this->send($page, 'page');
                    $this->send(true, 'require_item_select');
                    $this->send('Please choose an item to continue editing.', 'chooser_message');
                    $this->send('websitemanager/editPage', 'continue_action');
                }
            
            }else{
    	        
    	        if($page->getType() == 'ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_DATASET'){
    	            if($item = SmartestCmsItem::retrieveByPk($get['item_id'])){
    	                $page->setPrincipalItem($item);
    	            }
	            }
    	        
    	        $this->send(false, 'require_item_select');
    	        $editorContent = $page;
    		
            	if($this->getUser()->hasToken('modify_page_properties')){
		
            		$site_id = $this->getSite()->getId();
            		$page_id = $page->getId();
		
            		if($site_id){
			
            			if($this->getSite()->getTopPageId() == $page->getId()){
            				$ishomepage = true;
            			}else{
            				$ishomepage = false;
            			}
            		}
		        
    		        $parent_pages = $page->getOkParentPages();
        		
            		if($page->getIsHeld() == '1' && $page->getHeldBy() == $this->getUser()->getId()){
            		    $allow_release = true;
            		}else{
            		    $allow_release = false;
            		}
        		
            		$this->send($allow_release, 'allow_release');
            		$this->send($this->getUser()->hasToken('edit_page_name'), 'allow_edit_page_name');
		
            		// $pageUrls = $page->getUrlsAsArrays();
		        
    		        $available_icons = $page->getAvailableIconImageFilenames();
    		        
    		        $this->send($available_icons, 'available_icons');
		        
            		if($page->getType() == 'ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_DATASET'){
                
                        $model = new SmartestModel;
                        
                        if($model->hydrate($page->getDatasetId())){
                            $editorContent['model_name'] = $model->getName();
                            
                            if($page->getParent() && ($type_index[$page->getParent()] == 'ITEMCLASS' || $type_index[$page->getParent()] == 'SM_PAGETYPE_ITEMCLASS' || $type_index[$page->getParent()] == 'SM_PAGETYPE_DATASET')){
                                
                                $parent_indicator_properties = $model->getForeignKeyPropertiesForModelId($page->getParentPage(false)->getDatasetId(), (int) $get['item_id']);
                            
                                $this->send(true, 'show_parent_meta_page_property_control');
                                $this->send($model->__toArray(), 'model');
                                
                                if($page->getParentPage(false)->getDatasetId() == $page->getDatasetId()){
                                    
                                    // parent metapage has same model as this one
                                    $parent_model = &$model;
                                    $this->send(true, 'show_self_option');
                                    
                                }else{
                                    
                                    // quickly fetch parent meta-page's model
                                    $parent_model = new SmartestModel;
                                
                                    if($parent_model->hydrate($page->getParentPage(false)->getDatasetId())){
                                        
                                    }else{
                                        $this->addUserMessage("The parent of this page is a meta-page, but not linked to any existing model", SmartestUserMessage::WARNING);
                                    }
                                    
                                    $this->send(false, 'show_self_option');
                                    
                                }
                            
                                if(count($parent_indicator_properties) > 1){
                                    // there is a choice as to which property should be used to indicate which is the 'parent' item
                                    // convert to arrays and send to form
                                    
                                    $arrays = array();
                                    
                                    foreach($parent_indicator_properties as $p){
                                        
                                        $property_array = $p->__toArray();
                                        
                                        if($p instanceof SmartestItemPropertyValueHolder){
                                            
                                            $foreign_item = new SmartestItem;

                                            if($foreign_item->hydrate($p->getData()->getDraftContent())){
                                                $property_array['selected_item_name'] = $foreign_item->getName();
                                            }else{
                                                $property_array['selected_item_name'] = "Not Selected";
                                            }

                                        }else{
                                            $property_array['selected_item_name'] = "Unknown";
                                        }
                                        
                                        $arrays[] = $property_array;
                                        
                                    }
                                    
                                    $this->send($page->getParentMetaPageReferringPropertyId(), 'parent_data_source_property_id');
                                    $this->send('dropdown', 'parent_mpp_control_type');
                                    $this->send($arrays, 'parent_meta_page_property_options');
                                    
                                }else if(count($parent_indicator_properties) > 0){
                                    
                                    // the parent meta-page must be defined by a single foreign-key property of the model of this meta-page.
                                    // Display it, but there is no choice.
                                    
                                    if(!$page->getParentMetaPageReferringPropertyId()){
                                        $page->setParentMetaPageReferringPropertyId($parent_indicator_properties[0]->getId());
                                    }
                                    
                                    $this->send('text', 'parent_mpp_control_type');
                                    $property_array = $parent_indicator_properties[0]->__toArray();
                                    
                                    if($parent_indicator_properties[0] instanceof SmartestItemPropertyValueHolder){
                                        // $property_array['value'] = $parent_indicator_properties[0]->getData(true)->__toArray();
                                        $foreign_item = new SmartestItem;
                                        
                                        if($foreign_item->hydrate($parent_indicator_properties[0]->getData()->getDraftContent())){
                                            $property_array['selected_item_name'] = $foreign_item->getName();
                                        }else{
                                            $property_array['selected_item_name'] = "Not Selected";
                                        }
                                        
                                    }else{
                                        $property_array['selected_item_name'] = "Unknown";
                                    }
                                    
                                    $this->send($property_array, 'parent_meta_page_property');
                                    
                                }else{
                                    
                                    // there are no properties in this meta-page that point to the data type of the parent meta-page. this is a problem so we nnotify the user.
                                    if($page->getParentPage(false)->getDatasetId() == $page->getDatasetId()){
                                        $this->addUserMessage("This ".$model->getName()." meta-page is the child of a meta-page that is also used to represent ".$model->getPluralName().", but the ".$model->getName()." model has no foreign-key properties that refer to other ".$model->getPluralName().". This page will assign its own item to it's parent meta-page.", SmartestUserMessage::WARNING);
                                        $page->setParentMetaPageReferringPropertyId('_SELF');
                                        $this->send('_SELF', 'parent_meta_page_property');
                                        $this->send('text', 'parent_mpp_control_type');
                                    }else{
                                        $this->addUserMessage("This ".$model->getName()." meta-page is the child of a meta-page used for model ".$parent_model->getName().", but the ".$model->getName()." model (that this page refers to) has no foreign-key properties that refer to ".$parent_model->getPluralName().".", SmartestUserMessage::WARNING);
                                    }
                                    
                                }
                                
                            }
                            
                        }else{
                            
                            $this->addUserMessage("This page is a meta-page, but not linked to any existing model", SmartestUserMessage::WARNING);
                            
                        }
                    }
                
            		$this->setTitle("Edit Page | ".$page->getTitle());
    		
            		$this->send($editorContent, "page");
            		$this->send($parent_pages, "parent_pages");
            		$this->send($ishomepage, "ishomepage");
            		$this->send($this->getSite(), "site");
            		$this->send(true, 'allow_edit');
		
        	    }else{
	        
        	        $this->addUserMessageToNextRequest('You don\'t have permission to modify page properties.', SmartestUserMessage::ACCESS_DENIED);
        	        $this->redirect('/smartest/pages');
        	        $this->send($editorContent, "pageInfo");
        	        $this->send(false, 'allow_edit');
	        
        	    }
        	}
	    
        }else{
            $this->addUserMessageToNextRequest('The page ID was not recognized.', SmartestUserMessage::ERROR);
            $this->redirect("/smartest");
        }
		
	}
	
	function approvePageChanges($get){
	    
	    $page_webid = $get['page_id'];
        $page = new SmartestPage;
        
        if($page->hydrate($page_webid)){
	    
	        if($this->getUser()->hasToken('approve_page_changes')){
	        
	            $page->setChangesApproved(1);
	            $this->addUserMessageToNextRequest("The changes to this page have been approved.", SmartestUserMessage::SUCCESS);
	            $page->save();
	        
	        }else{
	            $this->addUserMessageToNextRequest("You don't have sufficient permissions to approve pages.", SmartestUserMessage::ACCESS_DENIED);
	        }
	        
	    }else{
	        $this->addUserMessageToNextRequest("The page ID wasn't recognized.", SmartestUserMessage::ERROR);
	    }
	    
	    $this->formForward();
	    
	}
	
	public function addPlaceholder($get){
		
		$asset_class_types = SmartestDataUtility::getAssetClassTypes();
		
		$placeholder_name = SmartestStringHelper::toVarName($get['name']);
		
		$this->send($placeholder_name, 'name');
		$this->send($asset_class_types, 'types');
		
	}
	
	public function addContainer($get){
		
		$container_name = SmartestStringHelper::toVarName($get['name']);
		
		$this->send($container_name, 'name');
		$this->send($asset_class_types, 'types');
		
	}
	
	public function insertPlaceholder($get, $post){
		
		$placeholder = new SmartestPlaceholder;
		
		if($post['placeholder_name']){
		    $name = SmartestStringHelper::toVarName($post['placeholder_name']);
		}else{
		    $name = SmartestStringHelper::toVarName($post['placeholder_label']);
		}
		
		if($placeholder->exists($name, $this->getSite()->getId())){
	        $this->addUserMessageToNextRequest("A placeholder with the name \"".$name."\" already exists.", SmartestUserMessage::WARNING);
	    }else{
		    $placeholder->setLabel($post['placeholder_label']);
		    $placeholder->setName($name);
		    $placeholder->setSiteId($this->getSite()->getId());
		    $placeholder->setType($post['placeholder_type']);
		    $placeholder->save();
		    $this->addUserMessageToNextRequest("A new container with the name \"".$name."\" has been created.", SmartestUserMessage::SUCCESS);
		}
		
		$this->formForward();
	}
	
	public function insertContainer($get, $post){
		
		if($post['container_name']){
		    $name = SmartestStringHelper::toVarName($post['container_name']);
		}else{
		    $name = SmartestStringHelper::toVarName($post['container_label']);
		}
		
		$container = new SmartestContainer;
		
		if($container->exists($name, $this->getSite()->getId())){
	        $this->addUserMessageToNextRequest("A container with the name \"".$name."\" already exists.", SmartestUserMessage::WARNING);
	    }else{
		    $container->setLabel($post['container_label']);
		    $container->setName($name);
		    $container->setSiteId($this->getSite()->getId());
		    $container->setType('SM_ASSETCLASS_CONTAINER');
		    $container->save();
		    $this->addUserMessageToNextRequest("A new container with the name \"".$name."\" has been created.", SmartestUserMessage::SUCCESS);
	    }
		
		$this->formForward();
	}
	
    public function placeholders(){
	    
	    $this->setFormReturnUri();
	    
	    $placeholders = $this->getSite()->getPlaceholders();
	    $this->send($placeholders, 'placeholders');
	    
	}
	
	public function editPlaceholder($get){
	    
	    $placeholder_id = (int) $get['placeholder_id'];
	    $placeholder = new SmartestPlaceholder;
	    
	    if($placeholder->find($placeholder_id)){
	        
	        $this->send($placeholder, 'placeholder');
	        $this->send($placeholder->getPossibleFileGroups(), 'possible_groups');
	        
	    }else{
	        
	        $this->addUserMessageToNextRequest("The placeholder ID wasn't recognized.", SmartestUserMessage::ERROR);
	        $this->formForward();
	        
	    }
	    
	}
	
	public function placeholderDefinitions($get){
	    
	    $placeholder_id = (int) $get['placeholder_id'];
	    $placeholder = new SmartestPlaceholder;
	    
	    if($placeholder->find($placeholder_id)){
	        
	        $mode = (isset($get['mode']) && $get['mode'] == 'live') ? "live" : "draft";
	        
	        $draft_mode = ($mode == "draft");
	        
	        $definitions = $placeholder->getDefinitions($draft_mode, $this->getSite()->getId());
	        
	        $this->send($placeholder, 'placeholder');
	        $this->send($definitions, 'definitions');
	        $this->send($mode, 'mode');
	    
	    }
	    
	}
	
	public function updatePlaceholder($get, $post){
	    
	    $placeholder_id = (int) $post['placeholder_id'];
	    $placeholder = new SmartestPlaceholder;
	    
	    if($placeholder->find($placeholder_id)){
	        
	        $placeholder->setLabel($post['placeholder_label']);
	        
	        if(isset($post['placeholder_filter'])){
	            if($post['placeholder_filter'] == 'NONE'){
	                $placeholder->setFilterType('SM_ASSETCLASS_FILTERTYPE_NONE');
	                $placeholder->setFilterValue('');
	            }else{
	                $placeholder->setFilterType('SM_ASSETCLASS_FILTERTYPE_ASSETGROUP');
	                $placeholder->setFilterValue($post['placeholder_filter']);
	            }
	        }
	        
	        $placeholder->save();
	        $this->addUserMessageToNextRequest("The placeholder was updated.", SmartestUserMessage::SUCCESS);
	        
	    }else{
	        
	        $this->addUserMessageToNextRequest("The placeholder ID wasn't recognized.", SmartestUserMessage::ERROR);
	        
	        
	    }
	    
	    $this->formForward();
	    
	}
	
	public function movePageUp($get){
	    
	    $page_webid = $get['page_id'];
	    $page = new SmartestPage();
	    $page->setDraftMode(true);
	    
	    if($page->hydrateBy('webid', $page_webid)){
	        $page->moveUp();
	        // $this->addUserMessageToNextRequest("The page has been moved up.", SmartestUserMessage::SUCCESS);
	        SmartestCache::clear('site_pages_tree_'.$page->getSiteId(), true);
	    }else{
	        $this->addUserMessageToNextRequest("The page ID wasn't recognised.", SmartestUserMessage::ERROR);
	    }
	    
	    $this->formForward();
	}
	
	public function movePageDown($get){
	    
	    $page_webid = $get['page_id'];
	    $page = new SmartestPage();
	    $page->setDraftMode(true);
	    
	    if($page->hydrateBy('webid', $page_webid)){
	        $page->moveDown();
	        // $this->addUserMessageToNextRequest("The page has been moved down.", SmartestUserMessage::SUCCESS);
	        SmartestCache::clear('site_pages_tree_'.$page->getSiteId(), true);
	    }else{
	        $this->addUserMessageToNextRequest("The page ID wasn't recognised.", SmartestUserMessage::ERROR);
	    }
	    
	    $this->formForward();
	}
	
	function preview($get){
		
		// if(!isset($get['from'])){
		    $this->setFormReturnUri();
		    $this->setFormReturnDescription('page preview');
	    // }
		
		$content = array();
		
		// $page_id = $this->manager->getPageIdFromPageWebId($get['page_id']);
		
		$page_webid = $get['page_id'];
		$page = new SmartestPage;
		
		if($page->hydrate($page_webid)){
		    
		    $this->send($page, 'page');
		    
		    $domain = 'http://'.$page->getParentSite()->getDomain();
		    
		    if(!SmartestStringHelper::endsWith('/', $domain)){
		        $domain .= '/';
		    }
		    
		    if($page->getDraftTemplate() && is_file(SM_ROOT_DIR.'Presentation/Masters/'.$page->getDraftTemplate())){
		    
		        if($page->getType() == 'NORMAL'){
		        
    		        $this->send(true, 'show_iframe');
    		        $this->send($domain, 'site_domain');
    		        $this->setTitle('Page Preview | '.$page->getTitle());
    		        $this->send(false, 'show_edit_item_option');
                    $this->send(false, 'show_publish_item_option');
		        
    		    }else if($page->getType() == 'ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_ITEMCLASS' || $page->getType() == 'SM_PAGETYPE_DATASET'){
		        
    		        if($get['item_id'] && is_numeric($get['item_id'])){
		            
    		            $item_id = $get['item_id'];
		            
    		            $item = SmartestCmsItem::retrieveByPk($item_id);
		            
    		            if(is_object($item)){
    		                $this->send($item, 'item');
    		                $this->send(true, 'show_iframe');
    		                $this->send($domain, 'site_domain');
    		                $this->setTitle('Meta-Page Preview | '.$item->getName());
    		                
    		                if(($this->getUser()->hasToken('publish_approved_items') && $item->isApproved() == 1) || $this->getUser()->hasToken('publish_all_items')){
                    	        $this->send(true, 'show_publish_item_option');
                    	    }else{
                    	        $this->send(false, 'show_publish_item_option');
                    	    }
                    	    
                    	    if($this->getUser()->hasToken('modify_items')){
                    	        $this->send(true, 'show_edit_item_option');
                    	    }else{
                    	        $this->send(true, 'show_false_item_option');
                    	    }
    		                
    		            }else{
		                    
		                    $this->send(false, 'show_edit_item_option');
		                    $this->send(false, 'show_publish_item_option');
		                    
    		                $this->send(false, 'show_iframe');
		                
    		                /* $set = new SmartestCmsItemSet;

        	                if($set->hydrate($page->getDatasetId())){

        	                    $items = $set->getMembersAsArrays(true);
        	                    $this->send($items, 'set_members');
        	                    $this->addUserMessage("Please choose an item to preview this page.");
        	                    $this->send(true, 'show_item_list');

        	                } */
                            
                            $this->send(true, 'show_item_list');

        	                $model = new SmartestModel;

        	                /* if($model->hydrate($page->getDatasetId())){
        	                    $items  = $model->getSimpleItemsAsArrays($this->getSite()->getId());
        	                    $this->send($items, 'items');
        	                    $this->send($model->__toArray(), 'model');
        	                }else{
        	                    $this->send(array(), 'items');
        	                } */
        	                
        	                if($model->hydrate($page->getDatasetId())){
	                            $items = $model->getSimpleItemsAsArrays($this->getSite()->getId());
	                            $this->send($items, 'items');
	                            $this->send($model, 'model');
	                            $this->send($page, 'page');
	                        }else{
	                            $this->send(array(), 'items');
	                        }
        	                
        	                $this->setTitle('Meta-Page Preview | Choose '.$model->getName().' to Continue');
    		            }
		            
    	            }else{
	                
    	                $this->send(false, 'show_iframe');
	                
    	                $this->send(true, 'show_item_list');
	                
    	                $model = new SmartestModel;
	                
    	                if($model->hydrate($page->getDatasetId())){
    	                    $items  = $model->getSimpleItemsAsArrays($this->getSite()->getId());
    	                    $this->send($items, 'items');
    	                    $this->send($model, 'model');
    	                    $this->send('Please choose an item to preview on this page.', 'chooser_message');
                            $this->send('websitemanager/preview', 'continue_action');
    	                }else{
    	                    $this->send(array(), 'items');
    	                }
    	                
    	                $this->setTitle('Meta-Page Preview | Choose '.$model->getName().' to Continue');
    	            }
    		    }    	    
    	    }else{
    	        
    	        $this->send(false, 'show_iframe');
    	        $this->addUserMessage("The preview of this page cannot be displayed because no master template is chosen.", SmartestUserMessage::WARNING);
    	        
    	    }
		    
		    if($this->getUser()->hasToken('approve_page_changes') && $page->getChangesApproved() != 1){
    	        $this->send(true, 'show_approve_button');
    	    }else{
    	        $this->send(false, 'show_approve_button');
    	    }
    	    
    	    if(($this->getUser()->hasToken('publish_approved_pages') && $page->getChangesApproved() == 1) || $this->getUser()->hasToken('publish_all_pages')){
    	        $this->send(true, 'show_publish_button');
    	    }else{
    	        $this->send(false, 'show_publish_button');
    	    }
    	    
    	    
		    
		}else{
		    $this->addUserMessage("The page ID was not recognized.", SmartestUserMessage::ERROR);
		    $this->send(false, 'show_iframe');
		}
		
		/* if($content["page"] = $this->manager->getPage($page_id)){
			return $content;
		}else{
			return array("page"=>array());
		}*/
	}
	
	public function comments(){
	    
	}
	
	function deletePage($get){
		
		$id = $get['page_id'];
		/* $sql = "UPDATE Pages SET page_deleted='TRUE' WHERE Pages.page_webid='$id'";
		$id = $this->database->rawQuery($sql);
		$title = $this->database->specificQuery('page_title', 'page_id', $id, 'Pages'); */
		
		$page = new SmartestPage;
		
		if($page->hydrate($id)){
		    
		    // retrieve site id for cache deletion
		    $site_id = $page->getSiteId();
		    
		    // set the page to deleted and save
		    $page->setDeleted('TRUE');
		    $page->save();
		    
		    // clear cache
		    SmartestCache::clear('site_pages_tree_'.$site_id, true);
		    
		    // make sure user is notified
		    $this->addUserMessageToNextRequest("The page has been successfully moved to the trash.", SmartestUserMessage::SUCCESS);
		    
		    // log deletion
    		SmartestLog::getInstance('site')->log("Page '".$title."' was deleted by user '".$this->getUser()->getUsername()."'", SmartestLog::USER_ACTION);
		    
		}else{
		    $this->addUserMessageToNextRequest("There was an error deleting the page.", SmartestUserMessage::ERROR);
		}
		
		// forward
		$this->formForward();
	}
	
	function sitePages($get){
		
		$this->requireOpenProject();
		
		$this->setFormReturnUri();

        $site_id = $this->getSite()->getId();
        
        $pagesTree = $this->getSite()->getPagesTree(true);
        
        if($get['refresh'] == 1){
            SmartestCache::clear('site_pages_tree_'.$site_id, true);
        }
        
        $this->setTitle($this->getSite()->getName()." | Site Map");
        
        $this->send($pagesTree, "tree");
        $this->send($site_id, "site_id");
        $this->send(true, "site_recognised");
        
        $recent = $this->getUser()->getRecentlyEditedPages($this->getSite()->getId());
	    $this->send($recent, 'recent_pages');
		    
	}
	
	public function releaseCurrentUserHeldPages(){
	    
	    $this->requireOpenProject();
	    
	    $num_held_pages = $this->getUser()->getNumHeldPages($this->getSite()->getId());
	    $this->getUser()->releasePages($this->getSite()->getId());
	    $this->addUserMessageToNextRequest($num_held_pages." pages were released.", SmartestUserMessage::SUCCESS);
	    $this->redirect('/smartest/pages');
	    
	}
	
	function addPage($get, $post){
		
		$this->requireOpenProject();
		
		$user_id = SmartestPersistentObject::get('user')->getId(); //['user_id'];
		
		$helper = new SmartestPageManagementHelper;
		
		if(isset($post['stage']) && is_numeric($post['stage']) && is_object(SmartestPersistentObject::get('__newPage'))){
			$stage = $post['stage'];
		}else if(isset($get['stage']) && is_numeric($get['stage']) && is_object(SmartestPersistentObject::get('__newPage'))){
			$stage = $get['stage'];
		}else{
		    $stage = 1;
		}
		
		/* if(isset($get['site_id']) && is_numeric($get['site_id'])){
			$site = new SmartestSite;
			$site->hydrate($get['site_id']);
			$site_info = $site->__toArray();
		}else if(isset($get['page_id'])){
			$parent_id = $get['page_id'];
			$site_id = $this->manager->database->specificQuery("page_site_id", "page_webid", $parent_id, "Pages");
			$site = new SmartestSite;
			$site->hydrate($site_id);
			$site_info = $site->__toArray();
		}else if(is_object(SmartestPersistentObject::get('__newPage')) && SmartestPersistentObject::get('__newPage')->getSiteId()){
			$site = new SmartestSite;
			$site->hydrate(SmartestPersistentObject::get('__newPage')->getSiteId());
			$site_info = $site->__toArray();
		}*/
		
		if($this->getSite() instanceof SmartestSite){
		    $site_id = $this->getSite()->getId();
		    $site_info = $this->getSite();
		}else{
		    $this->addUserMessageToNextRequest("You must have chosen a site to work on before adding pages.", SmartestUserMessage::INFO);
		    $this->redirect("/smartest");
		}
		
		if(isset($_REQUEST['page_id'])){
			$page_id = $_REQUEST['page_id'];
			$parent = new SmartestPage;
			$parent->hydrate($page_id);
			$parent_info = $parent;
		}else if(is_object(SmartestPersistentObject::get('__newPage')) && SmartestPersistentObject::get('__newPage')->getParent()){
			$parent = new SmartestPage;
			$parent->hydrate(SmartestPersistentObject::get('__newPage')->getParent());
			$parent_info = $parent;
		}
		
		// $templates = $helper->getMasterTemplates($site_id);
		$tlh = new SmartestTemplatesLibraryHelper;
		$templates = $tlh->getMasterTemplates($this->getSite()->getId());

		switch($stage){
			
			////////////// STAGE 2 //////////////
			
			case "2":
			
			// $type = strtolower(($post['page_type'] == 'ITEMCLASS') ? 'ITEMCLASS' : 'NORMAL');
			$type = in_array($post['page_type'], array('NORMAL', 'ITEMCLASS', 'LIST', 'TAG')) ? $post['page_type'] : 'NORMAL';
			$this->send($post['page_parent'], 'page_parent');
			
			$page_presets = $helper->getPagePresets($this->getSite()->getId());
			
			$template = "addPage.stage2.tpl";
			
			if(!SmartestPersistentObject::get('__newPage')->getType()){
				SmartestPersistentObject::get('__newPage')->setType(strtoupper($type));
			}
			
			$pages = $helper->getSerialisedPageTree($helper->getPagesTree($site_info['id']));
			$this->send('TRUE', 'chooseParent');
			$this->send($pages, 'pages');
			
			if(!SmartestPersistentObject::get('__newPage')->getCacheAsHtml()){
			    SmartestPersistentObject::get('__newPage')->setCacheAsHtml('TRUE');
			}
			
			$page_type = SmartestPersistentObject::get('__newPage')->getType();
			
			if($page_type == 'ITEMCLASS'){
				
				$this->send($this->getSite()->getModels(), 'models');
				
			}else if(SmartestPersistentObject::get('__newPage')->getType() == 'TAG'){
			    
			    $du = new SmartestDataUtility;
			    $tags = $du->getTagsAsArrays();
			    $this->send($tags, 'tags');
			    
			}
			
			$this->send($parent_info, 'parentInfo');
 			$this->send($site_info, 'siteInfo');
 			
 			$this->send($templates, 'templates');
 			$this->send($page_presets, 'presets');
 			
 			$newPage = SmartestPersistentObject::get('__newPage');
 			
 			$preset = new SmartestPagePreset;
 			
 			if($preset_id = SmartestSession::get('__newPage_preset_id') && $preset->hydrate(SmartestSession::get('__newPage_preset_id'))){
 			    $newPage['preset'] = $preset->getId();
 			    $newPage['draft_template'] = $preset->getMasterTemplateName();
		    }else{
		        $newPage['preset'] = '';
		    }
            
            $this->send($newPage, 'newPage');
			
			break;
			
			////////////// STAGE 3 //////////////
			
			case "3":
			
			// verify the page details
			
			SmartestPersistentObject::get('__newPage')->setTitle(strlen($post['page_title']) ? htmlentities($post['page_title'], ENT_COMPAT, 'UTF-8') : 'Untitled Smartest Web Page');
			SmartestPersistentObject::get('__newPage')->setName(strlen($post['page_title']) ? SmartestStringHelper::toSlug($post['page_title']) : SmartestStringHelper::toSlug('Untitled Smartest Web Page'));
			SmartestPersistentObject::get('__newPage')->setCacheAsHtml($post['page_cache_as_html']);
			SmartestPersistentObject::get('__newPage')->setCacheInterval($post['page_cache_interval']);
			SmartestPersistentObject::get('__newPage')->setIsPublished('FALSE');
			SmartestPersistentObject::get('__newPage')->setChangesApproved(0);
			SmartestPersistentObject::get('__newPage')->setSearchField(htmlentities(strip_tags($post['page_search_field']), ENT_COMPAT, 'UTF-8'));
			
			if(strlen($post['page_url']) && substr($post['page_url'], 0, 18) != 'website/renderPage'){
			    SmartestPersistentObject::get('__newPage')->addUrl($post['page_url']); 
			    $url = $post['page_url'];
		    }else{
		        
		        if(SmartestPersistentObject::get('__newPage')->getType() == 'ITEMCLASS'){
		            // $default_url = 'website/renderPageFromId?page_id='.SmartestPersistentObject::get('__newPage')->getWebId().'&item_id=:long_id';
		            // SmartestPersistentObject::get('__newPage')->getWebId().'.html');
	            }else{
	                // $default_url = SmartestPersistentObject::get('__newPage')->getWebId().'.html';
	            }
	            
		    } 
			
			SmartestPersistentObject::get('__newPage')->setDraftTemplate($post['page_draft_template']);
			SmartestPersistentObject::get('__newPage')->setDescription(strip_tags($post['page_description']));
			SmartestPersistentObject::get('__newPage')->setMetaDescription(strip_tags($post['page_meta_description']));
			SmartestPersistentObject::get('__newPage')->setKeywords(strip_tags($post['page_keywords']));
			
			if(isset($_REQUEST['page_id'])){
				SmartestPersistentObject::get('__newPage')->setParent($_REQUEST['page_id']);
			}
			
			if(isset($post['page_preset'])){
				SmartestSession::set('__newPage_preset_id', $post['page_preset']);
			}
			
			if(isset($post['page_model'])){
				SmartestPersistentObject::get('__newPage')->setDatasetId($post['page_model']);
				$model = new SmartestModel;
				$model->hydrate($post['page_model']);
			}
			
			if(isset($post['page_tag'])){
				SmartestPersistentObject::get('__newPage')->setDatasetId($post['page_tag']);
				$tag = new SmartestTag;
				$tag->hydrate($post['page_tag']);
			}
			
			$type_template = strtolower(SmartestPersistentObject::get('__newPage')->getType());
			
			$newPage = SmartestPersistentObject::get('__newPage')->__toArray();
			
			$urlObj = new SmartestPageUrl;
			
			if(isset($url) && !$urlObj->hydrateBy('url', $url)){
			    $newPage['url'] = $url;
		    }else{
		        $newPage['url'] = SM_CONTROLLER_DOMAIN.'website/renderPageById?page_id='.SmartestPersistentObject::get('__newPage')->getWebid();
		    }
			
			// should the page have a preset?
            if($preset_id = SmartestSession::get('__newPage_preset_id')){
                
                $preset = new SmartestPagePreset;
                
                // if so, apply those definitions
                if($preset->find($preset_id)){
                    SmartestPersistentObject::get('__newPage')->setDraftTemplate($preset->getMasterTemplateName());
                    $newPage['preset_label'] = $preset->getLabel();
    				$newPage['draft_template'] = SmartestPersistentObject::get('__newPage')->getDraftTemplate();
                }
            }
			
			/* if(SmartestPersistentObject::get('__newPage')->getPreset()){
				
				$newPage['preset'] = SmartestPersistentObject::get('__newPage')->getPreset();
				$preset = new SmartestPagePreset;
				$preset->hydrate(SmartestPersistentObject::get('__newPage')->getPreset());
				// SmartestPersistentObject::get('__newPage')->setPresetLabel($preset->getLabel());
				SmartestPersistentObject::get('__newPage')->setDraftTemplate($preset->getMasterTemplateName());
				$newPage['preset_label'] = SmartestPersistentObject::get('__newPage')->getPresetLabel();
				$newPage['draft_template'] = SmartestPersistentObject::get('__newPage')->getDraftTemplate();
				
			} */
			
			// print_r($newPage);
			
 			$this->send($newPage, 'newPage');
			
			$template = "addPage.stage3.tpl";
			break;
			
			
			////////////// DEFAULT //////////////
			
			default:
			
			$parent = new SmartestPage;
			
			if(isset($get['page_id']) && !isset($site_id)){
			    
			    $parent_id = $get['page_id'];
			    $parent->findBy('webid', $parent_id);
				
				$site_id = $parent->getSiteId();
				
			}
			
			$type = 'start';
			SmartestPersistentObject::set('__newPage', new SmartestPage);
			SmartestPersistentObject::get('__newPage')->setWebId(SmartestStringHelper::random(32));
			SmartestPersistentObject::get('__newPage')->setCreatedbyUserid($user_id);
			SmartestPersistentObject::get('__newPage')->setSiteId($site_info['id']);
			SmartestPersistentObject::get('__newPage')->setParent($parent_info['id']);
			
			$this->send($parent->getId(), 'page_parent');
			$template = "addPage.start.tpl";
			break;
		}
		
		$this->send($template, "_stage_template");
		
		$this->setTitle("Create A New Page");
		
 		
 		
	}
	
	function insertPage($get, $post){
	    
	    if($this->getSite() instanceof SmartestSite){
	        
	        if(SmartestPersistentObject::get('__newPage') instanceof SmartestPage){
	            
	            $page =& SmartestPersistentObject::get('__newPage');
	            
	            $page->setOrderIndex($page->getParentPage()->getNextChildOrderIndex());
	            $page->setCreated(time());
	            
	            $page->save();
	            
	            if($page->getType() == 'NORMAL'){
	                $page->addAuthorById($this->getUser()->getId());
                }
	            
	            // should the page have a preset?
	            if($preset_id = SmartestSession::get('__newPage_preset_id')){
	                
	                $preset = new SmartestPagePreset;
	                // if so, apply those definitions
	                if($preset->hydrate($preset_id)){
	                    $preset->applyToPage($page);
	                }
	            }
	            
	            $page_webid = $page->getWebId();
    		    $site_id = $page->getSiteId();
    		    
    		    // clear session and cached page tree
    		    SmartestCache::clear('site_pages_tree_'.$site_id, true);
	            SmartestPersistentObject::clear('__newPage');
	    
	            switch($post['destination']){
			
        			case "SITEMAP":
        			$this->addUserMessageToNextRequest("Your page was successfully added.", SmartestUserMessage::SUCCESS);
        			$this->redirect('/smartest/pages');
        			break;
			
        			case "ELEMENTS":
        			$this->addUserMessageToNextRequest("Your page was successfully added.", SmartestUserMessage::SUCCESS);
        			$this->redirect('/websitemanager/pageAssets?page_id='.$page_webid);
        			break;
			
        			case "EDIT":
        			$this->addUserMessageToNextRequest("Your page was successfully added.", SmartestUserMessage::SUCCESS);
        			$this->redirect('/websitemanager/openPage?page_id='.$page_webid);
        			break;
			
        			case "PREVIEW":
        			$this->addUserMessageToNextRequest("Your page was successfully added.", SmartestUserMessage::SUCCESS);
        			$this->redirect('/websitemanager/preview?page_id='.$page_webid);
    			    break;
    			
    		    }
    		
		    }else{
		        
		        $this->addUserMessageToNextRequest("The new page expired from the session.", SmartestUserMessage::WARNING);
    		    $this->redirect('/smartest');
		        
		    }
		
		}else{
		    
		    $this->addUserMessageToNextRequest("You must select a site before adding pages.", SmartestUserMessage::INFO);
		    $this->redirect('/smartest');
		    
		}
		
	}
	
	public function updatePage($get, $post){    
        
        $page = new SmartestPage;
        
        if($page->hydrate($post['page_id'])){
            
            $page->setTitle($post['page_title']);
            
            if(isset($post['page_name']) && strlen($post['page_name']) && $this->getUser()->hasToken('edit_page_name')){
                $page->setName(SmartestStringHelper::toSlug($post['page_name']));
            }
            
            $page->setParent($post['page_parent']);
            $page->setForceStaticTitle((isset($post['page_force_static_title']) && ($post['page_force_static_title'] == 'true')) ? 1 : 0);
            $page->setIsSection((isset($post['page_is_section']) && ($post['page_is_section'] == 'true')) ? 1 : 0);
            $page->setCacheAsHtml($post['page_cache_as_html']);
            $page->setCacheInterval($post['page_cache_interval']);
            $page->setIconImage($post['page_icon_image']);
            
            if($page->getType() == 'NORMAL'){
                $page->setSearchField(strip_tags($post['page_search_field']));
                $page->setKeywords(strip_tags($post['page_keywords']));
                $page->setDescription(strip_tags($post['page_description']));
                $page->setMetaDescription(strip_tags($post['page_meta_description']));
            }
            
            if($page->getType() == 'ITEMCLASS'){
                if(isset($post['page_parent_data_source']) && strlen($post['page_parent_data_source'])){
                    $page->setParentMetaPageReferringPropertyId($post['page_parent_data_source']);
                }
            }
            
            $page->save();
            SmartestCache::clear('site_pages_tree_'.$page->getSiteId(), true);
            $this->addUserMessageToNextRequest('The page was successfully updated.', SmartestUserMessage::SUCCESS);
            
        }else{
            $this->addUserMessageToNextRequest('There was an error updating page ID '.$post['page_id'].'.', SmartestUserMessage::ERROR);
        }
        
		$this->formForward();

	}

	function pageAssets($get){
	    
	    if($this->getUser()->hasToken('modify_draft_pages')){
	        
	        $page_webid = $get['page_id'];
	        
	        $helper = new SmartestPageManagementHelper;
    		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());

    		if(isset($type_index[$page_webid])){
    		    if($type_index[$page_webid] == 'ITEMCLASS' && isset($get['item_id']) && is_numeric($get['item_id'])){
    		        $page = new SmartestItemPage;
    		    }else{
    		        $page = new SmartestPage;
    		    }
    		}else{
    		    $page = new SmartestPage;
    		}
    		
    		if($page->hydrate($page_webid)){
	            
	            if($page->getDeleted() == 'TRUE'){
	                $this->send(true, 'show_deleted_warning');
	            }
	            
	            if($page->getType() == 'ITEMCLASS' && (!isset($get['item_id']) || !is_numeric($get['item_id']))){
	            
    	            $model = new SmartestModel;
            
                    if($model->hydrate($page->getDatasetId())){
                        $items  = $model->getSimpleItemsAsArrays($this->getSite()->getId());
                        $this->send($items, 'items');
                        $this->send($model, 'model');
                        $this->send('Please choose an item to edit the elements on this page.', 'chooser_message');
                        $this->send('websitemanager/pageAssets', 'continue_action');
                        $this->send(true, 'allow_edit');
                        $this->send($page, 'page');
                    }else{
                        $this->send(array(), 'items');
                    }
                
                    $this->send(true, 'require_item_select');
	            
    	        }else{
	                
	                $this->send(false, 'require_item_select');
	                
	                if($page->getType() == 'ITEMCLASS'){
        	            if($item = SmartestCmsItem::retrieveByPk($get['item_id'])){
        	                
        	                $page->setPrincipalItem($item);
        	                $recent_items = $this->getUser()->getRecentlyEditedItems($this->getSite()->getId(), $item->getItem()->getItemclassId());
        	                $model = $item->getItem()->getModel();
        	                $metapages = $item->getItem()->getModel()->getMetaPages();
        	                
        	                $default_metapage_id = $item->getItem()->getMetapageId();
        	                
        	                if($default_metapage_id){
        	                    if($default_metapage_id != $page->getId()){
        	                        $page = new SmartestPage;
            	                    $page->find($default_metapage_id);
            	                    $default_metapage_webid = $page->getWebId();
            	                    $this->send($default_metapage_webid, 'default_metapage_webid');
        	                        $this->send(true, 'show_metapage_warning');
        	                    }else{
        	                        $this->send(false, 'show_metapage_warning');
    	                        }
        	                }else{
        	                    $this->send(false, 'show_metapage_warning');
        	                }
        	                
        	                $this->send((bool) count($metapages), 'show_metapages');
        	                $this->send($metapages, 'metapages');
        	                $this->send($recent_items, 'recent_items');
        	                $this->send($model, 'model');
        	                $this->send(true, 'show_recent_items');
        	                $this->send($item, 'item');
        	            }
    	            }
	                
    		        $this->setFormReturnUri();
    		        $this->setFormReturnDescription('page elements tree');
		
            		// $definedAssets = $this->manager->getDefinedPageAssetsList($get['page_id']);
            		$version = (!empty($get['version']) && $get['version'] == "live") ? "live" : "draft";
            		$field = ($version == "live") ? "page_live_template" : "page_draft_template";
		            
		            if($page->getType() == 'ITEMCLASS'){
        		        $assetClasses = $this->manager->getPageTemplateAssetClasses($get['page_id'], $version, $item->getId());
    		        }else{
    		            $assetClasses = $this->manager->getPageTemplateAssetClasses($get['page_id'], $version);
    		        }
            		
            		$site_id = $this->database->specificQuery("page_site_id", "page_webid", $get['page_id'], "Pages");
            		// $templates = $this->manager->getMasterTemplates($site_id);
            		$tlh = new SmartestTemplatesLibraryHelper;
            		$templates = $tlh->getMasterTemplates($this->getSite()->getId());
            		
            		$this->setTitle("Page Elements");
    		
    		        if($version == 'live'){
            		    $template_name = $page->getLiveTemplate();
            		}else{
            		    $template_name = $page->getDraftTemplate();
            		}
            		
            		$template_object = $tlh->hydrateMasterTemplateByFileName($template_name, $this->getSite()->getId());
            		// print_r($template_object);
            		
            		$this->send((!$tlh->getMasterTemplateHasBeenImported($page->getDraftTemplate()) && $version == 'draft'), 'show_template_warning');
    		
            		if($page->getIsHeld() == '1' && $page->getHeldBy() == $this->getUser()->getId()){
            		    $allow_release = true;
            		}else{
            		    $allow_release = false;
            		}
    		
            		$this->send($allow_release, 'allow_release');
		
            		$mode = 'advanced';
    		
            		// $sub_template = ($mode == "basic") ? "getPageAssets.basic.tpl" : "getPageAssets.advanced.tpl";
        		    $sub_template = "getPageAssets.advanced.tpl";
		
            		$this->send($assetClasses["tree"], "assets");
            		$this->send($definedAssets, "definedAssets");
            		$this->send($page, "page");
            		$this->send($template_object, "page_template");
            		$this->send($templates, "templates");
            		$this->send($template_name, "templateMenuField");
            		$this->send($site_id, "site_id");
            		$this->send($version, "version");
            		$this->send($sub_template, "sub_template");
            		$this->send(true, 'allow_edit');
    		
    		    }
		    
	        }
		
	    }else{
	        
	        $this->addUserMessage('You don\'t have permission to modify pages.', SmartestUserMessage::ACCESS_DENIED);
	        $this->send(false, 'allow_edit');
	        
	    }
	}
	
	public function pageTags($get){
	    
	    $this->setFormReturnUri();
	    
	    $this->setTitle('Page Tags');
	    
	    $page_id = $get['page_id'];
	    $page = new SmartestPage;
	    
	    if($page->hydrate($page_id)){
	        
	        if($page->getType() == 'ITEMCLASS'){
	            
	            // Page is an Object meta page - force them to pick a specific item
	            $this->send(false, 'show_tags');
	            
	            $model = new SmartestModel;

                if($model->hydrate($page->getDatasetId())){
                    $items  = $model->getSimpleItemsAsArrays($this->getSite()->getId());
                    $this->send($items, 'items');
                    $this->send($model->__toArray(), 'model');
                    $this->send('Please choose which '.$model->getName().' you would like to tag:', 'chooser_message');
                    $this->send('datamanager/itemTags', 'continue_action');
                }else{
                    $this->send(array(), 'items');
                }
                
                $this->send($page->__toArray(), 'page');
                
                $this->setTitle('Meta-Page Tags | Choose '.$model->getName().' to Continue');
	            
	        }else{
	            
	            // Page is a normal web page
	            $du  = new SmartestDataUtility;
	            $tags = $du->getTags();
	        
	            $page_tags = array();
	            $i = 0;
	        
	            foreach($tags as $t){
	            
	                $page_tags[$i] = $t->__toArray();
	            
	                if($t->hasPage($page->getId())){
	                    $page_tags[$i]['attached'] = true;
	                }else{
	                    $page_tags[$i]['attached'] = false;
	                }
	            
	                $i++;
	            }
	        
	            $this->send($page_tags, 'tags');
	            $this->send(true, 'show_tags');
	            $this->send($page->__toArray(), 'page');
	            
	            $this->setTitle('Page Tags | '.$page->getTitle());
	        
            }
	        
	    }else{
	        $this->addUserMessage('The page ID has not been recognized.', SmartestUserMessage::ERROR);
	    }
	    
	}
	
	public function updatePageTags($get, $post){
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($post['page_id'])){
	    
	        $du  = new SmartestDataUtility;
            $tags = $du->getTags();
        
            if(is_array($post['tags'])){
                
                $page_new_tag_ids = array_keys($post['tags']);
                $page_current_tag_ids = $page->getTagIdsArray();
                
                foreach($tags as $t){
                    
                    if(in_array($t->getId(), $page_new_tag_ids) && !in_array($t->getId(), $page_current_tag_ids)){
                        $page->tag($t->getId());
                    }
                    
                    if(in_array($t->getId(), $page_current_tag_ids) && !in_array($t->getId(), $page_new_tag_ids)){
                        $page->untag($t->getId());
                    }
                    
                }
                
                $this->addUserMessageToNextRequest('The tags on this page were successfully updated.', SmartestUserMessage::SUCCESS);
                
            }else{
                // clear all page tags
                $page->clearTags();
                $this->addUserMessageToNextRequest('The tags on this page were successfully removed.', SmartestUserMessage::SUCCESS);
            }
        
        }else{
            
            // page ID wasn't recognised
            $this->addUserMessageToNextRequest('The page ID was not recognized', SmartestUserMessage::ERROR);
            
        }
	    
	    $this->formForward();
	    
	}
	
	public function relatedContent($get){
	    
	    $page = new SmartestPage;
	    $page->setDraftMode(true);
	    $page_webid = $get['page_id'];
	    
	    if($page->hydrate($page_webid)){
	        
	        $this->setFormReturnUri();
	        
	        if($page->getType() == 'ITEMCLASS'){
	            
	            $model = new SmartestModel;
            
                if($model->hydrate($page->getDatasetId())){
                    $items  = $model->getSimpleItemsAsArrays($this->getSite()->getId());
                    $this->send($items, 'items');
                    $this->send($model->__toArray(), 'model');
                    $this->send('Please choose an item to attache related content.', 'chooser_message');
                    $this->send('datamanager/relatedContent', 'continue_action');
                    $this->send($page->__toArray(), 'page');
                }else{
                    $this->send(array(), 'items');
                }
                
                $this->send(true, 'require_item_select');
	            
	        }else{
	        
	            $this->setTitle($page->getTitle()." | Related Content");
    	        $related_pages = $page->getRelatedPages();
	        
    	        $du = new SmartestDataUtility;
    	        $models = $du->getModelsAsArrays();
	        
    	        foreach($models as &$m){
    	            $m['related_items'] = $page->getRelatedItemsAsArrays($m['id']);
    	        }
	        
    	        $this->send($page, 'page');
    	        $this->send($related_pages, 'related_pages');
        	    $this->send($models, 'models');
        	    $this->send(false, 'require_item_select');
    	    
	        }
    	    
	    }else{
	        $this->addUserMessageToNextRequest('The page ID was not recognized', SmartestUserMessage::ERROR);
	        $this->redirect('/smartest/pages');
	    }
	    
	}
	
	public function editRelatedContent($get){
	    
	    $page = new SmartestPage;
	    $page->setDraftMode(true);
	    $page_webid = $get['page_id'];
	    
	    if($page->hydrate($page_webid)){
	        
	        if(isset($get['model_id'])){
	            
	            $model_id = (int) $get['model_id'];
	            $model = new SmartestModel;
	            
	            if($model->hydrate($model_id)){
	                $mode = 'items';
	            }else{
	                $mode = 'pages';
	            }
            }
	        
	        $this->send($mode, 'mode');
	        
	        if($mode == 'items'){
	            $this->setTitle($page->getTitle()." | Related ".$model->getPluralName());
	            $this->send($page->__toArray(), 'page');
	            $this->send($model->__toArray(), 'model');
	            $related_ids = $page->getRelatedItemIds($model->getId());
	            $all_items  = $model->getSimpleItemsAsArrays($this->getSite()->getId());
	            $this->send($all_items, 'items');
	            $this->send($related_ids, 'related_ids');
            }else{
                $this->setTitle($page->getTitle()." | Related pages");
    	        $this->send($page->__toArray(), 'page');
    	        $related_ids = $page->getRelatedPageIds(true);
    	        $helper = new SmartestPageManagementHelper;
    	        $pages = $helper->getPagesList($this->getSite()->getId());
    	        $this->send($pages, 'pages');
    	        $this->send($related_ids, 'related_ids');
            }
	        
	        $related_pages = $page->getRelatedPagesAsArrays();
    	    
	    }else{
	        $this->addUserMessageToNextRequest('The page ID was not recognized', SmartestUserMessage::ERROR);
	        $this->redirect('/smartest/pages');
	    }
	    
	}
	
	public function updateRelatedPageConnections($get, $post){
	    
	    $page = new SmartestPage;
	    $page->setDraftMode(true);
	    $page_webid = $post['page_id'];
	    
	    if($page->hydrate($page_webid)){
	        
	        if(isset($post['pages']) && is_array($post['pages'])){
	            
	            $new_related_ids = array_keys($post['pages']);
	            
	            if(count($new_related_ids)){
	            
	                $old_related_ids = $page->getRelatedPageIds(true);
        	        $helper = new SmartestPageManagementHelper;
        	        $pages = $helper->getPagesList($this->getSite()->getId());
    	        
        	        foreach($pages as $p){
    	            
        	            if(in_array($p['id'], $new_related_ids) && !in_array($p['id'], $old_related_ids)){
        	                // add connection
        	                $page->addRelatedPage($p['id']);
        	            }
    	            
        	            if(in_array($p['id'], $old_related_ids) && !in_array($p['id'], $new_related_ids)){
        	                // remove connection
        	                $page->removeRelatedPage($p['id']);
        	            }
        	        }
    	        
	            }else{
	                
	                $page->removeAllRelatedPages();
	                
	            }
    	        
            }else{
                $this->addUserMessageToNextRequest('Incorrect input format: Data should be array of pages', SmartestUserMessage::ERROR);
            }
        }else{
            $this->addUserMessageToNextRequest('The page ID was not recognized', SmartestUserMessage::ERROR);
        }
        
        $this->formForward();
	    
	}
	
	public function updateRelatedItemConnections($get, $post){
	    
	    $page = new SmartestPage;
	    $page->setDraftMode(true);
	    $page_webid = $post['page_id'];
	    
	    if($page->hydrate($page_webid)){
	        
	        if(isset($post['items']) && is_array($post['items'])){
	            
	            $new_related_ids = array_keys($post['items']);
	            
	            $model = new SmartestModel;
	            
	            if($model->hydrate($post['model_id'])){
	            
	                if(count($new_related_ids)){
	            
    	                $old_related_ids = $page->getRelatedItemIds($model->getId());
            	        $items = $model->getSimpleItemsAsArrays($this->getSite()->getId());
            	        
            	        foreach($items as $item){
    	            
            	            if(in_array($item['id'], $new_related_ids) && !in_array($item['id'], $old_related_ids)){
            	                // add connection
            	                $page->addRelatedItem($item['id']);
            	            }
    	            
            	            if(in_array($item['id'], $old_related_ids) && !in_array($item['id'], $new_related_ids)){
            	                // remove connection
            	                $page->removeRelatedItem($item['id']);
            	            }
            	        }
    	        
    	            }else{
	                
    	                $page->removeAllRelatedItems($model->getId());
	                
    	            }
	            
                }
    	        
            }else{
                $this->addUserMessageToNextRequest('Incorrect input format: Data should be array of pages', SmartestUserMessage::ERROR);
            }
        }else{
            $this->addUserMessageToNextRequest('The page ID was not recognized', SmartestUserMessage::ERROR);
        }
        
        $this->formForward();
	    
	}
	
	public function authors($get){
	    
	    if(!isset($get['from'])){
	        $this->setFormReturnUri();
	    }
	    
	    $page_webid = $get['page_id'];
	    
	    $page = new SmartestPage;
	    $page->setDraftMode(true);
	    
	    if($page->hydrate($page_webid)){
	        
	        $uhelper = new SmartestUsersHelper;
	        $users = $uhelper->getUsersOnSiteAsArrays($this->getSite()->getId());
	        $this->send($users, 'users');
	        $author_ids = $page->getAuthorIds();
	        $this->send($author_ids, 'author_ids');
	        $this->send($page, 'page');
	        
	    }else{
            $this->addUserMessage('The page ID was not recognized', SmartestUserMessage::ERROR);
        }
	    
	}
	
	public function updateAuthors($get, $post){
	    
	    $page_id = (int) $post['page_id'];
	    
	    $page = new SmartestPage;
	    $page->setDraftMode(true);
	    
	    if($page->hydrate($page_id)){
	        
	        if(isset($post['users']) && count($post['users'])){
	            
	            $uhelper = new SmartestUsersHelper;
                $users = $uhelper->getUsersOnSite($this->getSite()->getId());
            
                $new_author_ids = array_keys($post['users']);
                $old_author_ids = $page->getAuthorIds();
                
                foreach($users as $u){
                    
                    if(in_array($u->getId(), $old_author_ids) && !in_array($u->getId(), $new_author_ids)){
                        // remove connection
                        $page->removeAuthorById($u->getId());
                    }
                    
                    if(in_array($u->getId(), $new_author_ids) && !in_array($u->getId(), $old_author_ids)){
                        // add connection
                        $page->addAuthorById($u->getId());
                    }
                }
                
                $this->addUserMessageToNextRequest('The authors of this page were sucessfully updated.', SmartestUserMessage::SUCCESS);
            
            }else{
                
                $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_PAGE_AUTHORS');
        	    $q->setTargetEntityByIndex(1);
        	    $q->addQualifyingEntityByIndex(2, $page->getId());

        	    $q->addSortField('Users.user_lastname');

        	    $q->delete();
        	    
        	    $this->addUserMessageToNextRequest('The authors of this page were sucessfully removed.', SmartestUserMessage::SUCCESS);
                
            }
	        
	    }else{
            $this->addUserMessageToNextRequest('The page ID was not recognized', SmartestUserMessage::ERROR);
        }
	    
	    $this->formForward();
	    
	}
	
	public function structure($get){
	
		$this->setFormReturnUri();
		
		$version = ($get['version'] == "live") ? "live" : "draft";
		$field = ($version == "live") ? "page_live_template" : "page_draft_template";
		
		$elements = $this->manager->getPageElements($get['page_id'], $version);
		
	}
	
	public function layoutPresetForm($get){
		
		$page_webid = $get['page_id'];
		
		$helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());

		if(isset($type_index[$page_webid])){
		    if($type_index[$page_webid] == 'ITEMCLASS' && isset($get['item_id']) && is_numeric($get['item_id'])){
		        $page = new SmartestItemPage;
		    }else{
		        $page = new SmartestPage;
		    }
		}else{
		    $page = new SmartestPage;
		}
		
		if($page->hydrate($page_webid)){
		    
		    if($page->getType() == 'ITEMCLASS'){
	            if(isset($get['item_id']) && $item = SmartestCmsItem::retrieveByPk($get['item_id'])){
	                $page->setPrincipalItem($item);
	                $this->send($item, 'item');
	                $item_id = $get['item_id'];
	            }else{
	                $item_id = false;
	            }
            }
		    
		    $page->setDraftMode(true);
		    
		    $this->setTitle('Create Preset');
		    
		    $page_id = $this->database->specificQuery("page_id", "page_webid", $page_webid, "Pages");
		    $assetClasses = $this->manager->getPageTemplateAssetClasses($page_webid, "draft", $item_id);
		    $assetClasseslist = $this->manager->getSerialisedAssetClassTree($assetClasses['tree']);
 		    
 		    $this->send($assetClasseslist, 'elements');
 		    $this->send($page, 'page');
 		
	    }
	}
	
	function createLayoutPreset($get, $post){
	
		/* $page_id = $post['page_id'];
		$user_id = $_SESSION['user']['user_id'];
		$plp_name = $post['layoutpresetname'];
		$master_template =  $this->database->specificQuery("page_live_template", "page_id", $page_id, "Pages");
		$assets = $post['asset'];
		
		$this->manager->setupLayoutPreset($plp_name, $assets, $master_template, $user_id, $page_id); */
		
		$num_elements = 0;
		
		$preset = new SmartestPagePreset;
		
		$preset->setOrigFromPageId($post['page_id']);
		$preset->setMasterTemplateName($preset->getOriginalPage()->getDraftTemplate());
		$preset->setCreatedByUserId($this->getUser()->getId());
		$preset->setLabel($post['preset_name']);
		$preset->setSiteId($this->getSite()->getId());
		$shared = isset($post['preset_shared']) ? 1 : 0;
		$preset->setShared($shared);
		
		if(isset($post['placeholder']) && is_array($post['placeholder'])){
		    
		    $num_elements += count($post['placeholder']);
		    
		    foreach($post['placeholder'] as $placeholder_id){
		        $preset->addPlaceholderDefinition($placeholder_id);
		    }
		    
		}
		
		if(isset($post['container']) && is_array($post['container'])){
		    $num_elements += count($post['container']);
		    
		    foreach($post['container'] as $container_id){
		        $preset->addContainerDefinition($container_id);
		    }
		    
		}
		
		if(isset($post['field']) && is_array($post['field'])){
		    
		    $num_elements += count($post['field']);
		    
		    foreach($post['field'] as $field_id){
		        $preset->addFieldDefinition($field_id);
		    }
		    
		}
		
		if($num_elements > 0){
		    $preset->save();
		    $this->addUserMessageToNextRequest("The new preset has been created.", SmartestUserMessage::SUCCESS);
		}
		
		$this->formForward();
	}
	
	public function defineContainer($get){
	    
	    $container_name = $get['assetclass_id'];
	    $page_webid = $get['page_id'];
	    
	    $this->setTitle('Define Container');
	    
	    $helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());
	    
	    if(isset($type_index[$page_webid])){
		    
		    if($type_index[$page_webid] == 'ITEMCLASS'){
		        
		        if(isset($get['item_id']) && is_numeric($get['item_id'])){
		            
		            $item_id = (int) $get['item_id'];
		            
    		        $page = new SmartestItemPage;
		        
    		        if($item = SmartestCmsItem::retrieveByPk($get['item_id'])){
    	                $page->setPrincipalItem($item);
    	                $this->send($item, 'item');
    	                // rint_r($item['_model']);
    	                $this->send(true, 'show_item_options');
    	                $this->send(false, 'require_choose_item');
    	            }else{
    	                $this->send(true, 'require_choose_item');
    	                $require_item = true;
    	            }
    	            
	            
                }else{
                    // this is a meta page, but the item id is problematic
                    $page = new SmartestItemPage; // this is needed to prevent a fatal error when page is looked up via hydrateBy
                    $this->send(true, 'require_choose_item');
                    $require_item = true;
                }
		        
		    }else{
		        // this is just a normal static page
		        $item_id = '';
		        $page = new SmartestPage;
		        $this->send(false, 'require_choose_item');
		    }
		}else{
		    $page = new SmartestPage; // this is needed to prevent a fatal error when page is looked up via hydrateBy
		}
	    
	    if($page->hydrateBy('webid', $page_webid)){
	        
	        $page->setDraftMode(true);
	        
	        if(isset($require_item) && $require_item){
                
                $model = new SmartestModel;
                
                if($model->hydrate($page->getDatasetId())){
                    $items = $model->getSimpleItems($this->getSite()->getId());
                    $this->send($items, 'items');
                    $this->send($model, 'model');
                    $this->send($page, 'page');
                }
                
            }
	        
	        $container = new SmartestContainer;
	        
	        if($container->hydrateBy('name', $container_name)){
	            
	            $this->setTitle('Define Container | '.$container_name);
	            
	            $page_definition = new SmartestContainerDefinition;
	            
	            if($page_definition->load($container_name, $page, true)){
	                
	                if($type_index[$page_webid] == 'ITEMCLASS'){
	                    
	                    $item_definition = new SmartestContainerDefinition;
	                    
	                    if($item_definition->load($container_name, $page, true, $item_id)){
	                        
	                        if($page_definition->getDraftAssetId() == $item_definition->getDraftAssetId()){
	                            $item_uses_default = true;
	                        }else{
	                            $item_uses_default = false;
	                        }
	                        
	                        $this->send($item_definition->getDraftAssetId(), 'selected_template_id');
	                        
	                    }else{
	                        
	                        $this->send($page_definition->getDraftAssetId(), 'selected_template_id');
	                        $item_uses_default = true;
	                        
	                    }
	                    
	                    $this->send($item_uses_default, 'item_uses_default');
	                    
	                }else{
	                
	                    // container has live definition
    	                $this->send($page_definition->getDraftAssetId(), 'selected_template_id');
    	                $this->send(true, 'is_defined');
	                
                    }
	                
	            }else{
	                // container has no live definition
	                $this->send(0, 'selected_template_id');
	                $this->send(false, 'is_defined');
	            }
	            
	            $assets = $container->getPossibleAssets();
	            
	            $this->send($assets, 'templates');
	            $this->send($page, 'page');
	            $this->send($container, 'container');
	            
	        }
	    
        }else{
            // page not found
            $this->addUserMessageToNextRequest('The page ID was not recognized', SmartestUserMessage::ERROR);
            $this->redirect('/smartest/pages');
        }
	    
	}
	
	public function updateContainerDefinition($get, $post){
	    
	    $container_id = $post['container_id'];
	    $page_id = $post['page_id'];
	    $asset_id = $post['asset_id'];
	    
	    $helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());
		
	    if(isset($type_index[$page_id])){
		    if($type_index[$page_id] == 'ITEMCLASS' && isset($post['item_id']) && is_numeric($post['item_id'])){
		        $page = new SmartestItemPage;
		    }else{
		        $page = new SmartestPage;
		    }
		}else{
		    $page = new SmartestPage;
		}
	    
	    if($page->hydrate($page_id)){
	        
	        $page->setDraftMode(true);
	        
	        $container = new SmartestContainer;
	        
	        if($container->hydrate($container_id)){
	            
	            $definition = new SmartestContainerDefinition;
	            
	            /* if($definition->loadForUpdate($container->getName(), $page)){
	                
	                // update container
	                $definition->setDraftAssetId($asset_id);
	                $definition->save();
	                
	            }else{
	                
	                // wasn't already defined
	                $definition->setDraftAssetId($asset_id);
	                $definition->setAssetclassId($container_id);
	                $definition->setInstanceName('default');
	                $definition->setPageId($page->getId());
	                $definition->save();
	                
	            }
	            
	            $page->setChangesApproved(0);
                $page->setModified(time()); */
                
                if($type_index[$page_id] == 'NORMAL' || (isset($post['item_id']) && is_numeric($post['item_id']) && $post['definition_scope'] != 'THIS')){
	                
	                if($definition->loadForUpdate($container->getName(), $page, true)){
	                    
	                    // update container
	                    $definition->setDraftAssetId($asset_id);
	                    $log_message = $this->getUser()->__toString()." updated container '".$container->getName()."' on page '".$page->getTitle(true)."' to use asset ID ".$asset_id.".";
	                
	                }else{
	                    
	                    // wasn't already defined
	                    $definition->setDraftAssetId($asset_id);
	                    $definition->setAssetclassId($container_id);
	                    $definition->setInstanceName('default');
	                    $definition->setPageId($page->getId());
	                    $log_message = $this->getUser()->__toString()." defined container '".$container->getName()."' on page '".$page->getTitle(true)."' with asset ID ".$asset_id.".";
	                
	                }
	            
	                if($post['definition_scope'] == 'ALL'){
	                    
	                    // DELETE ALL PER-ITEM DEFINITIONS
	                    $pmh = new SmartestPageManagementHelper;
	                    $pmh->removePerItemDefinitions($page->getId(), $container_id);
	                    
	                }
	                
	                $definition->save();
	            
                }else if($type_index[$page_id] == 'ITEMCLASS' && (isset($post['item_id']) && is_numeric($post['item_id']) && $post['definition_scope'] == 'THIS')){
                    
                    if($definition->loadForUpdate($container->getName(), $page, true)){ // looks for all-items definition
	                    
	                    $item_def = new SmartestContainerDefinition;
	                    
	                    // item chosen is same as all-items definition
	                    if($definition->getDraftAssetId() == $asset_id){ 
	                        
	                        // if there is already a per-item definitions for this item
	                        if($item_def->loadForUpdate($container->getName(), $page, false, $post['item_id'])){
	                            
	                            $item_def->delete();
                                
	                        }
	                        
	                        $log_message = $this->getUser()->__toString()." set container '".$container->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." (which is the same as the all-items definition) when displaying item ID ".$post['item_id'].".";
	                    
	                    }else{
	                        
	                        if($item_def->loadForUpdate($container->getName(), $page, true, $post['item_id'])){
	                            // just update container
	                            $item_def->setDraftAssetId($asset_id);
	                            $log_message = $this->getUser()->__toString()." updated container '".$container->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                        }else{
	                            $item_def->setDraftAssetId($asset_id);
        	                    $item_def->setAssetclassId($container_id);
        	                    $item_def->setItemId($post['item_id']);
        	                    $item_def->setInstanceName('default');
        	                    $item_def->setPageId($page->getId());
	                            $log_message = $this->getUser()->__toString()." defined container '".$container->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                        }
	                        
	                        $item_def->save();
	                        
	                    }
	                
	                }else if($definition->loadForUpdate($container->getName(), $page, true, $post['item_id']) && $post['definition_scope'] == 'THIS'){
	                    
	                    // all-items definition doesn't exist but per-item for this item does
	                    $definition->setDraftAssetId($asset_id);
	                    
	                    if(is_array($post['params'])){
    	                    $definition->setDraftRenderData(serialize($post['params']));
    	                }
    	                
    	                $definition->save();
    	                $log_message = $this->getUser()->__toString()." updated container '".$container->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                    
	                }else{
	                    
	                    // wasn't already defined for any items at all. Define for this item
	                    $definition->setDraftAssetId($asset_id);
	                    $definition->setAssetclassId($container_id);
	                    if($post['definition_scope'] == 'THIS'){$definition->setItemId($post['item_id']);}
	                    $definition->setInstanceName('default');
	                    $definition->setPageId($page->getId());
	                    
	                    if(is_array($post['params'])){
    	                    $definition->setDraftRenderData(serialize($post['params']));
    	                }
    	                
    	                $definition->save();
    	                $log_message = $this->getUser()->__toString()." defined container '".$container->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                    
	                }
	                
                }
	            
	            $page->setChangesApproved(0);
                $page->setModified(time());
                $page->save();
                SmartestLog::getInstance('site')->log($log_message, SM_LOG_USER_ACTION);
	            
	            $this->addUserMessageToNextRequest('The container was updated.', SmartestUserMessage::SUCCESS);
	            
	        }else{
	            
	            $this->addUserMessageToNextRequest('The specified container doesn\'t exist', SmartestUserMessage::ERROR);
	            
	        }
	    
        }else{
            
            $this->addUserMessageToNextRequest('The specified page doesn\'t exist', SmartestUserMessage::ERROR);
            
        }
        
        $this->formForward();
	    
	}
	
	public function definePlaceholder($get){
	    
	    $placeholder_name = $get['assetclass_id'];
	    $page_webid = $get['page_id'];
	    
	    $this->setTitle('Define Placeholder');
	    
	    $helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());
	    
	    if(isset($type_index[$page_webid])){
		    
		    if($type_index[$page_webid] == 'ITEMCLASS'){
		        
		        if(isset($get['item_id']) && is_numeric($get['item_id'])){
		            
		            $item_id = (int) $get['item_id'];
		            
    		        $page = new SmartestItemPage;
		        
    		        if($item = SmartestCmsItem::retrieveByPk($get['item_id'])){
    	                $page->setPrincipalItem($item);
    	                $this->send($item, 'item');
    	                $this->send(true, 'show_item_options');
    	                $this->send(false, 'require_choose_item');
    	            }else{
    	                $this->send(true, 'require_choose_item');
    	                $require_item = true;
    	            }
	            
                }else{
                    // this is a meta page, but the item id is problematic
                    $page = new SmartestItemPage; // this is needed to prevent a fatal error when page is looked up via hydrateBy
                    $this->send(true, 'require_choose_item');
                    $require_item = true;
                }
		        
		    }else{
		        // this is just a normal static page
		        $item_id = '';
		        $page = new SmartestPage;
		        $this->send(false, 'require_choose_item');
		    }
		}else{
		    $page = new SmartestPage; // this is needed to prevent a fatal error when page is looked up via hydrateBy
		}
		
		if($page->hydrateBy('webid', $page_webid)){
	        
	        $page->setDraftMode(true);
	        
	        if(isset($require_item) && $require_item){
                
                $model = new SmartestModel;
                
                if($model->hydrate($page->getDatasetId())){
                    $items = $model->getSimpleItems($this->getSite()->getId());
                    $this->send($items, 'items');
                    $this->send($model, 'model');
                    $this->send($page, 'page');
                }
                
            }
	        
	        $placeholder = new SmartestPlaceholder;
	        
	        if($placeholder->hydrateBy('name', $placeholder_name)){
	            
	            $this->setTitle('Define Placeholder | '.$placeholder_name);
	            
	            $types_array = SmartestDataUtility::getAssetTypes();
                
                $page_definition = new SmartestPlaceholderDefinition;
                
                if($page_definition->load($placeholder_name, $page, true)){
	                
	                $is_defined = true;
	                
	                if($type_index[$page_webid] == 'ITEMCLASS'){
	                    
	                    $item_definition = new SmartestPlaceholderDefinition;
	                    if($item_definition->load($placeholder_name, $page, true, $item_id)){
	                        if($page_definition->getDraftAssetId() == $item_definition->getDraftAssetId()){
	                            $item_uses_default = true;
	                        }else{
	                            $item_uses_default = false;
	                        }
	                    }else{
	                        $item_uses_default = true;
	                    }
	                }
	                
	                if($existing_render_data = unserialize($page_definition->getDraftRenderData())){
	                    if(is_array($existing_render_data) && is_array($params)){
	                        
	                        foreach($params as $key => $value){
	                            if(isset($existing_render_data[$key])){
	                                $params[$key] = $existing_render_data[$key];
	                            }
	                        }
                        }
                    }
	                
	                $this->send($page_definition->getDraftAssetId(), 'draft_asset_id');
	                $this->send($page_definition->getLiveAssetId(), 'live_asset_id');
	                
	            }else{
	                $item_uses_default = false;
	                $is_defined = false;
	                $this->send($page_definition->getDraftAssetId(), 'draft_asset_id');
	                $existing_render_data = array();
	            }
	            
	            $this->send($item_uses_default, 'item_uses_default');
	            $this->send($is_defined, 'is_defined');
                
                $asset = new SmartestAsset;
                
                if($get['chosen_asset_id']){
                    
                    $chosen_asset_id = (int) $get['chosen_asset_id'];
                    $chosen_asset_exists = $asset->hydrate($chosen_asset_id);
                    
        	    }else{
        	        if($is_defined){
        	            
        	            // if asset is chosen
        	            if($type_index[$page_webid] == 'ITEMCLASS' && $item_definition->load($placeholder_name, $page, true, $item_id)){
        	                $chosen_asset_id = $item_definition->getDraftAssetId();
        	            }else{
        	                $chosen_asset_id = $page_definition->getDraftAssetId();
    	                }
    	                
        	            $chosen_asset_exists = $asset->hydrate($chosen_asset_id);
        	        }else{
        	            // No asset choasen. don't show params or 'continue' button
        	            $chosen_asset_id = 0;
        	            $chosen_asset_exists = false;
        	        }
        	    }
        	    
        	    if($chosen_asset_exists){
        	        
        	        $this->send($asset, 'asset');
        	        
        	        $type = $types_array[$asset->getType()];
        	        
        	        // Merge values for render data
        	        
        	        if(isset($type['param'])){

            	        $raw_xml_params = $type['param'];
                        $params = array();
            	        foreach($raw_xml_params as $rxp){
            	            
            	            if(isset($rxp['default'])){
            	                $params[$rxp['name']]['xml_default'] = $rxp['default'];
            	                $params[$rxp['name']]['value'] = $rxp['default'];
                            }else{
                                $params[$rxp['name']]['xml_default'] = '';
                                $params[$rxp['name']]['value'] = '';
                            }
                            
                            $params[$rxp['name']]['type'] = $rxp['type'];
                            $params[$rxp['name']]['asset_default'] = '';
            	        }
            	        
            	        $this->send($type, 'asset_type');

            	    }else{
            	        $params = array();
            	    }
            	    
            	    $asset_params = $asset->getDefaultParameterValues();
            	    
            	    $this->send($asset_params, 'asset_params');
            	    
            	    foreach($params as $key=>$p){
            	        // default values from xml are set above.
            	        
            	        // next, set values from asset
            	        if(isset($asset_params[$key]) && strlen($asset_params[$key])){
            	            // $params[$key]['value'] = $asset_params[$key];
            	            // $params[$key]['asset_default'] = $asset_params[$key];
            	        }
            	        
            	        // then, override any values that already exist
            	        if(isset($existing_render_data[$key]) && strlen($existing_render_data[$key])){
            	            $params[$key]['value'] = $existing_render_data[$key];
            	        }
        	        }
        	        
            	    $this->send(true, 'valid_definition');
            	    
    	        }else{
    	            
    	            $this->send(false, 'valid_definition');
    	            
    	        }
	            
	            $this->send($params, 'params');
	            
	            $assets = $placeholder->getPossibleAssets($this->getSite()->getId());
	            
	            $this->send($assets, 'assets');
	            $this->send($page, 'page');
	            $this->send($placeholder, 'placeholder');
	            
	        }
	    
        }else{
            $this->addUserMessageToNextRequest("The page ID was not recognized", SM_USER_MESSAGE_WARNING);
            $this->redirect('/smartest/pages');
        }
        
	}
	
	public function updatePlaceholderDefinition($get, $post){
	    
	    $placeholder_id = $post['placeholder_id'];
	    $page_id = $post['page_id'];
	    $asset_id = $post['asset_id'];
	    
	    $helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());
		
	    if(isset($type_index[$page_id])){
		    if($type_index[$page_id] == 'ITEMCLASS' && isset($post['item_id']) && is_numeric($post['item_id'])){
		        $page = new SmartestItemPage;
		    }else{
		        $page = new SmartestPage;
		    }
		}else{
		    $page = new SmartestPage;
		}
	    
	    if($page->hydrate($page_id)){
	        
	        $page->setDraftMode(true);
	        $placeholder = new SmartestPlaceholder;
	        
	        if($placeholder->hydrate($placeholder_id)){
	            
	            $definition = new SmartestPlaceholderDefinition;
	            
	            if($type_index[$page_id] == 'NORMAL' || (isset($post['item_id']) && is_numeric($post['item_id']) && $post['definition_scope'] != 'THIS')){
	                
	                if($definition->loadForUpdate($placeholder->getName(), $page)){
	                
	                    // update placeholder
	                    $definition->setDraftAssetId($asset_id);
	                    $log_message = $this->getUser()->__toString()." updated placeholder '".$placeholder->getName()."' on page '".$page->getTitle(true)."' to use asset ID ".$asset_id.".";
	                
	                }else{
	                
	                    // wasn't already defined
	                    $definition->setDraftAssetId($asset_id);
	                    $definition->setAssetclassId($placeholder_id);
	                    $definition->setInstanceName('default');
	                    $definition->setPageId($page->getId());
	                    $log_message = $this->getUser()->__toString()." defined placeholder '".$placeholder->getName()."' on page '".$page->getTitle(true)."' with asset ID ".$asset_id.".";
	                
	                }
	            
	                if(is_array($post['params'])){
	                    $definition->setDraftRenderData(serialize($post['params']));
	                }
	                
	                if($post['definition_scope'] == 'ALL'){
	                    
	                    // DELETE ALL PER-ITEM DEFINITIONS
	                    $pmh = new SmartestPageManagementHelper;
	                    $pmh->removePerItemDefinitions($page->getId(), $placeholder->getId());
	                    
	                }
	                
	                $definition->save();
	            
                }else if($type_index[$page_id] == 'ITEMCLASS' && (isset($post['item_id']) && is_numeric($post['item_id']) && $post['definition_scope'] == 'THIS')){
                    
                    if($definition->loadForUpdate($placeholder->getName(), $page)){ // looks for all-items definition
	                    
	                    $item_def = new SmartestPlaceholderDefinition;
	                    
	                    // item chosen is same as all-items definition
	                    if($definition->getDraftAssetId() == $asset_id){ 
	                        
	                        if(is_array($post['params'])){
	                            $now_prms = $post['params']; // copy needs to be made here because ksort() does not return
	                            $ex_prms = $definition->getRenderData(true);
	                            $default_def_params_hash = md5(serialize($ex_prms));
	                            $this_item_params_hash = md5(serialize($now_prms));
	                            $has_params = true;
                            }else{
                                $has_params = false;
                            }
	                        
	                        // if there is already a per-item definitions for this item
	                        if($item_def->loadForUpdate($placeholder->getName(), $page, $post['item_id'])){
	                            
	                            if($has_params && ($default_def_params_hash != $this_item_params_hash)){
	                                // don't delete, because display params are different to default.
	                                $item_def->setDraftRenderData(serialize($post['params']));
	                                $item_def->save();
	                            }else{
	                                $item_def->delete();
                                }
                                
	                        }else{ // No per-item definition found for this one so create *IF* the params are different.
	                            if($has_params && ($default_def_params_hash != $this_item_params_hash)){
	                                $item_def->setDraftAssetId($asset_id);
        	                        $item_def->setAssetclassId($placeholder_id);
        	                        $item_def->setItemId($post['item_id']);
        	                        $item_def->setInstanceName('default');
        	                        $item_def->setPageId($page->getId());
	                                $item_def->setDraftRenderData(serialize($post['params']));
                                    $item_def->save();
                                }
                                
	                        }
	                        
	                        $log_message = $this->getUser()->__toString()." set placeholder '".$placeholder->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." (which is the same as the all-items definition) when displaying item ID ".$post['item_id'].".";
	                    
	                    }else{
	                        
	                        if($item_def->loadForUpdate($placeholder->getName(), $page, $post['item_id'])){
	                            // just update placeholder
	                            $item_def->setDraftAssetId($asset_id);
	                            $log_message = $this->getUser()->__toString()." updated placeholder '".$placeholder->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                        }else{
	                            $item_def->setDraftAssetId($asset_id);
        	                    $item_def->setAssetclassId($placeholder_id);
        	                    $item_def->setItemId($post['item_id']);
        	                    $item_def->setInstanceName('default');
        	                    $item_def->setPageId($page->getId());
	                            $log_message = $this->getUser()->__toString()." defined placeholder '".$placeholder->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                        }
	                        
	                        if(is_array($post['params'])){
        	                    $item_def->setDraftRenderData(serialize($post['params']));
        	                }
        	                
        	                $item_def->save();
	                        
	                    }
	                
	                }else if($definition->loadForUpdate($placeholder->getName(), $page, $post['item_id'])){
	                    
	                    // all-items definition doesn't exist but per-item for this item does
	                    $definition->setDraftAssetId($asset_id);
	                    
	                    if(is_array($post['params'])){
    	                    $definition->setDraftRenderData(serialize($post['params']));
    	                }
    	                
    	                $definition->save();
    	                $log_message = $this->getUser()->__toString()." updated placeholder '".$placeholder->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                    
	                }else{
	                    
	                    // wasn't already defined for any items at all. Define for this item
	                    $definition->setDraftAssetId($asset_id);
	                    $definition->setAssetclassId($placeholder_id);
	                    $definition->setItemId($post['item_id']);
	                    $definition->setInstanceName('default');
	                    $definition->setPageId($page->getId());
	                    
	                    if(is_array($post['params'])){
    	                    $definition->setDraftRenderData(serialize($post['params']));
    	                }
    	                
    	                $definition->save();
    	                $log_message = $this->getUser()->__toString()." defined placeholder '".$placeholder->getName()."' on meta-page '".$page->getTitle(true)."' to use asset ID ".$asset_id." when displaying item ID ".$post['item_id'].".";
	                    
	                }
	                
                }
	            
	            $page->setChangesApproved(0);
                $page->setModified(time());
                $page->save();
	            
	            $this->addUserMessageToNextRequest('The placeholder was updated.', SmartestUserMessage::SUCCESS);
	            SmartestLog::getInstance('site')->log($log_message, SM_LOG_USER_ACTION);
	            
	        }else{
	            
	            $this->addUserMessageToNextRequest('The specified placeholder doesn\'t exist', SmartestUserMessage::ERROR);
	            
	        }
	    
        }else{
            
            $this->addUserMessageToNextRequest('The specified page doesn\'t exist', SmartestUserMessage::ERROR);
            
        }
        
        $this->formForward();
	    
	}
	
	public function undefinePlaceholder($get, $post){
	    
	    $placeholder_id = $get['assetclass_id'];
	    $page_id = $get['page_id'];
	    $item_id = isset($get['item_id']) ? $get['item_id'] : false;
	    
	    $this->setTitle('Un-Define Placeholder');
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($page_id)){
	        
	        $page->setDraftMode(true);
	        
	        $placeholder = new SmartestPlaceholder;
	        
	        if($placeholder->hydrateBy('name', $placeholder_id)){
	            
	            $definition = new SmartestPlaceholderDefinition;
	            
	            if($definition->loadForUpdate($placeholder->getName(), $page, $item_id)){
	                
	                // update placeholder
	                $definition->delete();
	                $this->addUserMessageToNextRequest('The placeholder definition was removed for this item.', SmartestUserMessage::SUCCESS);
	            
	            }else if($definition->loadForUpdate($placeholder->getName(), $page)){
	                
	                // update placeholder
	                $definition->setDraftAssetId('');
	                $definition->save();
	                $this->addUserMessageToNextRequest('The placeholder definition was removed.', SmartestUserMessage::SUCCESS);
	                
	            }else{
	                
	                // wasn't already defined
	                $this->addUserMessageToNextRequest('The placeholder wasn\'t defined to start with.', SmartestUserMessage::INFO);
	                
	                
	            }
	            
	            $page->setChangesApproved(0);
                $page->setModified(time());
                $page->save();
	            
	        }else{
	            
	            $this->addUserMessageToNextRequest('The specified placeholder doesn\'t exist', SmartestUserMessage::ERROR);
	            
	        }
	    
        }else{
            
            $this->addUserMessageToNextRequest('The specified page doesn\'t exist', SmartestUserMessage::ERROR);
            
        }
        
        $this->formForward();
	    
	}
	
	public function undefinePlaceholderOnItemPage($get, $post){
	    
	    $placeholder_id = $get['assetclass_id'];
	    $page_id = $get['page_id'];
	    $item_id = $get['item_id'];
	    
	    $this->setTitle('Un-Define Placeholder');
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($page_id)){
	        
	        $page->setDraftMode(true);
	        
	        $placeholder = new SmartestPlaceholder;
	        
	        if($placeholder->hydrateBy('name', $placeholder_id)){
	            
	            $definition = new SmartestPlaceholderDefinition;
	            
	            if($definition->loadForUpdate($placeholder->getName(), $page, $item_id)){
	                
	                // update placeholder
	                $definition->delete();
	                $this->addUserMessageToNextRequest('The placeholder definition was removed for this item.', SmartestUserMessage::SUCCESS);
	                
	            }else{
	                
	                // wasn't already defined
	                $this->addUserMessageToNextRequest('The placeholder wasn\'t defined to start with.', SmartestUserMessage::INFO);
	                
	                
	            }
	            
	            $page->setChangesApproved(0);
                $page->setModified(time());
                $page->save();
	            
	        }else{
	            
	            $this->addUserMessageToNextRequest('The specified placeholder doesn\'t exist', SmartestUserMessage::ERROR);
	            
	        }
	    
        }else{
            
            $this->addUserMessageToNextRequest('The specified page doesn\'t exist', SmartestUserMessage::ERROR);
            
        }
        
        $this->formForward();
	    
	}
	
	public function undefineContainer($get, $post){
	    
	    $container_id = $get['assetclass_id'];
	    $page_id = $get['page_id'];
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($page_id)){
	        
	        $page->setDraftMode(true);
	        
	        $container = new SmartestContainer;
	        
	        if($container->hydrateBy('name', $container_id)){
	            
	            $definition = new SmartestContainerDefinition;
	            
	            if(isset($get['item_id']) && $definition->loadForUpdate($container->getName(), $page, true, $get['item_id'])){
	            
	                $definition->delete();
	                $this->addUserMessageToNextRequest('The container definition was removed.', SmartestUserMessage::SUCCESS);
	            
	            }else if($definition->loadForUpdate($container->getName(), $page, true)){
	                
	                // update placeholder
	                // $definition->delete();
	                $definition->setDraftAssetId('');
	                $definition->save();
	                $this->addUserMessageToNextRequest('The container definition was removed.', SmartestUserMessage::SUCCESS);
	                
	            }else{
	                
	                // wasn't already defined
	                $this->addUserMessageToNextRequest('The container wasn\'t defined to start with.', SmartestUserMessage::INFO);
	                
	                
	            }
	            
	            $page->setChangesApproved(0);
                $page->setModified(time());
                $page->save();
	            
	        }else{
	            
	            $this->addUserMessageToNextRequest('The specified container doesn\'t exist', SmartestUserMessage::ERROR);
	            
	        }
	    
        }else{
            
            $this->addUserMessageToNextRequest('The specified page doesn\'t exist', SmartestUserMessage::ERROR);
            
        }
        
        $this->formForward();
	    
	}
	
	public function undefineContainerOnItemPage($get, $post){
	    
	    $container_id = $get['assetclass_id'];
	    $page_id = $get['page_id'];
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($page_id)){
	        
	        $page->setDraftMode(true);
	        
	        $container = new SmartestContainer;
	        
	        if($container->hydrateBy('name', $container_id)){
	            
	            $definition = new SmartestContainerDefinition;
	            
	            if(isset($get['item_id']) && $definition->loadForUpdate($container->getName(), $page, true, $get['item_id'])){
	            
	                $definition->delete();
	                $this->addUserMessageToNextRequest('The container definition was removed.', SmartestUserMessage::SUCCESS);
	            
	            }else{
	                
	                // wasn't already defined
	                $this->addUserMessageToNextRequest('The container wasn\'t defined to start with.', SmartestUserMessage::INFO);
	                
	                
	            }
	            
	            $page->setChangesApproved(0);
                $page->setModified(time());
                $page->save();
	            
	        }else{
	            
	            $this->addUserMessageToNextRequest('The specified container doesn\'t exist', SmartestUserMessage::ERROR);
	            
	        }
	    
        }else{
            
            $this->addUserMessageToNextRequest('The specified page doesn\'t exist', SmartestUserMessage::ERROR);
            
        }
        
        $this->formForward();
	    
	}
	
	public function editAttachment($get){
	    
	    $id = $get['assetclass_id'];
	    $page_webid = $get['page_id'];
	    $parts = explode('/', $id);
	    $asset_stringid = $parts[0];
	    $attachment = $parts[1];
	    $asset = new SmartestAsset;
	    
	    if($asset->hydrateBy('stringid', $asset_stringid)){
	        $this->redirect('/assets/defineAttachment?attachment='.$attachment.'&asset_id='.$asset->getId());
	    }else{
	        // $page = new SmartestPage;
	        if(strlen($page_webid) == 32){
	            $this->redirect('/websitemanager/pageAssets?page_id='.$page_webid);
	            $this->addUserMessageToNextRequest("The attachment ID was not recognized.", SmartestUserMessage::ERROR);
	        }else{
	            $this->redirect('/smartest/pages');
	        }
	    }
	}
	
	public function editFile($get){
	    
	    $id = $get['assetclass_id'];
	    $page_webid = $get['page_id'];
	    $asset = new SmartestAsset;
	    
	    if($asset->hydrateBy('stringid', $id, $this->getSite()->getId())){
            $this->redirect('/assets/editAsset?assettype_code='.$asset->getType().'&asset_id='.$asset->getId().'&from=pageAssets');
        }else{
            if(strlen($page_webid) == 32){
	            $this->redirect('/websitemanager/pageAssets?page_id='.$page_webid);
	            $this->addUserMessageToNextRequest("The file ID was not recognized.", SmartestUserMessage::ERROR);
	        }else{
	            $this->redirect('/smartest/pages');
	        }
        }
	}
	
	public function editTemplate($get){
	    
	    $id = $get['assetclass_id'];
	    $page_webid = $get['page_id'];
	    $asset = new SmartestTemplateAsset;
	    
	    if($asset->hydrateBy('stringid', $id)){
            $this->redirect('/templates/editTemplate?type=SM_ASSETTYPE_CONTAINER_TEMPLATE&template='.$asset->getId().'&from=pageAssets');
        }else{
            if(strlen($page_webid) == 32){
	            $this->redirect('/websitemanager/pageAssets?page_id='.$page_webid);
	            $this->addUserMessageToNextRequest("The template ID was not recognized.", SmartestUserMessage::ERROR);
	        }else{
	            $this->redirect('/smartest/pages');
	        }
        }
	}
	
	function setPageTemplate($get){
		$template_name = $get["template_name"];
		$field = ($get["version"] == "live") ? "page_live_template" : "page_draft_template";
		$version = ($get["version"] == "live") ? "live" : "draft";
		
		$page_id = $get["page_id"];
		$this->database->query("UPDATE Pages SET $field='$template_name' WHERE page_webid='$page_id'");
		// header("Location:".$this->domain.$this->module."/getPageAssets?page_id=$page_id&version=$version");
		$this->formForward();
	}
	
	function setPageTemplateForLists($get){
		$template_name = $get["template_name"];
		$version = ($get["version"] == "live") ? "live" : "draft";
		$field = ($get["version"] == "live") ? "page_live_template" : "page_draft_template";
		// echo $get["version"];
		$page_id = $get["page_id"];
		$this->database->query("UPDATE Pages SET $field='$template_name' WHERE page_webid='$page_id'");
		header("Location:".$this->domain.$this->module."/getPageLists?page_id=$page_id&version=$version");
	}
	
	public function setDraftAsset($get){

		$this->manager->setDraftAsset($get['page_id'], $get['assetclass_id'], $get['asset_id']);
		$this->formForward();
		// header("Location:".$this->domain.$this->module."/defineAssetClass?assetclass_id=".$get["assetclass_id"]."&page_id=".$get["page_id"]);
	}
	
	function setLiveAsset($get){
		
		$this->manager->setLiveAsset($get['page_id'], $get['assetclass_id']);
		
		$page_pk = $this->manager->database->specificQuery("page_id", "page_webid", $get['page_id'], "Pages");
		
		if(is_numeric($get['assetclass_id']) && @$get['assetclass_id']){
			$assetclass = $this->manager->database->specificQuery("assetclass_name", "assetclass_id", $get['assetclass_id'], "AssetClasses");
		}else{
			$assetclass = $get['assetclass_id'];
		}
		
		
		// This code clears the cached placeholders
		$cache_filename = "System/Cache/SmartestEngine/"."ac_".md5($assetclass)."-".$page_pk.".tmp";
		
		if(is_file($cache_filename) && SM_OPTIONS_CACHE_ASSETCLASSES){
			@unlink($cache_filename);
		}
		
		$this->formForward();
		
		//header("Location:".$this->domain.$this->module."/defineAssetClass?assetclass_id=".$get["assetclass_id"]."&page_id=".$get["page_id"]);
	}
	
	function publishPageContainersConfirm($get){
		$page_webid=$get['page_id'];
		$version="draft";
		$undefinedContainerClasses=$this->manager->publishPageContainersConfirm($page_webid,$version);
		$count=count($undefinedContainerClasses);
		return array ("undefinedContainerClasses"=>$undefinedContainerClasses,"page_id"=>$page_webid,"count"=>$count);
	}
	
	function publishPageContainers($get){
		$page_webid=$get['page_id'];
// 		echo $page_webid;
		$this->manager->publishPageContainers($page_webid);
		$this->formForward();
	}
	
	function publishPagePlaceholdersConfirm($get){
		$page_webid=$get['page_id'];
		$version="draft";
		$undefinedPlaceholderClasses=$this->manager->publishPagePlaceholdersConfirm($page_webid,$version);
		$count=count($undefinedPlaceholderClasses);
		return array ("undefinedPlaceholderClasses"=>$undefinedPlaceholderClasses,"page_id"=>$page_webid,"count"=>$count);
			
	}
	
	function publishPagePlaceholders($get){
		$page_webid=$get['page_id'];
		$this->manager->publishPagePlaceholders($page_webid);
		$this->formForward();
	}
	
	function publishPageConfirm($get){
		
		// display to the user a list of any placeholders or containers that are undefined in the draft page that is about to be published,
		// so that the user is warned before publishing undefined placeholders or containers that may cause the page to display incorrectly
		// the user should be able to publish either way - the notice will be just a warning.
		
		$helper = new SmartestPageManagementHelper;
		$type_index = $helper->getPageTypesIndex($this->getSite()->getId());
		$page_webid = $get['page_id'];
		
	    if(isset($type_index[$page_webid])){
		    if($type_index[$page_webid] == 'ITEMCLASS' && isset($get['item_id']) && is_numeric($get['item_id'])){
		        $page = new SmartestItemPage;
		    }else{
		        $page = new SmartestPage;
		    }
		}else{
		    $page = new SmartestPage;
		}
		
		if($page->hydrate($page_webid)){
		    
		    if($page->getType() == 'ITEMCLASS'){
                if(isset($get['item_id']) && $item = SmartestCmsItem::retrieveByPk($get['item_id'])){
                    
                    $page->setPrincipalItem($item);
                    $this->send($item, 'item');
                    $item_id = $get['item_id'];
                    
                    $user_can_publish_item = ($this->getUser()->hasToken('publish_approved_items') && $item->isApproved()) || $this->getUser()->hasToken('publish_all_items');
                    
                    $this->send($user_can_publish_item, 'user_can_publish_item');
                    
                }else{
                    $item_id = false;
                }
            }
            
            $page->setDraftMode(true);
		    
		    if(( (boolean) $page->getChangesApproved() && $this->getUser()->hasToken('publish_approved_pages')) || $this->getUser()->hasToken('publish_all_pages')){
		        
		        $version = "draft";
		        $undefinedAssetsClasses = $this->manager->getUndefinedElements($page_webid, 'draft', $item_id);
		        
		        $count = count($undefinedAssetsClasses);
		        $this->send(true, 'allow_publish');
		        $this->send($undefinedAssetsClasses, "undefined_asset_classes");
		        $this->send($page->getWebId(), "page_id");
		        $this->send(count($undefinedAssetsClasses), "count");
		    
	        }else{
	            
	            $this->send(false, 'allow_publish');
	            $this->send($page->getWebId(), "page_id");
	            
	            if((boolean) $page->getChangesApproved()){
		            $this->addUserMessage('You can\'t publish this page because you don\'t have permission to publish pages.', SmartestUserMessage::ACCESS_DENIED);
		        }else{
		            $this->addUserMessage('You can\'t publish this page because the changes on it haven\'t yet been approved and you don\'t have permission to override approval.', SmartestUserMessage::ACCESS_DENIED);
		        }
	            
	        }
		
	    }else{
	        
	        $this->addUserMessage('The page could not be found');
	        
	    }
			
	}
	
	public function publishPage($get, $post){
	    
	    $page = new SmartestPage;
	    $page_webid = $post['page_id'];
	    if(isset($post['item_id'])){$item_id = $post['item_id'];}else{$item_id = false;}
	    
	    if($page->hydrate($page_webid)){
	        
	        $page->setDraftMode(true);
	        
	        if(((boolean) $page->getChangesApproved() || $this->getUser()->hasToken('approve_page_changes')) && ($this->getUser()->hasToken('publish_approved_pages')) || $this->getUser()->hasToken('publish_all_pages')){
		        
		        $page->publish($item_id);
		        SmartestLog::getInstance('site')->log("{$this->getUser()} published page: {$page->getTitle()}.", SmartestLog::USER_ACTION);
		        
		        if(isset($post['item_id']) && $item = SmartestCmsItem::retrieveByPk($post['item_id'])){
                    
                    $user_can_publish_item = ($this->getUser()->hasToken('publish_approved_items') && $item->isApproved()) || $this->getUser()->hasToken('publish_all_items');
                    
                    if($user_can_publish_item){
                        if($post['publish_item'] == 'PUBLISH'){
                            $item->publish();
                            $this->addUserMessageToNextRequest('The page and the item '.$item->getName().' have both been successfully published.', SmartestUserMessage::SUCCESS);
                        }else{
                            $this->addUserMessageToNextRequest('The page has been successfully published.', SmartestUserMessage::SUCCESS);
                        }
                    }else{
                        $this->addUserMessageToNextRequest('The page has been successfully published, but the item could not be published.', SmartestUserMessage::INFO);
                    }
                    
                }else{
                    
                    $this->addUserMessageToNextRequest('The page has been successfully published.', SmartestUserMessage::SUCCESS);
                    
                }
		        
	        }else{
	            
	            if((boolean) $page->getChangesApproved()){
		            $this->addUserMessageToNextRequest('The page could not be published because you don\'t have permission to publish pages', SmartestUserMessage::ACCESS_DENIED);
		        }else{
		            $this->addUserMessageToNextRequest('The page could not be published because the changes on it haven\'t yet been approved and you don\'t have permission to approve pages', SmartestUserMessage::ACCESS_DENIED);
		        }
	            
	        }
        }
        
        $this->formForward();
	}
	
	public function unPublishPage($get){
	    
	    $page_webid = $get['page_id'];
		$page = new SmartestPage;
		
		if($page->hydrate($page_webid)){
		    
		    $page->setDraftMode(true);
		    $page->unpublish();
		    
		}
		
		$this->addUserMessageToNextRequest('The page has been un-published. No other changes have been made.', SmartestUserMessage::SUCCESS);
		$this->formForward();
		
	}

	function getPageLists($get){
		
		$this->setFormReturnUri();
		
		$page_webid = $get['page_id'];
		$version = ($get['version'] == "live") ? "live" : "draft";
		$field = ($version == "live") ? "page_live_template" : "page_draft_template";
		$site_id = $this->database->specificQuery("page_site_id", "page_webid", $get['page_id'], "Pages");
		$page = $this->manager->getPage($get['page_id']);
		$pageListNames = $this->manager->getPageLists($page_webid, $version);
 		
 		return array("pageListNames"=>$pageListNames,"page"=>$page,"version"=>$version,"templateMenuField"=>$page[$field],"site_id"=>$site_id);	
	}
	
	function defineList($get){
        
        $templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/ListItems/');
        
        $list_name = $get['assetclass_id'];
        
        $page_webid = $get['page_id'];
        
        $page = new SmartestPage;
        
        if($page->hydrate($page_webid)){
            
            $page->setDraftMode(true);
            
            $list = new SmartestCmsItemList;
            
            if($list->load($list_name, $page, true)){
                // this list was already defined
            }else{
                // this is a new list
            }
            
            $this->send($list->getDraftHeaderTemplate(), 'header_template');
            $this->send($list->getDraftFooterTemplate(), 'footer_template');
            $this->send($list->getDraftTemplateFile(), 'main_template');
            $this->send($list->getDraftSetId(), 'set_id');
            $this->send($list, 'list');
            $this->send($list_name, 'list_name');
            
            $alh = new SmartestAssetsLibraryHelper;
            $this->send($alh->getAssetsByTypeCode('SM_ASSETTYPE_COMPOUND_LIST_TEMPLATE', $this->getSite()->getId()), 'compound_list_templates');
            
            $tlh = new SmartestTemplatesLibraryHelper;
            $this->send($tlh->getArticulatedListTemplates($this->getSite()->getId()), 'art_list_templates');
            
            $datautil = new SmartestDataUtility;
            
            $sets = $datautil->getDataSetsAsArrays(false, $this->getSite()->getId());
            $this->send($sets, 'sets');
            $this->send($page, 'page');
            $this->send($templates, 'templates');
            
        }else{
            // page was not found
            $this->addUserMessageToNextRequest("The page ID was not recognised.", SmartestUserMessage::ERROR);
            $this->formForward();
        }
        
		/* $page_id = $this->manager->getPageIdFromPageWebId($get['page_id']);
		$list_name = $get['list_id'];

		$page = $this->manager->getPage($page_id);
		$sets = $this->setsManager->getSets();
		// $path = 'Presentation/ListItems'; 
		// $listitemtemplates = $this->templatesManager->getTemplateNames($path);
		
		$sql = "SELECT * FROM Lists WHERE list_page_id = '$page_id' AND list_name = '$list_name'";
		$result = $this->database->queryToArray($sql);
		$items = $this->manager->managePageData($result);
 		
 		$list_setid = $result[0]['list_draft_set_id'];
		$list_template = $result[0]['list_draft_template_file'];
		$list_header = $result[0]['list_draft_header_template'];
		$list_footer = $result[0]['list_draft_footer_template']; */
		
		// return array("page"=>$page, "sets"=>$sets, "listitemtemplates"=>$templates, "list_setid"=>$list_setid, "list_template"=>$list_template, "list_header"=>$list_header, "list_footer"=>$list_footer,"list_name"=>$list_id);
	
	}
	
	function saveList($get, $post){
	    
	    $list_name = $post['list_name'];
        
        $page_id = $post['page_id'];
        
        $page = new SmartestPage;
        
        if($page->hydrate($page_id)){
            
            $page->setDraftMode(true);
            
            $list = new SmartestCmsItemList;
            
            if($list->load($list_name, $page, true)){
                // this list was already defined
                $this->addUserMessageToNextRequest("The list \"".$list_name."\" was updated successfully.", SmartestUserMessage::SUCCESS);
            }else{
                // this is a new list
                $list->setName($post['list_name']);
                $list->setPageId($page->getId());
                $this->addUserMessageToNextRequest("The list \"".$list_name."\" was defined successfully.", SmartestUserMessage::SUCCESS);
            }
            
            $list_type = in_array($post['list_type'], array('SM_LIST_ARTICULATED', 'SM_LIST_SIMPLE')) ? $post['list_type'] : 'SM_LIST_SIMPLE';
            
            $list->setType($list_type);
            $list->setMaximumLength((int) $post['list_maximum_length']);
            $list->setTItle($post['list_title']);
            
            if($list_type == 'SM_LIST_ARTICULATED'){
            
                $templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/ListItems/');
            
                if(is_numeric($post['dataset_id'])){
                    $list->setDraftSetId($post['dataset_id']);
                }
            
                if(in_array($post['header_template'], $templates)){
                    $list->setDraftHeaderTemplate($post['header_template']);
                }
            
                if(in_array($post['footer_template'], $templates)){
                    $list->setDraftFooterTemplate($post['footer_template']);
                }
            
                if(in_array($post['main_template'], $templates)){
                    $list->setDraftTemplateFile($post['main_template']);
                }
            
            }else{
                
                if(is_numeric($post['dataset_id'])){
                    $list->setDraftSetId((int) $post['dataset_id']);
                }
                
                $list->setDraftTemplateFile($post['art_main_template']);
                
            }
            
            $list->save();
            
            $this->formForward();
            
            // print_r($list->__toArray());
            /* $this->send($list->getDraftHeaderTemplate(), 'header_template');
            $this->send($list->getDraftFooterTemplate(), 'footer_template');
            $this->send($list->getDraftTemplateFile(), 'main_template');
            $this->send($list->getDraftSetId(), 'set_id');
            $this->send($list->__toArray(), 'list');
            $this->send($list_name, 'list_name');
            
            $sets = $this->getSite()->getDataSetsAsArrays();
            $this->send($sets, 'sets');
            $this->send($page->__toArray(), 'page');
            $this->send($templates, 'templates'); */
            
        }else{
            // page was not found
            $this->addUserMessageToNextRequest("The page ID was not recognizsed.", SmartestUserMessage::ERROR);
            $this->formForward();
        }
	    
	}
	
	public function clearList($get){
	    
	    $list_name = $get['assetclass_id'];
        
        $page_id = $get['page_id'];
        
        $page = new SmartestPage;
        
        if($page->hydrate($page_id)){
            
            $page->setDraftMode(true);
            
            $list = new SmartestCmsItemList;
            
            if($list->load($list_name, $page, true)){
                // this list was already defined
                $list->delete();
                $this->addUserMessageToNextRequest("The list \"".$list_name."\" was updated successfully.", SmartestUserMessage::SUCCESS);
            }else{
                $this->addUserMessageToNextRequest("The list \"".$list_name."\" was not defined.", SmartestUserMessage::INFO);
            }
            
            $this->formForward();
            
        }else{
            
            $this->addUserMessageToNextRequest("The page ID was not recognizsed.", SmartestUserMessage::ERROR);
            $this->formForward();
            
        }
	    
	}
	
	/* function insertList($get){
		
		$page_webid = $get['page_id'];
		$page_id=$this->manager->getPageIdfromPageWebId($page_webid);
		$list_name = $get['list_name'];
		$set_id = $get['dataset'];
		$list_template = $get['listtemplate_name'];
		$header_template = $get['header_template'];
		$footer_template = $get['footer_template'];
		$this->manager->insertList($page_id,$list_name,$set_id,$list_template,$header_template,$footer_template);
		
		$this->formForward();
			
	} */
	
	public function publishListsConfirm($get){
		$page_webid=$get['page_id'];
		$version="draft";
		$undefinedLists=$this->manager->publishListsConfirm($page_webid, $version);
		$count=count($undefinedLists);
		return array ("undefinedLists"=>$undefinedLists,"page_id"=>$page_webid,"count"=>$count);
	}
	
	public function publishPageLists($get){
		$page_webid=$get['page_id'];
		$this->manager->publishPageLists($page_webid);
		$this->formForward();
	}
	
	public function addItemSpace($get){
	    
	    $new_name = SmartestStringHelper::toVarName($get['name']);
	    $item_space = new SmartestItemSpace;
	    
	    if($item_space->exists($new_name, $this->getSite()->getId())){
	        // item space already exists with this name
	        $this->send(false, 'allow_continue');
	    }else{
	        
	        // get templates
	        $assetshelper = new SmartestAssetsLibraryHelper;
	        $templates = $assetshelper->getAssetsByTypeCode('SM_ASSETTYPE_ITEMSPACE_TEMPLATE', $this->getSite()->getId());
	        $this->send($templates, 'templates');
	        
	        // get sets
	        $du = new SmartestDataUtility;
	        $sets = $du->getDataSets(false, $this->getSite()->getId());
	        $this->send($sets, 'sets');
	        
	        $this->send($new_name, 'name');
	        
	        $this->send(true, 'allow_continue');
	    }
	    
	}
	
	public function insertItemSpace($get, $post){
	    
	    $new_name = SmartestStringHelper::toVarName($post['itemspace_name']);
	    $item_space = new SmartestItemSpace;
	    
	    if(strlen($new_name)){
	    
	        if($item_space->exists($new_name, $this->getSite()->getId())){
	            // item space already exists with this name
	            $this->addUserMessageToNextRequest('An itemspace with that name already exists', SmartestUserMessage::WARNING);
	        }else{
	        
	            $item_space->setName($new_name);
	            $item_space->setLabel($new_name);
	            $item_space->setSiteId($this->getSite()->getId());
	        
	            $dataset_id = (int) $post['itemspace_dataset_id'];
	            $item_space->setDataSetId($dataset_id);
	        
	            $use_template = isset($post['itemspace_use_template']);
	            $item_space->setUsesTemplate($use_template);
	        
	            if($use_template){
	                $template_id = (int) $post['itemspace_template_id'];
    	            $item_space->setTemplateAssetId($template_id);
	            }
	        
	            $this->addUserMessageToNextRequest('An itemspace called \''.$new_name.'\' has been created.', SmartestUserMessage::SUCCESS);
	            $item_space->save();
	        }
	        
        }else{
            $this->addUserMessageToNextRequest('You didn\'t enter a name for the itemspace. Please try again.', SmartestUserMessage::WARNING);
        }
        
        $this->formForward();
        
	}
	
	public function defineItemspace($get){
	    
	    $page = new SmartestPage;
	    $page_webid = $get['page_id'];
	    
	    if($page->hydrate($page_webid)){
	        
	        $page->setDraftMode(true);
	        
	        $name = SmartestStringHelper::toVarName($get['assetclass_id']);
	    
    	    $item_space = new SmartestItemSpace;
            
            if($item_space->exists($name, $this->getSite()->getId())){
            
                $definition = new SmartestItemSpaceDefinition;
            
                if($definition->load($name, $page, true)){
                    $definition_id = $definition->getItemId(true);
                }else{
                    $definition_id = 0;
                }
                
                $options = $item_space->getOptions();
                
                $this->send($definition_id, 'definition_id');
                $this->send($options, 'options');
                $this->send($item_space->__toArray(), 'itemspace');
                $this->send($page, 'page');
                
            }else{
                $this->addUserMessageToNextRequest("The itemspace ID wasn't recognized", SmartestUserMessage::ERROR);
                $this->formForward();
            }
        
        }else{
            
            $this->addUserMessageToNextRequest("The page ID wasn't recognized", SmartestUserMessage::ERROR);
            $this->formForward();
            
        }
	    
	}
	
	public function updateItemspaceDefinition($get, $post){
	    
	    $page = new SmartestPage;
	    $page_id = $post['page_id'];
	    
	    if($page->hydrate($page_id)){
	        
	        $page->setDraftMode(true);
	        
	        $name = SmartestStringHelper::toVarName($post['itemspace_name']);
	    
    	    $item_space = new SmartestItemSpace;
        
            if($exists = $item_space->exists($name, $this->getSite()->getId())){
            
                $definition = new SmartestItemSpaceDefinition;
            
                if(!$definition->load($name, $page, true)){
                    $definition->setItemSpaceId($item_space->getId());
                    $definition->setPageId($page->getId());
                }
                
                $definition->setDraftItemId($post['item_id']);
                
                $this->addUserMessageToNextRequest("The itemspace ID was successfully updated", SmartestUserMessage::SUCCESS);
                $definition->save();
                
            }else{
                $this->addUserMessageToNextRequest("The itemspace ID wasn't recognized", SmartestUserMessage::ERROR);
            }
        
        }else{
            
            $this->addUserMessageToNextRequest("The page ID wasn't recognized", SmartestUserMessage::ERROR);
            
        }
        
        $this->formForward();
	    
	}
	
	public function openItem($get){
	    
	    $item = new SmartestItem;
	    
	    if($item->findBy('slug', $get['assetclass_id'], $this->getSite()->getId())){
	        
	        $this->redirect('/datamanager/openItem?item_id='.$item->getId());
	        
	    }
	    
	}
	
	public function addPageUrl($get){
	    
	    $page_webid=$get['page_id'];
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($page_webid)){
		    
		    $page->setDraftMode(true);
		    
		    $ishomepage = $get['ishomepage'];
		    $page_id = $page->getId();
		    $page_info = $page->__toArray();
		    $page_info['site'] = $page->getSite()->__toArray();
		    
		    $this->send($page_info, "pageInfo");
		    $this->send($page->isHomePage(), "ishomepage");
		
	    }
	    
		// return array("pageInfo"=>$page_info, "msg"=>$msg, "ishomepage"=>$ishomepage );
	}
	
	public function addNewPageUrl($get,$post){
		
		$url = new SmartestPageUrl;
		
		if($url->existsOnSite($post['page_url'], $this->getSite()->getId())){
		    $this->addUserMessageToNextRequest("That URL already exists for another page.", SmartestUserMessage::WARNING);
		}else{
		    
		    $page = new SmartestPage;
		    
		    if($page->hydrate($post['page_id'])){
		        // $page->setDraftMode(true);
		        // $page->addUrl($post['page_url']);
		        // $page->save();
		        $url = new SmartestPageUrl;
		        $url->setUrl(SmartestStringHelper::sanitize($post['page_url']));
		        $url->setPageId($page->getId());
		        $url->setType(isset($post['forward_to_default']) ? 'SM_PAGEURL_INTERNAL_FORWARD' : 'SM_PAGEURL_NORMAL');
		        $url->setIsDefault(0);
		        $url->save();
		        SmartestLog::getInstance('site')->log("{$this->getUser()} added URL '{$post['page_url']}' to page: {$page->getTitle()}.", SmartestLog::USER_ACTION);
		        $this->addUserMessageToNextRequest("The new URL was successully added.", SmartestUserMessage::SUCCESS);
		    }else{
		        $this->addUserMessageToNextRequest("The page ID was not recognized.", SmartestUserMessage::ERROR);
		    }
		    
		}
		
		$this->formForward();
		
		/* $page_webid=$post['page_webid'];
		$page_id = $this->manager->database->specificQuery("page_id", "page_webid", $page_webid, "Pages");
		$page_url=$post['page_url'];
		$url_count = $this->manager->checkUrl($page_url);
		
		if($url_count > 0){
			header("Location:".$this->domain.$this->module."/addPageUrl?page_id=$page_webid&msg=1");
		}else{
			$this->manager->insertNewUrl($page_id,$page_url);
			$this->formForward();
		} */
	}
	
	public function editPageUrl($get){
		
		$page_webid = $get['page_id'];
		
		$page = new SmartestPage;
		$url = new SmartestPageUrl;
		
		if($url->find($get['url_id'])){
		    
		    $this->send($url, "url");
		
    		if($page->find($url->getPageId())){
    		    
    		    if($page->getType() == "ITEMCLASS"){
    		        $model = new SmartestModel;
    		        $model->find($page->getDatasetId());
    		        $this->send($model, "model");
    		    }
    		    
    		    $page->setDraftMode(true);
    		    $site = $page->getSite();
    		    $this->send($site, 'site');
    		    $this->send($page->isHomepage(), "ishomepage");
    		    $this->send($page, "pageInfo");
    	    }
	    }
	}
	
	public function updatePageUrl($get,$post){
		
		$page_webid = $post['page_webid'];
		$page_url = $post['page_url'];
		$url_id = $post['url_id'];
		
		$url = new SmartestPageUrl;
		$url->hydrate($url_id);
		
		if(isset($post['forward_to_default']) && $post['forward_to_default'] == 1){
		    
		    if($url->getIsDefault()){
		        $url->setType('SM_PAGEURL_NORMAL');
		        $this->addUserMessageToNextRequest("The default URL cannot also be an internal forward");
		    }else{
		        $url->setType('SM_PAGEURL_INTERNAL_FORWARD');
		    }
		    
		}else{
		    $url->setType('SM_PAGEURL_NORMAL');
		}
		
		$url->setUrl($page_url);
		$url->save();
		
		// $pageurl_id = $this->manager->database->specificQuery("pageurl_id", "pageurl_url", $page_oldurl, "PageUrls");
		// $pageurl_id;
		// $page_id = $this->manager->database->specificQuery("page_id", "page_webid", $page_webid, "Pages");
		// $this->manager->updatePageUrl($page_id,$pageurl_id,$page_url);
		
		$this->formForward();
	}
	
	public function deletePageUrl($get){
		
		$url = new SmartestPageUrl;
		$p = new SmartestPage;
		
		if($url->hydrate($get['url'])){
		    
		    $p->hydrate($url->getPageId());
		    
		    $u = $url->getUrl();
		    $url->delete();
		    SmartestLog::getInstance('site')->log("{$this->getUser()} deleted URL '$u' from page: {$p->getTitle()}.", SmartestLog::USER_ACTION);
		    $this->addUserMessageToNextRequest("The URL has been successfully deleted. It's recommended that you now clear the pages cache to avoid dead links.", SmartestUserMessage::SUCCESS);
		
	    }else{
	        
	        $this->addUserMessageToNextRequest("The URL ID was not recognized.", SmartestUserMessage::ERROR);
	        
	    }
	    
		$this->formForward();
	}
	
	public function setPageDefaultUrl($get){
	    
	    $page = new SmartestPage;
	    
	    if($page->hydrate($get['page_id'])){
	        
	        $page->setDraftMode(true);
	        
	        $result = $page->setDefaultUrl($get['url']);
	        
	        if(!$result){
	            if($url == (int) $url){
	                $this->addUserMessageToNextRequest("The URL ID was not recognized.", SmartestUserMessage::ERROR);
                }else{
                    $this->addUserMessageToNextRequest("The URL is already in use for another page.", SmartestUserMessage::ERROR);
                }
	        }
	        
	    }else{
	        $this->addUserMessageToNextRequest("The page ID was not recognized.", SmartestUserMessage::ERROR);
	    }
	    
	    $this->formForward();
	    
	}
	
	public function editField($get){
		// This is a hack. Sorry.
		$this->redirect(SM_CONTROLLER_DOMAIN.'metadata/defineFieldOnPage?page_id='.$get['page_id'].'&assetclass_id='.$get['assetclass_id']);
	}
	
	public function setLiveProperty($get){
		// This is a hack. Sorry.
		$this->redirect(SM_CONTROLLER_DOMAIN.'metadata/setLiveProperty?page_id='.$get['page_id'].'&assetclass_id='.$get['assetclass_id']);
	}
	
	public function undefinePageProperty($get){
		// This is a hack. Sorry.
		$this->redirect(SM_CONTROLLER_DOMAIN.'metadata/undefinePageProperty?page_id='.$get['page_id'].'&assetclass_id='.$get['assetclass_id']);
	}
	
	public function pageGroups(){
	    
	    $pgh = new SmartestPageGroupsHelper;
	    $groups = $pgh->getSiteGroups($this->getSite()->getId());
	    
	}
	
	public function addPageGroup(){
	    
	}
	
	public function insertPageGroup(){
	    
	}
	
	public function editPageGroup(){
	    
	}
	
	public function updatePageGroup(){
	    
	}

}