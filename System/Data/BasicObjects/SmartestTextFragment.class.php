<?php

class SmartestTextFragment extends SmartestDataObject{
    
    protected function __objectConstruct(){
        
        $this->_table_prefix = 'textfragment_';
		$this->_table_name = 'TextFragments';
        
    }
    
    public function getAttachments(){
        
        $attachment_names = $this->parseAttachmentNames();
        $attachments = array();
        
        foreach($attachment_names as $a){
            $attachments[$a] = '';
        }
        
        // look up any defined attachments
        $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_TEXTFRAGMENT_ATTACHMENTS');
        $q->setTargetEntityByIndex(2);
        $q->addQualifyingEntityByIndex(1, $this->getId());
        
        foreach($attachment_names as $a){
            $q->addAllowedInstanceName($a);
        }
        
        $q->addForeignTableConstraint('Assets.asset_deleted', 1, SmartestQuery::NOT_EQUAL);
        $results = $q->retrieve();
        
        foreach($attachment_names as $a){
            if(array_key_exists($a, $results)){
                $attachments[$a] = $results[$a];
            }else{
                $attachments[$a] = new SmartestTextFragmentAttachment;
            }
        }
        
        return $attachments;
        
    }
    
    public function getAttachmentsAsArrays(){
        
        $attachments = $this->getAttachments();
        $arrays = array();
        
        foreach($attachments as $name => $object){
            $arrays[$name] = $object->__toArray();
            $arrays[$name]['_name'] = $name;
        }
        
        return $arrays;
        
    }
    
    public function getAttachmentsForElementsTree($level, $version){
        if($version == 'draft'){
            $attachments = $this->getAttachmentsAsArrays();
            $children = array();
            foreach($attachments as $key=>$a){
                $child = array();
                $child['info']['assetclass_id'] = $a['asset']['id'].'/'.$key;
                $child['info']['assetclass_name'] = $key;
                $child['info']['type'] = 'attachment';
                $child['info']['exists'] = 'true';
                $child['info']['defined'] = 'PUBLISHED';
                $child['info']['asset_id'] = $a['asset']['id'];
                $child['info']['asset_webid'] = $a['asset']['webid'];
                $child['info']['asset_type'] = $a['asset']['type'];
                $child['info']['filename'] = $a['asset']['url'];
                $children[] = $child;
            }
            return $children;
        }else{
            return array();
        }
    }
    
    public function parseAttachmentNames(){
        $regexp = preg_match_all("/<\?sm:attachment.+name=\"([\w-_]+)\".*:\?>/i", $this->getContent(), $matches);
        $attachment_names = $matches[1];
        return $attachment_names;
    }
    
    public function getAttachmentCurrentDefinition($attachment_name){
        
        $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_TEXTFRAGMENT_ATTACHMENTS');
        $q->setTargetEntityByIndex(2);
        $q->addQualifyingEntityByIndex(1, $this->getId());
        $q->addAllowedInstanceName($attachment_name);
        $q->addForeignTableConstraint('Assets.asset_deleted', 1, SmartestQuery::NOT_EQUAL);
        
        $results = array_values($q->retrieve());
        
        if(count($results)){
            $def = $results[0];
            if($def instanceof SmartestTextFragmentAttachment){
                return $def;
            }else{
                return new SmartestTextFragmentAttachment;
            }
        }else{
            return new SmartestTextFragmentAttachment;
        }
    }
    
    public function attachmentIsDefined($attachment_name){
        $q = new SmartestManyToManyQuery('SM_MTMLOOKUP_TEXTFRAGMENT_ATTACHMENTS');
        $q->setTargetEntityByIndex(2);
        $q->addQualifyingEntityByIndex(1, $this->getId());
        $q->addAllowedInstanceName($attachment_name);
    }
    
    public function getDisplayParameters(){
	    
	    $info = $this->getTypeInfo();
	    
	}
	
	public function getParsableFilePath($draft_mode=false){
	    
	    if($draft_mode){
	        $file_path = SM_ROOT_DIR.'System/Cache/TextFragments/Previews/tfpreview_'.SmartestStringHelper::toHash($this->getId(), 8, 'SHA1').'.tmp.tpl';
	    }else{
	        $file_path = SM_ROOT_DIR.'System/Cache/TextFragments/Live/tflive_'.SmartestStringHelper::toHash($this->getId(), 8, 'SHA1').'.tpl';
	    }
	    
	    return $file_path;
	}
	
	public function publish(){
	    return SmartestFileSystemHelper::save($this->getParsableFilePath(), $this->getContent(), true);
	}
	
	public function isPublished(){
	    return file_exists($this->getParsableFilePath());
	}
	
	public function createPreviewFile(){
	    $result = SmartestFileSystemHelper::save($this->getParsableFilePath(true), stripslashes($this->getContent()), true);
	    return $result;
	}
	
	public function ensurePreviewFileExists(){
	    if(!file_exists($this->getParsableFilePath(true))){
	        return $this->createPreviewFile();
	    }else{
	        return true;
	    }
	}
	
	public function save(){
	    
	    $this->createPreviewFile();
	    parent::save();
	    
	}
    
}