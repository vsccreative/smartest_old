<?php

class Desktop extends SmartestSystemApplication{
    
    protected function __moduleConstruct(){
        
    }
    
    public function startPage(){
        
        if($this->getSite() instanceof SmartestSite){
            
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

    		$this->send($sites, 'sites');
    		$this->send('sites', 'display');
    		$this->send(count($sites), 'num_sites');
    		$this->send($this->getUser()->hasToken('create_sites'), 'show_create_button');
    		
        }
        
        
    }
    
    public function editSite($get){
	    
	    if($this->getSite() instanceof SmartestSite){
		    
		    $site_id = $this->getSite()->getId();
		    
		    $main_page_templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/Masters/');
		    
		    $sitedetails = $this->getSite()->__toArray();
		    $pages = $this->getSite()->getPagesList();
            $this->send($pages, 'pages');
            
            $this->setTitle("Edit Site Parameters");
		    $this->send($sitedetails, 'site');
		    
	    }else{
	        
	        $this->addUserMessageToNextRequest('You must have an open site to open edit settings.', SmartestUserMessage::INFO);
	        $this->redirect('/smartest');
	        
	    }
		
	}
	
	public function updateSiteDetails($get, $post){
	    
	    if($this->getSite() instanceof SmartestSite){
	        
	        $site = $this->getSite();
	        $site->setName($post['site_name']);
	        $site->setTitleFormat($post['site_title_format']);
	        $site->setDomain($post['site_domain']);
	        $site->setIsEnabled((int) (bool) $post['site_is_enabled']);
	        // $site->setTopPageId($post['site_top_page']);
	        $site->setTagPageId($post['site_tag_page']);
	        $site->setSearchPageId($post['site_search_page']);
	        $site->setErrorPageId($post['site_error_page']);
	        $site->setAdminEmail($post['site_admin_email']);
	        $site->save();
	        
		    $this->formForward();
	    }
	}
    
    public function openSite($get){
		
		if(@$get['site_id']){
		    
		    if(in_array($get['site_id'], $this->getUser()->getAllowedSiteIds(true))){
		    
		        $site = new SmartestSite;
		    
		        if($site->hydrate($get['site_id'])){
			        
			        SmartestSession::set('current_open_project', $site);
			        $this->getUser()->reloadTokens();
			        
			        if(!$site->getDirectoryName()){
			        
			            SmartestSiteCreationHelper::createSiteDirectory($site);
            		
        		    }
            		
			        $this->redirect('/smartest');
		        }
		        
	        }else{
	            
	            $this->addUserMessageToNextRequest('You don\'t have permission to access that site. This action has been logged.', SmartestUserMessage::ACCESS_DENIED);
	            SmartestLog::getInstance('site')->log("User ".$this->getUser()->__toString()." tried to access this site but is not currently granted permission to do so.");
	            $this->redirect('/smartest');
	            
	        }
		}
	}
	
	public function closeCurrentSite($get){
		SmartestSession::clear('current_open_project');
		$this->getUser()->reloadTokens();
		$this->redirect('/smartest');
	}
	
	public function createSite(){
	    if($this->getUser()->hasToken('create_sites')){
	        $this->send(SM_ROOT_DIR, "sm_root_dir");
	        $this->send($this->getUser(), "user");
	        $tlh = new SmartestTemplatesLibraryHelper;
	        $templates = $tlh->getSharedMasterTemplates();
	        $this->send($templates, 'templates');
	        $this->send(is_writable(SM_ROOT_DIR.'Presentation/Masters/'), 'allow_create_master_tpl');
	    }else{
	        $this->addUserMessageToNextRequest('You don\'t have permission to create new sites. This action has been logged.', SmartestUserMessage::ACCESS_DENIED);
            $this->redirect('/smartest');
	    }
	}
	
	public function buildSite($get, $post){
	    
	    $p = new SmartestParameterHolder('New site parameters');
	    $p->setParameter('site_name', $post['site_name']);
	    $p->setParameter('site_domain', $post['site_domain']);
	    $p->setParameter('site_admin', $post['site_admin_email']);
	    $p->setParameter('site_master_template', $post['site_master_template']);
	    
	    $sch = new SmartestSiteCreationHelper;
	    
	    try{
	        $site = $sch->createNewSite($p);
	    }catch(SmartestException $e){
	        
	    }
	    
	    $this->openSite(array('site_id'=>$site->getId()));
	    $this->getUser()->reloadTokens();
		$this->addUserMessageToNextRequest("The site has successfully been created. You must now log out and back in again to start editing.", SmartestUserMessage::SUCCESS);
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
    
    public function aboutSmartest(){
        
        preg_match('/(Apache\/(1|2.\d.\d))/', $_SERVER['SERVER_SOFTWARE'], $matches);
        $server = str_replace('/', ' ', $matches[1]);
        
        $this->send($server, 'platform');
        $sys = SmartestYamlHelper::fastLoad(SM_ROOT_DIR.'System/Core/Info/system.yml');
        $this->send($sys['system']['info']['revision'], 'revision');
        $this->send($sys['system']['info']['version'], 'version');
        $this->send($sys['system']['info']['build'], 'build');
        $this->send(str_replace('M', ' MB', ini_get('memory_limit')), 'memory_limit');
        $this->send(phpversion(), 'php_version');
        $this->send(SM_ROOT_DIR, 'root_dir');
        
        $linux = `head -n 1 /etc/issue`;
        $linux = trim(str_replace('\n', '', $linux));
        $linux = trim(str_replace('\l', '', $linux));
        
        if(strlen($linux)){
            $this->send($linux, 'linux_version');
        }else if(is_file('/Applications/Utilities/Terminal.app/Contents/version.plist')){
            // sw_vers | grep 'ProductVersion:' | grep -o '[0-9]*\.[0-9]*\.[0-9]*'
            $linux = "Mac OS X ".`sw_vers | grep 'ProductVersion:' | grep -o '[0-9]*\.[0-9]*\.[0-9]*'`;
            $this->send($linux, 'linux_version');
        }
        
        $this->send($this->getUser()->hasToken('test_server_speed'), 'allow_test_server_speed');
        $this->send($this->getUser()->hasToken('see_server_speed'), 'allow_see_server_speed');
        
        $this->send(SmartestSystemSettingHelper::load('_server_speed_index'), 'speed_score');
        
        if(SmartestSystemSettingHelper::hasData('_system_installed_timestamp')){
            $system_installed_timestamp = SmartestSystemSettingHelper::load('_system_installed_timestamp');
        }else{
            
            // Attempt to figure out when the system was set up by looking at the oldest page
            $sql = "SELECT page_created FROM Pages ORDER BY page_id ASC LIMIT 1";
            $db = SmartestPersistentObject::get('db:main');
            $r = $db->queryToArray($sql);
            $system_installed_timestamp = $r[0]['page_created'];
            
            if($system_installed_timestamp > 0){
                SmartestSystemSettingHelper::save('_system_installed_timestamp', $system_installed_timestamp);
            }
        }
        
        $this->send($system_installed_timestamp, 'system_installed_timestamp');
        
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
        
        $this->redirect('/desktop/aboutSmartest');
        
    }
    
}