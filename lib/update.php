<?php
/**
 * A class used for internally updating items for the widget.
 *
 * @package Stickywidget
 *
 * @author  widget <widget@stickyeyes.com>
 */
class CoreWidgetUpdate
{
    /**
     * Prefix.
     *
     * @var string
     */
    public static $widgetPrefix = 'SHOPBOP_';

    /**
     * Updater running.
     *
     * @var boolean
     */
    private static $_running = false;

    /**
     * Are we inside an internal update?
     *
     * @return boolean
     */
    public static function isRunning()
    {
        return (bool)self::$_running;
    }

    /**
     * Schedule update event.
     *
     * @return void
     */
    public function scheduleCheck($force = false)
    {
        if($force === true || !is_null($this->fetchOldestUpdateRequest()))
            wp_schedule_single_event(time(), "core_widget_update_hook");
    }

    /**
     * Updates the cache table's last_update column if it hasn't been set already.
     *
     * @param string  $path      URI of page
     * @param string  $type      type of item
     * @param integer $timestamp timestamp override
     *
     * @return void
     */
    public function updateUpdateRequestedTime($path, $type, $timestamp = null)
    {
        global $wpdb;

        if(is_null($timestamp))
            $timeString = date('Y-m-d H:i:s');
        else
            $timeString = date('Y-m-d H:i:s', $timestamp);

        $query = $wpdb->prepare(
            '
            UPDATE ' . $wpdb->prefix . 'shopbop_cache
            SET
              update_requested = "%s"
            WHERE
              path = "%s"
            AND
              type = "%s"
            AND update_requested IS NULL
            ',
            array(
             $timeString,
             $path,
             $type,
            )
        );

        $wpdb->query($query);
    }

    /**
     * Clear all the update requested times
     *
     * @return void
     */
    public function clearAllUpdateRequestedTimes()
    {
        global $wpdb;

        $query = $wpdb->prepare(
            '
            UPDATE ' . $wpdb->prefix . 'shopbop_cache
            SET
              update_requested = NULL
            '
        );

        $wpdb->query($query);
    }

    /**
     * Clear updated requested time.
     *
     * @param string $path URI of page
     * @param string $type type of item
     *
     * @return void
     */
    public function clearUpdateRequestedTime($path, $type)
    {
        global $wpdb;

        $query = $wpdb->prepare(
            '
            UPDATE ' . $wpdb->prefix . 'shopbop_cache
            SET
              update_requested = NULL
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
     * Fetch oldest update request.
     *
     * @param integer $offset offset to prevent an infinite loop
     *
     * @return array
     */
    public function fetchOldestUpdateRequest($offset = 0)
    {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "shopbop_cache` WHERE `update_requested` IS NOT NULL ORDER BY `update_requested` ASC LIMIT ".(int)$offset.", 1");
    }

    /**
     * Get oldest update request timestamp.
     *
     * @return integer
     */
    public function getOldestUpdateRequestTimestamp()
    {
        $row = $this->fetchOldestUpdateRequest();
        if(is_null($row) || !isset($row->update_requested))
            return null;

        return strtotime($row->update_requested);
    }

    /**
     * Run update of pages that have requested it.
     *
     * @return void
     */
    public function runUpdate()
    {
        self::$_running = true;
        $offset         = 0;
        $maxLoops       = 1000;
        $startTime      = time();

        for($iteration = 0; $iteration < $maxLoops; $iteration++)
        {
            $iteration++;
            $row = $this->fetchOldestUpdateRequest($offset);

            if(is_null($row))
                break;

            $coreWebservice = new CoreWebservice();
            $coreWebservice->setInternalUpdaterRunning(true);
            $coreCategories = new CoreCategories();

            switch ($row->type)
            {
                case 'marketing':
                    $coreWebservice->getMarketingMessage($row->post_id, $row->path);
                    break;

                case 'promotion':
                    $coreWebservice->getPromotion($row->post_id, $row->path);
                    break;

                case 'pane1':
                    $coreWebservice->getPaneContent($row->post_id, $row->type, $row->path);
                    break;

                case 'pane2':
                    $coreCategories->getCategoriesFromCache();
                    $catPath = $coreCategories->getCategoryPathForPost($row->post_id, false, false);
                    if(!is_null($catPath) && !is_wp_error($catPath))
                    {
                        $catPath      = explode('|||', $catPath);
                        $catQuery     = array("category" => str_replace('\u2215', '∕', json_encode($catPath)));
                        $pane2Content = $coreWebservice->getPaneContent($row->post_id, 'pane2', $row->path, $catQuery);
                        if(is_array($pane2Content) && count($pane2Content) == 0)
                        {
                            $newCatPath   = $coreCategories->getDefaultCategoryPath();
                            $catPath      = explode('|||', $newCatPath);
                            $catQuery     = array("category" => str_replace('\u2215', '∕', json_encode($catPath)));
                            $pane2Content = $coreWebservice->getPaneContent($row->post_id, 'pane2', $row->path, $catQuery);
                            if(!(is_array($pane2Content) && count($pane2Content) == 0))
                                $coreCategories->setSelectedCategory($row->post_id, $newCatPath);
                        }
                    }
                    break;

                default:
                    //
                    break;
            }

            if($coreWebservice->getLastStatusCode() == 200 || $coreWebservice->getLastStatusCode() == 304)
                $this->clearUpdateRequestedTime($row->path, $row->type);
            else
                $offset++;

            // If the cron has taken longer than 5 minutes, break out of the loop
            if((int)(time() - $startTime) >= 300)
                break;
        }

        $coreCategories = new CoreCategories();
        $coreCategories->getCategoriesFromCache(true);
        self::$_running = false;
    }
}

/**
 * Schedule the cron update if needed.
 *
 * @return void
 */
function core_widget_update_schedule()
{
    $updater = new CoreWidgetUpdate();
    $updater->scheduleCheck();
}

/**
 * Run the update.
 *
 * @return void
 */
function core_widget_update()
{
    $updater = new CoreWidgetUpdate();
    $updater->runUpdate();
}

add_action('core_widget_update_hook', 'core_widget_update');
add_action('shutdown', 'core_widget_update_schedule');