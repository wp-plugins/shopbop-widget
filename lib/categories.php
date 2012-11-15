<?php
require_once("webservice.php");

/**
 * Initialise meta field.
 *
 * @return CoreCategories
 */
function call_CoreCategories()
{
	$obj = new CoreCategories();
	$obj->init();
	return $obj;
}

if ( is_admin() )
	add_action( 'load-post.php', 'call_CoreCategories' );

/**
 * Categories.
 *
 * @package CoreWidget
 *
 * @author  stickywidget <widgets@stickyeyes.com>
 */
class CoreCategories
{

    /**
     * Widget prefix string for constants.
     *
     * @var string
     */
    public static $widgetPrefix = 'SHOPBOP_';

    /**
     * Select element name.
     *
     * @var string
     */
    private $_selectElementName = '';

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
    	$this->_selectElementName = strtolower(constant(self::$widgetPrefix. 'PUBLIC_WIDGET_NAME')).'_category';
    }

    /**
     * Init for edit post page in admin.
     *
     * @return void
     */
	public function init()
	{
		add_action( 'add_meta_boxes', array( &$this, 'add_some_meta_box' ) );
		add_action( 'save_post', array( &$this, 'savePostdata' ) );
	}

	/**
	 * Adds the meta box container
	 */
	public function add_some_meta_box()
	{
		add_meta_box(
			'some_meta_box_name',
			constant(self::$widgetPrefix. 'PUBLIC_WIDGET_NAME')." Category",
			array( &$this, 'render_meta_box_content' ),
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Render Meta Box content.
     *
     * @return void
	 */
	public function render_meta_box_content( $post_id )
	{
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'myplugin_noncename' );

		$result = $this->getCategorySelectOptions( $post_id->ID );
		if(is_wp_error($result))
		{
			echo "<p>Could not get a list of categories</p>";
			return;
		}

		echo $this->renderSelect($this->_selectElementName, $result);
	}

	/**
	 * Renders a select box.
	 *
	 * @param string $name    The form name of the select element
	 * @param array  $options An array of options for the select element
	 *
	 * @return string
	 */
	public function renderSelect($name, $options)
	{
		$output  = "<select name=\"{$name}\">";
		foreach($options as $key => $val) {
			$selected = ($val['selected']) ? " selected=\"selected\"" : "";
			$indent   = str_repeat("&nbsp;", $val['indent']*3);

			if(array_key_exists('label', $val))
				$label = $val['value'];
			else
				$label = ucwords(strtolower($val['value']));

			$output  .= "<option value=\"{$val['label']}\"{$selected}>{$indent}{$label}</option>";
		}
		$output .= "</select>";
		$output .= "<br><span class=\"description\">Please note that it may take several minutes for changes to be reflected across all pages.</span>";
		return $output;
	}

	/**
	 * Returns an array of options ready for parsing in a select list.
	 * Will mark the selected category for the post unless selectRandomCategory
	 * is flagged. Will also show 'Use Default' is flagged.
	 *
	 * @param integer $post_id
	 * @param bool    $hasDefaultMenuOption Set to true to show the default select option
	 * @param bool    $selectRandomCategory Set to true to select the 'random' option if random is selected, otherwise will select the randomly assigned value
	 *
	 * @return array
	 */
	public function getCategorySelectOptions( $post_id, $hasDefaultMenuOption=true, $selectRandomCategory=true )
	{
		$result = $this->getCategoriesFromCache();
		if(is_wp_error($result)) {
			return new WP_Error("1", "Could not get categories from cache or API");
		}
		$cats = $result;

		$result = $this->getCategoryPathForPost($post_id, $selectRandomCategory);
		if(is_wp_error($result)) {
			return new WP_Error("1", "Could not get category path for post");
		}
		$selectedCategoryPath = $result;

		return $this->getOptionsArray($cats, $selectedCategoryPath, $hasDefaultMenuOption, $selectRandomCategory); // from cache/API
	}

	/**
	 * Returns the categories from the cache. If the cache is invalid, will fetch from
	 * the Reach API and feed the cache, returning the result.
	 *
	 * @return array
	 */
	public function getCategoriesFromCache()
	{
		$cacheTimestamp  = get_option(constant(self::$widgetPrefix . 'WIDGET_WS_WP_CATEGORIES_TIMESTAMP'));
        $lastUpdate      = (int)get_option(constant(self::$widgetPrefix . 'WIDGET_WS_WP_CATEGORIES_LAST_UPDATE'));
		$cacheTimeout    = get_option(constant(self::$widgetPrefix . 'WIDGET_WS_WP_CATEGORIES_CACHE_TIMEOUT'));
		$categories      = get_option(constant(self::$widgetPrefix . 'WIDGET_WS_WP_CATEGORIES'));
        $widgetWsOptions = get_option(constant(self::$widgetPrefix . 'WIDGET_WS_WP_OPTIONS_NAME'));

        if(!array_key_exists('registered', $widgetWsOptions) || $widgetWsOptions['registered'] === false)
            return;

		if($lastUpdate == 0 || $categories=="" || count($categories)==0 || ((int)gmdate('U')-$lastUpdate)>$cacheTimeout)
		{
            if(is_array($categories) && count($categories) > 0)
            {
                $updater = new CoreWidgetUpdate();
                $updater->scheduleCheck(true);
                return $categories;
            }
			$result = $this->getCategoriesFromAPI($cacheTimestamp);
			if(!is_wp_error($result))
			{
                $lastUpdate = (int)gmdate('U');
				update_option(constant(self::$widgetPrefix. 'WIDGET_WS_WP_CATEGORIES_TIMESTAMP'), $cacheTimestamp);
                update_option(constant(self::$widgetPrefix. 'WIDGET_WS_WP_CATEGORIES_LAST_UPDATE'), $lastUpdate);
				update_option(constant(self::$widgetPrefix. 'WIDGET_WS_WP_CATEGORIES'), $result);

				return $result;
			} else if (is_null($categories) || is_string($categories) && $categories == "") {
				return $result; // Return Error
			}
            else
            {
                update_option(constant(self::$widgetPrefix. 'WIDGET_WS_WP_CATEGORIES_LAST_UPDATE'), ((int)gmdate('U') - ($cacheTimeout - 3600)));
            }
		}

		return $categories;
	}

	/**
	 * Fetches the category list from the Reach API, setting the
	 * cacheTimestamp.
	 *
	 * @param integer $cacheTimestamp Cache timestamp
	 * @param string  $lang           The language code
	 *
	 * @return array
	 */
	public function getCategoriesFromAPI(&$cacheTimestamp, $lang='en-us')
	{
        $core       = new CoreWidgetBase(self::$widgetPrefix);
        $lang       = $core->getLanguage();
        $coreWs     = new CoreWebservice();
        $url        = '/clients/'.strtolower(constant(self::$widgetPrefix.'PUBLIC_WIDGET_NAME')).'/categories/latest-products-by-category/' . $lang;
		$response   = $coreWs->prepHmacRequest($url, 'GET');
		$statusCode = wp_remote_retrieve_response_code($response);

		if($statusCode != 200)
		{
			return new WP_Error('$statusCode', 'API Request was not successful', $response);
		}
		$cacheTimestamp = strtotime(wp_remote_retrieve_header($response, "last-modified"));
		$categories     = json_decode(wp_remote_retrieve_body($response), true);

		return $categories;
	}

	/**
	 * Returns a category path for a post. Will generate a random path
	 * if selected, or will pull from the default category etc.
	 *
	 * @param integer $postId                The post id
	 * @param boolean $selectRandomCategory  If the category path is random, true to mark 'Random' as the category path
     * @param boolean $selectDefaultCategory Select default category
	 *
	 * @return string
	 */
	public function getCategoryPathForPost($postId, $selectRandomCategory=true, $selectDefaultCategory=true)
	{
		global $wpdb;

		$sql    = "SELECT category_path, use_default, is_random FROM " . $wpdb->prefix . "shopbop_category_assignments WHERE post_id=%s";
		$params = array($postId);
		$stmt   = $wpdb->prepare($sql, $params);
		$row    = $wpdb->get_row($stmt, ARRAY_A);

		if(is_null($row) || is_array($row) && count($row)==0)
		{
			// No record for this post exists?
			// Create a new record, and have it pull its value from default
			$row = array(
					'category_path' => "",
					'is_random'     => null,
					'use_default'   => 1,
				   );

			$params[] = $row['is_random'];
			$params[] = $row['use_default'];
			$params[] = $row['category_path'];

			$sql = "INSERT INTO " . $wpdb->prefix . "shopbop_category_assignments (post_id, is_random, use_default, category_path) VALUES (%s, %s, %s, %s)";
			$wpdb->query($wpdb->prepare($sql, $params)); // Check for errors?
		}

		if((int)$row['use_default'] == 0)
		{
			if((int)$row['is_random'] == 1)
			{
				if($row['category_path'] == "")
				{
					// Set random path and update record
					$result = $this->getCategoriesFromCache();
					if(is_wp_error($result))
					{
						return new WP_Error("1", "Could not get categories from cache");
					}
					$categories = $result;
					$categoryPath = $this->getRandomCategoryPath($categories);
					$params       = array($categoryPath, $postId);
					$sql          = "UPDATE " . $wpdb->prefix . "shopbop_category_assignments SET category_path=%s WHERE post_id=%s";
					$wpdb->query($wpdb->prepare($sql, $params)); // Check for errors?

					if(!$selectRandomCategory)
						$categoryPath = null;

				} else {
					if($selectRandomCategory)
					{
						$categoryPath = -1;
					} else {
						$categoryPath = $row['category_path'];
					}
				}
			} else {
				$categoryPath = $row['category_path'];
			}
		}
		else if((int)$row['use_default'] == 1 && $row['category_path'] == "")
		{
			$result = $this->getDefaultCategoryPath();
			if(is_wp_error($result))
			{
				return new WP_Error("1", "Could not get category path for post");
			}
			$categoryPath = $result;
			$params       = array($categoryPath, $postId);
			$sql          = "UPDATE " . $wpdb->prefix . "shopbop_category_assignments SET category_path=%s WHERE post_id=%s";
			$wpdb->query($wpdb->prepare($sql, $params)); // Check for errors?
			if($selectDefaultCategory)
				$categoryPath = -2;
		}
        else if((int)$row['use_default'] == 1 && $selectDefaultCategory)
        {
			$categoryPath = -2;
		}
        else
        {
			$categoryPath = $row['category_path'];
		}

		return $categoryPath;
	}

	/**
	 * Returns the category path assigned to default. If default is
	 * set to 'random category' then returns a random category path.
	 * The default category has a postId of -1.
	 *
	 * @return string
	 */
	public function getDefaultCategoryPath($generateRandomEntry=true, $hideNonPath=true)
	{
		global $wpdb;

		// The default category has a postId of -1
		$sql = "SELECT category_path, is_random FROM " . $wpdb->prefix . "shopbop_category_assignments WHERE post_id=-1";
		$row = $wpdb->get_row($sql, ARRAY_A);

        if(is_null($row) || (is_array($row) && count($row)==0) || (is_array($row) && array_key_exists('category_path', $row) && $row['category_path'] == ''))
        {
            // Don't exist? Log an error and create a new default entry set to random
            _shopbop_widget_log("CoreCategories::getDefaultCategoryPath(): Default category does not exist (postId=-1 missing)");
            $sql = "INSERT IGNORE INTO " . $wpdb->prefix . "shopbop_category_assignments (post_id, is_random, use_default, category_path) VALUES (-1, 1, null, null)";
			$wpdb->query($sql); // Check for errors?

			$categoryPath = null;
			$isRandom     = true;
		}
        else
        {
			$categoryPath = $row['category_path'];
			$isRandom     = (bool)$row['is_random'];
		}

		if($isRandom === true && $generateRandomEntry)
		{
			$result = $this->getCategoriesFromCache();
			if(is_wp_error($result) || !is_array($result))
			{
				return new WP_Error("1", "Could not get categories from cache");
			}
			$categoryPath = $this->getRandomCategoryPath($result);
		}

		if($isRandom === true && $hideNonPath === false)
		{
			return -1;
		}

		return $categoryPath;
	}

	/**
	 * Returns a random valid category path.
	 *
	 * @return string
	 */
	public function getRandomCategoryPath(array $categories)
	{
		$catList = array();
		$this->generateFlattenedCategoryList($categories, $catList);

		return $catList[rand(0, count($catList)-1)];
	}

	/**
	 * Returns an array of options built from the category tree, with
	 * an option selected by default.
	 *
	 * @param array  $cats                 Heirarchical array of categories
	 * @param string $catId                A slash seperated string marking the tree path to be selected by default
	 * @param bool   $hasDefaultMenuOption Indicates whether the 'Use default' menu option should be shown
	 * @param bool   $showRandomCategory   Indicates that if 'Random Category' has been chosen then the actual random category be selected
	 *
	 * @return array
	 */
	public function getOptionsArray($cats, $catId, $hasDefaultMenuOption=true, $showRandomCategory=true)
	{
		$options = array();

		$this->expandNodeDetailsCategoryWalker($cats);

		if($showRandomCategory && ($catId != -1 && $catId != -2))
			$this->setSelectedNodeCategoryWalker($cats, explode('|||', $catId));

		$this->populateFlattenedOptionsListCategoryWalker($cats, $options);

		if(!$showRandomCategory || $catId == -1)
		{
			array_unshift($options, array('label'=>-1, 'value'=>'Random Category', 'selected'=>true, 'indent'=>0));
		}
		else
		{
			$selected = ($catId == -1) ? true : false;
			array_unshift($options, array('label'=>-1, 'value'=>'Random Category', 'selected'=>$selected, 'indent'=>0));
		}

		if($hasDefaultMenuOption)
		{
			$selected = ($catId == -2) ? true : false;
			array_unshift($options, array('label'=>-2, 'value'=>'Use default', 'selected'=>$selected, 'indent'=>0));
		}

		return $options;
	}

	/**
	 * Expands a category tree by adding an array under each node which contains
	 * any sub-trees in 'values' and a 'selected' property to indicate if the
	 * node has been selected.
	 *
	 * @param array $item Heirarchical array of categories
	 *
	 * @return void
	 */
	public function expandNodeDetailsCategoryWalker(&$item)
	{
        if(!is_array($item))
            return;

		foreach($item as &$i)
		{
			$i = array('value'=>$i, 'selected'=>false);
			if(is_array($i['value']))
			{
				$this->expandNodeDetailsCategoryWalker($i['value']);
			}
		}
	}

	/**
	 * Sets the selected node in an heirarchical tree of category nodes.
	 * Expects expandNodeDetailsCategoryWalker() to have been called on the
	 * category tree first!
	 *
	 * @param array  $item Expanded heirarchical array of categories
	 * @param string $ref  A slash seperated string marking the tree path to be selected by default
	 *
	 * @return void
	 */
	public function setSelectedNodeCategoryWalker(&$item, &$ref)
	{
        if(!is_array($item))
            return;

		if(is_array($ref) && count($ref)>0)
		{
			foreach($item as $key=>&$i)
			{
				if(count($ref)>0 && $key == $ref[0] && is_array($i))
				{
					array_shift($ref);

					if(count($ref)==0 && array_key_exists('selected', $i))
					{
						$i['selected'] = true;
						return;
					}

					if(count($ref)>0 && array_key_exists('value', $i) && count($i['value'])>0)
					{
						$this->setSelectedNodeCategoryWalker($i['value'], $ref);
					}
				}
			}
		}
	}

	/**
	 * Flattens an expanded heirarchical category tree into a the options array.
	 * The resulting options array can be passed straight to renderSelect() to
	 * be rendered as a select list.
	 *
	 * @param array $item     Expanded heirarchical array of categories
	 * @param array $options  Array to pass the flatended nodes into
	 * @param integer $indent The node indent
	 *
	 * @return void
	 */
	public function populateFlattenedOptionsListCategoryWalker($item, &$options, $indent=0, $parent="")
	{
        if(!is_array($item))
            return;

		foreach($item as $key=>$i)
		{
			$label = ($parent=="") ? $key : $parent.'|||'.$key;
			$options[] = array('label'=>$label, 'value'=>$key, 'selected'=>$i['selected'], 'indent'=>$indent);
			if(is_array($i['value']))
			{
				$this->populateFlattenedOptionsListCategoryWalker($i['value'], $options, $indent+1, $label);
			}
		}
	}

	/**
	 * Generates a flat list of category paths.
	 *
	 * @param array  $categories    The heirarchical category tree
	 * @param array  $options       The array to push the flattented tree into
	 * @param string $currentBranch The current branch
	 *
	 * @return void
	 */
	public function generateFlattenedCategoryList(array $categories, array &$options, $currentBranch="")
	{
		foreach($categories as $key=>$i)
		{
			$c         = ($currentBranch == "") ? $key : $currentBranch.'|||'.$key;
			$options[] = $c;
			if(is_array($i))
			{
				$this->generateFlattenedCategoryList($i, $options, $c);
			}
		}
	}

	/**
	 * Called to handle the saving of the category selected via wp-admin.
	 *
	 * @param int $post_id The post id
	 *
	 * @return void
	 */
	public function savePostdata( $post_id )
	{
		global $wpdb;

		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['myplugin_noncename'], plugin_basename( __FILE__ ) ) )
			return;

		// Check permissions
		if ( 'page' == $_POST['post_type'] )
		{
			if ( !current_user_can( 'edit_page', $post_id ) )
				return;
		}
		else
		{
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
		}
		// OK, we're authenticated: we need to find and save the data

		$this->setSelectedCategory($_POST['post_ID'], $_POST[$this->_selectElementName]);

		return;
	}

	/**
	 * Set the selected category for a post.
	 *
	 * @param int    $post_id          The post id
	 * @param string $selectedCategory The selected category
	 *
	 * @return void
	 */
	public function setSelectedCategory($post_id, $selectedCategory)
	{
		global $wpdb;

		$categoryPath = $this->getCategoryPathForPost($post_id, false, false);
		$realPath     = $this->getCategoryPathForPost($post_id);
		$params       = array();
		if($selectedCategory == "-1") // Is Random
		{
			if($categoryPath == "" || $realPath != "-1") // Newly assigned random
			{
				$result = $this->getCategoriesFromCache();
				if(is_wp_error($result) || !is_array($result))
				{
					return new WP_Error("2", "Could not get category path for post");
				}
				$categories   = $result;
				$categoryPath = $this->getRandomCategoryPath($categories);

				// Clear cached page(s)
				$webService = new CoreWebservice();
				$webService->deleteCache(get_permalink($post_id), 'pane1');
				$webService->deleteCache(get_permalink($post_id), 'pane2');
			} else {
				return; // Category already randomly set: keep existing category
			}

			$params[] = 1;
			$params[] = 0;
			$params[] = $categoryPath;
		} // Use Default
		else if($selectedCategory == "-2")
		{
			if($categoryPath == "" || $realPath != "-2") // Newly assigned use default
			{
				// Get the default category path, but dont give us a random path if
				// default category is set to random! We need to know!
				$result = $this->getDefaultCategoryPath(false, false);
				if(is_wp_error($result))
				{
					return new WP_Error("2", "Could not get category path for post");
				}
				$defaultCategoryPath = $result;

				if($defaultCategoryPath == "-1")
				{
					$categories   = $this->getCategoriesFromCache();
					$categoryPath = $this->getRandomCategoryPath($categories);
				} else {
					$categoryPath = $defaultCategoryPath;
				}

				// Clear cached page(s)
				$webService = new CoreWebservice();
				$webService->deleteCache(get_permalink($post_id), 'pane1');
				$webService->deleteCache(get_permalink($post_id), 'pane2');
			}

			$params[] = 0;
			$params[] = 1;
			$params[] = $categoryPath;
		}
		else
		{
			if($categoryPath != $selectedCategory)
			{
				// Clear cached page(s)
				$webService = new CoreWebservice();
				$webService->deleteCache(get_permalink($post_id), 'pane1');
				$webService->deleteCache(get_permalink($post_id), 'pane2');
			}

			$params[] = 0;
			$params[] = 0;
			$params[] = $selectedCategory;
		}

		$p   = array();
		$p[] = $post_id;
		$p   = array_merge($p, $params, $params);
		$sql = "INSERT INTO " . $wpdb->prefix . "shopbop_category_assignments
		(post_id, is_random, use_default, category_path)
		VALUES
		(%s, %s, %s, %s)
		ON DUPLICATE KEY UPDATE
		is_random=%s, use_default=%s, category_path=%s";

		$stmt = $wpdb->prepare($sql, $p);
		$wpdb->query($stmt); // Check for errors?

		return;
	}

	/**
	 * Resets the category for posts that defer to the default category.
	 * If the default category is set to 'random' then will randomly
	 * assign a category for each post.
	 *
	 * @param string $widgetDefaultCategory The default category
	 *
	 * @return array
	 */
	public function resetDeferingPosts($widgetDefaultCategory)
	{
		global $wpdb;

		$sql     = "SELECT post_id FROM " . $wpdb->prefix . "shopbop_category_assignments WHERE use_default = 1";
		$postIds = $wpdb->get_col($sql);

		if($postIds && count($postIds)>0)
		{
			$sql = "UPDATE " . $wpdb->prefix . "shopbop_category_assignments SET category_path=%s WHERE post_id=%d";

			foreach($postIds as $postId)
			{
				if($widgetDefaultCategory == -1)
				{
					$catPath = $this->getRandomCategoryPath($this->getCategoriesFromCache());
				} else {
					$catPath = $widgetDefaultCategory;
				}

				$stmt = $wpdb->prepare($sql, array($catPath, $postId));
				$wpdb->query($stmt); // Check for errors?
			}

			return $postIds;
		}
		
		return null;
	}
}