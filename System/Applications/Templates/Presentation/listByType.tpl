<script type="text/javascript">
{literal}
  var templates = new Smartest.UI.OptionSet('pageViewForm', 'item_id_input', 'option', 'options_grid');
{/literal}
</script>

<div id="work-area">

<h3>{$type.label}s</h3>

<div class="instruction">{$type.description}</div>

{if !$dir_is_writable}
<div class="warning">The directory <code>{$type.storage.location}</code> needs to be writable before you can add more of these files via Smartest.</div>
{/if}

<form id="pageViewForm" method="get" action="">
  <input type="hidden" name="type" value="{$type.template_type}" />
  <input type="hidden" name="asset_type" value="{$type.id}" />
  <input type="hidden" name="template" id="item_id_input"  value="" />
</form>

<div id="options-view-chooser">
Found {$count} {$type.label|lower}{if $count != 1}s{/if}. View as:
<a href="#" onclick="return templates.setView('list', 'list_by_type_view')">List</a> /
<a href="#" onclick="return templates.setView('grid', 'list_by_type_view')">Icons</a>
</div>

<ul class="options-{$list_style}" style="margin-top:0px" id="options_grid">
{foreach from=$templates item="template"}
<li>
  <a href="#" class="option" id="{if $template.status == 'imported'}imported-template{else}unimported-template{/if}_{if $template.status == 'imported'}{$template.id}{else}{$template.url}{/if}" onclick="return templates.setSelectedItem('{if $template.status == 'imported'}{$template.id}{else}{$template.url}{/if}', '{if $template.status == 'imported'}imported-template{else}unimported-template{/if}');" ondblclick="window.location='{$domain}{$section}/editTemplate?asset_type={$template.type}&amp;template={if $template.status == 'imported'}{$template.id}{else}{$template.url}{/if}'">
    <img border="0" src="{$domain}Resources/Icons/{if $template.status == 'imported'}blank{else}mystery{/if}_page.png" />{$template.url}</a>
</li>
{/foreach}
</ul>

</div>

<div id="actions-area">

<ul class="actions-list" id="imported-template-specific-actions" style="display:none">
    
  <li><b>Selected template:</b></li>
	<li class="permanent-action"><a href="javascript:templates.workWithItem('editTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/pencil.png" border="0" alt="" /> Edit this template</a></li>
	<li class="permanent-action"><a href="javascript:templates.workWithItem('templateInfo');" class="right-nav-link"><img src="{$domain}Resources/Icons/information.png" border="0" alt="" /> About this template</a></li>
	<li class="permanent-action"><a href="javascript:templates.workWithItem('duplicateTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/page_edit.png" border="0" alt="" /> Duplicate this template</a></li>
	<li class="permanent-action"><a href="javascript:{literal}if(confirm('Really delete this template?')){ templates.workWithItem('deleteTemplate'); }{/literal}" class="right-nav-link"><img src="{$domain}Resources/Icons/page_delete.png" border="0" alt="" /> Delete this template</a></li>
	<li class="permanent-action"><a href="javascript:templates.workWithItem('downloadTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/page_white_put.png" border="0" alt="" /> Download this template</a></li>
</ul>

<ul class="actions-list" id="unimported-template-specific-actions" style="display:none">
    
  <li><b>Unimported template:</b></li>
	<li class="permanent-action"><a href="#" onclick="return templates.workWithItem('importSingleTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/page_add.png" border="0" alt="" /> Import this template</a></li>
	<li class="permanent-action"><a href="#" onclick="return templates.workWithItem('editTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/pencil.png" border="0" alt="" /> Edit as-is</a></li>
	<li class="permanent-action"><a href="#" onclick="return templates.workWithItem('deleteTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/page_delete.png" border="0" alt="" /> Delete this template</a></li>
	<li class="permanent-action"><a href="#" onclick="return templates.workWithItem('duplicateTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/page_edit.png" border="0" alt="" /> Duplicate this template</a></li>
	<li class="permanent-action"><a href="#" onclick="return templates.workWithItem('downloadTemplate');" class="right-nav-link"><img src="{$domain}Resources/Icons/page_white_put.png" border="0" alt="" /> Download this template</a></li>
</ul>

<ul class="actions-list" id="non-specific-actions">
  <li><b>Template options</b></li>
	{if $dir_is_writable}<li class="permanent-action"><a href="javascript:nothing()" onclick="window.location='{$domain}{$section}/addTemplate?type={$type.id}';" class="right-nav-link"><img src="{$domain}Resources/Icons/page_add.png" border="0" alt="" /> Add another {$type.label|lower}</a></li>{/if}
	<li class="permanent-action"><a href="javascript:nothing()" onclick="window.location='{$domain}smartest/templates';" class="right-nav-link"><img src="{$domain}Resources/Icons/folder.png" border="0" alt="" style="width:16px;height:16px" /> Back to template types</a></li>
</ul>

<ul class="actions-list" id="non-specific-actions">
  <li><b>Recent {$type.label|strtolower}s</b></li>
  {foreach from=$recently_edited item="recent_template"}
	<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$recent_template.action_url}'"><img border="0" src="{$recent_template.small_icon}" /> {$recent_template.label|summary:"30"}</a></li>
  {/foreach}
</ul>

</div>