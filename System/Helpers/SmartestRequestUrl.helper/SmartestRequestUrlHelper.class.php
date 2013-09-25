<?php

class SmartestRequestUrlHelper{

	private $database;
	
	public function __construct(){
		$this->database = SmartestPersistentObject::get('db:main');
		$this->_request_data = SmartestPersistentObject::get('request_data');
	}
	
	public function getSiteByDomain($domain, $url=''){
	    
	    $sql = "SELECT * FROM Sites WHERE site_domain='".$domain."'";
	    $result = $this->database->queryToArray($sql);
	    
	    if(count($result)){
	        
	        $site = new SmartestSite;
	        $site->hydrate($result[0]);
	        return $site;
	        
	    }else{
	        
	        if(substr($domain, 0, 4) == 'www.'){
	            $try_domain = substr($domain, 4);
	        }else{
	            $try_domain = 'www.'.$domain;
	        }
	        
	        $sql = "SELECT * FROM Sites WHERE site_domain='".$try_domain."'";
    	    $result = $this->database->queryToArray($sql);
    	    
    	    if(count($result)){
    	        throw new SmartestRedirectException('http://'.$try_domain.'/'.$url, SmartestRedirectException::PERMANENT);
	        }else{
	            return false;
            }
            
	    }
	    
	}
	
	public function getSiteByPageWebId($page_webid){
	    
	    $sql = "SELECT Sites.* FROM Sites, Pages WHERE Pages.page_webid='{$page_webid}' AND Pages.page_site_id=Sites.site_id";
	    $result = $this->database->queryToArray($sql);
	    
	    if(count($result)){
	        $site = new SmartestSite;
	        $site->hydrate($result[0]);
	        return $site;
	    }else{
	        return false;
	    }
	    
	}
	
	public function getNormalPageByUrl($url, $site_id){
		
		$url = mysql_real_escape_string(SmartestStringHelper::sanitize(urldecode($url)));
		$sql = "SELECT Pages.*, PageUrls.pageurl_id, PageUrls.pageurl_type, PageUrls.pageurl_url, PageUrls.pageurl_redirect_type FROM Pages, PageUrls WHERE Pages.page_id=PageUrls.pageurl_page_id AND page_type='NORMAL' AND Pages.page_site_id='".$site_id."' AND PageUrls.pageurl_url='".$url."' AND Pages.page_is_published='TRUE' AND Pages.page_deleted !='TRUE'";
		$page = $this->database->queryToArray($sql);
		
		$p = new SmartestPage;
		
		if(count($page) > 0){
			
			$p->hydrate($page[0]);
			
			if($page[0]['pageurl_type'] == 'SM_PAGEURL_INTERNAL_FORWARD'){
			    
			    if($page[0]['pageurl_url'] == $p->getDefaultUrl()){
			        SmartestLog::getInstance('system')->log("PageUrl ID ".$page[0]['pageurl_id']." cannot simultaneously be a forward and a default URL.");
			        return $p;
			    }else{
			        $this->_request_data = SmartestPersistentObject::get('request_data');
			        throw new SmartestRedirectException($this->_request_data->g('domain').$p->getDefaultUrl(), $page[0]['pageurl_redirect_type']);
		        }
			    
			}else{
			
			    return $p;
			
		    }
			
		}else{
		    
		    if(substr($url, -1) == '/'){
		        $new_url = substr($url, 0, -1);
		    }else{
		        $new_url = $url.='/';
		    }
		    
		    $sql = "SELECT Pages.* FROM Pages, PageUrls WHERE Pages.page_id=PageUrls.pageurl_page_id AND page_type='NORMAL' AND Pages.page_site_id='".$site_id."' AND PageUrls.pageurl_url='$new_url' AND Pages.page_is_published='TRUE' AND Pages.page_deleted !='TRUE'";
    		$page = $this->database->queryToArray($sql);
    		
    		if(count($page) > 0){
    		    $this->_request_data = SmartestPersistentObject::get('request_data');
    			throw new SmartestRedirectException($this->_request_data->g('domain').$new_url, SmartestRedirectException::SEE_OTHER);
    		}
    		
		}
		
	}
	
	public function getNormalPageByWebId($web_id, $draft_mode=false, $site_domain=null){
	    
	    $web_id = mysql_real_escape_string(SmartestStringHelper::sanitize($web_id));
	    
	    $sql = "SELECT * FROM Pages";
	    
	    if(strlen($site_domain)){
	        $sql .= ", Sites";
	    }
	    
	    $sql .= " WHERE page_webid='".$web_id."' AND page_type='NORMAL'";
	    
	    if(!$draft_mode){
	        $sql .= " AND page_is_published='TRUE'";
        }
	    
	    $sql .= " AND page_deleted !='TRUE'";
	    
	    if(strlen($site_domain)){
	        $sql .= " AND Pages.page_site_id=Sites.site_id AND Sites.site_domain='".$site_domain."'";
	    }
	    
	    $page = $this->database->queryToArray($sql);
	    
	    $pageObj = new SmartestPage;
	    
	    if(count($page) > 0){
			
			$pageObj->hydrate($page[0]);
			
			if($draft_mode){
			    $pageObj->setDraftMode(true);
			}
			
			return $pageObj;
		}else{
			return null;
		}
	    
	}
	
	public function getItemClassPageByWebId($web_id, $item_id, $draft_mode=false, $site_domain=''){
	    
	    $web_id = mysql_real_escape_string(SmartestStringHelper::sanitize($web_id));
	    $item_id = (int) $item_id;
	    
	    $sql = "SELECT * FROM Pages";
	    
	    if(strlen($site_domain)){
	        $sql .= ", Sites";
	    }
	    
	    $sql .= " WHERE page_webid='".$web_id."' AND (page_type='ITEMCLASS' OR page_type='SM_PAGETYPE_ITEMCLASS' OR page_type='SM_PAGETYPE_DATASET')";
	    
	    if(!$draft_mode){
	        $sql .= " AND page_is_published='TRUE'";
        }
	    
	    $sql .= " AND page_deleted !='TRUE'";
	    
	    if(strlen($site_domain)){
	        $sql .= " AND Pages.page_site_id=Sites.site_id AND Sites.site_domain='".$site_domain."'";
	    }
	    
	    $result = $this->database->queryToArray($sql);
	    
	    if(count($result) > 0){
			
			$page = new SmartestItemPage;
			$page->hydrate($result[0]);
			
			if($draft_mode){
			    $page->setDraftMode(true);
			}
			
			if(is_numeric($item_id)){
			    $page->setIdentifyingFieldName("id");
			}else{
			    $page->setIdentifyingFieldName("webid");
		    }
		    
			$page->setIdentifyingFieldValue($item_id);
			
			if($page->isAcceptableItem()){
			    // the item id was ok. get the item
			    $page->assignPrincipalItem();
			    return $page;
			}else{
			    // the item was not the right type, so I guess it's a 404
			    SmartestLog::getInstance('system')->log("Unacceptable item ID: $item_id requested while trying to build Page ID: ".$page->getId());
			}
		    
		}else{
			return null;
		}
	    
	}
	
	public function getItemClassPageByUrl($url, $site_id){
		
		$url = mysql_real_escape_string(SmartestStringHelper::sanitize(urldecode($url)));
		
		$sql = "SELECT Pages.page_id, Pages.page_webid, Pages.page_name, PageUrls.pageurl_url, PageUrls.pageurl_type, PageUrls.pageurl_redirect_type, PageUrls.pageurl_page_id, PageUrls.pageurl_item_id FROM Pages, PageUrls WHERE (Pages.page_type='ITEMCLASS' OR Pages.page_type='SM_PAGETYPE_ITEMCLASS' OR Pages.page_type='SM_PAGETYPE_DATASET') AND Pages.page_site_id='".$site_id."' AND Pages.page_id = PageUrls.pageurl_page_id AND Pages.page_is_published='TRUE' AND Pages.page_deleted !='TRUE'";
		$dataset_pages = $this->database->queryToArray($sql);
		
		if(is_array($dataset_pages)){
			
			$found_page = false;
			
			// loop through dataset pages and urls and check the urls against the current one
			foreach($dataset_pages as $page_record){
			    
			    // Before doing anything intensive like regexes, check for more straightforward cases
			    if($page_record['pageurl_type'] == 'SM_PAGEURL_SINGLE_ITEM' && $page_record['pageurl_url'] == $url){
			        
			        // load up page and exit
			        $page = new SmartestItemPage;
			        
			        if($page->find($page_record['pageurl_page_id'])){
			        
			            $page->setIdentifyingFieldName("id");
    				    $page->setIdentifyingFieldValue($page_record['pageurl_item_id']);
    			        $page->setUrlNameValuePair("id", $page_record['pageurl_item_id']);
			            
			            if($page->isAcceptableItem()){
    		                $page->assignPrincipalItem();
    		                return $page;
    		            }
		            
	                }
			        
			    }else if($page_record['pageurl_type'] == 'SM_PAGEURL_ITEM_FORWARD' && $page_record['pageurl_url'] == $url){
			        
			        // load up page and forward
			        $page = new SmartestItemPage;
			        
			        if($page->find($page_record['pageurl_page_id'])){
			        
			            $page->setIdentifyingFieldName("id");
    				    $page->setIdentifyingFieldValue($page_record['pageurl_item_id']);
    			        $page->setUrlNameValuePair("id", $page_record['pageurl_item_id']);
			        
    			        if($page->isAcceptableItem()){
    		                $page->assignPrincipalItem();
    		                // echo $page->getPrincipalItem()->getUrl();
    		                throw new SmartestRedirectException($page->getPrincipalItem()->getUrl(), $page_record['pageurl_redirect_type']);
    		            }
		            
	                }
			        
			    }else if($page_record['pageurl_type'] == 'SM_PAGEURL_NORMAL' || $page_record['pageurl_type'] == 'SM_PAGEURL_INTERNAL_FORWARD'){
			        
			        $page_url_regexp = $this->convertPageUrlToRegExp($page_record["pageurl_url"]);
			        
			        // if the stored url being checked matches the current one
    			    if(preg_match($page_url_regexp, $url, $matches)){
				    
    				    // create the page object
    				    $page = new SmartestItemPage;
				    
    				    // hydrate it
    				    if($page->hydrate($page_record['page_id'])){
				        
    				        // $template_url_parts = explode("/", $page_record["pageurl_url"]);
    				        $template_url_parts = preg_split("/[\.\/]/", $page_record["pageurl_url"]);
				        
    				        $actual_url_parts = $matches;
    					
        					array_shift($actual_url_parts);
    					
        					$i = 0;

        					foreach($template_url_parts as $key => $url_placeholder){
    					    
        					    $regex = '/^(\$|:)([\w_]+)/';
    					        
    					        if(preg_match($regex, $url_placeholder, $url_var_matches)){
    							
        							if($url_placeholder == ":id"){
        							    $page->setIdentifyingFieldName("id");
        							    $page->setIdentifyingFieldValue($actual_url_parts[$i]);
        							}else if($url_placeholder == ":name"){
        							    $page->setIdentifyingFieldName("slug");
        							    $page->setIdentifyingFieldValue($actual_url_parts[$i]);
        							}else if($url_placeholder == ":long_id"){
            							$page->setIdentifyingFieldName("webid");
            							$page->setIdentifyingFieldValue($actual_url_parts[$i]);
            						}
        						
            						$page->setUrlNameValuePair($url_var_matches[2], $actual_url_parts[$i]);

            						$i++;
    							
        						}
    						
        					}
    					
        					if($page->isAcceptableItem()){
        					    
        					    $page->assignPrincipalItem();
        					    
        					    if($page_record['pageurl_type'] == 'SM_PAGEURL_INTERNAL_FORWARD'){
        					        // var_dump($page->getPrincipalItem());
        					        throw new SmartestRedirectException($page->getPrincipalItem()->getUrl(), $page_record['pageurl_redirect_type']);
        					    }else{
        					        // the item id was ok. get the item
        					        return $page;
    					        }
    					        
        					}else{
        					    // the item was not in the set, so I guess it's a 404
        					    continue;
        					}
    					
        					if(!$page->getIdentifyingFieldName()){
        					    // error 404
        					    return false;
        					}
				        
    				    }else{
    				        // the page id attached to the stored page url doesn't exist or couldn't be hydrated
    				        // 404
    				        return false;
    				    }
					
    				}
				
			    }else{
			        
			        if(substr($url, -1) == '/'){
        		        $new_url = substr($url, 0, -1);
        		    }else{
        		        $new_url = $url.'/';
        		    }
        		    
        		    // TODO: URLS corresponding to models that are missing a final slash or have one when they shouldn't, should be matched here
        		    
        		    $sql = "SELECT Pages.* FROM Pages, PageUrls WHERE Pages.page_id=PageUrls.pageurl_page_id AND page_type='ITEMCLASS' AND Pages.page_site_id='".$site_id."' AND PageUrls.pageurl_url='$new_url' AND Pages.page_is_published='TRUE' AND Pages.page_deleted !='TRUE'";
            		$page = $this->database->queryToArray($sql);

            		if(count($page) > 0){
            		    $this->_request_data = SmartestPersistentObject::get('request_data');
            			throw new SmartestRedirectException($this->_request_data->g('domain').$new_url, SmartestRedirectException::SEE_OTHER);
            		}
			        
			    }
	
			} // End of the foreach
			
			return false;
		}else{
			return false;
		}
	}
	
	protected function convertPageUrlToRegExp($url){
		
		$url = str_replace("/", "\/", $url);
		$url = str_replace(".", "\.", $url);
		$url = str_replace(':id', "(\d+)", $url);
		$url = str_replace(':long_id', "([\w\*\^\$\(\)\.%\!_-]{4,36})", $url);
		$url = str_replace(':name', "([^\/\s]+)", $url);
		// TODO: arbitrary url variables
		// $url = preg_replace('/(:([\w_-]+))/', "([^\/\s]+)", $url);
		// $url .= '(\.[\w]+)?';
		$url = "/^".$url."\/?$/i";
		
		return $url;
	}

}