<div id="work-area">

{load_interface file="edit_set_tabs.tpl"}

<h3><a href="{$domain}smartest/models">Items</a> &gt; {if $model.id}<a href="{$domain}datamanager/getItemClassMembers?class_id={$model.id}">{$model.plural_name}</a> &gt; <a href="{$domain}sets/getItemClassSets?class_id={$model.id}">Sets</a>{else}<a href="{$domain}smartest/sets">Sets</a>{/if} &gt; <a href="{$domain}sets/editSet?set_id={$set.id}">{$set.label}</a> &gt; Browse</h3>

<form id="pageViewForm" method="get" action="">
  <input type="hidden" name="set_id"  value="{$set.id}" />
  <input type="hidden" name="item_id" id="item_id_input" value="" />
</form>

<form action="{$domain}{$section}/previewSet" method="get" id="mode-form">
  
  <div class="special-box">Show:
    <input type="hidden" name="set_id"  value="{$set.id}" />
    <select name="mode" onchange="$('mode-form').submit();">
      {if $set.type == 'DYNAMIC'}
      <option value="0"{if $mode == 0} selected="selected"{/if}>All {$model.plural_name|strtolower}, using draft property values</option>
      <option value="1"{if $mode == 1} selected="selected"{/if}>All {$model.plural_name|strtolower}, using draft property values, but only in archive</option>
      <option value="2"{if $mode == 2} selected="selected"{/if}>All {$model.plural_name|strtolower}, using draft property values, excluding those that are archived</option>
      <option value="3"{if $mode == 3} selected="selected"{/if}>All {$model.plural_name|strtolower}, using live property values</option>
      <option value="4"{if $mode == 4} selected="selected"{/if}>All {$model.plural_name|strtolower}, using live property values, but only in archive</option>
      <option value="5"{if $mode == 5} selected="selected"{/if}>All {$model.plural_name|strtolower}, using live property values, excluding those that are archived</option>
      <option value="6"{if $mode == 6} selected="selected"{/if}>Published {$model.plural_name|strtolower}, but using draft property values</option>
      <option value="7"{if $mode == 7} selected="selected"{/if}>Published {$model.plural_name|strtolower}, but using draft property values, but only in archive</option>
      <option value="8"{if $mode == 8} selected="selected"{/if}>Published {$model.plural_name|strtolower}, but using draft property values, excluding those that are archived</option>
      <option value="9"{if $mode == 9} selected="selected"{/if}>Published {$model.plural_name|strtolower}, using live property values</option>
      <option value="10"{if $mode == 10} selected="selected"{/if}>Published {$model.plural_name|strtolower}, using live property values, but only in archive</option>
      <option value="11"{if $mode == 11} selected="selected"{/if}>Published {$model.plural_name|strtolower}, using live property values, excluding those that are archived</option>
      {else}
      <option value="0"{if $mode == 0} selected="selected"{/if}>All {$model.plural_name|strtolower}</option>
      <option value="1"{if $mode == 1} selected="selected"{/if}>All archived {$model.plural_name|strtolower}</option>
      <option value="2"{if $mode == 2} selected="selected"{/if}>All {$model.plural_name|strtolower}, excluding those that are archived</option>
      <option value="6"{if $mode == 6} selected="selected"{/if}>Published {$model.plural_name|strtolower}</option>
      <option value="7"{if $mode == 7} selected="selected"{/if}>Published {$model.plural_name|strtolower} that are archived</option>
      <option value="8"{if $mode == 8} selected="selected"{/if}>Published {$model.plural_name|strtolower}, excluding those that are archived</option>
      {/if}
    </select>
    {help id="datamanager:query_modes"}What's this?{/help}
  </div>
</form>

{if $set.type == 'STATIC' || $count > 0}
  {if empty($items)}
    <div class="warning">There are currently no items in this set. <a href="{$domain}{$section}/editSet?set_id={$set.id}">Click here</a> to add some.</div>
  {else}
    <div class="instruction">Found {$count} item{if $count != 1}s{/if} in this data set</div>
  {/if}
{else}
<div class="warning">Please note: This saved query is currently empty because there are no items that match its conditions in the current mode. {help id="datamanager:query_modes"}What's a mode{/help}</div>
{/if}

{if !empty($items)}

View as:
<a href="{dud_link}" onclick="setView('list', '{if $content.count > 10}options_list{else}options_grid{/if}')">List</a> /
<a href="{dud_link}" onclick="setView('grid', '{if $content.count > 10}options_list{else}options_grid{/if}')">Icons</a>
  
  <ul class="{if $content.count > 10}options-list{else}options-grid{/if}" id="{if $content.count > 10}options_list{else}options_grid{/if}">
  {foreach from=$items key="key" item="item"}
    <li>
      <a href="{dud_link}" class="option" id="item_{$item.id}" onclick="setSelectedItem('{$item.id}', '{$item.name|escape:quotes}');" ondblclick="window.location='{$domain}datamanager/openItem?item_id={$item.id}'">
        <img border="0" src="{$domain}Resources/Icons/item.png">{$item.name}</a></li>
  {/foreach}
  </ul>
{/if}

</div>

<div id="actions-area">
    
    <ul class="actions-list" id="item-specific-actions" style="display:none">
      <li><b>Selected Item</b></li>
      <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}datamanager/openItem?item_id='+selectedPage+'&amp;from=previewSet'"><img border="0" src="{$domain}Resources/Icons/pencil.png" style="width:16px;height:18px"> Edit this {$model.name|strtolower}</a></li>	
      <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}datamanager/publishItem?item_id='+selectedPage"><img border="0" src="{$domain}Resources/Icons/page_lightning.png" style="width:16px;height:18px"> Publish this {$model.name|strtolower}</a></li>	
    </ul>
    
    <ul class="actions-list">
      <li><b>Sets Options</b></li>
      <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editSet?set_id={$set.id}'"><img border="0" src="{$domain}Resources/Icons/pencil.png" style="width:16px;height:18px" /> Edit this set</a></li>
      <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/deleteSetConfirm?set_id={$set.id}'"><img border="0" src="{$domain}Resources/Icons/package_delete.png" style="width:16px;height:18px" /> Delete this set</a></li>
      <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editStaticSetOrder?set_id={$set.id}';" ><img border="0" src="{$domain}Resources/Icons/arrow_switch.png"> Change order</a></li>
      {if $model.id}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getItemClassSets?class_id={$model.id}'"><img border="0" src="{$domain}Resources/Icons/folder.png" style="width:16px;height:18px" /> Back to sets</a></li>{else}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}smartest/sets'"><img border="0" src="{$domain}Resources/Icons/folder.png" style="width:16px;height:18px"> Back to sets</a></li>{/if}
      <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}datamanager/getItemClassMembers?class_id={$set.model.id}'"><img border="0" src="{$domain}Resources/Icons/package_small.png" /> Browse {$set.model.plural_name}</a></li>
      <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}smartest/models'"><img border="0" src="{$domain}Resources/Icons/package_small.png" /> Browse all items</a></li>
    </ul>
        
</div>
