<?php $widgetPrefix = 'SHOPBOP_'; ?>
<div class="wrap metabox-holder">
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
                                                <span class="description">Note: Updates of the Shopbop content requires wp-cron to successfully run. If you find that during subsequent visits to this page that the above oldest entry remains at the same age, then there may be an issue with your wp-cron process that requires your attention.</span>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
	        					<?php endif; ?>

								<?php //endif;?>
                        <p class="install-help shopbop-widget-note"><strong>Please note:</strong> that the Shopbop widgets will attempt to periodically update the Shopbop content being displayed. However the use of Wordpress caching solutions can interrupt and stop these updates and in such cases regular flushing or disabling of the cache can allow the content to be updated. </p>
                        <p class="install-help shopbop-widget-note"><strong>Please also note:</strong> to display differing products on the widgets being displayed on your posts, please ensure that your blog is using a non-query string based URL structure e.g. blog.com/2012/10/postname/ rather than blog.com/?p=123.</p>
								
				    <p class="submit">
						<input name="submit" type="submit" class="button-primary" value="Save Changes" />
					</p>
					<p><small>v<?php echo constant($widgetPrefix.'WIDGET_VERSION');?></small></p>
			</form>
		</div>