<!--Smartest Text Attachment: <?sm:$_textattachment._name:?>-->
<div class="image" style="<?sm:if $_textattachment.float && $_textattachment.alignment != 'center' :?>width:<?sm:$_textattachment.div_width :?>px;<?sm:/if:?><?sm:if $_textattachment.border :?>border: 1px solid #ccc;<?sm:/if:?><?sm:if $_textattachment.float && $_textattachment.alignment != 'center' :?>float: <?sm:else:?>text-align: <?sm:/if:?><?sm:$_textattachment.alignment:?>; margin<?sm:if $_textattachment.alignment == "right" :?>-left<?sm:else if $_textattachment.alignment == "left" :?>-right<?sm:/if:?>: 10px;">
<?sm:asset id=$_textattachment.asset.id style="margin:5px;":?>
<?sm:if $_textattachment.caption :?><div class="caption" style="text-align:<?sm:$_textattachment.caption_alignment :?>;display:block; margin:5px;font-size:11px;<?sm:if $_textattachment.float && $_textattachment.alignment != 'center' :?>width:<?sm:$_textattachment.asset.width :?>px<?sm:/if:?>"><?sm:$_textattachment.caption:?></div><?sm:/if:?>
</div>