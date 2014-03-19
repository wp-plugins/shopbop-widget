<?php
if(!class_exists('CoreWidget'))
{
    /**
     * //action handler.
     *
     * @package CoreWidget
     *
     * @author  stickywidget <widgets@stickyeyes.com>
     */
    class CoreWidget
    {

    	/**
    	 * Widget prefix string for constants.
    	 *
    	 * @var string $widgetPrefix
    	 */
    	public static $widgetPrefix = 'SHOPBOP_';

    	/**
    	 * Instantation constructor.
         *
         * @return void
    	 */
    	function __construct()
    	{
    		// Add all action, filter and shortcode hooks
    		$this->_addHooks();
    	}

    	/**
    	 * Add all action, filter and shortcode hooks.
    	 *
    	 * @return void
    	 */
    	private function _addHooks()
    	{
    		/******************************************
    		 * 	 ADD ACTION HOOKS
    		 *****************************************/
    		//Adds admin notices which is used to shows information
    		// like update message, errors messages to alert user
    		add_action('admin_notices', array($this, 'adminNotice'));

    		add_action('admin_init', array($this, 'coreWidgetPluginRedirect'));

    		// register Foo_Widget widget
    		add_action('widgets_init', array($this, 'loadPublicWidget'));

    		//add required files to header.
    		add_action("template_redirect", array($this, 'coreWidgetStylesScripts'));

            //Runs on widget upgrade.
            add_filter('update_plugin_complete_actions',  array($this, 'coreWidgetUpdatePluginCompleteAction'));
            add_filter('update_bulk_plugins_complete_actions',  array($this, 'coreWidgetUpdateBulkPluginCompleteAction'));

    		add_action('mktMsgCacheScheduler', array($this, 'mktMsgCache'));

    		add_filter('http_request_timeout', array($this, 'coreFilterTimeoutTime'));

    		/******************************************
    		 * 	 ADD FILTER HOOKS
    		*****************************************/
    		add_filter('xmlrpc_methods', array($this, 'xmlrpcMethods'));
    	}

        /**
         * Run on Bulk widget upgrade
         *
         */
        public function coreWidgetUpdateBulkPluginCompleteAction()
        {
            //Get upgrade information. only do that when Shopbop widget update.PUBLIC_WIDGET_BASE_FILE_AND_SLUG_NAME
            $pluginUpdateInfo = $_REQUEST;
            if($pluginUpdateInfo['action'] == 'update-selected')
            {
                if($pluginUpdateInfo['plugins'] == constant(self::$widgetPrefix.'PUBLIC_WIDGET_BASE_FILE_AND_SLUG_NAME') || strpos($pluginUpdateInfo['plugins'],'shopbop-widget.php') !== false)
                {
                    $this->runPostUpgradeCode();
                }
            }
        }

        /**
         * Run on Post upgrade or post update
         */
        public function runPostUpgradeCode()
        {
            //eula option delete when upgrade to re-agree.
            delete_option(constant(self::$widgetPrefix.'WIDGET_PLUGIN_WP_EULA_AGREEMENT'));

            $widgetUpdate = new CoreWidgetUpdate();
            if(!$widgetUpdate->checkUpdateRequestIndexExist())
                self::loadUpdateQueries();
        }

        /**
         * Run code on widgt upgrade
         *
         */
        public function coreWidgetUpdatePluginCompleteAction()
        {
            //Get upgrade information. only do that when Shopbop widget update.PUBLIC_WIDGET_BASE_FILE_AND_SLUG_NAME
            $pluginUpdateInfo = $_REQUEST;
            if($pluginUpdateInfo['action'] == 'upgrade-plugin')
            {
                if($pluginUpdateInfo['plugin'] == constant(self::$widgetPrefix.'PUBLIC_WIDGET_BASE_FILE_AND_SLUG_NAME') || strpos($pluginUpdateInfo['plugin'],'shopbop-widget.php') !== false)
                {
                    $this->runPostUpgradeCode();
                }
            }
        }


    	/**
    	 * Http request time out.
    	 *
    	 * @param integer $time time in seconds
    	 *
    	 * @return number
    	 */
    	public function coreFilterTimeoutTime($time)
    	{
    		$time = 25; //new number of seconds
    		return $time;
    	}

    	/**
    	 * Cron job.
    	 *
    	 * @param mixed $schedules every min.
    	 *
    	 * @return array
    	 */
    	public function coreCronWidget($schedules)
    	{
    		//create a 'weekly' recurrence schedule
    		$schedules['every_minute'] = array(
    									  'interval' => 60,
    									  'display'  => 'Every Once Minute',
    							         );

    		return $schedules;
    	}

    	/**
    	 * Loads the scripts and styles in to the public widget.
    	 *
    	 * @return void
    	 */
    	public function coreWidgetStylesScripts()
    	{
    		//CSS links
    		wp_enqueue_style(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG') . '-customjs', constant(self::$widgetPrefix.'PLUGIN_DIR_URL') . 'css/public_widget.css?where=blog&modified=20140201');

    		//javascript librarires
            wp_enqueue_script('jquery', constant(self::$widgetPrefix.'PLUGIN_DIR_URL') . 'js/jquery-1.7.1.min.js');
    		wp_enqueue_script(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG') . '-carouselFred', constant(self::$widgetPrefix.'PLUGIN_DIR_URL') . 'js/lib/jquery.carouFredSel-6.1.0-packed.js?where=blog', array('jquery'));
    		wp_enqueue_script(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG') . '-customjs', constant(self::$widgetPrefix.'PLUGIN_DIR_URL') . 'js/public_widget.js?where=blog&modified=20140201');
    	}

    	/**
    	 * Adds admin notices which is used to shows information like update message, errors messages to alert user.
    	 *
    	 * @return void
    	 */
    	public function adminNotice()
    	{
            //Check for eula agreement if agreed or not.
            $EulaAgree = new CoreWebservice;

            if(!$EulaAgree->eulaCheck())
            {
                ?>
                <div class="updated">
                    <p><?php _e( 'To continue using the Shopbop Widget please read and agree the ' ); ?><a class="button" href="<?php echo admin_url('admin.php?page='.constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME').'-core-widget-options') ?>">END USER LICENSE AGREEMENT</a></p>
                </div>
                <?php
            }

    		$widgetWs = get_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));

    		if(!is_array($widgetWs) || (array_key_exists('registered', $widgetWs) && $widgetWs['registered'] == false))
    		{
    		    if(is_null($widgetWs['widgetId']))
    		    {
                    add_settings_error(
                        'Stickywidget_user_registration',
                        'Stickywidget_user_registration_awaiting_id',
                        'Thanks for installing the shopbop widget. Please enter your email address into the ShopBop widget settings and then click "Save Changes".',
                        'updated'
                    );
    		    }
    		    else
    		    {
                    add_settings_error(
                        'Stickywidget_user_registration',
                        'Stickywidget_user_registration_awaiting_email',
                        'Please check your email for an activation link.',
                        'updated'
                    );
    		    }
    		}
    		elseif(array_key_exists('resetRequested', $widgetWs) && $widgetWs['resetRequested'] == true)
    		{
                add_settings_error(
                    'Stickywidget_user_registration',
                    'Stickywidget_user_registration_awaiting_email_reset',
                    'Please check your email for a registration reset link.',
                    'updated'
                );
    		}
    	}

    	/**
    	 * CoreWidgetPluginRedirect.
    	 *
    	 * @return void
    	 */
    	public function coreWidgetPluginRedirect()
    	{
    		if(get_option(constant(self::$widgetPrefix.'ACTIVATE_PLUGIN_REDIRECT'), false))
    		{
    			delete_option(constant(self::$widgetPrefix.'ACTIVATE_PLUGIN_REDIRECT'));
    			wp_redirect(admin_url('admin.php?page='.constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME').'-core-widget-options'));
    		}
    	}

    	/**
    	 * Adds the xmlrpc function.
    	 *
    	 * @param array $methods list of the methods for xmlrpc.
    	 *
    	 * @return array $methods list of the methods for xmlrpc.
    	 */
    	public function xmlrpcMethods(array $methods = array())
    	{
    		$widgetName = strtolower(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME'));

    		$methods[$widgetName.".setAuthKey"] = array(
    											   $this, 'setAuthKey',
    											  );

            $methods[$widgetName.".resetRequested"] = array(
    											       $this, 'resetRequested',
    											      );

    		$methods[$widgetName.".flushAll"] = array(
    											 'CoreWidgetXmlRpc', 'flushAll',
    				 							);

    		$methods[$widgetName.".flushMarketingMessages"] = array(
    														   'CoreWidgetXmlRpc', 'flushMarketingMessages',
    														  );

    		$methods[$widgetName.".flushPane1"] = array(
    											   'CoreWidgetXmlRpc', 'flushPane1',
    											  );

    		$methods[$widgetName.".flushPane2"] = array(
    											   'CoreWidgetXmlRpc', 'flushPane2',
    											  );

    		$methods[$widgetName.".flushPromotion"] = array(
    												   'CoreWidgetXmlRpc', 'flushPromotion',
    												  );
    		return $methods;
    	}


    	/**
    	 * This takes the registration key and stores the key in to widget options.
    	 *
    	 * @param string $args registrition key and additional data.
    	 *
    	 * @return string
    	 */
    	public function setAuthKey($args = null)
    	{
    		if(isset($args) && is_string($args))
    		{
    			$widgetWsOptions = array(
    								'widgetId'       => $args,
    								'registered'     => true,
    			                    'resetRequested' => false,
    							   );

    			update_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'), $widgetWsOptions);

			    return get_admin_url();
    		}

    		return false;
    	}

    	/**
    	 * This checks to see if a reset has been requested.
    	 *
    	 * @return boolean
    	 */
    	public function resetRequested()
    	{
    	    $widgetWsOptions = get_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));

    	    if(!isset($widgetWsOptions['resetRequested']))
    	        return false;

    	    return $widgetWsOptions['resetRequested'];
    	}


    	/**
    	 * Wordpress widget activate function.
    	 *
    	 * @return void
    	 */
    	public function onActivate()
    	{
    		self::loadInstallTables();

    		$widgetPluginOptions = get_option(constant(self::$widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'), null);

    		if(!is_array($widgetPluginOptions))
    		    $widgetPluginOptions = array();

    		add_option(constant(self::$widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'), $widgetPluginOptions);
            update_option(constant(self::$widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'), $widgetPluginOptions);

    		$widgetWsOptions = get_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));

    		if($widgetWsOptions == false)
    		{
                add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'), array());
    		}
    		else if(!isset($widgetWsOptions['registered']) || $widgetWsOptions['registered'] != true)
    		{
    			add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'), null);

    			$widgetWsOptions = array(
    								'widgetId'   => null,
    								'registered' => false,
    							   );

    		    update_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'), $widgetWsOptions);
    		}

    		add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES'), null);
    		add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES_TIMESTAMP'), null);
            add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES_LAST_UPDATE'), null);
    		add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES_CACHE_TIMEOUT'), (int)constant(self::$widgetPrefix.'WIDGET_WS_WP_CACHE_TIMEOUT'));
            add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_LAST_FAIL'), null);
            add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_LAST_SUCCESS'), null);
            add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_REQUESTED_DATE'), null);
            add_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_THROTTLE_TIME_START'), null);
    		add_option(constant(self::$widgetPrefix.'ACTIVATE_PLUGIN_REDIRECT'), true);
    	}

    	/**
    	 * Wordpress widget deactivate function.
    	 *
    	 * @return void
    	 */
    	public function onDeactivate()
    	{
            update_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_LAST_FAIL'), null);
            update_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_LAST_SUCCESS'), null);
    	}


    	/**
    	 * Wordpress widget uninstall function.
    	 *
    	 * @return void
    	 */
    	public function onUninstall()
    	{
            $widgetWsOptions = get_option(constant(self::$widgetPrefix. 'WIDGET_WS_WP_OPTIONS_NAME'));
            $key             = $widgetWsOptions['widgetId'];

            if(!is_null($key))
            {
        		//Delete the widget (API call) if we have a key.
        		$webService = new CoreWebservice();
        		$webService->deleteWidget();
            }

    		//Uninstall the tables.
    		self::loadUninstallTables();

    		//Delete the options
    		delete_option(constant(self::$widgetPrefix.'WIDGET_PLUGIN_WP_EULA_AGREEMENT'));
    		delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));
    		delete_option(constant(self::$widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'));
    		delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES'));
    		delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES_TIMESTAMP'));
            delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES_LAST_UPDATE'));
    		delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_CATEGORIES_CACHE_TIMEOUT'));
            delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_LAST_FAIL'));
            delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_LAST_SUCCESS'));
            delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_INTERNAL_UPDATE_REQUESTED_DATE'));
            delete_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_THROTTLE_TIME_START'));
            delete_option(constant(self::$widgetPrefix.'WIDGET_PLUGIN_WP_ENABLE_GOOGLE_ANALYTICS'));
    	}



        /**
         * Adds the Widget tables to the WordPress Database.
         * Also applies a simple patch to add a missing column for
         * more recent versions of the Widget.
         *
         * @return void
         */
        public static function loadInstallTables()
        {
            global $wpdb;

            $queries = explode('|', file_get_contents(constant(self::$widgetPrefix.'PLUGIN_DIR_PATH') .'sql/tables.sql'));

            foreach($queries as $q) /* @var $q string */
            {
                $q = str_replace('%PREFIX%', $wpdb->prefix, $q);
                $wpdb->query($q);
            }

        }
        /**
         * Adds the Widget tables to the WordPress Database.
         * Also applies a simple patch to add a missing column for
         * more recent versions of the Widget.
         *
         * @return void
         */
        public static function loadUpdateQueries()
        {
            global $wpdb;

            $queries = explode('|', file_get_contents(constant(self::$widgetPrefix.'PLUGIN_DIR_PATH') .'sql/updateQueries.sql'));

            foreach($queries as $q) /* @var $q string */
            {
                $q = str_replace('%PREFIX%', $wpdb->prefix, $q);
                $wpdb->query($q);
            }

        }

        /**
    	 * Initilizes and loads the public widget.
    	 *
    	 * @return void
    	 */
    	public function loadPublicWidget()
    	{
    		$widgetWsOptions = get_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));
    		if(isset($widgetWsOptions['registered']) && $widgetWsOptions['registered'] == true)
    		{
    			register_widget('CoreWidgetPublic');
    		}
    	}

    	/**
    	 * Uninstall the tables.
    	 *
    	 * @return void
    	 */
    	public static function loadUninstallTables()
    	{
    		global $wpdb;
    		$sqldir     = constant(self::$widgetPrefix.'PLUGIN_DIR_PATH') . 'sql/uninstall.sql';
    		$installSql = file_get_contents($sqldir);
    		$queries    = explode('|', $installSql);

    		foreach($queries as $q)
    		{
    		    $q = str_replace('%PREFIX%', $wpdb->prefix, $q);
    			$wpdb->query($q);
    		}
    	}

    }
}

new CoreWidget();