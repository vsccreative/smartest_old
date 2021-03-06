<div id="work-area">

{load_interface file="edit_tabs.tpl"}

<h3>Preview of page: {$page.title}{if $item} (as {$item._model.name|lower} &quot;{$item.name}&quot; <a href="{dud_link}" onclick="MODALS.load('datamanager/itemInfo?item_id={$item.id}', '{$item._model.name} info');" title="Get info"><img src="{$domain}Resources/Icons/information.png" alt="Get info" /></a>){/if}</h3>

{if $show_iframe}


<script language="javascript">
{literal}  
    var t1, t2;
    
    function showPreview(){
        $('preview-iframe').style.height = '500px';
        $('preview-iframe').removeClassName('building');
        $('preview-iframe').addClassName('built');
        clearTimeout(t1);
        clearTimeout(t2);
    }
    
    function hidePreview(){
        $('preview-iframe').style.height = '0px';
        $('preview-iframe').removeClassName('built');
        $('preview-iframe').addClassName('building');
    }
    
    function previewSlow(){
        $('preview-loading').style.display = 'none';
        $('preview-slow').style.display = 'block';
    }
    
    function previewTimedOut(){
        $('preview-slow').style.display = 'none';
        $('preview-failed').style.display = 'block';
    }
    
    t1 = setTimeout(function(){previewSlow();}, 8000);
    t2 = setTimeout(function(){previewTimedOut();}, 20000);

{/literal}    
</script>


<div class="menubar">
  <a href="{dud_link}" class="js-menu-activator" id="actions-menu-activator">Actions</a> {*<a href="javascript:showPreview()">Show</a>*}
  {if $stylesheets._count > 0}<a href="{dud_link}" class="js-menu-activator" id="files-menu-activator">Stylesheets</a>{/if}
{* if !empty($stylesheets)}
  {foreach from=$stylesheets item="stylesheet"}
  <a href="{$stylesheet.action_url}"><img src="{$stylesheet.small_icon}" />&nbsp;{$stylesheet.label}</a>
  {/foreach}
{/if *}
</div>

<div id="preview-actions-menu" class="js-menu" style="display:none">
  <ul></ul>
  <ul><li><a href="#reload-preview" id="reload-preview">Reload preview</a></li><li>{if $show_approve_button}<a href="{dud_link}" onclick="window.location='{$domain}{$section}/approvePageChanges?page_id={$page.webid}'">{else}<span>{/if}Approve changes{if $show_approve_button}</a>{else}</span>{/if}</li><li>{if $show_publish_button}<a href="{dud_link}" onclick="window.location='{$domain}{$section}/publishPageConfirm?page_id={$page.webid}{if $item}&amp;item_id={$item.id}{/if}'">{else}<span>{/if}Publish this page{if $show_publish_button}</a>{else}</span>{/if}</li>{if $item && $item.id}{else}<li><a href="{$domain}websitemanager/addPage?page_id={$page.webid}">Add a child page</a></li>{/if}{if $show_edit_item_option}{if $show_publish_item_option}<li><a href="{dud_link}" onclick="window.location='{$domain}datamanager/publishItem?page_id={$page.webid}&amp;item_id={$item.id}&amp;from=preview'">Publish this {$item._model.name}</a></li>{/if}<li><a href="{dud_link}" onclick="window.location='{$domain}datamanager/editItem?item_id={$item.id}&amp;page_id={$page.webid}&amp;from=pagePreview'">Edit this {$item._model.name}</a></li>{/if}<li>{if $show_release_page_option}<a href="{dud_link}" onclick="window.location='{$domain}{$section}/releasePage?page_id={$page.webid}'">{else}<span>{/if}Release this page{if $show_release_page_option}</a>{else}</span>{/if}</li></ul>
  <script type="text/javascript">
  {literal}
  $('reload-preview').observe('click', function(e){
      hidePreview();
      document.getElementById('preview-iframe').contentWindow.location.reload(true);
      t1 = setTimeout(function(){previewSlow();}, 8000);
      t2 = setTimeout(function(){previewTimedOut();}, 20000);
      e.stop();
  });
  {/literal}</script>
</div>

{if $stylesheets._count > 0}
<div id="preview-files-menu" class="js-menu">
  <ul></ul>
  <ul>{foreach from=$stylesheets item="stylesheet"}<li><a href="{$stylesheet.action_url}">{$stylesheet.label}</a></li>{/foreach}</ul>
  <script type="text/javascript"></script>
</div>
{/if}

<div id="preview">
  <iframe class="building" id="preview-iframe" src="{$domain}website/renderEditableDraftPage?page_id={$page.webid}{if $item}&amp;item_id={$item.id}{/if}{if $request_parameters.author_id}&amp;author_id={$request_parameters.author_id}{/if}{if $request_parameters.search_query}&amp;q={$request_parameters.search_query}{/if}{if $request_parameters.tag}&amp;tag_name={$request_parameters.tag}{/if}{if $request_parameters.hash}#{$request_parameters.hash}{/if}" style="height:0px"></iframe>
</div>

<div id="preview-loading" style="padding-top:50px;text-align:center">
    <p>Please wait. Rendering preview...</p>
    <p><img src="{$domain}Resources/System/Images/smartest_working.gif" /></p>
</div>

<div id="preview-slow" style="padding-top:50px;text-align:center;display:none">
    <p>Sorry for the wait. Just a bit longer... <a href="javascript:showPreview()">Show now</a></p>
    <p><img src="{$domain}Resources/System/Images/smartest_working.gif" /></p>
</div>

<div id="preview-failed" style="padding-top:50px;text-align:center;display:none">
    <p>Still no luck. Something stopped the page from building. <br />Try having a look at <a href="javascript:window.open('{$domain}website/renderEditableDraftPage?page_id={$page.webid}{if $item}&amp;item_id={$item.id}{/if}');">the page by itself</a>.</p>
</div>

{elseif $show_item_list}

{load_interface file="choose_item.tpl"}

{elseif $show_tag_list}

{load_interface file="choose_tag.tpl"}

{/if}

</div>

<script type="text/javascript">

var actionsMenu = new Smartest.UI.Menu('preview-actions-menu', 'actions-menu-activator');

{literal}
  $('actions-menu-activator').observe('click', function(e){
    actionsMenu.toggleVisibility();
    e.stop();
  });
{/literal}

{if $stylesheets._count > 0}
var filesMenu = new Smartest.UI.Menu('preview-files-menu', 'files-menu-activator');

{literal}
  $('files-menu-activator').observe('click', function(e){
    filesMenu.toggleVisibility();
    e.stop();
  });
{/literal}
{/if}

</script>