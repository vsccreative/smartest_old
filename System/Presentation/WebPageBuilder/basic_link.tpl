<?sm:if $_linkparameters._link_show_anchor:?><a href="<?sm:$_linkparameters._link_url:?>"<?sm:$_linkparameters._link_parameters:?>><?sm:if $_linkparameters._link_use_span:?><span<?sm:if $_linkparameters._link_span_invisible:?> style="display:none"<?sm:/if:?>><?sm:/if:?><?sm:$_linkparameters._link_contents:?><?sm:if $_linkparameters._link_use_span:?></span><?sm:/if:?></a><?sm:else:?><!--cold link - would have been linked to: <?sm:$_linkparameters._link_url:?>--><?sm:$_linkparameters._link_contents:?><?sm:/if:?>