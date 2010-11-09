<div id="work-area">
  
  {load_interface file="edit_asset_tabs.tpl"}
  <h3>Edit Text File Source</h3>
  <form action="{$domain}{$section}/updateAsset" method="post" name="newHtml" enctype="multipart/form-data">

    <input type="hidden" name="asset_id" value="{$asset.id}" />
    <input type="hidden" name="asset_type" value="{$asset.type}" />

      {foreach from=$asset.type_info.param item="parameter"}
      <div class="edit-form-row">
        <div class="form-section-label">{$parameter.name}</div>
        <input type="text" name="params[{$parameter.name}]" value="{$parameter.value}" style="width:250px" />
      </div>
      {/foreach}

      <div class="special-box">Name of the Asset: <strong>{$asset.url}</strong></div>
      
      <div class="textarea-holder" style="width:100%">
          <textarea name="asset_content" id="tpl_textArea" wrap="virtual" style="width:100%;padding:0">{$textfragment_content}</textarea>
          <span class="form-hint">Editor powered by CodeMirror</span>
      </div>
        
      <div class="buttons-bar">
        {* <input type="submit" value="Save Changes" />
        <input type="button" onclick="cancelForm();" value="Cancel" /> *}
        <input type="hidden" name="editor" value="source" />
        {save_buttons}
      </div>

  </form>
  
  <script src="{$domain}Resources/System/Javascript/CodeMirror-0.65/js/codemirror.js" type="text/javascript"></script>

  <script type="text/javascript">
  {literal}  var editor = new CodeMirror.fromTextArea('tpl_textArea', {{/literal}
    parserfile: 'parsexml.js',
    stylesheet: "{$domain}Resources/System/Javascript/CodeMirror-0.65/css/xmlcolors.css",
    continuousScanning: 500,
    height: '300px',
    path: "{$domain}Resources/System/Javascript/CodeMirror-0.65/js/"
  {literal}  }); {/literal}
  </script>
  
</div>

<div id="actions-area">
  <ul class="actions-list" id="non-specific-actions">
    <li><b>Options</b></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getAssetTypeMembers?asset_type={$asset_type.id}'"><img src="{$domain}Resources/Icons/folder_old.png" alt=""/> View all {$asset_type.label} assets</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/assetInfo?asset_id={$asset.id}'"><img src="{$domain}Resources/Icons/information.png" alt=""/> About this file</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editAsset?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $smarty.get.from}&amp;from={$smarty.get.from}{/if}'"><img src="{$domain}Resources/Icons/pencil.png" alt=""/> Edit in Rich Text Editor</a></li>
    {if $show_attachments}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/textFragmentElements?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $smarty.get.from}&amp;from={$smarty.get.from}{/if}'"><img src="{$domain}Resources/Icons/attach.png" alt=""/> Edit File Attachments</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/previewAsset?asset_id={$asset.id}'"><img src="{$domain}Resources/Icons/page_lightning.png" alt=""/> Preview This File</a></li>
    {if $show_publish}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/publishTextAsset?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}'"><img src="{$domain}Resources/Icons/page_lightning.png" alt=""/> Publish This Text</a></li>{/if}
  </ul>
</div>