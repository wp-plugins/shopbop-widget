<select name="<?php echo $optionsName; ?>[widget_language]">
    <?php foreach($languages as $key => $value): ?>
    	<option <?php if($widgetLanguage == strtolower($value)) echo 'selected="selected"' ?> value="<?php echo strtolower($value); ?>"><?php echo $key; ?></option>
    <?php endforeach; ?>
</select>
<span class='description'>Select the widget language to display.</span>