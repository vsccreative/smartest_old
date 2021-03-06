<div id="work-area">
<h3>Add Tag</h3>

<div class="instruction">Enter one or more tags. Separate multiple tags with commas.</div>

<form action="{$domain}{$section}/insertTag" method="post">
  <div id="edit-form-layout">
    <div class="edit-form-row">
      <div class="form-section-label">Tag Name(s): </div>
      <input type="text" name="tag_label" />
    </div>
    {if $item.id}
    <div class="edit-form-row">
      <input type="checkbox" name="tag_item" value="1" checked="checked" id="tag_item_checkbox" />
      <label for="tag_item_checkbox">Tag '{$item.name}' with new tags I create here</label>
      <input type="hidden" name="item_id" value="{$item.id}" />
      {if $page_webid}<input type="hidden" name="page_webid" value="{$page_webid}" />{/if}
    </div>
    {/if}
    {if $page.id}
    <div class="edit-form-row">
      <input type="checkbox" name="tag_page" value="1" checked="checked" id="tag_page_checkbox" />
      <label for="tag_page_checkbox">Tag '{$page.name}' with new tags I create here</label>
      <input type="hidden" name="page_id" value="{$page.id}" />
    </div>
    {/if}
    {if $asset.id}
    <div class="edit-form-row">
      <input type="checkbox" name="tag_asset" value="1" checked="checked" id="tag_asset_checkbox" />
      <label for="tag_page_checkbox">Tag '{$asset.label}' with new tags I create here</label>
      <input type="hidden" name="asset_id" value="{$asset.id}" />
    </div>
    {/if}
    <div class="edit-form-row">
      <div class="buttons-bar">
        <input type="button" value="Cancel" onclick="cancelForm();" />
        <input type="submit" name="action" value="Save" />
      </div>
    </div>
  </div>
</form>

</div>

<div id="actions-area">
  
</div>