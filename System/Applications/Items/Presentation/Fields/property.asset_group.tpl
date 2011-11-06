{capture name="name" assign="name"}item[{$property.id}]{/capture}
{capture name="property_id" assign="property_id"}item_property_{$property.id}{/capture}

{asset_group_select id=$property_id name=$name value=$value options=$property._options required=$property.required}

{if is_numeric($value.id)}
 <input type="button" onclick="window.location='{$domain}assets/editAssetGroup?from=item_edit&amp;group_id='+$('item_property_{$value.id}').value" value="Edit &gt;&gt;" />
{/if}

{if strlen($property.hint)}<span class="form-hint">{$property.hint}</span>{/if}