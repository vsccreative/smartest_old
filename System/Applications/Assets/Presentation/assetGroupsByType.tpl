<div id="work-area">
  
  <h3>{$type.label} files</h3>
  
  {load_interface file="assettype_tabs.tpl"}
  
  <div class="instruction">Groups that are able to contain {$type.label|lower} files</div>
  
  <form id="pageViewForm" method="get" action="">
    <input type="hidden" name="group_id" id="item_id_input" value="" />
  </form>


  <ul class="{if count($groups) > 10}options-list{else}options-grid{/if}" id="{if count($groups) > 10}options_list{else}options_grid{/if}">
  {foreach from=$groups key="key" item="group"}
    <li style="list-style:none;" 
  			ondblclick="window.location='{$domain}{$section}/browseAssetGroup?group_id={$group.id}'">
  			<a class="option" id="item_{$group.id}" onclick="setSelectedItem('{$group.id}', 'fff');" >
  			  <img border="0" src="{$domain}Resources/Icons/folder.png">
  			  {$group.label}</a></li>
  {/foreach}
  </ul>
  
</div>

<div id="actions-area">
  <ul class="actions-list" id="item-specific-actions" style="display:none">
    <li><b>Selected Group</b></li>
    <li class="permanent-action"><a href="#" onclick="{literal}if(selectedPage){workWithItem('editAssetGroup');}{/literal}"><img border="0" src="{$domain}Resources/Icons/information.png"> Group info</a></li>
    <li class="permanent-action"><a href="#" onclick="{literal}if(selectedPage){workWithItem('editAssetGroupContents');}{/literal}"><img border="0" src="{$domain}Resources/Icons/folder_edit.png"> Edit contents</a></li>
    <li class="permanent-action"><a href="#" onclick="{literal}if(selectedPage){workWithItem('browseAssetGroup');}{/literal}" ><img border="0" src="{$domain}Resources/Icons/folder_magnify.png"> Browse contents</a></li>
  </ul>
  
  <ul class="actions-list" id="non-specific-actions">
    <li><b>Options</b></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/newAssetGroup?filter_type={$type.id}'" class="right-nav-link"><img src="{$domain}Resources/Icons/folder_add.png" border="0" alt="" /> New {$type.label|lower} file group</a></li>
  	<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}smartest/assets'" class="right-nav-link"><img src="{$domain}Resources/Icons/folder_old.png" border="0" alt="" style="width:16px;height:16px" /> View all files by type</a></li>
  </ul>
  
</div>