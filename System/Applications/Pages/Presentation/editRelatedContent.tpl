<div id="work-area">
  <h3>Related {if $mode == 'items'}{$model.plural_name}{else}Pages{/if}</h3>
  {if $mode == 'items'}
  
    {if empty($items)}
    
    <div class="instruction">There are no {$model.plural_name|strtolower} in the system yet.</div>
    
    <div id="edit-form-layout">
      <div class="edit-form-row">
        <div class="buttons-bar">
          <input type="button" value="Cancel" onclick="cancelForm();" />
        </div>
      </div>
    </div>
    
    {else}
    
    <div class="instruction">Check the boxes next to the {$model.plural_name|strtolower} you'd like to link to this page.</div>
    
    <form action="{$domain}{$section}/updateRelatedItemConnections" method="post">
    
      <input type="hidden" name="page_id" value="{$page.id}" />
      <input type="hidden" name="model_id" value="{$model.id}" />
    
      <ul class="basic-list scroll-list" style="height:350px;border:1px solid #ccc">
      
        {foreach from=$items item="item"}
        <li><input type="checkbox" name="items[{$item.id}]" id="item_{$item.id}"{if in_array($item.id, $related_ids)} checked="checked"{/if} /><label for="item_{$item.id}">{$item.name}</label></li>
        {/foreach}
      
      </ul>
  
      <div id="edit-form-layout">
        <div class="edit-form-row">
          <div class="buttons-bar">
            <input type="button" value="Cancel" onclick="cancelForm();" />
            <input type="submit" name="action" value="Save" />
          </div>
        </div>
      </div>
  
    </form>
  
    {/if}
  
  {else}
  
  <div class="instruction">Check the boxes next to the pages you'd like to link to this one</div>
  
  <form action="{$domain}{$section}/updateRelatedPageConnections" method="post">
    
    <input type="hidden" name="page_id" value="{$page.id}" />
    
    <ul class="basic-list scroll-list" style="height:350px;border:1px solid #ccc">
      {foreach from=$pages item="relatable_page"}
      
      {if $relatable_page.type == 'NORMAL' && $relatable_page.id != $page.id}
      <li><input type="checkbox" name="pages[{$relatable_page.id}]" id="page_{$relatable_page.id}"{if in_array($relatable_page.id, $related_ids)} checked="checked"{/if} /><label for="page_{$relatable_page.id}">{$relatable_page.title}</label></li>
      {/if}
      {/foreach}
    </ul>
  
    <div id="edit-form-layout">
      <div class="edit-form-row">
        <div class="buttons-bar">
          <input type="button" value="Cancel" onclick="cancelForm();" />
          <input type="submit" name="action" value="Save" />
        </div>
      </div>
    </div>
  
  </form>
  
  {/if}
</div>

<div id="actions-area">
  
</div>