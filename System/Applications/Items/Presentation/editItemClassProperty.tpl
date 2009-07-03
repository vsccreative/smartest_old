<div id="work-area">

  <h3><a href="{$domain}smartest/data">Items</a> &gt; <a href="{$domain}smartest/models">Models</a> &gt; <a href="{$domain}{$section}/getItemClassMembers?class_id={$model.id}">{$model.plural_name}</a> &gt; Edit Property</h3>

  <div id="instruction">You are editing the property &quot;{$property.name}&quot; of model &quot;{$model.plural_name}&quot;</div>

  <form action="{$domain}{$section}/updateItemClassProperty" method="post" enctype="multipart/form-data">
  
  <input type="hidden" name="class_id" value="{$model.id}" />
  <input type="hidden" name="itemproperty_id" value="{$property.id}" />

    <div class="edit-form-row">
        <div class="form-section-label">Name</div>
        {$property.name} (Accessed as <strong>{$property.varname}</strong>)
    </div>

    <div class="edit-form-row">
        <div class="form-section-label">Data type</div>
        {$data_types[$property.datatype].label} <span style="color:#999">({$property.datatype})</span>
    </div>
    
{if $property.datatype == 'SM_DATATYPE_ASSET' || $property.datatype == 'SM_DATATYPE_ASSET_DOWNLOAD'}

    <div class="edit-form-row">
        <div class="form-section-label">Restrict selection to a file group?</div>
        <input type="hidden" name="itemproperty_filter_type" value="ASSET_GROUP" />
        <select name="itemproperty_filter">
          <option value="NONE">No restriction</option>
{foreach from=$possible_groups item="group"}
          <option value="{$group.id}"{if $group.id == $property.option_set_id} selected="selected"{/if}>{$group.label}</option>
{/foreach}

        </select>
    </div>

{/if}
    
    <div class="edit-form-row">
        <div class="form-section-label">Required</div>
        <input type="checkbox" name="itemproperty_required" id="is-required" value="TRUE"  {if $property.required == "TRUE"} checked="checked"{/if}/><label for="is-required">Check if required</label>
    </div>
    
    <div class="edit-form-row">
        <div class="buttons-bar">
            <input type="button" value="Cancel" onclick="window.location='{$domain}{$section}/getItemClassProperties?class_id={$model.id}';" />
            <input type="submit" value="Save Changes" />
        </div>
    </div>

  </form>

</div>