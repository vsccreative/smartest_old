<?php

class Desktop extends SmartestSystemApplication{
    
    public function startPage(){
        
        $this->clearCookie('SMARTEST_RET');
        
        if($this->getSite() instanceof SmartestSite){
            
            $this->forward('websitemanager', 'sitePages');
            
            $this->setFormReturnUri();
            
            // code to assemble the desktop goes here
            $this->setTitle('Start Page');
            $this->send('desktop', 'display');
            $this->send($this->getSite(), 'site');
            
        }else{
            
            if($this->getUserAgent()->isExplorer() && $this->getUserAgent()->getAppVersionInteger() < 7){
                $this->addUserMessage("Smartest has noticed that you're using Internet Explorer 6 or below. Your browser <em>is</em> supported, however you may find that the interface works better in Internet Explorer 7, Safari or Firefox.");
            }
            
            $this->setTitle('Choose a Site');
            $sites = $this->getUser()->getAllowedSites();
            
            if(count($sites) == 1 && !$this->getUser()->hasToken('create_sites')){
                
                $this->getUser()->openSiteById($sites[0]->getId());
                // This is like a reload, except a new request is not needed.
                $this->forward('desktop', 'startPage');
                
            }else{
            
        		$this->send($sites, 'sites');
        		$this->send('sites', 'display');
        		$this->send(count($sites), 'num_sites');
        		$this->send(($this->getUser()->hasToken('create_sites') || $this->getUser()->hasToken('create_users')), 'show_create_button');
    		
		    }
    		
        }
        
    }
    
    public function newDesktop(){
        
        if($this->getSite() instanceof SmartestSite){
            
            $du = new SmartestDataUtility;
            $alh = new SmartestAssetsLibraryHelper;
            $tlh = new SmartestTemplatesLibraryHelper;
            $ach = new SmartestAssetClassesHelper;
            
            $models = $du->getModels(false, $this->getSite()->getId());
            
            $re = new SmartestParameterHolder("Recently edited things");
            $re->setParameter('files', $this->getUser()->getRecentlyEditedAssets($this->getSite()->getId()));
            $re->setParameter('pages', $this->getUser()->getRecentlyEditedPages($this->getSite()->getId()));
            $re->setParameter('templates', $this->getUser()->getRecentlyEditedTemplates($this->getSite()->getId()));
            $ri = new SmartestParameterHolder("Recently edited items");
            
            $this->send($models, 'models');
            $this->send($alh->getTypes(array('templates')), 'file_types');
            $this->send($tlh->getTypes(), 'template_types');
            $this->send($ach->getTypes(), 'placeholder_types');
            
            foreach($models as $m){
                $ri->setParameter($m->getId(), $this->getUser()->getRecentlyEditedItems($this->getSite()->getId(), $m->getId()));
            }
            
            $re->setParameter('items', $ri);
            $this->send($re, 'recently_edited');
            
        }
        
    }
    
    public function editSite($get){
	    
	    if($this->getUser()->hasToken('modify_site_parameters')){
	    
    	    if($this->getSite() instanceof SmartestSite){
		    
    		    $site_id = $this->getSite()->getId();
		    
    		    $main_page_templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/Masters/');
		    
    		    $sitedetails = $this->getSite();
    		    $pages = $this->getSite()->getPagesList();
                $this->send($pages, 'pages');
                
                $site_logos_group = new SmartestAssetGroup;
                if($site_logos_group->find(SmartestSystemSettingHelper::getSiteLogosFileGroupId())){
                    $logos = $site_logos_group->getMembers();
                }else{
                    $logos = array();
                }
                
                $this->send($logos, 'logo_assets');
            
                $this->setTitle("Edit Site Settings");
    		    $this->send($sitedetails, 'site');
		    
    	    }else{
	        
    	        $this->addUserMessageToNextRequest('You must have an open site to open edit settings.', SmartestUserMessage::INFO);
    	        $this->redirect('/smartest');
	        
    	    }
	    
        }else{
            
            $this->addUserMessageToNextRequest('You don\'t have permission to edit site settings.', SmartestUserMessage::ACCESS_DENIED);
	        $this->redirect('/smartest');
            
        }
		
	}
	
	public function updateSiteDetails($get, $post){
	    
	    if($this->getSite() instanceof SmartestSite){
	        
	        $site = $this->getSite();
	        
	        if($this->getUser()->hasToken('modify_site_parameters')){
	        
    	        $site->setName($this->getRequestParameter('site_name'));
    	        $site->setInternalLabel($this->getRequestParameter('site_internal_label'));
    	        $site->setTitleFormat($this->getRequestParameter('site_title_format'));
    	        $site->setDomain($this->getRequestParameter('site_domain'));
    	        $site->setTagPageId($this->getRequestParameter('site_tag_page'));
    	        $site->setSearchPageId($this->getRequestParameter('site_search_page'));
    	        $site->setErrorPageId($this->getRequestParameter('site_error_page'));
    	        $site->setAdminEmail($this->getRequestParameter('site_admin_email'));
    	        $site->save();
	        
            }else{
                
                $this->addUserMessageToNextRequest('You don\'t have permission to edit site settings', SmartestUserMessage::ACCESS_DENIED);
                
            }
	        
	        $site->setLanguageCode($this->getRequestParameter('site_language'));
	        
	        if($site->getIsEnabled() == '1' && $this->getRequestParameter('site_is_enabled') == '0'){
	            if($this->getUser()->hasToken('disable_site')){
	                $site->setIsEnabled((int) (bool) $this->getRequestParameter('site_is_enabled'));
                }else{
                    $this->addUserMessageToNextRequest('You don\'t have permission to disable sites', SmartestUserMessage::ACCESS_DENIED);
                }
	        }
	        
	        if($site->getIsEnabled() == '0' && $this->getRequestParameter('site_is_enabled') == '1'){
	            if($this->getUser()->hasToken('enable_site')){
	                $site->setIsEnabled((int) (bool) $this->getRequestParameter('site_is_enabled'));
                }else{
                    $this->addUserMessageToNextRequest('You don\'t have permission to enable sites', SmartestUserMessage::ACCESS_DENIED);
                }
	        }
	        
	        if(SmartestUploadHelper::uploadExists('site_logo')){
	            
	            $alh = new SmartestAssetsLibraryHelper;
	            $upload = new SmartestUploadHelper('site_logo');
                $upload->setUploadDirectory(SM_ROOT_DIR.'System/Temporary/');
                $types = $alh->getPossibleTypesBySuffix($upload->getDotSuffix());
                
                if(count($types)){
                    $t = $types[0]['type']['id'];
                    
                    $ach = new SmartestAssetCreationHelper($t);
                    $ach->createNewAssetFromFileUpload($upload, "Logo for ".$site->getInternalLabel().' - '.date('M d Y'));
                    
                    $file = $ach->finish();
                    $file->setShared(1);
                    $file->setIsSystem(1);
                    $file->setIsHidden(1);
                    $file->save();
                    
                    $site->setLogoImageAssetId($file->getId());
                    $site->save();
                    
                    $site_logos_group = new SmartestAssetGroup;
                    
                    if($site_logos_group->find(SmartestSystemSettingHelper::getSiteLogosFileGroupId())){
                        $site_logos_group->addAssetById($file->getId());
                    }
                }
	        }else{
	            $site->setLogoImageAssetId($this->getRequestParameter('site_logo_image_asset_id'));
	            $site->save();
	        }
	        
	        if($this->getRequestParameter('site_user_page') == 'NEW' && !is_numeric($site->getUserPageId())){
	            $p = new SmartestPage;
	            $p->setTitle('User Profile');
	            $p->setName('user');
	            $p->setSiteId($site->getId());
	            $p->setParent($site->getTopPageId());
        	    $p->setWebid(SmartestStringHelper::random(32));
        	    $p->setCreatedbyUserid($this->getUser()->getId());
        	    $p->setOrderIndex(1020);
        	    $p->save();
        	    $site->setUserPageId($p->getId());
	        }
	        
	        SmartestCache::clear('site_pages_tree_'.$site->getId(), true);
	        
		    $this->formForward();
	    }
	}
    
    public function openSite($get){
		
		if($this->getRequestParameter('site_id')){
		    
		    if($this->getUser()->openSiteById($this->getRequestParameter('site_id'))){
		        $this->redirect('/smartest');
		    }else{
		        $this->addUserMessageToNextRequest('You don\'t have permission to access that site. This action has been logged.', SmartestUserMessage::ACCESS_DENIED);
                SmartestLog::getInstance('site')->log("User ".$this->getUser()->__toString()." tried to access this site but is not currently granted permission to do so.");
                $this->redirect('/smartest');
		    }
		    
		}
	}
	
	public function closeCurrentSite($get){
		SmartestSession::clear('current_open_project');
		$this->clearFormReturnInfo();
		$this->getUser()->reloadTokens();
		$this->redirect('/smartest');
	}
	
	public function createSite(){
	    if($this->getUser()->hasToken('create_sites')){
	        $this->setTitle('Create a new site');
	        $this->send(SM_ROOT_DIR, "sm_root_dir");
	        $this->send($this->getUser(), "user");
	        $tlh = new SmartestTemplatesLibraryHelper;
	        $templates = $tlh->getSharedMasterTemplates();
	        $this->send($templates, 'templates');
	        if(!$this->getRequestParameter('site_name')){$this->setRequestParameter('site_name', 'My Smartest Web Site');}
	        $this->send(is_writable(SM_ROOT_DIR.'Presentation/Masters/'), 'allow_create_master_tpl');
	    }else{
	        $this->addUserMessageToNextRequest('You don\'t have permission to create new sites. This action has been logged.', SmartestUserMessage::ACCESS_DENIED);
	        SmartestLog::getInstance('system')->log($this->getUser()->getFullName().' tried to create a new site, but doesn\'t have permission to do so.');
            $this->redirect('/smartest');
	    }
	}
	
	public function buildSite($get, $post){
	    
	    $errors = array();
	    
	    if(!$this->getRequestParameter('site_name')){
	        $errors['name'] = "You must enter a title for your site.";
	    }
	    
	    if(!$this->getRequestParameter('site_domain')){
	        $errors['domain'] = "You must enter a host name for your site.";
	    }
	    
	    if(!$this->getRequestParameter('site_admin_email')){
	        $errors['email'] = "You must enter an administrator email address for your site.";
	    }else if(!SmartestStringHelper::isEmailAddress($this->getRequestParameter('site_admin_email'))){
	        $errors['email'] = "The administrator email address you entered was not valid.";
	    }
	    
	    $this->send($errors, 'errors');
	    
	    if(count($errors)){
	        $this->forward('desktop', 'createSite');
	    }
	    
	    $p = new SmartestParameterHolder('New site parameters');
	    $p->setParameter('site_name', $this->getRequestParameter('site_name'));
	    $p->setParameter('site_internal_label', $this->getRequestParameter('site_name'));
	    $p->setParameter('site_domain', $this->getRequestParameter('site_domain'));
	    $p->setParameter('site_admin', $this->getRequestParameter('site_admin_email'));
	    $p->setParameter('site_master_template', $this->getRequestParameter('site_master_template'));
	    
	    $sch = new SmartestSiteCreationHelper;
	    
	    try{
	        $site = $sch->createNewSite($p);
	        
	        if(SmartestUploadHelper::uploadExists('site_logo')){
	            
	            $alh = new SmartestAssetsLibraryHelper;
	            $upload = new SmartestUploadHelper('site_logo');
                $upload->setUploadDirectory(SM_ROOT_DIR.'System/Temporary/');
                
                $ach = new SmartestAssetCreationHelper;
                $ach->createNewAssetFromFileUpload($upload, "Logo for ".$site->getInternalLabel().' - '.date('M d Y'));
                
                if($file = $ach->finish()){
                    
                    $file->setShared(1);
                    $file->setIsSystem(1);
                    $file->setIsHidden(1);
                    $file->save();
                
                    $site->setLogoImageAssetId($file->getId());
                    $site->save();
                
                    $site_logos_group = new SmartestAssetGroup;
                
                    if($site_logos_group->find(SmartestSystemSettingHelper::getSiteLogosFileGroupId())){
                        $site_logos_group->addAssetById($file->getId());
                    }
                
                }
                
	        }
	        
	        $this->getUser()->reloadTokens();
	        $this->getUser()->openSiteById($site->getId());
	        
	    }catch(SmartestException $e){
	        throw $e;
	    }
	    
	    $this->redirect("/smartest");
	    
	}
	
	public function assignTodo($get){
	    
	}
	
	public function insertTodo($get, $post){
	    
	}
	
	public function completeTodoItem($get){
	    
	    $todo_id = (int) $get['todo_id'];
	    
	    $todo = new SmartestTodoItem;
	    
	    if($todo->hydrate($todo_id)){
	        
	        $todo->complete(true);
	        $this->addUserMessageToNextRequest("The to-do item has been marked as completed", SmartestUserMessage::SUCCESS);
	        
	    }else{
	        
	        $this->addUserMessageToNextRequest("The to-do item ID was not recognized", SmartestUserMessage::ERROR);
	        
	    }
	    
	    $this->formForward();
	    
	}
	
	public function deleteTodoItem($get){
	    
	    $todo_id = $get['todo_id'];
	    
	    $todo = new SmartestTodoItem;
	    
	    if($todo->hydrate($todo_id)){
	        
	        $todo->delete();
	        
	    }else{
	        
	        $this->addUserMessageToNextRequest("The to-do item ID was not recognized", SmartestUserMessage::ERROR);
	        
	    }
	    
	    $this->formForward();
	    
	}
	
	public function deleteCompletedTodos($get){
	    
	    $this->getUser()->clearCompletedTodos();
	    
	    $this->addUserMessageToNextRequest("Your completed to-do items have been removed", SmartestUserMessage::SUCCESS);
	    
	    $this->formForward();
	    
	}
	
	public function comments($get){
	    
	    $status = (isset($get['show']) && in_array($get['show'], array('SM_COMMENTSTATUS_APPROVED', 'SM_COMMENTSTATUS_PENDING', 'SM_COMMENTSTATUS_REJECTED'))) ? $get['show'] : 'SM_COMMENTSTATUS_PENDING';
	    $this->send($this->getSite()->getPublicComments(constant($status)), 'comments');
	    
	}
    
    public function caches(){
        
        // Controller cache
        // Data cache
        // Includes cache
        // Data objects
        // Models
        // Pages
        // Smarty
        // Draft Text-Fragments
        
    }
    
    public function clearCaches(){
        
        
        
    }
    
    public function todoList(){
        
        $this->setFormReturnUri();
        
        $this->setTitle('Your To-do List');
        
        $todo_items = $this->getUser()->getTodoItemsAsArrays(false, true);
        $this->send($todo_items, 'todo_items');
        
        /*
        
        // print_r($this->manager);
        $this->setTitle('Your To-do List');
        
        // get all self-assigned items, which can be marked as done without a follow-up
        $self_assigned = $this->manager->getSelfAssignedTodoListItemsAsArrays($this->getUser()->getId());
        $this->send($self_assigned, 'self_assigned_tasks');
        $this->send(count($self_assigned), 'num_self_assigned_tasks');
        
        // get all items assigned by other users
        $other_assigned = $this->manager->getAssignedTodoListItemsAsArrays($this->getUser()->getId());
        $this->send($other_assigned, 'assigned_tasks');
        $this->send(count($other_assigned), 'num_assigned_tasks');
        
        // collect other responsibilities from the system
        
        $duty_items = array();
        $total_num_duty_items = 0;
        
        // get Locked Pages
        $locked_pages = $this->manager->getLockedPageDuties($this->getUser()->getId());
        $total_num_duty_items += count($locked_pages);
        $this->send($locked_pages, 'locked_pages');
        
        // get Locked Items
        $locked_items = $this->manager->getLockedItemDuties($this->getUser()->getId());
        $total_num_duty_items += count($locked_items);
        $this->send($locked_items, 'locked_items');
        
        // get Items awaiting approval
        if($this->getUser()->hasToken('approve_item_changes')){
            $items_awaiting_approval = $this->manager->getItemsAwaitingApproval($this->getUser()->getId());
            $total_num_duty_items += count($items_awaiting_approval);
            $this->send($items_awaiting_approval, 'items_awaiting_approval');
            $this->send(true, 'show_items_awaiting_approval');
        }else{
            $this->send(false, 'show_items_awaiting_approval');
        }
        
        // get Pages awaiting approval
        if($this->getUser()->hasToken('approve_page_changes')){
            $pages_awaiting_approval = $this->manager->getPagesAwaitingApproval($this->getUser()->getId());
            $total_num_duty_items += count($pages_awaiting_approval);
            $this->send($pages_awaiting_approval, 'pages_awaiting_approval');
            $this->send(true, 'show_pages_awaiting_approval');
        }else{
            $this->send(false, 'show_pages_awaiting_approval');
        }
        
        // get Items awaiting publishing
        if($this->getUser()->hasToken('publish_approved_items')){
            $items_awaiting_publishing = $this->manager->getItemsAwaitingPublishing($this->getUser()->getId());
            $total_num_duty_items += count($items_awaiting_publishing);
            $this->send($items_awaiting_publishing, 'items_awaiting_publishing');
            $this->send(true, 'show_items_awaiting_publishing');
        }else{
            $this->send(false, 'show_items_awaiting_publishing');
        }
        
        // get Pages awaiting publishing
        if($this->getUser()->hasToken('publish_approved_pages')){
            $pages_awaiting_publishing = $this->manager->getPagesAwaitingPublishing($this->getUser()->getId());
            $total_num_duty_items += count($pages_awaiting_publishing);
            $this->send($pages_awaiting_publishing, 'pages_awaiting_publishing');
            $this->send(true, 'show_pages_awaiting_publishing');
        }else{
            $this->send(false, 'show_pages_awaiting_publishing');
        }
        
        $this->send($total_num_duty_items, 'num_duty_items'); */
        
    }
    
    public function createDialog(){
        $this->send($this->getUser()->hasToken('add_new_pages') && $this->getSite(), 'allow_create_pages');
        $this->send($this->getUser()->hasToken('create_models') && $this->getSite(), 'allow_create_models');
        $this->send($this->getUser()->hasToken('add_items') && $this->getSite(), 'allow_create_items');
        $this->send($this->getUser()->hasToken('create_sites'), 'allow_create_sites');
        $this->send($this->getUser()->hasToken('create_assets') && $this->getSite(), 'allow_create_files');
        $this->send($this->getUser()->hasToken('create_users'), 'allow_create_users');
        $du = new SmartestDataUtility;
        $models = $this->getSite() ? $du->getModels(false, $this->getSite()->getId(), true) : array();
		$this->send($models, 'models');
    }
    
    public function aboutSmartest(){
        
        // Web server
        $server = SmartestSystemHelper::getWebServerSoftware();
        $this->send($server, 'platform');
        
        // Version, Build and Revision
        /* $sys = SmartestYamlHelper::fastLoad(SM_ROOT_DIR.'System/Core/Info/system.yml');
        $this->send($sys['system']['info']['revision'], 'revision');
        $this->send($sys['system']['info']['version'], 'version');
        $this->send($sys['system']['info']['revision']-$sys['system']['info']['lastversion_last_revision'], 'build'); */
        $this->send(SmartestSystemHelper::getSmartestLocalVersionInfo(), 'smartest_info');
        
        // Memory Limit
        $this->send(SmartestSystemHelper::getPhpMemoryLimit(true), 'memory_limit');
        
        // PHP Version
        $this->send(SmartestSystemHelper::getPhpVersion(), 'php_version');
        
        // Root Directory
        $this->send(SM_ROOT_DIR, 'root_dir');
        
        // Operating system
        $this->send(SmartestSystemHelper::getOperatingSystem(), 'linux_version');
        
        // Server speed
        $this->send($this->getUser()->hasToken('test_server_speed'), 'allow_test_server_speed');
        $this->send($this->getUser()->hasToken('see_server_speed'), 'allow_see_server_speed');
        
        $raw_speed_score = SmartestSystemSettingHelper::load('_server_speed_index');
        $cats = SmartestYamlHelper::fastLoad(SM_ROOT_DIR.'System/Core/Info/serverspeed.yml');
        $speed_categories = $cats['levels'];
        $speed_categories[0] = null;
        $previous_category = array('description'=>'Unrated', 'image'=>'server-level-0.png', 'color'=>'333');
        
        $this->setTitle('About Smartest');
        
        ksort($speed_categories);
        
        $category = end($speed_categories);
        reset($speed_categories);
        
        foreach($speed_categories as $k => $sc){
          
            if($raw_speed_score < $k){
                $category = $sc;
                break;
            }else{
                // $previous_category = $speed_categories[$k];
                continue;
            }
        }
        
        $this->send($raw_speed_score, 'speed_score');
        $this->send($category, 'speed_category_info');
        
        // Install date
        $system_installed_timestamp = SmartestSystemHelper::getInstallDate(true);
        $this->send($system_installed_timestamp, 'system_installed_timestamp');
        
        // Install ID
        $this->send(SmartestSystemSettingHelper::getInstallId(), 'system_install_id');
        
        // Version control
        $this->send(new SmartestBoolean(is_dir(SM_ROOT_DIR.'.svn/')), 'is_svn_checkout');
    }
    
    public function testServerSpeed(){
        
        if($this->getUser()->hasToken('test_server_speed')){
        
            $sql = "SELECT page_id FROM Pages WHERE page_deleted != 'TRUE' ORDER BY page_id DESC LIMIT 1";
            $db = SmartestPersistentObject::get('db:main');
            $r = $db->queryToArray($sql);
            $id = $r[0]['page_id'];
        
            $test_start_time = microtime(true);
        
            for($i=0;$i<2000;$i++){
            
                // look it up and hydrate it by ID
                $p = new SmartestPage;
                $p->find($id);
            
                // access it via ArrayAccess
                $d = $p['title'];
            
            }
            
            $test_finish_time = microtime(true);
            $test_time_taken = number_format(($test_finish_time - $test_start_time)*1000, 2, ".", "");
        
            SmartestSystemSettingHelper::save('_server_speed_index', $test_time_taken);
        
        }else{
            
            $this->addUserMessageToNextRequest("You do not have permission to test the server's speed.", SmartestUserMessage::ACCESS_DENIED);
            
        }
        
        $this->redirect('/smartest/about');
        
    }
    
    public function phpinfo(){
        
        if($this->getUser()->hasToken('view_phpinfo')){
            phpinfo();
            exit;
        }else{
            $this->addUserMessageToNextRequest("You do not have permission to view PHP Info.", SmartestUserMessage::ACCESS_DENIED);
            $this->formForward();
        }
        
    }
    
}