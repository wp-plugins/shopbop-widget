<?php
/**
 * A class that is used to make calls to API webservice.
 *
 * @package Stickywidget
 *
 * @author  widget <widget@stickyeyes.com>
 */
class CoreWebservice
{

    /**
     * Widget prefix string for constants.
     *
     * @var string $widgetPrefix
     */

    public static $widgetPrefix = 'SHOPBOP_';

    /**
     * If true, cache updates will use the internal updater.
     *
     * @var boolean
     */
    private $_updater;

    /**
     * Last status code from API.
     *
     * @var integer
     */
    private $_lastStatusCode;

    /**
     * Is the internal updater running this?
     *
     * @var boolean
     */
    private static $_internalUpdaterRunning = false;

    /**
     * Constructor.
     *
     * @param CoreWidgetUpdate $updater internal updater instance
     *
     * @return void
     */
    public function __construct(CoreWidgetUpdate $updater = null)
    {
        if($updater instanceof CoreWidgetUpdate)
            $this->_updater = $updater;
    }

    /**
     * Let the class know that the internal updater is running.
     *
     * @param boolean $isRunning is the internal updater running or not
     *
     * @return void
     */
    public function setInternalUpdaterRunning($isRunning)
    {
        self::$_internalUpdaterRunning = (bool)$isRunning;
    }

    /**
     * If the internal updater class was passed, use it.
     *
     * @return boolean
     */
    private function _useInternalUpdater()
    {
        return ($this->_updater instanceof CoreWidgetUpdate);
    }

    /**
     * Get the current language.
     *
     * @return string
     */
    private function _getLanguage()
    {
        $core = new CoreWidgetBase(self::$widgetPrefix);
        return $core->getLanguage();
    }

    /**
     * This function used to prepare and make a HMAC request.
     *
     * @param string $url          URL to make a request.
     * @param string $method       Methods like GET, POST.
     * @param string $lastUpDate   last updated date.
     * @param string $optionalParm Optional parameter.
     *
     * @return array
     */
    public function prepHmacRequest($url = null, $method = null, $lastUpDate = null, $optionalParm = null)
    {

        $widgetpluginOptions = get_option(constant(self::$widgetPrefix. 'WIDGET_PLUGIN_WP_OPTIONS_NAME'));
        $widgetWsOptions     = get_option(constant(self::$widgetPrefix. 'WIDGET_WS_WP_OPTIONS_NAME'));
        $throttleTimeStart   = (int)get_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_THROTTLE_TIME_START'));

        if(((int)gmdate('U') - $throttleTimeStart) <= (int)constant(self::$widgetPrefix.'WIDGET_WS_WP_THROTTLE_TIME'))
            return false;

        $key                = $widgetWsOptions['widgetId'];
        $username           = $widgetpluginOptions['widget_user_email'];
        $date               = new DateTime(null, new DateTimeZone('Etc/UTC'));
        $date               = sha1($date->format('Y-m-d H:i'));
        $safeUrl            = strpos($url, '?') !== false ? substr($url, 0, strpos($url, '?')) : $url;
        $digestStr          = hash_hmac('sha1', "$method\n$date\n$safeUrl", $key);
        $url                = (constant(self::$widgetPrefix.'WIDGET_WS_URL')) . $url;
        $digestParams       = array(
                               'username' => $username,
                               'domain'   => $this->getDomain(),
                               'nonce'    => $digestStr,
                              );
        $digestParamsString = "";

        foreach($digestParams as $key => $value)
        {
            if($digestParamsString != "")
                $digestParamsString .= ", ";

            $digestParamsString .= $key . '="' . $value . '"';
        }

        $args            = array('timeout' => 10);
        $args['headers'] = array(
                            'method'        => "$method",
                            'Authorization' => "HMACDigest $digestParamsString",
                            'Content-Type'  => 'application/json',
                            'Accept'        => 'application/json',
                           );

        if(!is_null($lastUpDate) && $lastUpDate != '')
            $args['headers']['If-Modified-Since'] = "$lastUpDate";

        if(self::$_internalUpdaterRunning === true)
            $args['headers']['X-Internal-Updater'] = "1";

        $args['body'] = "";

        if(!is_null($optionalParm))
        {
            $url .= $optionalParm;
        }

        switch($method)
        {
            case 'DELETE':
                $response = wp_remote_request($url, $args);
                break;

            case 'GET':
                $response = wp_remote_get($url, $args);
                break;

            case 'POST':
                $response = wp_remote_post($url, $args);
                break;

            default:
                // nada
                break;
        }

        $this->_lastStatusCode = wp_remote_retrieve_response_code($response);

        if(!is_null($response) && wp_remote_retrieve_response_code($response) == 503)
            update_option(constant(self::$widgetPrefix.'WIDGET_WS_WP_THROTTLE_TIME_START'), (int)gmdate('U'));

        if(is_wp_error($response))
        {
        	_shopbop_widget_log($url, $args, $response);
        }

        return $response;
    }

    /**
     * Get last status code from API.
     *
     * @return integer
     */
    public function getLastStatusCode()
    {
        return $this->_lastStatusCode;
    }

    /**
     * Grabs the API url for sending requests.
     *
     * @return string
     */
    public function getAPIUrl()
    {
        return (constant(self::$widgetPrefix.'WIDGET_WS_URL')). '/clients/' . strtolower(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME')) . '/';
    }

    /**
     * This function is used to register the widget.
     *
     * @param string $email the email address of the blog owner.
     * @param string $host  the hostname of the blog.
     *
     * @return string|false
     */
    public function widgetRequestToken($email = null, $host = null)
    {
        $url            = (constant(self::$widgetPrefix.'WIDGET_WS_URL')). '/clients/' . strtolower(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME')) . '/request-token';
        $xmlRpcEndpoint = get_option('siteurl').'/xmlrpc.php';

        $request = json_encode(
            array(
             'email'          => $email,
             'domain'         => $host,
             'xmlRpcEndpoint' => $xmlRpcEndpoint,
             'widgetVersion'  => constant(self::$widgetPrefix.'WIDGET_VERSION'),
            )
        );

        $args            = array();
        $args['headers'] = array(
                            'Content-Type' => 'application/json',
                            'Accept'       => 'application/xhtml+xml',
                           );
        $args['body']    = $request;
        $response        = wp_remote_post($url, $args);

        if(wp_remote_retrieve_response_code($response) == 200)
        {
            add_settings_error(
                'Stickywidget_user_email',
                'Stickywidget_user_email_error',
                'An activation email has been sent successfully. Please check your mailbox and click on the activation link.',
                'updated'
            );

            return wp_remote_retrieve_body($response);
        }
        elseif(wp_remote_retrieve_response_code($response) == 409)
        {

            add_settings_error(
                'Stickywidget_user_email',
                'Stickywidget_user_email_error',
                'This domain and email is already registered with us.',
                'error'
            );

            return false;
        }
        else
        {
            _shopbop_widget_log("Unexpected response: " . wp_remote_retrieve_response_code($response) . " ". var_export($response, true));
            add_settings_error(
                'Stickywidget_user_email',
                'Stickywidget_user_email_error',
                'The server is a little busy right now. Please try again later.',
                'error'
            );
        }
    }

    /**
     * This function is used to request a reset of the widgets user account.
     *
     * @param string $email the email address of the blog owner
     * @param string $host  the hostname of the blog
     *
     * @return boolean
     */
    public function widgetRequestReset($email = null, $host = null)
    {
        $url            = (constant(self::$widgetPrefix.'WIDGET_WS_URL')). '/clients/' . strtolower(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME')) . '/request-reset';
        $xmlRpcEndpoint = get_option('siteurl').'/xmlrpc.php';

        $request = json_encode(
            array(
             'email'          => $email,
             'domain'         => $host,
             'xmlRpcEndpoint' => $xmlRpcEndpoint,
            )
        );

        $args            = array();
        $args['headers'] = array(
                            'Content-Type' => 'application/json',
                            'Accept'       => 'text/html',
                           );
        $args['body']    = $request;
        $response        = wp_remote_post($url, $args);

        if(wp_remote_retrieve_response_code($response) == 200)
        {
            add_settings_error(
                'Stickywidget_user_email',
                'Stickywidget_user_email_error',
                'An email has been sent to your account. Please check your mailbox and click on the reset link to enable access using this email address.',
                'updated'
            );

            return true;
        }
        elseif(wp_remote_retrieve_response_code($response) == 409)
        {

            add_settings_error(
                'Stickywidget_user_email',
                'Stickywidget_user_email_error',
                'Email could not be sent.',
                'error'
            );

            return false;
        }
        else
        {
            _shopbop_widget_log("Unexpected response: " . wp_remote_retrieve_response_code($response) . " ". var_export($response, true));
            add_settings_error(
                'Stickywidget_user_email',
                'Stickywidget_user_email_error',
                'The server is a little busy right now. Please try again later.',
                'error'
            );
            return false;
        }
    }

    /**
     * This function pull the data from the web service after successfull registration.
     *
     * @return void
     */

    public function postRegistration()
    {
    	$cats = new CoreCategories();
    	$cats->getCategoriesFromCache();
    }

    /**
	 * This function calls the api to fetch the pane content.
	 *
	 * @param integer $postId  post ID of the page
	 * @param string  $path    local path
	 * @param mixed   $queries queries
     * @param string  $lang    ISO lang code (default en-us)
	 *
	 * @return array
	 */
	public function getPaneContent($postId, $paneId, $path, $queries = null, $lang = null)
	{
        if(is_null($lang))
            $lang = $this->_getLanguage();

		$response = array();
		$queries  = (is_null($queries)) ? array() : $queries;

		switch($paneId)
		{
			case "pane1":
			    $response = $this->getItemFromAnywhereWithCacheUpdate(
                    $postId,
    	            $path,
    	            'panes/latest-products/content/' . $lang . '/' . $this->formatUrl($path),
    	            'pane1'
			    );
				break;

			case "pane2":
				$url = 'panes/latest-products-by-category/content/' . $lang . '/' . $this->formatUrl($path) . '?' . http_build_query($queries);
			    $response = $this->getItemFromAnywhereWithCacheUpdate(
                    $postId,
    	            $path,
		            $url,
		            'pane2'
			    );
				break;

			default:
			    // nada
			    break;
		}

		if(is_null($response))
		    return array();

		if(is_array($response) && array_key_exists('data', $response))
		{
		    if(is_array($response['data']))
		        return $response['data'];
		    else
		        return array();
		}

		if(!is_array($response))
			_shopbop_widget_log($paneId, $response);

		return $response;
	}

    /**
     * Grab the most appropriate marketing message for a URL.
     *
     * @param integer $postId post ID of the page
     * @param string  $path   The request url and path
     * @param string  $lang   ISO lang code (default en-us)
     *
     * @return string
     */
    public function getMarketingMessage($postId, $path, $lang = null)
    {
        if(is_null($lang))
            $lang = $this->_getLanguage();

        return $this->getItemFromAnywhereWithCacheUpdate(
            $postId,
            $path,
            'global-marketing-messages/' . $lang . '/' . $this->formatUrl($path),
            'marketing',
            'text'
        );
    }

    /**
     * Grab the most appropriate promotion content for a URL.
     *
     * @param integer $postId post ID of the page
     * @param string  $path   The request url and path
     * @param string  $lang   ISO lang code (default en-us)
     *
     * @return string
     */
    public function getPromotion($postId, $path, $lang = null)
    {
        if(is_null($lang))
            $lang = $this->_getLanguage();

        return $this->getItemFromAnywhereWithCacheUpdate(
            $postId,
            $path,
            'global-promotions/' . $lang . '/' . $this->formatUrl($path),
            'promotion',
            'text'
        );
    }

    /**
     * Generic get item function. Returns either cached item or API response
     * depending on dates/availability etc.
     *
     * @param string $path      The request url and path
     * @param string $url       Relative url from /clients/CLIENTNAME/ to get
     * @param string $type      type of item
     * @param string $returnKey return key in JSON response
     *
     * @return mixed
     */
    public function getItemFromAnywhereWithCacheUpdate($postId, $path, $url, $type, $returnKey = null)
    {
        $time       = (int)gmdate('U');
        $path       = $this->formatUrl($path);
        $cachedItem = $this->getItemFromCache($path, $type);
        $localDate  = is_null($cachedItem) ? null : $cachedItem['date'];
        $lastUpdate = is_null($localDate) ? null : $cachedItem['last_update'];

        if(!is_null($lastUpdate) && strtotime($lastUpdate) > ($time - (int)constant(self::$widgetPrefix.'WIDGET_WS_WP_CACHE_TIMEOUT')))
            return is_null($returnKey) ? $cachedItem : $cachedItem['data'][$returnKey];

        // Use internal updater if possible
        if($this->_useInternalUpdater() && !is_null($cachedItem))
        {
            $updater = $this->_updater;
            $updater->updateUpdateRequestedTime($path, $type);
            return is_null($returnKey) ? $cachedItem['data'] : $cachedItem['data'][$returnKey];
        }

        $apiItem = $this->getItemFromApi(
            '/clients/' . strtolower(constant(self::$widgetPrefix . 'PUBLIC_WIDGET_NAME')) . '/' . trim($url, '/'),
            $localDate
        );

        if(is_null($cachedItem) && ($apiItem === false || is_null($apiItem)))
        {
        	_shopbop_widget_log("Nothing from Cache and nothing from WEB-API");
        	return null;
        }

        if($apiItem === false && !is_null($cachedItem))
        {
            $this->updateCacheLastCheckTime($postId, $path, $type, ($time - ((int)constant(self::$widgetPrefix.'WIDGET_WS_WP_CACHE_TIMEOUT') - 3600)));
            return is_null($returnKey) ? $cachedItem['data'] : $cachedItem['data'][$returnKey];
        }
        else if(is_null($apiItem) && !is_null($cachedItem))
        {
            $this->updateCacheLastCheckTime($postId, $path, $type);
            return is_null($returnKey) ? $cachedItem['data'] : $cachedItem['data'][$returnKey];
        }

        $this->saveItemToCache(
            $postId,
            $path,
            $apiItem['body'],
            $type,
            $apiItem['date']
        );

        return is_null($returnKey) ? $apiItem['body'] : $apiItem['body'][$returnKey];
    }

    /**
     * Formats a URL for an API request.
     *
     * @param string $path URL to format.
     *
     * @return string
     */
    public function formatUrl($path)
    {
		$path = str_replace("http://", "", $path);
		$path = str_replace("https://", "", $path);

		if(strpos($path, '?') !== false)
		    $res = substr($path, 0, strpos($path, '?'));
	    else
		    $res = $path;

	    $homepath = get_home_url();
		$homepath = str_replace("http://", "", $homepath);
		$homepath = str_replace("https://", "", $homepath);
		$homepath = str_replace('/', '', $homepath);

		if($homepath == '')
		    $homepath = 'zzzzzzzzzzz.com';

	    if(strlen($path) == 0)
	        $res = (array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : $homepath) . '/';

	    if(substr($path, 0, 1) == '/')
	        $res = (array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : $homepath) . $path;

		return $res;
    }

    /**
     * Grab an item from the API (GET) sending the locally stored date as the If-Modified-Since header.
     * Returns array(body, date) if there's an update, null otherwise.
     * false means invalid API response.
     * null means not modified.
     * array is a valid reseponse.
     *
     * @param string $url       API url to request
     * @param string $localDate locally stored date for If-Modified-Since (Y-m-d H:i:s)
     *
     * @return array|null|false
     */
    public function getItemFromApi($url, $localDate = null)
    {
        $response = $this->prepHmacRequest($url, 'GET', $localDate);

        if(is_wp_error($response))
        	return null;

        $apiResponse = $response;

        if(wp_remote_retrieve_response_code($apiResponse) == 200)
        {
            $body = json_decode(wp_remote_retrieve_body($apiResponse), true);

            if(!is_array($body))
                return false;

            return array(
                    'body' => $body,
                    'date' => wp_remote_retrieve_header($apiResponse, 'last-modified'),
                   );
        }

        if(wp_remote_retrieve_response_code($apiResponse) == 304)
            return null;

        return false;
    }

    /**
     * Grabs a single item from the cache.
     *
     * @param string $path URI of page.
     * @param string $type type of item.
     *
     * @return array|null
     */

    public function getItemFromCache($path, $type)
    {
        global $wpdb;

        $sql   = 'SELECT * FROM `' . $wpdb->prefix . 'shopbop_cache` WHERE `type` = "%s" AND `path` = "%s"';
        $query = $wpdb->prepare($sql, array($type, $path));
        $ret   = $wpdb->get_row($query, ARRAY_A);

        if(is_null($ret))
            return null;

        if(array_key_exists('data', $ret))
        {
            $ret['data'] = base64_decode($ret['data']);

            if(function_exists("gzuncompress"))
                $ret['data'] = @gzuncompress($ret['data']);

            if($ret['data'] !== false)
                $ret['data'] = @unserialize($ret['data']);
        }

        if($ret['data'] === false)
        {
            $this->deleteCache($path, $type);
            return null;
        }

        if(is_null($ret['data']))
        	return null;

        return $ret;
    }

    /**
     * Saves any item to the cache table.
     *
     * @param integer $postId       post ID of the page
     * @param string  $path         URI of page
     * @param mixed   $data         data to save to column
     * @param string  $type         type of item
     * @param string  $responseDate Date: header from server D, d M Y H:i:s \G\M\T
     *
     * @return void
     */
    public function saveItemToCache($postId, $path, $data, $type, $responseDate)
    {
        global $wpdb;

        $data = serialize($data);

        if(function_exists("gzcompress"))
            $data = gzcompress($data, 1);

        $data  = base64_encode($data);
        $query = $wpdb->prepare(
            '
            INSERT INTO ' . $wpdb->prefix . 'shopbop_cache
            (`path`, `data`, `type`, `date`, `last_update`, `post_id`)
            VALUES
            (%s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
              `data` = "%s",
              `date` = "%s",
              `last_update` = "%s",
              `post_id` = "%s"
            ',
            array(
             $path,
             $data,
             $type,
             $responseDate,
             date('Y-m-d H:i:s', (int)gmdate('U')),
             $postId,
             $data,
             $responseDate,
             date('Y-m-d H:i:s', (int)gmdate('U')),
             $postId,
            )
        );

        $wpdb->query($query);
    }

    /**
     * Updates the cache table's last_update column.
     *
     * @param integer $postId post ID of the page
     * @param string  $path   URI of page.
     * @param string  $type   type of item
     *
     * @return void
     */
    public function updateCacheLastCheckTime($postId, $path, $type, $timestamp = null)
    {
        global $wpdb;

        if(is_null($timestamp))
            $timeString = date('Y-m-d H:i:s', (int)gmdate('U'));
        else
            $timeString = date('Y-m-d H:i:s', $timestamp);

        $query = $wpdb->prepare(
            '
            UPDATE ' . $wpdb->prefix . 'shopbop_cache
            SET
              last_update = "%s",
              post_id = "%s"
            WHERE
              path = "%s"
            AND
              type = "%s"
            ',
            array(
             $timeString,
             $postId,
             $path,
             $type,
            )
        );

        $wpdb->query($query);
    }

    /**
     * Deletes the cache table entry for a certain path and type.
     *
     * @param string $path URI of page.
     * @param string $type type of item
     *
     * @return void
     */
    public function deleteCache($path, $type)
    {
        global $wpdb;

        $path = $this->formatUrl($path);

        $query = $wpdb->prepare(
            '
            DELETE FROM ' . $wpdb->prefix . 'shopbop_cache
            WHERE
              path = "%s"
            AND
              type = "%s"
            ',
            array(
             $path,
             $type,
            )
        );

        $wpdb->query($query);
    }

    /**
     * Get domain of the blog.
     *
     * @return string
     */
    public function getDomain()
    {
        $url = get_option('siteurl', null);
        if(is_null($url))
        {
            if(array_key_exists('HTTP_HOST', $_SERVER))
            {
                $url = (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            }
        }
        $parseUrl = parse_url(trim($url));
        return trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));
    }

    /**
     * Clear the entire cache table
     *
     * @return void
     */
    public function clearCache()
    {
        global $wpdb;
        $query = $wpdb->prepare('TRUNCATE TABLE ' . $wpdb->prefix . 'shopbop_cache');
        $wpdb->query($query);
    }

    /**
     * This function deletes the widget from the API.
     *
     * @return boolean
     */
    public function deleteWidget()
    {
        $url      = '/clients/'.strtolower(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME')).'/request-token';
        $response = $this->prepHmacRequest($url, 'DELETE', null, '?X-Method=DELETE');

		if(is_wp_error($response)) {
			/* no-op */
		}

        return true;
    }
}