<?php

class SmartestManyToManyNetwork{
    
    protected $_table;
    protected $_foreignKey;
    protected $_class;
    
    public function __construct($table, $foreignKey, $class){
        
        $dbth = new SmartestDatabaseTableHelper;
        
        if($dbth->tableExists($table)){
            
            $this->_table = $table;
            
            if($dbth->tableHasColumn($this->_table, $foreignKey)){
                $this->_foreignKey = $foreignKey;
            }else{
                throw new SmartestException('The column \''.$foreignKey.'\' does not exist in table \''.$table.'\'');
            }
            
        }else{
            throw new SmartestException('The table \''.$table.'\' does not exist.');
        }
        
    }
    
    public function getTable(){
        return $this->_table;
    }
    
    public function getForeignKeyField($add_table=true){
        if($add_table){
            return $this->_table.'.'.$this->_foreignKey;
        }else{
            return $this->_foreignKey;
        }
    }
    
    public function getClass(){
        return $this->_class;
    }
    
    public function areLinkedDirectly($id1, $id2){
        
    }
    
}