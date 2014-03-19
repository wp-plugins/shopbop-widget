<?php
/**
 * A class that is used to display wordpress front end widget on the side bar.
 *
 * @package Corewidget
 *
 * @author  widget <widget@stickyeyes.com>
 */
class CoreWidgetPublic extends WP_Widget
{

	/**
	 * Widget prefix string fro sonstants.
	 *
	 * @var string
	 */
	public $widgetPrefix = 'SHOPBOP_';

	/**
	 * Consturctor to add the widget options.
     *
     * @return void
	 */
	public function __construct()
	{
		// widget actual processes
		$widgetOptions  = array(
						   "classname"   => __(constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME')),
						   "description" => __(constant($this->widgetPrefix.'PUBLIC_WIDGET_DESCRIPTION')),
						  );
		$controlOptions = array(
						   "width"   => constant($this->widgetPrefix.'WIDGET_DEFAULT_WIDTH'),
						   "height"  => "350",
						   "id_base" => constant($this->widgetPrefix.'PUBLIC_WIDGET_ID_BASE'),
						  );

		parent::__construct(constant($this->widgetPrefix.'PUBLIC_WIDGET_ID_BASE'), __(constant($this->widgetPrefix.'PUBLIC_WIDGET_NAME')), $widgetOptions, $controlOptions);


        $core         = new CoreWidgetBase($this->widgetPrefix);
        $languageFile = dirname(__FILE__)  . '/../languages/corewidget-' . $core->getLanguage() . ".mo";

        if(file_exists($languageFile))
            load_textdomain(constant($this->widgetPrefix . 'WIDGET_TRANSLATION'), $languageFile );
	}

	/**
	 * Displays the form on the widget area.
	 *
	 * @param string $instance values of each fields.
	 *
	 * @see WP_Widget::form()
	 *
	 * @return void
	 */
	public function form($instance)
	{

	}

	/**
	 * Handles updating widget.
	 *
	 * @param string $newInstance values of the current instant
	 * @param string $oldInstance values of the old instant
	 *
	 * @see WP_Widget::update()
	 *
	 * @return void
	 */
	public function update($newInstance, $oldInstance)
	{

	}

	/**
	 * Display the output of the widget.
	 *
	 * @param string $args 	   arguments
	 * @param string $instance values of each fields.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @return void
	 */
	public function widget($args, $instance)
	{
        $optionSettings = array();
		// outputs the content of the widget
		$title = apply_filters('Widget_title', $instance['title']);

		if($title)
			echo $title;

		//Set the widget display options here
		//Stickywidget width by default it is 200px or it can also be set in the plugin options.
		//$stickywidgetWidth = SHOPBOP_WIDGET_DEFAULT_WIDTH;
		//$stickywidgetTheme = "stickywidget-theme-".SHOPBOP_WIDGET_DEFAULT_WIDTH;

		$widgetOptionsArray = get_option(constant($this->widgetPrefix.'WIDGET_PLUGIN_WP_OPTIONS_NAME'));

		if(isset($widgetOptionsArray['widget_width_type']) && $widgetOptionsArray['widget_width_type'] == 'fluid')
		{
		    $widgetWidth = 'auto';
		}
		else
		{
    		if(isset($widgetOptionsArray['widget_width']) && $widgetOptionsArray['widget_width'] >=200 && $widgetOptionsArray['widget_width'] <=constant($this->widgetPrefix.'WIDGET_DEFAULT_MAX_WIDTH'))
    		{
    			$widgetWidth = $widgetOptionsArray['widget_width'] . 'px';
    		}
    		else
    		{
    			$widgetWidth = constant($this->widgetPrefix.'WIDGET_DEFAULT_MAX_WIDTH') . 'px';
    		}
		}


		//Stickywidget Theme by default it is light or it can also be set in the plugin options.
		if(isset($widgetOptionsArray['widget_theme']))
		{
			$widgetTheme = "shopbop-widget-theme-".$widgetOptionsArray['widget_theme'];
		}
		else
		{
			$widgetTheme = "shopbop-widget-theme-light";
		}

        //widget pane to open
        if(isset($widgetOptionsArray['widget_pane_to_open']))
        {
            $widgetPaneToOpen = $widgetOptionsArray['widget_pane_to_open'];
        }
        else
        {
            $widgetPaneToOpen = constant($this->widgetPrefix . 'WIDGET_DEFAULT_PANE_TO_OPEN');
        }
        if($widgetPaneToOpen == 'random')
        {
            $random = array('justarrived', 'shop', 'featured');
            $rand = (int)rand(0,9);
            $open =($rand < 6) ? 0 :(($rand < 9) ? 1 : 2);
            $widgetPaneToOpen = $random[$open];
        }
        $optionSettings['widgetPaneToOpen'] = $widgetPaneToOpen;

        //This is to check if the comission junction ID exist or not.
        if(isset($widgetOptionsArray['widget_affiliate_information']) && $widgetOptionsArray['widget_affiliate_information'] == 'cjAffiliateSelected')
        {
            $optionSettings['widget_affiliate_pid'] = isset($widgetOptionsArray['widget_affiliate_id'])? $widgetOptionsArray['widget_affiliate_id']: null;
            $optionSettings['widget_affiliate_information'] = isset($widgetOptionsArray['widget_affiliate_information'])? $widgetOptionsArray['widget_affiliate_information']: null;
        }
        else
        {
            $optionSettings['widget_affiliate_pid'] = null;
            $optionSettings['widget_affiliate_information'] = isset($widgetOptionsArray['widget_affiliate_information'])? $widgetOptionsArray['widget_affiliate_information']: null;
        }


		//Renders the HTML code for the widget.
		$this->widgetHTML($widgetWidth, $widgetTheme, $optionSettings);
	}

	/**
	 * This function is used to display the html for the widget.
	 *
	 * @param integer $width size of the width to display.
	 * @param string  $theme theme selection light / dark.
	 * @param string $widgetPaneToOpen widget pane to open.
     * @param array $optionSettings more widget options are passed throught as array.
	 * @return void
	 */
	public function widgetHTML($width, $theme, $optionSettings)
	{
        global $post;
        //Check for eula agreement if agreed or not.
        $coreWs = new CoreWebservice;

        if(!$coreWs->eulaCheck())
            return;

        wp_reset_query();
        $coreWebservice = new CoreWebservice(new CoreWidgetUpdate());
        $postId         = ((is_page() || is_single()) && isset($post->ID)) ? $post->ID : -1;
        $path           = (is_home() || is_search() || get_permalink($postId) === false) ? get_home_url() : get_permalink($postId);
		$mktMsg         = $coreWebservice->getMarketingMessage($postId, $path);
		$promotion      = $coreWebservice->getPromotion($postId, $path);
		$coreCategories = new CoreCategories();
        $coreCategories->getCategoriesFromCache();
		$catPath = $coreCategories->getCategoryPathForPost($postId, false, false);

		if(!is_null($catPath) && !is_wp_error($catPath))
		{
            $catPath      = explode('|||', $catPath);
            $pane2Title   = sprintf(__("SHOP %s", constant($this->widgetPrefix . 'WIDGET_TRANSLATION')), __($catPath[count($catPath)-1], constant($this->widgetPrefix . 'WIDGET_TRANSLATION')));
            $catQuery     = array("category" => str_replace('\u2215', '∕', json_encode($catPath)));
            $pane2Content = $coreWebservice->getPaneContent($postId, 'pane2', $path, $catQuery);
            if(is_array($pane2Content) && count($pane2Content) == 0)
            {
                $newCatPath   = $coreCategories->getDefaultCategoryPath();
                $catPath      = explode('|||', $newCatPath);
                $pane2Title   = sprintf(__("SHOP %s", constant($this->widgetPrefix . 'WIDGET_TRANSLATION')), __($catPath[count($catPath)-1], constant($this->widgetPrefix . 'WIDGET_TRANSLATION')));
                $catQuery     = array("category" => str_replace('\u2215', '∕', json_encode($catPath)));
                $pane2Content = $coreWebservice->getPaneContent($postId, 'pane2', $path, $catQuery);
                if(!(is_array($pane2Content) && count($pane2Content) == 0))
                    $coreCategories->setSelectedCategory($postId, $newCatPath);
            }
		}
		else
		{
			$pane2Title   = null;
			$pane2Content = array();
		}


        $params = array(
            'width'       => $width,
            'max-width'   => constant($this->widgetPrefix . 'WIDGET_DEFAULT_MAX_WIDTH'),
            'cjAid'       => constant($this->widgetPrefix . 'WIDGET_DEFAULT_AFFILIATE_CJ_ID'),
            'cjPid'       => empty($optionSettings['widget_affiliate_pid'])? null:trim($optionSettings['widget_affiliate_pid']),
            'affiliateOption'       => $optionSettings['widget_affiliate_information'],
            'theme'       => $theme,
            'pane1'       => $coreWebservice->getPaneContent($postId, 'pane1', $path),
            'pane2'       => $pane2Content,
            'pane2Title'  => $pane2Title,
            'mktMsg'      => trim($mktMsg),
            'promotion'   => trim($promotion),
            'postUrl'     => $coreWebservice->getAPIUrl(),
            'postId'      => $postId,
            'path'        => $path,
            'madeRequest' => (!is_null($coreWebservice->getLastStatusCode())),
            'paneToOpen'  => $optionSettings['widgetPaneToOpen'],
            'gaCodeStatus' => (bool)$coreWs->getGaCodeControl(),
        );

        $core = new CoreWidgetBase($this->widgetPrefix);
        $core->loadView('widget', $params);
	}
}