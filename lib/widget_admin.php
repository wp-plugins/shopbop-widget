<?php
require_once("categories.php");

/**
 * A class that is used to prepare settings for the wordpress admin widget options.
 *
 * @package Stickywidget
 *
 * @author  widget <widget@stickyeyes.com>
 */
class CoreWidgetAdmin
{
    /**
     * This variable Holds the stickywidget option values.
     *
     * @var array
     */
    public $options;

    /**
     * Widget prefix string fro sonstants.
     *
     * @var string
     */

    public $widgetPrefix = 'SHOPBOP_';

    /**
     * Constructor function.
     *
     * @return void
     */
    public function __construct()
    {
    	add_action('admin_init', array($this, 'init'));
        add_action('admin_menu', array($this, 'widgetMenuPage'));
    }

    /**
     * This function initiates the initial settings for the wordpress admin options page.
     *
     * @return void
     */
    public function init()
    {
        $this->options = get_option(constant($this->widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'));
        $this->widgetRegisterSettingsAndFields();
    }

    /**
     * This function displays the stickywidget option menu on the wordpress admin page.
     *
     * @return void
     */
    public function widgetMenuPage()
    {
        $settings = add_menu_page(constant($this->widgetPrefix.'WIDGET_OPTIONS_PAGE_TITLE'), __(constant($this->widgetPrefix.'WIDGET_OPTIONS_MENU_TITLE'), 'corewidget'), 'administrator', constant($this->widgetPrefix.'WIDGET_OPTIONS_MENU_SLUG_TITLE'), array( $this, 'optionsPage'));

        // Add JS to the setting page
        add_action('load-'.$settings, array( $this, 'addSettingsScript' ));

        // Load categories is no categories have been cached
		$options = get_option(constant($this->widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));
		if(array_key_exists('widgetId', $options) && !is_null($options['widgetId']))
		{
        	$categories = get_option(constant($this->widgetPrefix. 'WIDGET_WS_WP_CATEGORIES'));
        	if($categories=="" || count($categories)==0)
        	{
	        	$coreCats = new CoreCategories();
    	    	$coreCats->setSelectedCategory(-1, -1);
        	}
    	}
    }

    /**
     *  Add JS to the plugin's settings page.
     *
     * @return void
     */
    public function addSettingsScript()
    {
    	global $wp_version;

    	wp_enqueue_style(constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG') . '-Admin-css', constant($this->widgetPrefix.'PLUGIN_DIR_URL') . 'css/admin_widget.css?where=settings');
    	if(version_compare($wp_version,"3.3","<"))
    	{
    	    wp_enqueue_script(constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG') . '-Admin-js', constant($this->widgetPrefix.'PLUGIN_DIR_URL') . 'js/admin_widget.js?where=settings');
    	    wp_enqueue_script('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js?where=settings');
    	}
    	else
    	{
    	    wp_enqueue_script(constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG') . '-Admin-js', constant($this->widgetPrefix.'PLUGIN_DIR_URL') . 'js/admin_widget.js?where=settings', array('jquery-ui-slider'));
    	}

    }

    /**
     * This function that displays the stickywidget admin page with its options.
     *
     * @return void
     */
    public function optionsPage()
    {
        if($this->_isRegistered())
        {
            $coreCats = new CoreCategories();
            $cachedCats = $coreCats->getCategoriesFromCache();
            if(!is_array($cachedCats) || count($cachedCats) == 0)
            {
                add_settings_error(
                                'Stickywidget_fetch_category_first_time',
                                'Stickywidget_fetch_category_first_time_error',
                                'We were unable to fetch the categories from the sever.',
                                'error'
                            );
            }
        }
        $adminView               = new CoreWidgetBase($this->widgetPrefix);
        $updater                 = new CoreWidgetUpdate();
        $oldestUpdateRequestTime = $updater->getOldestUpdateRequestTimestamp();
        $adminView->loadView('admin', array('oldestEntryTimestamp' => $oldestUpdateRequestTime));
    }

    /**
     * This function registers the settings and its fields in to the wordpress options list.
     *
     * @return void
     */
    public function widgetRegisterSettingsAndFields()
    {
        register_setting(constant($this->widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'), constant($this->widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'), array($this, 'widgetOptionsValidationCb'));
        // Add Appearence sections
        add_settings_section('core_widget_register_section', '', array($this, 'widgetRegisterSectionCb'), 'core_widget_register_section');
        add_settings_section('core_widget_apperance_section', '', array($this, 'widgetApperanceSectionCb'), 'core_widget_apperance_section');
        add_settings_section('core_widget_category_section', '', array($this, 'widgetCategorySectionCb'), 'core_widget_category_section');

        //Add fields in Appearence sections
	        add_settings_field(
	            constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG').
	        	'widget_user_email', 'User Email',
	        	array(
	        	 $this,
	        	 'widgetUserEmail',
	            ),
	        	'core_widget_register_section',
	        	'core_widget_register_section'
	        );


        	add_settings_field(
        		constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG').
        		'widget_theme', 'Theme',
        		array(
        		 $this,
        		 'widgetTheme',
        		),
        		'core_widget_apperance_section',
        		'core_widget_apperance_section'
        	);
        	add_settings_field(
        		constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG').
        		'widget_width', 'Width',
        		array(
        		 $this,
        		 'widgetWidth',
        		),
        		'core_widget_apperance_section',
        		'core_widget_apperance_section'
        	);

        	add_settings_field(
        	    constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG').
        		'widget_language', 'Language',
        		array(
        		 $this,
        		 'widgetLanguage',
        		),
        		'core_widget_apperance_section',
        		'core_widget_apperance_section'
        	);

	        add_settings_field(
	        	constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME_SLUG').
	        	'widget_default_category', '',
	        	array(
	        	 $this,
	        	 'widgetCustomCategory',
	        	),
	        	'core_widget_category_section',
	        	'core_widget_category_section'
	        );

    }

    /**
     * Widget Registration call back method.
     *
     * @return void
     */
    public function widgetRegisterSectionCb()
    {
    	//no-op
    }

    /**
     * Widget Appereance call back method.
     *
     * @return void
     */
    public function widgetApperanceSectionCb()
    {
    	//no-op
    }

    /**
     * Widget Appereance call back method.
     *
     * @return void
     */
    public function widgetCategorySectionCb()
    {
    	//no-op
    }

    /**
     * Return true if the widget has been registered or not.
     *
     * @return boolean
     */
    private function _isRegistered()
    {
        $widgetWsOptions = get_option(constant($this->widgetPrefix . 'WIDGET_WS_WP_OPTIONS_NAME'));
        return !(!array_key_exists('registered', $widgetWsOptions) || $widgetWsOptions['registered'] === false);
    }

    /**
     * This function is used to validate the entries given in the wordpress admin sticckywidget options.
     *
     * @param mixed $input is used to pass the options submited on the sitckywidget widget.
     *
     * @return array
     */
    public function widgetOptionsValidationCb($input = null)
    {

    	$validInput      = array();
    	$ws              = new CoreWebservice();
    	$widgetWsOptions = get_option(constant($this->widgetPrefix . 'WIDGET_WS_WP_OPTIONS_NAME'));
        $currentOptions  = get_option(constant($this->widgetPrefix . 'WIDGET_PLUGIN_WP_OPTIONS_NAME'));

    	if(!array_key_exists('registered', $widgetWsOptions) || $widgetWsOptions['registered'] === false || !array_key_exists('widgetId', $widgetWsOptions) || is_null($widgetWsOptions['widgetId']))
    	{
    	    $ws    = new CoreWebservice();
    	    $url   = get_option('siteurl', null);
    	    if(is_null($url))
    	    {
    	        if(array_key_exists('HTTP_HOST', $_SERVER))
    	        {
    	            $url = (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    	        }
    	    }

    	    $token                             = $ws->widgetRequestToken($input['widget_user_email'], $this->getHost($url));
    	    $widgetWsOptions['resetRequested'] = false;

    	    if($token === false)
    	    {
    	        $resetEmailResult = $ws->widgetRequestReset($input['widget_user_email'], $this->getHost($url));

    	        if($resetEmailResult === true)
    	            $widgetWsOptions['resetRequested'] = true;
    	    }
    	    else
    	    {
    	        $widgetWsOptions['widgetId']   = $token;
	    	    $widgetWsOptions['registered'] = false;
    	    }

			update_option(constant($this->widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'), $widgetWsOptions);

			$validInput['widget_user_email'] = $input['widget_user_email'];
    		$validInput['widget_width_type'] = constant($this->widgetPrefix.'WIDGET_DEFAULT_WIDTH_TYPE');
    		$validInput['widget_width']      = constant($this->widgetPrefix.'WIDGET_DEFAULT_WIDTH');
    		$validInput['widget_theme']      = constant($this->widgetPrefix.'WIDGET_DEFAULT_THEME');
    		$validInput['widget_language']   = constant($this->widgetPrefix.'WIDGET_DEFAULT_LANGUAGE');

			return $validInput;
	    }

    	$widgetOptions = $_POST;

        if(array_key_exists('width-type', $widgetOptions))
    	    $validInput['widget_width_type'] = $widgetOptions['width-type'];

    	if(array_key_exists('widget_width_type', $input) && $input['widget_width_type'] == 'fluid')
    	{
    		$validInput['widget_width'] = null;
    	}
    	elseif(array_key_exists('widget_width', $input) && ($input['widget_width'] == 'fixed' || $input['widget_width']== null))
    	{
    		$validInput['widget_width'] = constant(self::$widgetPrefix.'WIDGET_DEFAULT_WIDTH');
    	}
    	elseif(array_key_exists('widget_width', $input))
    	{
    		$validInput['widget_width'] = $input['widget_width'];
    	}

        if(array_key_exists('widget_theme', $input))
    	    $validInput['widget_theme'] = $input['widget_theme'];

        if(array_key_exists('widget_language', $input))
    	    $validInput['widget_language'] = $input['widget_language'];


        if(array_key_exists('widget_default_category', $input))
        {
            $coreCats = new CoreCategories();
            $currentDefaultCategory = $coreCats->getDefaultCategoryPath(false, false);
            if($input['widget_default_category'] != $currentDefaultCategory)
            {
                $coreCats->setSelectedCategory(-1, $input['widget_default_category']);
                $postIds    = $coreCats->resetDeferingPosts($input['widget_default_category']); // Reset all posts that defer that useDefault
                $webService = new CoreWebservice();

                // Flush the cache for the homepage
                $webService->deleteCache(get_home_url(), 'pane1');
                $webService->deleteCache(get_home_url(), 'pane2');

                if(is_array($postIds) && count($postIds)>0) // Flush caches for defered category posts
                {
                    foreach($postIds as $id)
                    {
                        $webService->deleteCache(get_permalink($id), 'pane1');
                        $webService->deleteCache(get_permalink($id), 'pane2');
                    }
                }
            }
            $validInput['widget_default_category'] = $input['widget_default_category'];
        }
        if(array_key_exists('widget_language', $currentOptions) && array_key_exists('widget_language', $validInput) && $validInput['widget_language'] != $currentOptions['widget_language'])
        {
            $webService = new CoreWebservice();
            $webService->clearCache();
        }
        $validInput = array_merge($currentOptions, $validInput);
    	return $validInput;
    }

    /**
     * Array walker used to get the permalink from an array
     * of post objs.
     *
     * @param array|int $item Post array obj or post id
     *
     * @return void
     */
	private function getPermalink(&$item)
	{
		$item = get_permalink($item);
	}

    /**
     * Array walker used to strip the http scheme from an array or urls.
     *
     * @param array $item Array of urls
     *
     * @return void
     */
	private function stripScheme(&$path)
	{
		$path = str_replace("http://", "", $path);
		$path = str_replace("https://", "", $path);
	}

    /**
     * This is the callback function for the main section.
     *
     * @return void
     */
    public function widgetMainSectionCb()
    {

    }

    /**
     * This function is used to give the user email field to the wordpress admin page.
     *
     * @return void
     */
    public function widgetUserEmail()
    {
		$adminEmail = get_option('admin_email');

		if(isset($this->options['widget_user_email']) && !empty($this->options['widget_user_email']) )
			$adminEmail = $this->options['widget_user_email'];

		$widgetWsOptions = get_option(constant($this->widgetPrefix.'WIDGET_WS_WP_OPTIONS_NAME'));

        $params = array(
                   'locked'      => isset($widgetWsOptions['registered']) && $widgetWsOptions['registered'] == true,
                   'optionsName' => constant($this->widgetPrefix . 'WIDGET_PLUGIN_WP_OPTIONS_NAME'),
                   'adminEmail'  => $adminEmail,
                  );

        $widgetView = new CoreWidgetBase($this->widgetPrefix);
        $widgetView->loadView('admin/widget-user-email', $params);
    }

    /**
     * This function is used to specify the width of the widget on the wordpress admin page.
     *
     * @return void
     */
    public function widgetWidth()
    {
    	global $wp_version;

    	$type   = (array_key_exists('widget_width_type', $this->options)) ? $this->options['widget_width_type'] : constant($this->widgetPrefix . 'WIDGET_DEFAULT_WIDTH_TYPE');
    	$width  = (array_key_exists('widget_width', $this->options)) ? $this->options['widget_width'] : null;
        $params = array(
                   'fluid'       => $type,
                   'optionsName' => constant($this->widgetPrefix . 'WIDGET_PLUGIN_WP_OPTIONS_NAME'),
                   'widgetWidth' => $width,
        		   'wp_version'  => $wp_version,
                  );

        $widgetView = new CoreWidgetBase($this->widgetPrefix);
        $widgetView->loadView('admin/widget-width', $params);
    }

    /**
     * This function is used to specify the Theme color of the widget on the wordpress admin page.
     *
     * @return void
     */
    public function widgetTheme()
    {
    	$theme  = (array_key_exists('widget_theme', $this->options)) ? $this->options['widget_theme'] : null;
        $params = array(
                   'themes'      => array(
                                     'Light',
                                     'Dark',
                                    ),
                   'active'      => $theme,
                   'optionsName' => constant($this->widgetPrefix . 'WIDGET_PLUGIN_WP_OPTIONS_NAME'),
                  );

        $widgetView = new CoreWidgetBase($this->widgetPrefix);
        $widgetView->loadView('admin/widget-theme', $params);
    }

    /**
     * This function is used to select the category of the widget on the wordpress admin page.
     *
     * @return void
     */
    public function widgetCustomCategory()
    {
    	$coreCats = new CoreCategories();
    	$result   = $coreCats->getCategorySelectOptions( -1 , false, true );
    	if(is_wp_error($result))
    	{
    		echo "<p>Could not get a list of categories</p>";
    		return;
    	}

    	echo $coreCats->renderSelect('ShopbopWidgetPluginOptions[widget_default_category]', $result);
    	return;
 	}

    /**
     * This function iterates through the the array and prepares the categories options.
     *
     * @param array   $category an array of categories.
     * @param integer $indent 	indents the space.
     * @param string  $parent   The parent category branch.
     * @param mixed   $catPath  path to category
     *
     * @return string
     */
    public function categoryRecurse($category, $indent, $parent = null, $catPath = null)
    {
        $ret = '';

    	foreach($category as $key => $val)
      	{
      		$currentPosition = (is_null($parent)) ? $key : $parent . "|||" . $key;

      		if($catPath == $currentPosition)
            	$ret .= "<option selected=selected";
            else
            	$ret .= "<option";

            $ret .= " value=\"$currentPosition\"><b>".str_repeat("&nbsp&nbsp", $indent).'&#746 ' . $key."</option>";

    		if(is_array($val))
    			$ret .= $this->categoryRecurse($val, $indent+1, $currentPosition, $catPath);
    	}

    	return $ret;
    }

    /**
     * Given a category tree and a category ID returns the category path
     * e.g. CLOTHES/TOPS/TSHIRTS.
     *
     * @param stdClass $category Category tree
     * @param integer  $id       The category ID
     * @param string   &$catPath The category tree string
     *
     * @return boolean
     */
    public function getCatPath($category, $id, &$catPath)
    {
    	foreach($category as $val)
    	{
    		if($val->term_id == $id)
    		{
    			$catPath = ($catPath == "") ? $val->name : $catPath . '|||' . $val->name;
    			$catPath = strtoupper($catPath);
    			return true;
    		}

    		if(isset($val->children))
    		{
    			$res = $this->getCatPath($val->children, $id, $catPath);
    			if($res == true)
    			{
    				$catPath = strtoupper($val->name . '|||' . $catPath);
    				return true;
    			}
    		}
    	}
    }

    /**
     * This function is used to select the language of widget on the wordpress admin page.
     *
     * @return void
     */
    public function widgetLanguage()
    {
    	$language = (array_key_exists('widget_language', $this->options)) ? $this->options['widget_language'] : null;
    	$params   = array(
                     'optionsName'    => constant($this->widgetPrefix . 'WIDGET_PLUGIN_WP_OPTIONS_NAME'),
                     'languages'      => array(
                                          "English (US)"      => "en-us",
                                          "French (Français)" => "fr-fr",
                                          "German (Deutsch)"  => "de-de",
                                          "Chinese (中文)"     => "zh-cn",
                                          "Japanese (日本語)"  => "ja-jp",
                                          "Russian (русский)" => "ru-ru",
                                          "Spanish (Español)" => "es-es",
                                          "Swedish (Svenska)" => "sv-se",
                                          "Danish (Dansk)"    => "da-dk",
                                          "Norwegian (Norsk)" => "no-no",
                                         ),
    	             'widgetLanguage' => $language,
                    );

        $widgetView = new CoreWidgetBase($this->widgetPrefix);
        $widgetView->loadView('admin/widget-language', $params);
    }

    /**
     * This function just returns the host name form the given url.
     *
     * @param string $siteUrl this is the blog url.
     *
     * @return string
     */
    function getHost($siteUrl)
    {
    	$parseUrl = parse_url(trim($siteUrl));
    	return trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));
    }
}

new CoreWidgetAdmin();