<script language="javascript">

var show_params_holder = false;

{literal}

function toggleParamsHolder(){
  if(show_params_holder){
    new Effect.BlindUp('params-holder', {duration: 0.6});
    show_params_holder = false;
    $('params-holder-toggle-link').innerHTML = "show";
  }else{
    new Effect.BlindDown('params-holder', {duration: 0.6});
    show_params_holder = true;
    $('params-holder-toggle-link').innerHTML = "hide";
  }
}

{/literal}
</script>

<div id="work-area">
  
  <h3>Define placeholder: {$placeholder.name}</h3>
  
  {if $require_choose_item}
  
  <div class="instruction">As this is a meta-page, you must choose an item to continue</div>
  
  <form id="item_chooser" method="get" action="{$domain}{$section}/definePlaceholder">
    
    <input type="hidden" name="assetclass_id" value="{$placeholder.name}" />
    <input type="hidden" name="page_id" value="{$page.webid}" />
    
    <select name="item_id" onchange="$('item_chooser').submit()" style="width:300px">
      {foreach from=$items item="possible_item"}
        <option value="{$possible_item.id}">{$possible_item.name}</option>
      {/foreach}
    </select>
    <input type="submit" value="Continue" />
    
  </form>
  
  {else}
  
  <form id="file_chooser" method="get" action="{$domain}{$section}/definePlaceholder">
    
    <div class="edit-form-row">
      <div class="form-section-label">Choose a file to define this placeholder with</div>
      <select name="chosen_asset_id" onchange="$('file_chooser').submit()">
        {if !$valid_definition}<option value="">None Selected</option>{/if}
        {foreach from=$assets item="available_asset"}
          <option value="{$available_asset.id}"{if $available_asset.id==$asset.id} selected="selected"{/if}>{if $available_asset.id==$live_asset_id}* {/if}{$available_asset.label}</option>
        {/foreach}
      </select>
      
    </div>
    
    <input type="hidden" name="assetclass_id" value="{$placeholder.name}" />
    <input type="hidden" name="page_id" value="{$page.webid}" />
    {if $show_item_options}<input type="hidden" name="item_id" value="{$item.id}" />{/if}
    
    </form>
    
    <form id="pageViewForm" method="post" action="{$domain}{$section}/updatePlaceholderDefinition">
    
      <input type="hidden" name="page_id" value="{$page.id}" />
      <input type="hidden" name="placeholder_id" value="{$placeholder.id}" />
      {if $show_item_options}<input type="hidden" name="item_id" value="{$item.id}" />{/if}
    
    {if $valid_definition}
    
      <input type="hidden" name="asset_id" value="{$asset.id}" />
    
      <div class="edit-form-row">
        <div class="form-section-label">Chosen File:</div>
        <b>{$asset.label}</b> ({if $asset_type.storage.type == 'file'}{$asset_type.storage.location}{/if}{$asset.url}) - {$asset_type.label}{if $asset.is_image} ({$asset.width} x {$asset.height} pixels){/if}
      </div>
      
      {if $show_item_options}
      <div class="edit-form-row">
        <div class="form-section-label">Meta Page:</div>
        {$page.static_title}
      </div>
      
      <div class="edit-form-row">
        <div class="form-section-label">{$item.model.name}:</div>
        {$item.name}
      </div>
      
      <div class="edit-form-row">
        <div class="form-section-label">Define placeholder on this meta-page for:</div>
        <select name="definition_scope">
          
          <option value="THIS">This {$item.model.name|strtolower} only</option>
          {if $item_uses_default}<option value="DEFAULT">All {$item.model.plural_name|strtolower} currently using the default definition</option>{/if}
          <option value="ALL">All {$item.model.plural_name|strtolower}{if $is_defined} (removes all other per-item definitions){/if}</option>
          
        </select>
      </div>
      {else}
      <div class="edit-form-row">
        <div class="form-section-label">Page:</div>
        {$page.title}
      </div>
      {/if}
      
{if !empty($params)}

<div class="special-box">
    
    <div class="heading">Instance parameters (<a id="params-holder-toggle-link" href="javascript:toggleParamsHolder()">show</a>)</div>
    
    <div id="params-holder" style="display:none">
    {foreach from=$params key="parameter_name" item="parameter"}
      <div class="edit-form-row">
        <div class="form-section-label">{$parameter_name}</div>
        <input type="text" name="params[{$parameter_name}]" style="width:250px" value="{$parameter.value}" id="render_parameter_{$parameter_name}" /> Default: {$asset_params[$parameter_name]}
      </div>
    {/foreach}
    </div>

</div>
{/if}

{/if}
  
  <div class="edit-form-row">
    <div class="buttons-bar">
      {if $valid_definition}<input type="submit" value="Save Changes" />{/if}
      <input type="button" onclick="cancelForm();" value="Cancel" />
    </div>
  </div>
  
  </form>
  
  {/if}
  
</div>

<div id="actions-area">
  
  <ul class="actions-list" id="item-specific-actions" style="display:none">
    <li><b>Selected Asset</b></li>
    <li class="permanent-action"><a href="#" onclick="{literal}if(selectedPage){workWithItem('updatePlaceholderDefinition');}{/literal}" class="right-nav-link"><img src="{$domain}Resources/Icons/tick.png" border="0" alt=""> Use This Asset</a></li>
  </ul>

  <ul class="actions-list" id="non-specific-actions">
    <li><b>Options</b></li>
    <li class="permanent-action"><a href="#" onclick="window.location='{$domain}assets/startNewFileCreationForPlaceholderDefinition?placeholder_id={$placeholder.id}&amp;page_id={$page.id}{if $show_item_options}&amp;item_id={$item.id}{/if}'" class="right-nav-link"><img src="{$domain}Resources/Icons/page_add.png" border="0" alt=""> Define with a new file</a></li>
    {* <li class="permanent-action"><a href="#" onclick="window.location='{$domain}{$section}/pageAssets?page_id={$page.id}'" class="right-nav-link"><img src="{$domain}Resources/Icons/cross.png" border="0" alt=""> Cancel</a></li> *}
{if $item}
    <li class="permanent-action"><a href="#" onclick="window.location='{$domain}websitemanager/undefinePlaceholder?page_id={$page.id}&amp;assetclass_id={$placeholder.name}';" class="right-nav-link"><img src="{$domain}Resources/Icons/cross.png" border="0" alt=""> Clear this placeholder</a></li>
    <li class="permanent-action"><a href="#" onclick="window.location='{$domain}websitemanager/undefinePlaceholderOnItemPage?page_id={$page.id}&amp;assetclass_id={$placeholder.name}&amp;item_id={$item.id}';" class="right-nav-link"><img src="{$domain}Resources/Icons/cross.png" border="0" alt=""> Clear or this {$item.model.name|strtolower} only</a></li>
{else}
    <li class="permanent-action"><a href="#" onclick="window.location='{$domain}websitemanager/undefinePlaceholder?page_id={$page.id}&amp;assetclass_id={$placeholder.name}';" class="right-nav-link"><img src="{$domain}Resources/Icons/cross.png" border="0" alt=""> Clear this placeholder</a></li>
{/if}
  </ul>
  
</div>