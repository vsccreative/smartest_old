<script language="javascript">

// var acceptable_suffixes = {$suffixes};
var input_mode = '{$starting_mode}';
var show_params_holder = false;
var itemNameFieldDefaultValue = '{$start_name}';
var preventDefaultValue = {if $suggested_name}false{else}true{/if};


{literal}

document.observe('dom:loaded', function(){
    
    $('new-asset-name').observe('focus', function(){
        if(($('new-asset-name').getValue() == itemNameFieldDefaultValue)|| $('new-asset-name').getValue() == ''){
            $('new-asset-name').removeClassName('unfilled');
            $('new-asset-name').setValue('');
        }
    });
    
    $('new-asset-name').observe('blur', function(){
        if(($('new-asset-name').getValue() == itemNameFieldDefaultValue) || $('new-asset-name').getValue() == ''){
            $('new-asset-name').addClassName('unfilled');
            $('new-asset-name').setValue(itemNameFieldDefaultValue);
        }else{
            $('new-asset-name').removeClassName('error');
        }
    });
    
    $('new-asset-form').observe('submit', function(e){
        
        if(($('new-asset-name').getValue() == itemNameFieldDefaultValue) || $('new-asset-name').getValue() == ''){
            $('new-asset-name').addClassName('error');
            e.stop();
        }
        
    });
    
});

/* function insertAssetClass(){
	var assetClassName = prompt("Enter the asset class name");
	var html = '{assetclass get="'+assetClassName+'"}';
	insertElement(html);
}

function insertElement(){
	var field = document.getElementById("tpl_textArea");
	field.focus();
	alert(field.value);
}

function toggleParamsHolder(){
  if(show_params_holder){
    new Effect.BlindUp('params-holder', {duration: 0.6});
    show_params_holder = false;
    $('params-holder-toggle-link').innerHTML = "Show Parameters";
  }else{
    new Effect.BlindDown('params-holder', {duration: 0.6});
    show_params_holder = true;
    $('params-holder-toggle-link').innerHTML = "Hide Parameters";
  }
}

function showUploader(){
	$('uploader').style.display = 'block';
	$('uploader_link').style.display = 'none';
	$('text_window').style.display = 'none';
	input_mode = 'upload';
	$('input_mode').value = input_mode;
	
}

function hideUploader(){
	$('uploader').style.display = 'none';
	$('uploader_link').style.display = 'block';
	$('text_window').style.display = 'block';
	input_mode = 'direct';
	$('input_mode').value = input_mode;
	$('tpl_textArea').disabled = false;
}

function validateUploadSuffix(){
	
  if(input_mode == 'upload'){
    
  }else{
    return true;
  }

} */

{/literal}
</script>


<div id="work-area">
  
  <h3>Add a new {$new_asset_type_info.label|strtolower} file to the repository</h3>
  
  {if $require_type_selection}
  
    <div class="instruction">Please choose which type of file you would like to add{if $for=='placeholder'} to define this placeholder with{elseif $for=='ipv'} to define this property with{/if}</div>
    
    <form action="{$domain}smartest/file/new" method="get" id="file-type-form">
      <div class="edit-form-row">
        <select name="asset_type" id="file-type-select">
{foreach from=$types item="type"}
          <option value="{$type.id}">{$type.label}</option>
{/foreach}
        </select>
      </div>
      
    {if $for}
      {if $for=='placeholder'}
        <input type="hidden" name="for" value="placeholder" />
        <input type="hidden" name="placeholder_id" value="{$placeholder.id}" />
        <input type="hidden" name="page_id" value="{$page.id}" />
        {if $item}<input type="hidden" name="item_id" value="{$item.id}" />{/if}
      {elseif $for=='ipv'}
        <input type="hidden" name="for" value="ipv" />
        <input type="hidden" name="property_id" value="{$property.id}" />
        {if $item}<input type="hidden" name="item_id" value="{$item.id}" />{/if}
      {elseif $for=='filegroup'}
        <input type="hidden" name="for" value="filegroup" />
        <input type="hidden" name="group_id" value="{$group.id}" />
      {/if}
    {/if}
    
    {if $group}<input type="hidden" name="group_id" value="{$group.id}" />{/if}
    
    <div class="buttons-bar"><input type="submit" value="Continue" /></div>
    
    </form>
  
  {else}
  
    {if $allow_save}
      
    <form action="{$domain}smartest/file/new/save" method="post" enctype="multipart/form-data" id="new-asset-form">  
    
      {if $for=='placeholder'}
        <input type="hidden" name="for" value="placeholder" />
        <input type="hidden" name="placeholder_id" value="{$placeholder.id}" />
        <input type="hidden" name="page_id" value="{$page.id}" />
      {elseif $for=='ipv'}
        <input type="hidden" name="for" value="ipv" />
        <input type="hidden" name="property_id" value="{$property.id}" />
      {elseif $for=='filegroup'}
        <input type="hidden" name="for" value="filegroup" />
        <input type="hidden" name="group_id" value="{$group.id}" />
      {/if}
      
      <input type="hidden" name="input_method" value="{$input_method}">
      <input type="hidden" name="asset_type" value="{$new_asset_type_info.id}">
      
{if count($input_methods) > 1}
    <ul class="tabset">
{foreach from=$input_methods key="input_method_code" item="input_method_tab"}
      <li{if $input_method_code == $input_method} class="current"{/if}><a href="{$domain}smartest/file/new?asset_type={$type_code}&amp;input_method={$input_method_code}{if $for}&amp;for={$for}{/if}{if $for == "placeholder"}&amp;placeholder_id={$placeholder.id}&amp;page_id={$page.id}{/if}{if $for == "ipv"}&amp;property_id={$property.id}{/if}{if $item.id}&amp;item_id={$item.id}{/if}">{$input_method_tab.label}</a></li>
{/foreach}
    </ul>
{/if}

      {if count($possible_groups)}
      <div id="groups" class="special-box">

        <div>
          Add this file to group:
            <select name="initial_group_id"{if $lock_group_dropdown} disabled="disabled"{/if}>
              <option value="">None</option>
{foreach from=$possible_groups item="possible_group"}
              <option value="{$possible_group.id}"{if $group_id && $possible_group.id == $group_id} selected="selected"{/if}>{$possible_group.label}</option>
{/foreach}
            </select>
        </div>

      </div>
      {/if}
      
      {if $for=='placeholder'}
        <div class="instruction">The {$new_asset_type_info.label|strtolower} file you are creating will be used to define placeholder <strong>'{$placeholder.label}'</strong> on {if $item}meta-page <strong>'{$page.title}'</strong><span id="item-specificity"> for {$item._model.name|lower} <strong>'{$item.name}'</strong></span>.{else}page <strong>'{$page.title}'</strong>.{/if}</div>
      {elseif $for=='ipv'}
        {if $item}
          <div class="instruction">The {$new_asset_type_info.label|strtolower} file you are creating will be used to define property <strong>'{$property.name}'</strong> of {$property._model.name|strtolower} <strong>'{$item.name}'</strong>.</div>
        {else}
          <div class="instruction">The {$new_asset_type_info.label|strtolower} file you are creating will be used as the value for property <strong>'{$property.name}'</strong> for a new <strong>{$property._model.name|strtolower}</strong>.</div>
        {/if}
      {elseif $for=='filegroup'}
        <div class="instruction">The {$new_asset_type_info.label|strtolower} file you are creating will be added to </div>
      {/if}
      
      <div class="edit-form-row">
        <div class="form-section-label">{$name_instruction}</div>
        <input type="text" name="asset_label" value="{if $suggested_name}{$suggested_name}{else}{$start_name}{/if}" {if !$suggested_name}class="unfilled"{/if} id="new-asset-name" />
      </div>

      {if $for=='placeholder'}
        {if $item}
        <div class="edit-form-row">
          <div class="form-section-label">Scope</div>
          <select name="item_id" id="item-id">
            <option value="{$item.id}">Use only when viewing {$item._model.name|lower} {$item.name}</option>
            <option value="ALL">Use on this meta-page for all {$item._model.plural_name|lower}.</option>
          </select>
          <input type="hidden" name="continue_item_id" value="{$item.id}" />
        </div>
        <script type="text/javascript">
          {literal}
          $('item-id').observe('change', function(e){
            if($('item-id').value == 'ALL'){
              $('item-specificity').hide();
            }else{
              $('item-specificity').show();
            }
          });
          {/literal}
        </script>
        {/if}
      {elseif $for=='ipv'}
        {if $item}<input type="hidden" name="item_id" value="{$item.id}" />{/if}
      {/if}
      
    {load_interface file="$interface_file"}
    
    <div class="edit-form-row">
      <div class="form-section-label">Language</div>
      <select name="asset_language">
{foreach from=$_languages item="lang" key="langcode"}
        <option value="{$langcode}"{if $langcode == $site_language} selected="selected"{/if}>{$lang.label}</option>
{/foreach}
      </select>
    </div>

    <div class="edit-form-row">
      <div class="form-section-label">Share this file?</div>
      <input type="checkbox" name="asset_shared" id="asset_shared" /><label for="asset_shared">Check here to allow all your sites to use this file.</label>
    </div>

    <div class="buttons-bar">
      <input type="button" value="Cancel" onclick="cancelForm()">
      <input type="submit" value="Save">
    </div>
</form>
    {else}
  
  <div class="warning">
    The directory <strong><code>{$path}</code></strong> is not writable by the web server, so <strong>{$new_asset_type_info.label}</strong> files cannot currently be created or uploaded via Smartest.
  </div>
  
  <div class="buttons-bar">
    <input type="button" id="cancel-asset-create" value="Cancel" />
    <script type="text/javascript">
      $('cancel-asset-create').observe('click', cancelForm);
    </script>
  </div>
  
    {/if}
    
  {/if}
  
</div>

<div id="actions-area">

</div>