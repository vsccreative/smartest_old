<script language="javascript">{literal}

function updateSetConditionsFormFromOperator(condition, value){
  
  if(condition=='new'){
    
    $('add_new_condition').checked=true;
    
    var no_property = 'no-property-input-new-condition';
    var choose_property = 'property-input-new-condition';
    
  }else{
    var no_property = 'no-property-input-'+condition;
    var choose_property = 'property-select-input-'+condition;
  }
  
  if(value == 8 || value == 9){
    $(no_property).style.display='inline';
    $(choose_property).style.display='none';
  }else{
    $(no_property).style.display='none';
    $(choose_property).style.display='inline';
  }
  
}

{/literal}
</script>

<style type="text/css">
{literal}
  #new-condition-value-input select{
    width:250px;
  }
{/literal}
</style>

<div id="work-area">

{load_interface file="edit_set_tabs.tpl"}

<h3><a href="{$domain}smartest/models">Items</a> &gt; {if $model.id}<a href="{$domain}datamanager/getItemClassMembers?class_id={$model.id}">{$model.plural_name}</a> &gt; <a href="{$domain}sets/getItemClassSets?class_id={$model.id}">Sets</a>{else}<a href="{$domain}smartest/sets">Sets</a>{/if} &gt; {$set.label}</h3>

<div class="instruction">Create conditions to filter your data into a pre-saved set that can be used anywhere.</div>

  <form id="pageViewForm" method="post" action="{$domain}{$section}/updateDynamicSet">
    
    <input type="hidden" name="set_id" value="{$set.id}" />
    <input type="hidden" name="add_new_condition" value="false" id="add-new-condition" />
    
    <table border="0" style="width:100%">
      
      <tr>
        <td style="width:180px">Set Label:</td>
        <td><input type="text"  name="set_name" value="{$set.label}" /> ({$set.name})</td>
      </tr>
      
      <tr>
        <td style="width:180px">Get {$model.plural_name} from:</td>
        <td>
          <select name="set_data_source_site_id">
            {foreach from=$sites item="site"}
				    <option value="{$site.id}" {if $site.id == $set.data_source_site_id} selected="selected"{/if}>{$site.internal_label}</option>
            {/foreach}
            {if count($sites) > 1}<option value="ALL" {if $set.data_source_site_id == "ALL"} selected="selected"{/if}>All Sites</option>{/if}
            <option value="CURRENT"{if $set.data_source_site_id == "CURRENT"} selected="selected"{/if}>The site where it is used (contextual)</option>
			    </select>
        </td>
      </tr>
      
      <tr>
        <td style="width:180px">Sort By Property:</td>
        <td>
          <select name="set_sort_field">
            <option value="_SMARTEST_ITEM_NAME" {if $set.sort_field == '_SMARTEST_ITEM_NAME'} selected="selected"{/if}>{$model.item_name_field_name}</option>
            <option value="_SMARTEST_ITEM_ID" {if $set.sort_field == '_SMARTEST_ITEM_ID'} selected="selected"{/if}>ID</option>
            <option value="{$random_value}" {if $set.sort_field == $random_value} selected="selected"{/if}>Random order</option>
{foreach from=$properties item="property"}
			      <option value="{$property.id}" {if $property.id == $set.sort_field} selected="selected"{/if}>{$property.name}</option>
{/foreach}
            <option value="_SMARTEST_ITEM_NUM_HITS" {if $set.sort_field == '_SMARTEST_ITEM_NUM_HITS'} selected="selected"{/if}>Number of Hits</option>
            <option value="_SMARTEST_ITEM_NUM_COMMENTS" {if $set.sort_field == '_SMARTEST_ITEM_NUM_COMMENTS'} selected="selected"{/if}>Number of Comments</option>
		      </select>
			  </td>
      </tr>
      
      <tr>
        <td style="width:180px">Sort Direction:</td>
        <td>
          <select name="set_sort_direction">
				    <option value="ASC" {if $set.sort_direction == "ASC"} selected{/if}>Ascending</option>
				    <option value="DESC" {if $set.sort_direction == "DESC"} selected{/if}>Descending</option>
			    </select>
			  </td>
      </tr>
    </table>

        <h4 style="margin-top:15px">Conditions</h4>
  		  
  		  <script type="text/javascript" src="{$domain}Resources/System/Javascript/smartest/dynamic-set-builder.js"></script>
  		  <script type="text/javascript">
  		    var newConditionMaker = new Smartest.DynamicSetBuilder;
  		  </script>
  		  
  		  <table id="rules-list">
  		    
{if empty($conditions)}			
          <tr><td colspan="4"><div>There are no conditions for this data set yet</div></td></tr>
{else}
          <tr><td colspan="4"><div>Retrieve all {$model.plural_name} where:</div></td></tr>
{/if}
          
{foreach from=$conditions item="rule" }
          <tr id="rule-tr-{$rule.itemproperty_id}">            
            <td>
					    <span id="no-property-input-{$rule.id}" style="{if $rule.itemproperty_id == '_SMARTEST_ITEM_TAGGED'}display:inline{else}display:none{/if}">The {$model.name}
					    <input value="_SMARTEST_ITEM_TAGGED" name="conditions[{$rule.id}][property_id]" type="hidden" /></span>
  					  <select name="conditions[{$rule.id}][property_id]" id="property-select-input-{$rule.id}" style="{if $rule.itemproperty_id == '_SMARTEST_ITEM_TAGGED'}display:none{else}display:inline{/if}">
  						  <option value="_SMARTEST_ITEM_NAME" {if $rule.itemproperty_id == "_SMARTEST_ITEM_NAME"} selected{/if}>{$model.name} {$model.item_name_field_name}</option>
  						  <option value="_SMARTEST_ITEM_ID" {if $rule.itemproperty_id == "_SMARTEST_ITEM_ID"} selected{/if}>{$model.name} ID</option>
  					    {foreach from=$properties item="property"}
  						  <option value="{$property.id}" {if $property.id == $rule.itemproperty_id} selected{/if}>{$property.name}</option>
                {/foreach}
  						  <option value="_SMARTEST_ITEM_NUM_HITS" {if $rule.itemproperty_id == "_SMARTEST_ITEM_NUM_HITS"} selected{/if}>Number of hits</option>
  						  <option value="_SMARTEST_ITEM_NUM_COMMENTS" {if $rule.itemproperty_id == "_SMARTEST_ITEM_NUM_COMMENTS"} selected{/if}>Number of comments</option>
  					  </select>
					  </td>
					  <td>
  					  <select name="conditions[{$rule.id}][operator]" onchange="updateSetConditionsFormFromOperator('{$rule.id}', this.value)">
  						  <option value="0" {if $rule.operator == "0"} selected="selected" {/if}>Equals</option>
  						  <option value="1" {if $rule.operator == "1"} selected="selected" {/if}>Does Not Equal</option>
  						  <option value="2" {if $rule.operator == "2"} selected="selected" {/if}>Contains</option>
  						  <option value="3" {if $rule.operator == "3"} selected="selected" {/if}>Does Not Contain</option>
  						  <option value="4" {if $rule.operator == "4"} selected="selected" {/if}>Starts With</option>
  						  <option value="5" {if $rule.operator == "5"} selected="selected" {/if}>Ends With</option>
  						  <option value="6" {if $rule.operator == "6"} selected="selected" {/if}>Greater Than</option>
  						  <option value="7" {if $rule.operator == "7"} selected="selected" {/if}>Less Than</option>
  						  <option value="8" {if $rule.operator == "8"} selected="selected" {/if}>Is Tagged With</option>
  						  <option value="9" {if $rule.operator == "9"} selected="selected" {/if}>Is Not Tagged With</option>
  					  </select>
  					</td>
						<td><input type="text" value="{$rule.value}" name="conditions[{$rule.id}][value]" /></td>
            <td><input type="button" value="-" onclick="window.location='{$domain}{$section}/removeConditionFromSet?condition_id={$rule.id}'" /></td>

        </tr>
{/foreach}
        
      <!--Add new condition?-->
      <tr id="add-new-condition-checkbox-holder">
			  <td colspan="4"><div>Add a new Condition:</div></td>
      </tr>
      
      <!--New condition form-->
			<tr id="add-new-condition">
			  <td>
			    
				  <select name="new_condition_property_id" id="new-condition-aspect">
  				  <option value="">Choose...</option>
  				  <option value="_SMARTEST_ITEM_PIECE_ITSELF" id="nc_name">The {$model.name} itself</option>
  				  <option value="_SMARTEST_ITEM_NAME" id="nc_name">{$model.name} {$model.item_name_field_name}</option>
  				  <option value="_SMARTEST_ITEM_ID" id="nc_id">{$model.name} ID</option>
            {foreach from=$properties item="property"}<option value="{$property.id}">{$property.name}</option>{/foreach}
					  <option value="_SMARTEST_ITEM_NUM_HITS">Number of hits</option>
					  <option value="_SMARTEST_ITEM_NUM_COMMENTS">Number of comments</option>
  		    </select>
  		    
  		    <script type="text/javascript">
  		      {literal}
  		      $('new-condition-aspect').observe('change', function(){
  		        newConditionMaker.setAspect();
  		      });
  		      {/literal}
  		    </script>
  		    
  		  </td>
    		  
  			<td>
  			  <div id="new-condition-operator-input"><span style="display:none;color:#999" id="operator-loading-text">loading...</span></div>
  			</td>
  			
  			<td>
  			  <div id="new-condition-value-input"><span style="display:none;color:#999" id="value-loading-text">loading...</span></div>
  			</td>
  			
  			<td></td>
  			
      </tr>
    </table>
  
    <div class="edit-form-row">
      <div class="buttons-bar">
        {* <input type="button" value="Cancel" />
        <input type="submit" value="Save Changes" /> *}
        {save_buttons}
      </div>
    </div>
  
  </form>

</div>

<div id="actions-area">
		
		<ul class="actions-list">
		  <li><b>Options</b></li>
			<li class="permanent-action"><a href="#" onclick="window.location='{$domain}{$section}/previewSet?set_id={$set.id}'"><img border="0" src="{$domain}Resources/Icons/folder_magnify.png"> Browse set contents</a></li>
      <li class="permanent-action"><a href="#" onclick="window.location='{$domain}{$section}/deleteSetConfirm?set_id={$set.id}'"><img border="0" src="{$domain}Resources/Icons/package_delete.png"> Delete this set</a></li>
			<li class="permanent-action"><a href="#" onclick="window.location='{$domain}{$section}/editStaticSetOrder?set_id={$set.id}'"><img border="0" src="{$domain}Resources/Icons/package_add.png"> Change the order of this set</a></li>
			<li class="permanent-action">{if $model.id}<a href="#" onclick="window.location='{$domain}{$section}/getItemClassSets?class_id={$model.id}'"><img border="0" src="{$domain}Resources/Icons/folder_old.png"> Browse sets of {$model.plural_name|strtolower}</a>{else}<a href="#" onclick="window.location='{$domain}smartest/sets'"><img border="0" src="{$domain}Resources/Icons/folder_old.png"> Back to data sets</a></li>{/if}		
			<li class="permanent-action"><a href="#" onclick="window.location='{$domain}smartest/models'"><img border="0" src="{$domain}Resources/Icons/package_small.png"> Browse all items</a></li>
			{* <li class="permanent-action"><a href="#" onclick="window.location='{$domain}{$section}/chooseSchemaForExport?set_id={$set.id}'"><img border="0" src="{$domain}Resources/Icons/package_add.png"> Export this Set as XML</a></li> *}
		</ul>
		
</div>


