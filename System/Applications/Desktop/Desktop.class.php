<?php

class Desktop extends SmartestSystemApplication{
    
    protected function __moduleConstruct(){
        
    }
    
    public function startPage(){
        
        if($this->getSite() instanceof SmartestSite){
            
            // code to assemble the desktop goes here
            $this->setTitle('Start Page');
            $this->send('desktop', 'display');
            $this->send($this->getSite()->__toArray(), 'site');
            
        }else{
            
            if($this->getUserAgent()->isExplorer() && $this->getUserAgent()->getAppVersionInteger() < 7){
                $this->addUserMessage("Smartest has noticed that you're using Internet Explorer 6 or below. Your browser <em>is</em> supported, however you may find that the interface works better in IE7 or Firefox.");
            }
            
            $this->setTitle('Choose a Site');
            $result = $this->getUser()->getAllowedSites();

    		$sites = array();

    		foreach($result as $site){
    		    $sites[] = $site->__toArray();
    		}
    		
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
	        $site->setRoot($post['site_root']);
	        $site->setTopPageId($post['site_top_page']);
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
			        
			            /* $site_dir = SM_ROOT_DIR.'Sites/'.substr(SmartestStringHelper::toCamelCase($site->getName()), 0, 64).'/';

                	    if(is_dir($site_dir)){
                	        $old_site_dir = 
                	        $folder = $site->getName().microtime();
                	        $site_dir = SM_ROOT_DIR.'Sites/'.sha1($folder).'/';
                	    }

                	    mkdir($site_dir);
                	    if(!is_dir($site_dir.'Presentation')){mkdir($site_dir.'Presentation');}
                	    if(!is_dir($site_dir.'Configuration')){mkdir($site_dir.'Configuration');}
                	    if(!is_file($site_dir.'Configuration/site.yml')){file_put_contents($site_dir.'Configuration/site.yml', '');}
                	    if(!is_dir($site_dir.'Library')){mkdir($site_dir.'Library');}
                	    if(!is_dir($site_dir.'Library/Actions')){mkdir($site_dir.'Library/Actions');}
                	    $actions_class_name = SmartestStringHelper::toCamelCase($site->getName()).'Actions';
                	    $class_file_contents = file_get_contents(SM_ROOT_DIR.'System/Base/ClassTemplates/SiteActions.class.php.txt');
                	    $class_file_contents = str_replace('__TIMESTAMP__', time('Y-m-d h:i:s'), $class_file_contents);
                	    if(!is_file($site_dir.'Library/Actions/SiteActions.class.php')){file_put_contents($site_dir.'Library/Actions/SiteActions.class.php', $class_file_contents);}
                	    chmod($site_dir.'Library/Actions/SiteActions.class.php', 0666);
                	    $site->setDirectoryName(substr(SmartestStringHelper::toCamelCase($site->getName()), 0, 64));
                	    $site->save(); */
                	    
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
	        $this->send($this->getUser()->__toArray(), "user");
	        $templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/Masters/');
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
        
        // $todo_item_objects = $this->getUser()->getTodoItems();
        // print_r($todo_item_objects);
        
        $todo_items = $this->getUser()->getTodoItemsAsArrays(false, true);
        // print_r($todo_items);
        
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
    
}