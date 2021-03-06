<script language="javascript">

{literal}

var selectedPage = null;
var selectedPageName = null;
var lastRow;
var lastRowColor;

function workWithItem(pageAction){
	
	var editForm = document.getElementById('pageViewForm');

	if(editForm){
		
{/literal}		editForm.action="/{$section}/"+pageAction;{literal}
		
		editForm.submit();
	}
}

function setView(viewName, list_id){
	if(viewName == "grid"){
		document.getElementById(list_id).className="options-grid";
	}else if(viewName == "list"){
		document.getElementById(list_id).className="options-list";
	}
}

function setSelectedItem(page_id, pageName, rowColor){
	
	var row='item_'+page_id;
	var editForm = document.getElementById('pageViewForm');
	rowColor='#'+rowColor;
	selectedPage = page_id;
	selectedPageName = pageName;
	
	document.getElementById('item-specific-actions').style.display = 'block';
	
	if(lastRow){
		document.getElementById(lastRow).className="option";
		// document.getElementById('pageNameField').innerHTML='';
	}
	
	document.getElementById(row).className="selected-option";
	
	lastRow = row;
	lastRowColor = rowColor;
	editForm.schema_id.value = page_id;
}

{/literal}
</script>

<div id="work-area">

<h3><a href="{$domain}datamanager">Data Manager</a> &gt; <a href="{$domain}{$section}">Sets</a> &gt;{$set.set_name}  &gt; Choose a Schema to Countinue...</h3>

<form id="pageViewForm" method="get" action="">
  <input type="hidden" name="schema_id"   />
  <input type="hidden" name="set_id"  value="{$set.set_id}" />
</form>

<div id="options-view-chooser">
View as:
<a href="#" onclick="setView('list', 'options_grid')">List</a> /
<a href="#" onclick="setView('grid', 'options_grid')">Icons</a>
</div>

<ul class="options-grid" id="options_grid">
{foreach from=$schemas key=key item=schema}
{if $schema.schema_id}
  <li ondblclick="window.location='{$domain}modeltemplates/schemaDefinition?schema_id={$schema.schema_id}'">
	<a class="option" id="item_{$schema.schema_id}" onclick="setSelectedItem('{$schema.schema_id}', '{$schema.schema_name|escape:quotes}', 'fff');" >
	  <img border="0" src="http://smartest.dev.visudo.net/Resources/Icons/page_code.png">{$schema.schema_name}</a></li>
{/if}
{/foreach}

</ul>

</div>
<div id="actions-area">

<ul class="actions-list" id="item-specific-actions">
  
  <li class="permanent-action"><img border="0" src="{$domain}Resources/Icons/page_code.png"> <a href="#" onclick="workWithItem('exportDataOptions');">Countinue...</a></li>
</ul>

<ul class="actions-list" id="non-specific-actions">
  <li class="permanent-action">Select a Schema</li>
</ul>

</div>


