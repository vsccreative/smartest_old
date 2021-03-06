<form action="{$domain}{$section}/updateAsset" method="post" name="newHtml" enctype="multipart/form-data">

  <input type="hidden" name="asset_id" value="{$asset.id}" />
  <input type="hidden" name="asset_type" value="{$asset.type}" />
    
    <div class="special-box">
      <span class="heading">Language</span>
      <select name="asset_language">
        <option value="">{$lang.label}</option>
    {foreach from=$_languages item="lang" key="langcode"}
        <option value="{$langcode}"{if $asset.language == $langcode} selected="selected"{/if}>{$lang.label}</option>
    {/foreach}
      </select>
    </div>
    
    {foreach from=$asset._editor_parameters key="parameter_name" item="parameter"}
    <div class="edit-form-row">
      <div class="form-section-label">{$parameter.label}</div>
      <input type="text" name="params[{$parameter_name}]" value="{$parameter.value}" style="width:250px" />
    </div>
    {/foreach}
    
    <div id="textarea-holder" style="width:100%">
        <textarea name="asset_content" id="tpl_textArea" wrap="virtual" style="width:100%;padding:0">{$textfragment_content}</textarea>
        <span id="wordcount"></span>
        <div class="buttons-bar">
            {save_buttons}
        </div>
    <div>
        
</form>

<!--<script language="javascript" type="text/javascript" src="{$domain}Resources/System/Javascript/tiny_mce/tiny_mce.js"></script>-->
<script src="{$domain}Resources/System/Javascript/tinymce4/tinymce.min.js"></script>

<script language="javascript" type="text/javascript">
{literal}

tinymce.init({
    selector: "#tpl_textArea",
    plugins: [
        "advlist autolink lists charmap print preview anchor",
        "searchreplace visualblocks code fullscreen",
        "media table contextmenu paste link wordcount"
    ],
    paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,h4,p",
    toolbar: "insertfile undo redo | styleselect | bold italic | link unlink | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | code"
});

/* tinyMCE.init({
	mode : "exact",
	elements : "tpl_textArea",
	theme : "advanced",
	plugins : "paste",
	theme_advanced_buttons3_add : "paste,pasteword,selectall",
	theme_advanced_disable : "image,styleprops",
	theme_advanced_toolbar_location : "top",
	theme_advanced_resizing : true,
	theme_advanced_toolbar_align : "center",
	convert_fonts_to_spans : true,
	paste_use_dialog : true,
  paste_remove_spans : true,
  paste_remove_styles: true,
  paste_strip_class_attributes: true,
  relative_urls : false,
  remove_script_host : true,
{/literal}  document_base_url : "{$domain}" {literal}
  
});
  */
  
  var AutoSaver = new PeriodicalExecuter(function(pe){
    // autosave routine
  }, 5);
  
{/literal}

</script>