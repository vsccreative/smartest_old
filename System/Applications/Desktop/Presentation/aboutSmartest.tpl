<div id="work-area">
  <h3>About Smartest</h3>
  
  {literal}<style type="text/css">p{margin:0 0 5px 0;}</style>{/literal}
  
  <div>
    <div style="float:left;width:106px;height:300px">
      <img src="{$domain}Resources/System/Images/info_logo.png" alt="Smartest Logo" />
    </div>
    <p><strong>Version</strong>: {$version} (Revision {$revision})</p>
    <p><strong>Installed</strong>: {if $system_installed_timestamp > 0}{$system_installed_timestamp|date_format:"%A %B %e, %Y, %l:%M%p"}{else}<em>Unknown</em>{/if}</p>
    <p><strong>PHP Version</strong>: {$php_version}</p>
    <p><strong>Available Memory</strong>: {$memory_limit}</p>
    <p><strong>Server Speed</strong>: {$speed_score} <input type="button" value="Test..." onclick="window.location='{$domain}desktop/testServerSpeed'" /></p>
    <p><strong>Platform</strong>: {$platform}</p>
    <p><strong>Credit</strong>: Designed and developed by Marcus Gilroy-Ware. Originally devised by Marcus Gilroy-Ware and Eddie Tejeda. Thanks to Chris Brauer, Eddie Tejeda, Sereen Joseph, Nancy Arnold, Paul Gilroy, Vron Ware, a few brave MA Journalism students at City University London, and early adopters everywhere.</p>
    <p style="margin-top:10px"><span style="font-size:10px;color:#999">"Smartest" and the Smartest logo are trademarks of and © VSC Creative Ltd 2006-{$smarty.now|date_format:"%Y"}.</span></p>
    <p style="margin-top:40px;text-align:right"></p>
  </div>
  
</div>

<div id="actions-area" style="text-align:center;padding-top:20px">
    <img src="{$domain}Resources/System/Images/info_vsc_labs.png" alt="More great software from VSC Labs" />
</div>