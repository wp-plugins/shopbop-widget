Fluid <input type="radio" name="width-type" value="fluid" <?php if($fluid=='fluid') echo 'checked="checked"'; ?> />
Fixed <input type="radio" name="width-type" value="fixed" <?php if($fluid=='fixed') echo 'checked="checked"'; ?> />

<span id="lf_width_input_hide">
<input id="widget-width-px" name="<?php echo $optionsName; ?>[widget_width]" type="text" readonly="readonly"
 	   size="5" style="border: none" value="<?php echo is_null($widgetWidth ) ? 220 : $widgetWidth; ?>">pixels <span class="description">(min: 200, max: 350)</span>

<div id="slider" style="width:300px"></div>
<span class="description">Choose either a fixed width or "fluid" to automatically select the most appropriate size.</span>
</span>