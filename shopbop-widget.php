<?php
/*

Plugin Name: Shopbop
Plugin URI: http://www.shopbop.com/go/widgets
Description: This plugin allows you to add the official Shopbop widget to the sidebar of your Wordpress blog.
Version: 2.0
Author: Stickyeyes
Author URI: http://www.stickyeyes.com/
License: GPL2

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

$widgetPrefix = 'SHOPBOP_';

//Widget relative path.
if(!defined($widgetPrefix .'PLUGIN_DIR_PATH'))
	define($widgetPrefix .'PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

//Widget absolute path.
if(!defined($widgetPrefix .'PLUGIN_DIR_URL'))
	define($widgetPrefix .'PLUGIN_DIR_URL', plugin_dir_url(__FILE__));




require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/constants.php';
require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/core.php';
require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/update.php';
require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/categories.php';
require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/action_handlers.php';
require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/widget_public.php';
require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/webservice.php';
require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/xmlrpc.php';

load_plugin_textdomain('corewidget', false, 'corewidget/languages');


if(is_admin())
{
	require_once constant($widgetPrefix . 'PLUGIN_DIR_PATH') . 'lib/widget_admin.php';
}

function core_plugin_update_info() {
        echo '<br />Before upgrading, please read the upgrade notes here <a href="http://wordpress.org/plugins/shopbop-widget/upgrade/" target="_blank">http://wordpress.org/plugins/shopbop-widget/upgrade/</a>';
}
add_action('in_plugin_update_message-'.constant($widgetPrefix . 'PUBLIC_WIDGET_BASE_FILE_AND_SLUG_NAME'), 'core_plugin_update_info');


/**
 * Improved error logging.
 *
 * @return void
 */
function _shopbop_widget_log()
{
	$msg = "";
	foreach(func_get_args() as $i)
	{
		$msg .= var_export($i, true)."\n";
	}
	error_log($msg);
}

/**
 * Widget activation and deactivation hook registration.
 */
register_activation_hook(__FILE__, array('CoreWidget', 'onActivate'));
register_deactivation_hook(__FILE__, array('CoreWidget', 'onDeactivate'));
register_uninstall_hook(__FILE__, array('CoreWidget', 'onUninstall'));

// Hooks for Cron Updater
register_deactivation_hook(__FILE__, array('CoreWidgetUpdate', 'deregisterScheduledEvent'));