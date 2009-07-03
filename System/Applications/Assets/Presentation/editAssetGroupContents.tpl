<script language="javascript">
{literal}

function setMode(mode){

	document.getElementById('transferAction').value=mode;

	if(mode == "add"){
		document.getElementById('add_button').disabled=false;
		document.getElementById('remove_button').disabled=true;
		
	}else if(mode == "remove"){
		document.getElementById('add_button').disabled=true;
		document.getElementById('remove_button').disabled=false;
		formList = document.getElementById('used_items');
	}	
	
}

function executeTransfer(){
	document.transferForm.submit();
}

{/literal}
</script>

<div id="work-area">
    
  <h3>Files in {$group.label}</h3>
  
  <form action="{$domain}{$section}/transferAssets" method="post" name="transferForm">

    <input type="hidden" id="transferAction" name="transferAction" value="" /> 
    <input type="hidden" name="group_id" value="{$group.id}" />

    <table width="100%" border="0" cellpadding="0" cellspacing="5" style="border:1px solid #ccc">
      <tr>
        <td align="center">
          <div style="text-align:left">Files that <strong>aren't</strong> in this group</div>

  		    <select name="available_assets[]"  id="available_assets" size="2" multiple style="width:270px; height:300px;"  onclick="setMode('add')"  >

{foreach from=$non_members key="key" item="asset"}
  		      <option value="{$asset.id}" >{$asset.url}</option>
{/foreach}

  		    </select>

  		  </td>
        
        <td valign="middle" style="width:40px">
  		    <input type="button" value="&gt;&gt;" id="add_button" disabled="disabled" onclick="executeTransfer();" /><br /><br />
          <input type="button" value="&lt;&lt;" id="remove_button" disabled="disabled" onclick="executeTransfer();" />
        </td>
        
        <td align="center">
          <div style="text-align:left">Files that <strong>are</strong> in this group</div>
   	      
   	      <select name="used_assets[]"  id='used_assets' size="2" multiple style="width:270px; height:300px" onclick="setMode('remove')" >	
{foreach from=$members key="key" item="asset"}
  		      <option value="{$asset.id}" >{$asset.url}</option>
{/foreach}
          </select>
          
  	    </td>
      </tr>
    </table>
  </form>
  
</div>

<div id="actions-area">
  
</div>