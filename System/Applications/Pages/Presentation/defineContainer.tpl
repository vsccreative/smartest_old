<script type="text/javascript">
  var templates = new Smartest.UI.OptionSet('pageViewForm', 'item_id_input', 'option', 'options_grid');
  {if $selected_template_id > 0}templates.setPriorSelection('{$selected_template_id}', 'template');{/if}
</script>

<div id="work-area">
  
  <h3>Define Container</h3>
  <div class="instruction">Please choose a template to use in this container.</div>
  
  <form id="pageViewForm" method="post" action="">
    <input type="hidden" name="page_id" value="{$page.id}" />
    {if $show_item_options}<input type="hidden" name="item_id" value="{$item.id}" />{/if}
    <input type="hidden" name="container_id" value="{$container.id}" />
    <input type="hidden" name="asset_id" id="item_id_input" value="" />
  
    <div class="edit-form-row">
      <div class="form-section-label">Container:</div>
      {$container.name}
    </div>
  
  {if $show_item_options}
    <div class="edit-form-row">
      <div class="form-section-label">Meta Page:</div>
      {$page.static_title}
    </div>
    
    <div class="edit-form-row">
      <div class="form-section-label">{$item._model.name}:</div>
      {$item.name}
    </div>
    
    <div class="edit-form-row">
      <div class="form-section-label">Define container on this meta-page for:</div>
      <select name="definition_scope">
        
        <option value="THIS">This {$item._model.name|strtolower} only</option>
        {if $item_uses_default}<option value="DEFAULT">All {$item._model.plural_name|strtolower} currently using the default definition</option>{/if}
        {if $selected_template_id > 0}<option value="ALL">All {$item._model.plural_name|strtolower} (removes all other per-item definitions)</option>{else}<option value="DEFAULT">All {$item.model.plural_name|strtolower} without per-item definitions</option><option value="ALL">All {$item.model.plural_name|strtolower} (removes all other per-item definitions)</option>{/if}
        
      </select>
    </div>
    {else}
    <div class="edit-form-row">
      <div class="form-section-label">Page:</div>
      {$page.title}
    </div>
    {/if}
  
  </form>
  
  <div id="options-view-chooser">
  <a href="#" onclick="return templates.setView('list', 'define_container_list_view')">List</a> /
  <a href="#" onclick="return templates.setView('grid', 'define_container_list_view')">Icons</a>
  </div>

  <ul class="options-{$list_view}" style="margin-top:0px" id="options_grid">
  {foreach from=$templates item="asset"}
  <li>
      <a href="#" class="option" id="template_{$asset.id}" onclick="return templates.setSelectedItem('{$asset.id}', 'template');" >
      <img border="0" src="{$domain}Resources/Icons/blank_page.png" />{$asset.stringid}</a>
  </li>
  {/foreach}
  </ul>
  
</div>

<div id="actions-area">
  
  <ul class="actions-list" id="template-specific-actions" style="display:none">
    <li><b>Selected template</b></li>
    <li class="permanent-action"><a href="#" onclick="return templates.workWithItem('updateContainerDefinition');" class="right-nav-link"><img src="{$domain}Resources/Icons/tick.png" border="0" alt=""> Use This Template</a></li>
  </ul>

  <ul class="actions-list" id="non-specific-actions">
    <li><b>Options</b></li>
    <li class="permanent-action"><a href="#" onclick="window.location='{$domain}templates/importNewTemplateForContainerDefinition?container_id={$container.id}&amp;page_id={$page.id}{if $show_item_options}&amp;item_id={$item.id}{/if}';" class="right-nav-link"><img src="{$domain}Resources/Icons/layout_add.png" border="0" alt=""> Import new template...</a></li>
    <li class="permanent-action"><a href="#" onclick="window.location=sm_cancel_uri;" class="right-nav-link"><img src="{$domain}Resources/Icons/cross.png" border="0" alt=""> Cancel</a></li>
  </ul>
  
</div>

{if $selected_template_id > 0}

<script language="javascript">
  templates.setPriorSelection('{$selected_template_id}', 'template');
</script>

{/if}