<script type="text/javascript">
  var asset_id = {$asset.id};
  var asset_label = '{$asset.label}';
</script>

<div id="work-area">
    {load_interface file="edit_asset_tabs.tpl"}
    
    <h3>File Info</h3>
    
    <input type="hidden" name="asset_id" value="{$asset.id}" />
    
    <table cellspacing="1" border="0" class="info-table">
      <tr>
        <td style="width:170px;background-color:#fff" valign="middle" class="field-name">File name:</td>
        <td>
          <p class="editable" id="asset-label">{$asset.label}</p>
          <script type="text/javascript">
          new Ajax.InPlaceEditor('asset-label', sm_domain+'ajax:assets/setAssetLabelFromInPlaceEditField', {ldelim}
            callback: function(form, value) {ldelim}
              return 'asset_id={$asset.id}&new_label='+encodeURIComponent(value);
            {rdelim},
            highlightColor: '#ffffff',
            hoverClassName: 'editable-hover',
            savingClassName: 'editable-saving'
          {rdelim});
          </script>
        </td>
      </tr>
      {if !$asset.is_external}
      <tr>
        <td style="width:150px;background-color:#fff" class="field-name">Name on disk:</td>
        <td><code>{$asset.full_path}</code></td>
      </tr>
      {/if}
      {if $asset.is_web_accessible}
      <tr>
        <td style="width:150px;background-color:#fff" class="field-name">Direct URL</td>
        <td><code>{$asset.absolute_uri}</code></td>
      </tr>
      {else}
      <tr>
        <td style="width:150px;background-color:#fff" class="field-name">Public download URL</td>
        <td><code>{$asset.download_uri}</code></td>
      </tr>
      {/if}
      {if !$asset.is_external}
      <tr>
        <td class="field-name">Size:</td>
        <td>{$asset.size}{if $asset.is_image}, ({$asset.width} x {$asset.height} pixels){/if}</td>
      </tr>
      {/if}
      <tr>
        <td class="field-name">Type:</td>
        <td><a href="{$domain}{$section}/getAssetTypeMembers?asset_type={$asset.type}">{$asset.type_info.label}</a> <span style="color:#666">({$asset.type})</span></td>
      </tr>
      {if $asset.created > 0}
      <tr>
        <td class="field-name">Created:</td>
        <td>{$asset.created|date_format:"%A %B %e, %Y, %l:%M%p"}</span></td>
      </tr>
      {/if}
      {if $asset.modified > 0}
      <tr>
        <td class="field-name">Modified:</td>
        <td>{$asset.modified|date_format:"%A %B %e, %Y, %l:%M%p"}</span></td>
      </tr>
      {/if}
      <tr>
        <td valign="middle" class="field-name">Owner:</td>
        <td>
          <select name="asset_user_id" id="asset-owner" style="width:300px">
  {foreach from=$potential_owners item="p_owner"}
            <option value="{$p_owner.id}"{if $asset.owner.id == $p_owner.id} selected="selected"{/if}>{$p_owner.fullname} ({$p_owner.id})</option>
  {/foreach}
          </select>
          <script type="text/javascript">
          {literal}
          $('asset-owner').observe('change', function(){
            var url = sm_domain+'ajax:assets/setAssetOwnerById';
            new Ajax.Request(url, {
              method: 'post',
              parameters: {'asset_id': asset_id, 'owner_id': $('asset-owner').value}
            });
          });
          {/literal}
          </script>
        </td>
      </tr>
      <tr>
        <td valign="middle" class="field-name">Language:</td>
        <td>

          <select name="asset_language" id="asset-language" style="width:300px">
        {foreach from=$_languages item="lang" key="langcode"}
            <option value="{$langcode}"{if $asset.language == $langcode} selected="selected"{/if}>{$lang.label}</option>
        {/foreach}
          </select>
          
          <script type="text/javascript">
          {literal}
          $('asset-language').observe('change', function(){
            var url = sm_domain+'ajax:assets/setAssetLanguage';
            new Ajax.Request(url, {
              method: 'post',
              parameters: {'asset_id': asset_id, 'asset_language': $('asset-language').value}
            });
          });
          {/literal}
          </script>
          
        </td>
      </tr>
      <tr>
        <td class="field-name">Original site:</td>
        <td>{$asset.site.internal_label}</td>
      </tr>
      <tr>
        <td class="field-name">Shared with other sites:</td>
        <td>
          <input type="checkbox" id="asset-shared" name="asset_shared" value="1"{if $asset.shared==1} checked="checked"{if $asset.site_id!=$_site.id} disabled="disabled"{/if}{/if} />{if $asset.site_id!=$_site.id}<span class="form-hint">File cannot be un-shared because it belongs to a different site. To set sharing, edit the file in the site where it was created.</span>{/if}
          <script type="text/javascript">
          {literal}
          $('asset-shared').observe('click', function(){
            var url = sm_domain+'ajax:assets/setAssetShared';
            var checked = $('asset-shared').checked ? 1 : 0;
            new Ajax.Request(url, {
              method: 'post',
              parameters: {'asset_id': asset_id, 'is_shared': checked}
            });
          });
          {/literal}
          </script>
        </td>
      </tr>
      <tr>
        <td class="field-name">&nbsp;</td>
        <td>
          <a href="#open-file-notes" id="file-notes-link"><img src="{$domain}Resources/Icons/note.png"> Notes</a>
          <script type="text/javascript">
          {literal}
          $('file-notes-link').observe('click', function(e){
            MODALS.load('assets/assetCommentStream?asset_id='+asset_id, 'Notes on file: \''+asset_label+'\'');
            e.stop();
          });
          {/literal}
          </script>
        </td>
      </tr>
    </table>
  
  <div class="special-box">
    <div class="special-box-key">File groups:</div>
    {if count($groups)}{foreach from=$groups item="group"}<a href="{$domain}{$section}/browseAssetGroup?group_id={$group.id}">{$group.label}</a> (<a href="{$domain}{$section}/transferSingleAsset?asset_id={$asset.id}&amp;group_id={$group.id}&amp;transferAction=remove">remove</a>), {/foreach}{else}<em style="color:#666">None</em>{/if}
{if count($possible_groups)}
      <div>
        <form action="{$domain}{$section}/transferSingleAsset" method="post">
          <input type="hidden" name="asset_id" value="{$asset.id}" />
          <input type="hidden" name="transferAction" value="add" />
          Add this file to group:
          <select name="group_id">
{foreach from=$possible_groups item="possible_group"}
            <option value="{$possible_group.id}">{$possible_group.label}</option>
{/foreach}
          </select>
          <input type="submit" value="Go" />
        </form>
      </div>
{/if}
  </div>
      
{*      <h4 style="margin-top:15px">Usage of this file</h4> *}
  
</div>

<div id="actions-area">
  <ul class="actions-list" id="non-specific-actions">
    <li><b>Options</b></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editAsset?asset_type={$asset_type.id}&amp;asset_id={$asset.id}'"><img src="{$domain}Resources/Icons/pencil.png" alt=""/> Edit this file</a></li>
    {if $allow_source_edit}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editTextFragmentSource?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $smarty.get.from}&amp;from={$smarty.get.from}{/if}'"><img src="{$domain}Resources/Icons/page_edit.png" alt=""/> Edit This File's Source</a></li>{/if}
    {if $show_attachments}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/textFragmentElements?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $smarty.get.from}&amp;from={$smarty.get.from}{/if}'"><img src="{$domain}Resources/Icons/attach.png" alt=""/> Edit File Attachments</a></li>{/if}
    {if $show_publish}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/publishTextAsset?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}'"><img src="{$domain}Resources/Icons/page_lightning.png" alt=""/> Publish This Text</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getAssetTypeMembers?asset_type={$asset.type_info.id}'"><img src="{$domain}Resources/Icons/folder_old.png" alt=""/> View all {$asset.type_info.label} files</a></li>
  </ul>
</div>