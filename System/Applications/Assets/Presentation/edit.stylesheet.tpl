<form action="{$domain}{$section}/updateAsset" method="post" name="newCss" enctype="multipart/form-data">
  
  <input type="hidden" name="asset_type" value="{$asset.type}" />
  <input type="hidden" name="asset_id" value="{$asset.id}" />
  
  <div class="instruction">You are editing file: Public/Resources/Stylesheets/{$asset.url}</div>
    
  {foreach from=$asset.default_parameters key="parameter_name" item="parameter_value"}
  <div class="edit-form-row">
    <div class="form-section-label">{$parameter_name}</div>
    <input type="text" name="params[{$parameter_name}]" value="{$parameter_value}" style="width:250px" />
  </div>
  {/foreach}
    
  <div class="edit-form-row">
    <div class="form-section-label">File contents</div>
    <textarea name="asset_content" id="tpl_textArea" wrap="virtual" >{$textfragment_content}</textarea>
  </div>
  
  <div class="buttons-bar">
    <input type="submit" value="Save Changes" />
    <input type="button" onclick="cancelForm();" value="Done" />
  </div>
  
</form>