<div class="core-widget-affiliate-wrapper">

    No Thanks <input id="core-widget-no-thanks-affiliate" type="radio" name="widget_affiliate_information"
                     value="nothanks" <?php if ($aff == 'nothanks') echo 'checked="checked"'; ?> />&nbsp;&nbsp;&nbsp;&nbsp;
    Publisher ID <input id="core-widget-affiliate" type="radio" name="widget_affiliate_information"
                        value="cjAffiliateSelected" <?php if ($aff == 'cjAffiliateSelected') echo 'checked="checked"'; ?> />&nbsp;
    <input id="core-widget-affiliate-id" type="text" name="cjPublisherID" value="<?php echo $affid; ?>"/>
</div>
<br>