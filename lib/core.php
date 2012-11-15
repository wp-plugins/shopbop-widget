<?php
/**
 * This is the core widget base class.
 *
 * @package Corewidget
 *
 * @author  widget <widget@stickyeyes.com>
 */
class CoreWidgetBase
{
	/**
	 * Widget prefix string for constants.
	 *
	 * @var string
	 */
	public $widgetPrefix = '';

	/**
	 * Constructor.
	 *
	 * @param string $prefix widget prefix.
     *
     * @return void
	 */
	public function __construct($prefix = '')
	{
	    if(!is_null($prefix))
	        $this->widgetPrefix = $prefix;
	}

	/**
	 * Show a view.
	 *
	 * @param string $view the name of the view
	 * @param array  $data (optional) variables to pass to the view
	 *
	 * @return void
	 */
	public function loadView($view, array $data = array())
	{
		$view     = $view . '.php';
		$viewfile = constant($this->widgetPrefix . 'PLUGIN_DIR_PATH') . 'views/' . $view;

		if(!file_exists($viewfile) || !is_readable($viewfile))
		{
            echo "<p>Couldn't load view.</p>";
		    return;
		}

		foreach($data as $key => $value)
			${$key} = $value;

		$widgetPrefix = $this->widgetPrefix;

		include $viewfile;
	}

    /**
     * Get set widget language.
     *
     * @return string
     */
    public function getLanguage()
    {
        $options = get_option(constant($this->widgetPrefix . 'WIDGET_PLUGIN_WP_OPTIONS_NAME'));

        if(array_key_exists('widget_language', $options))
            return $options['widget_language'];
        else
            return constant($this->widgetPrefix.'WIDGET_DEFAULT_LANGUAGE');
    }
}