<select name="<?php echo $optionsName; ?>[widget_theme]" style="width: 70px">
    <?php foreach($themes as $value): ?>
        <option <?php if(strtolower($active) == strtolower($value)) echo 'selected="selected"'; ?> value="<?php echo strtolower($value); ?>"><?php echo $value; ?></option>
    <?php endforeach; ?>
</select>

<span class="description">Choose a dark or light display theme.</span>