<script language="javascript">

var acceptable_suffixes = {$suffixes};
var input_mode = '{$starting_mode}';
var show_params_holder = false;

{literal}

function insertAssetClass(){
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
	// new Effect.BlindDown('uploader', {duration: 0.6});
	$('uploader_link').style.display = 'none';
	$('text_window').style.display = 'none';
	// new Effect.BlindUp('text_window', {duration: 0.6});
	input_mode = 'upload';
	$('input_mode').value = input_mode;
	
}

function hideUploader(){
	$('uploader').style.display = 'none';
	// new Effect.BlindUp('uploader', {duration: 0.6});
	$('uploader_link').style.display = 'block';
	$('text_window').style.display = 'block';
	// new Effect.BlindDown('text_window', {duration: 0.6});
	input_mode = 'direct';
	$('input_mode').value = input_mode;
	$('tpl_textArea').disabled = false;
	/* tinyMCE.init({
  	mode : "textareas",
  	theme : "advanced",
  	theme_advanced_buttons3_add_before : "tablecontrols,separator",
  	theme_advanced_toolbar_location : "top",
  	theme_advanced_resizing : true,
  	convert_fonts_to_spans : false
  });*/
}

function validateUploadSuffix(){
	
  if(input_mode == 'upload'){
    
  }else{
    return true;
  }

}

{/literal}
</script>


<div id="work-area">
  
  <h3>Add a new file</h3>
  
  <form action="{$domain}{$section}/saveNewAsset" method="post" name="newAsset" enctype="multipart/form-data">
    
    <input type="hidden" name="asset_type" value="{$type_code}" />
    <input type="hidden" name="MAX_FILE_SIZE" value="8000000" />
    <input type="hidden" name="input_mode" id="input_mode" value="{$starting_mode}" />
    
    {if count($possible_groups)}
      <div id="groups" class="special-box">
    
            <div>
              Add this file to group:
                <select name="initial_group_id">
                  <option value="">None (for now)</option>
    {foreach from=$possible_groups item="possible_group"}
                  <option value="{$possible_group.id}">{$possible_group.label}</option>
    {/foreach}
                </select>
            </div>
    
      </div>
    {/if}
    
    {load_interface file=$form_include}
    
    {if !empty($params)}<a id="params-holder-toggle-link" href="javascript:toggleParamsHolder()">Show Parameters</a>{/if}
    
    <div id="params-holder" style="display:none">
    {foreach from=$params key="parameter_name" item="parameter_value"}
    <div class="edit-form-row">
      <div class="form-section-label">{$parameter_name}</div>
      <input type="text" name="params[{$parameter_name}]" style="width:250px" />
    </div>
    {/foreach}
    </div>
    
    <div class="edit-form-row">
      <div class="form-section-label">Language</div>
      <select name="asset_language">
        <option value="">{$lang.label}</option>
{foreach from=$_languages item="lang" key="langcode"}
        <option value="{$langcode}">{$lang.label}</option>
{/foreach}
      </select>
    </div>
    
    <div class="edit-form-row">
      <div class="form-section-label">Share this asset with other sites?</div>
      <input type="checkbox" name="asset_shared" /> Check here to allow all sites to use this file.
    </div>
    
    <div class="edit-form-row">
      <div class="buttons-bar">
        <input type="button" value="Cancel" onclick="cancelForm();">
        <input type="submit" value="Save" />
      </div>
    </div>
    
  </form>
  
</div>

<div id="actions-area">

</div>