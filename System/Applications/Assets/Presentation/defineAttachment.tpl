{literal}
<script language="javascript">

function toggleZoomImageOption(){
    if($('attached_file_zoom').checked){
        new Effect.BlindDown('thumbnail_size_selector', {duration:0.4});
        new Effect.Appear('highslide-credit', {duration:0.6});
    }else{
        new Effect.BlindUp('thumbnail_size_selector', {duration:0.4});
        new Effect.Fade('highslide-credit', {duration:0.6});
    }
}

</script>
{/literal}
<div id="work-area">
  <h3>Define Attachment</h3>
  <form action="{$domain}{$section}/updateAttachmentDefinition" method="post" id="attachment-form">
  <div id="edit-form-layout">
    <div class="edit-form-row">
      <div class="form-section-label">Attachment Name</div>
      <code style="display:inline-block;padding-top:3px;font-size:14px">{$attachment_name}</code><input type="hidden" name="attachment_name" value="{$attachment_name}" />
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Text File</div>
      <img src="{$asset.small_icon}" alt="" /> {$asset}<input type="hidden" name="textfragment_id" value="{$textfragment_id}" />
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Attached File</div>
      <select name="attached_file_id">
        <option value="">No file attached</option>
        {foreach from=$files item="file"}
        <option value="{$file.id}"{if $file.id == $attached_asset_id} selected="selected"{/if}>{$file.stringid} ({$file.url})</option>
        {/foreach}
      </select>
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Zoom</div>
      <input type="checkbox" name="attached_file_zoom" value="TRUE" id="attached_file_zoom"{if $zoom} checked="checked"{/if} onchange="toggleZoomImageOption()" />&nbsp;<label for="attached_file_zoom">Zoom from thumbnail file</label> <span id="highslide-credit" class="form-hint" style="display:{if $zoom}inline{else}none{/if}">(Powered by <a href="http://www.highslide.com/" target="_blank">Highslide</a>. License terms apply. Please {help id="assets:highslide"}click here{/help} for more information. )</span>
    </div>
    <div class="edit-form-row" style="display:{if $zoom}block{else}none{/if}" id="thumbnail_size_selector">
      <div class="form-section-label">Thumbnail Relative Size:</div>
      {slider name="thumbnail_relative_size" value=$relative_size min="20" max="90" value_unit="%"}
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Position</div>
      <select name="attached_file_alignment">
        <option value="left"{if $alignment == "left"} selected="selected"{/if}>On the Left</option>
        <option value="right"{if $alignment == "right"} selected="selected"{/if}>On the Right</option>
        <option value="center"{if $alignment == "center"} selected="selected"{/if}>In the Center (Non-floating only)</option>
      </select>
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Caption</div>
      <textarea name="attached_file_caption" style="width:300px;height:30px">{$caption}</textarea>
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Caption Alignment</div>
      <select name="attached_file_caption_alignment">
        <option value="left"{if $caption_alignment == "left"} selected="selected"{/if}>From Left</option>
        <option value="right"{if $caption_alignment == "right"} selected="selected"{/if}>From Right</option>
        <option value="center"{if $caption_alignment == "center"} selected="selected"{/if}>Centered</option>
      </select>
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Float</div>
      <input type="checkbox" name="attached_file_float" value="TRUE"{if $float} checked="checked"{/if} id="attached_file_float" />&nbsp;<label for="attached_file_float">Float within the text.</label>
    </div>
    <div class="edit-form-row">
      <div class="form-section-label">Border</div>
      <input type="checkbox" name="attached_file_border" value="TRUE"{if $border} checked="checked"{/if} id="attached_file_border" />&nbsp;<label for="attached_file_border">Show a 1px grey border.</label>
    </div>
    <div class="edit-form-row">
      <div class="buttons-bar">
        <input type="submit" value="Save" />
        <input type="button" value="Cancel" onclick="cancelForm();" />
      </div>
    </div>
  </div>
  </form>
</div>