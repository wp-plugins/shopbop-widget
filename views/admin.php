<?php $widgetPrefix = 'SHOPBOP_'; ?>

<!-- Begining of EULA-->
<?php
 //Check for eula agreement if agreed or not.
$coreWs = new CoreWebservice;
if(!$coreWs->eulaCheck()):?>
<div class="wrap metabox-holder">
    <div id="icon-users" class="icon32"></div>
    <h1>Shopbop Widget End User License Agreement</h1><br>

    <form method="post" action="options.php" enctype="multipart/form-data">
        <?php settings_fields(constant($widgetPrefix.'WIDGET_PLUGIN_WP_EULA_AGREEMENT')); ?>
        <?php do_settings_sections('core_widget_eula_section'); ?>
        <br>
        <input type="submit" class="button-primary" name="core-widget-eula" value="Agree"/>
        <input type="submit" class="button-primary" name="core-widget-eula" value="Cancel">
    </form>
</div>
<!--    END of EULA-->
<?php else: ?>
<div class="wrap metabox-holder core-widget-admin-table">
<div id="icon-options-general" class="icon32"></div>
<h2><?php echo constant($widgetPrefix .'WIDGET_OPTIONS_PAGE_TITLE') ?></h2><br />
            <?php settings_errors(); ?>
		            <form method="post" action="options.php" enctype="multipart/form-data">
			             <?php settings_fields(constant($widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME')); ?>


							    <div id="shopbop-widget-registerbox" class="postbox" >
									<h3>Registration</h3>
								    <?php do_settings_sections('core_widget_register_section'); ?>
								</div>
								<?php
								$widgetWsOptions = get_option(constant($this->widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));
								if(isset($widgetWsOptions['registered']) && $widgetWsOptions['registered'] == true):
	        					?>
	        					<div id="shopbop-widget-apperancebox" class="postbox" >
									<h3>Widget Appearance</h3>
								    <?php do_settings_sections('core_widget_apperance_section'); ?>
								</div>
								
	        					<div id="shopbop-widget-categoriesbox" class="postbox" >
									<h3>Default Categories</h3>
								    <?php do_settings_sections('core_widget_category_section'); ?>								    
								</div>

                                    <div id="shopbop-widget-apperancebox" class="postbox">
                                        <h3>Affiliate Information</h3>
                                        <table class="form-table">
                                            <tbody>
                                            <tr valign="top">
                                                <td colspan="2">
                                                    <span class="description">We cannot guarantee the tracking of any payments from any affiliate program (including Skimlinks and Commission Junction), and we recommend the regular checking of payments from the associated companies to confirm that expected payments are being tracked and received.<br><br>You can find out more information about Skimlinks and Commission Junction <a href="http://www.shopbop.com/go/widgets" target="_blank">here</a>. If your site is affiliated to Commission Junction then you can enter your Commission Junction Publisher ID below.</span>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>

                                        <?php do_settings_sections('core_widget_affiliate_information_section'); ?>
                                    </div>

                                <div id="shopbop-widget-categoriesbox" class="postbox" >
                                    <h3>Content Update Status</h3>
                                    <table class="form-table">
                                        <tbody>
                                        <tr valign="top">
                                            <th scope="row">Oldest Entry to be Updated:</th>
                                            <td>
                                                <?php if(is_null($oldestEntryTimestamp)): ?>
                                                    <p>Nothing to update.</p>
                                                <?php else: ?>
                                                    <p><?php echo date("Y-m-d H:i:s", $oldestEntryTimestamp); ?></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td colspan="2">
                                                <span class="description">Note: Updates of the Shopbop content requires wp-cron to successfully run. If you find that during subsequent visits to this page that the above oldest entry remains at the same age (and there has been at least an hour between these visits), then there may be an issue with your wp-cron process that requires your attention.</span>
                                            </td>
                                        </tr>
                                        <?php if($canCronCreateALockFile === false): ?>
                                            <tr valign="top">
                                                <td colspan="2">
                                                    <span class="error"><b>Warning</b>: We have been unable to write to the temporary file (<?php echo $cronLockFilePath; ?>). This could potentially cause overlapping wp-cron processes on your system. If possible, please investigate and fix this issue.</span>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
	        					<?php endif; ?>

                        <p class="install-help shopbop-widget-note"><strong>Please note:</strong> that the Shopbop widgets will attempt to periodically update the Shopbop content being displayed. However the use of Wordpress caching solutions can interrupt and stop these updates and in such cases regular flushing or disabling of the cache can allow the content to be updated. </p>

				    <p class="submit">
						<input name="submit" type="submit" class="button-primary" value="Save Changes" />
					</p>
					<p><small>v<?php echo constant($widgetPrefix.'WIDGET_VERSION');?></small></p>
			</form>
		</div>

<?php endif; ?>