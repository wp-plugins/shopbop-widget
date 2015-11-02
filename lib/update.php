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
    const SCHEDULE_HOOK = "shopbop_widget_update_hook";
    const LOCK_FILENAME = "shopbop_widget.lock";

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
     * Register scheduled event.
     *
     * @return void
     */
    public function registerScheduledEvent()
    {
        if (!wp_next_scheduled(self::SCHEDULE_HOOK))
		{
			wp_schedule_event(time(), strtolower(self::$widgetPrefix . 'cron_schedule'), self::SCHEDULE_HOOK);
		}
            
    }

    /**
     * Deregister Scheduled Event.
     *
     * @return void
     */
    public function deregisterScheduledEvent()
    {
        wp_clear_scheduled_hook(self::SCHEDULE_HOOK);
    }

    /**
     * Add Cron Schedule.
     *
     * @param array $schedules existing schedules
     *
     * @return array
     */
    function addCronSchedule($schedules)
    {
        $scheduleTime                                                 = constant(self::$widgetPrefix . 'WIDGET_UPDATE_TIME_BETWEEN_CRON');
        $schedules[strtolower(self::$widgetPrefix . 'cron_schedule')] = array(
                                                                         'interval' => $scheduleTime,
                                                                         'display'  => __('Widget Update Schedule'),
                                                                        );
        return $schedules;
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
     * Fetch oldest update request only one to show on wp admin page
     *
     * @param integer $offset offset to prevent an infinite loop
     *
     * @return array
     */
    public function fetchOldestUpdateRequestOneRow($offset = 0)
    {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "shopbop_cache` WHERE `update_requested` IS NOT NULL ORDER BY `update_requested` ASC LIMIT ".(int)$offset.", 1");
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
        $var =$wpdb->get_results("SELECT type, post_id, path FROM `" . $wpdb->prefix . "shopbop_cache` WHERE `update_requested` IS NOT NULL ORDER BY `update_requested` ASC LIMIT ".(int)$offset.", 5");
        return $var;
    }

    /**
     * Get oldest update request timestamp.
     *
     * @return integer
     */
    public function getOldestUpdateRequestTimestamp()
    {
        $row = $this->fetchOldestUpdateRequestOneRow();
        if(is_null($row) || !isset($row->update_requested))
            return null;

        return strtotime($row->update_requested);
    }

    /**
     * Get lock file name.
     *
     * @return string
     */
    public function getLockFileName()
    {
        return sys_get_temp_dir() . "/" . self::LOCK_FILENAME;
    }

    /**
     * Attempt to lock file.
     *
     * @param resource $fp lock file handle
     *
     * @throws InvalidArgumentException
     *
     * @return boolean
     */
    private function _lockFile($fp)
    {
        if(!flock($fp, LOCK_EX|LOCK_NB))
            return false;

        return true;
    }

    /**
     * Release lock file.
     *
     * @param resource $fp file pointer
     *
     * @return void
     */
    private function _releaseLockFile($fp)
    {
        flock($fp, LOCK_UN);
    }

    /**
     * Open lock file.
     *
     * @return resource
     */
    private function _openLockFile()
    {
        $lockFile = $this->getLockFileName();
        return @fopen($lockFile, "w");
    }

    /**
     * Test we can open the lock file for writing.
     *
     * @return boolean
     */
    public function testLockFile()
    {
        $fp = $this->_openLockFile();
        if(is_resource($fp))
        {
            fclose($fp);
            return true;
        }

        return false;
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
        $fp             = $this->_openLockFile();

        if(is_resource($fp))
            if(!$this->_lockFile($fp))
                return;

        for($iteration = 0; $iteration < $maxLoops; $iteration++)
        {
            $rows = $this->fetchOldestUpdateRequest($offset);

            if(empty($rows))
                break;

            foreach ($rows as $row)
            {
                $coreWebservice = new CoreWebservice();
                $coreWebservice->setInternalUpdaterRunning(true);
                $coreCategories = new CoreCategories();

                switch ($row->type)
                {
                    case 'marketing':
                        $coreWebservice->getMarketingMessage($row->post_id, $row->path, null, 'cronActive');
                        break;

                    case 'promotion':
                        $coreWebservice->getPromotion($row->post_id, $row->path, null, 'cronActive');
                        break;

                    case 'pane1':
                        $coreWebservice->getPaneContent($row->post_id, $row->type, $row->path, null, null, 'cronActive');
                        break;

                    case 'pane2':
                        $coreCategories->getCategoriesFromCache();
                        $catPath = $coreCategories->getCategoryPathForPost($row->post_id, false, false);
                        if (!is_null($catPath) && !is_wp_error($catPath))
                        {
                            $catPath      = explode('|||', $catPath);
                            $catQuery     = array("category" => str_replace('\u2215', '∕', json_encode($catPath)));
                            $pane2Content = $coreWebservice->getPaneContent($row->post_id, 'pane2', $row->path, $catQuery, null, 'cronActive');
                            if (is_array($pane2Content) && count($pane2Content) == 0)
                            {
                                $newCatPath   = $coreCategories->getDefaultCategoryPath();
                                $catPath      = explode('|||', $newCatPath);
                                $catQuery     = array("category" => str_replace('\u2215', '∕', json_encode($catPath)));
                                $pane2Content = $coreWebservice->getPaneContent($row->post_id, 'pane2', $row->path, $catQuery, null, 'cronActive');
                                if (!(is_array($pane2Content) && count($pane2Content) == 0))
                                    $coreCategories->setSelectedCategory($row->post_id, $newCatPath);
                            }
                        }
                        break;

                    default:
                        // na
                        break;
                }

                if($coreWebservice->getLastStatusCode() == 200 || $coreWebservice->getLastStatusCode() == 304)
                    $this->clearUpdateRequestedTime($row->path, $row->type);
                else
                    $offset++;
            }

            // If the cron has taken longer than 5 minutes, break out of the loop
            if((int)(time() - $startTime) >= constant(self::$widgetPrefix . 'WIDGET_UPDATE_MAX_CRON_RUN_TIME'))
                break;
        }

        $coreCategories = new CoreCategories();
        $coreCategories->getCategoriesFromCache(true);

        if(is_resource($fp))
        {
            $this->_releaseLockFile($fp);
            fclose($fp);
        }

        self::$_running = false;
    }


    /**
     * Check update request index exists.
     *
     * @return boolean
     */
    public function checkUpdateRequestIndexExist()
    {
        global $wpdb;
        $sql = 'SHOW INDEX FROM`' . $wpdb->prefix . 'shopbop_cache` WHERE Key_name = "%s"';
        $query = $wpdb->prepare($sql, array('update_requested'));
        $ret   = $wpdb->get_row($query, ARRAY_A);

        return ($ret == null)? false: true;
    }
}

/**
 * Run the update.
 *
 * @return void
 */
function shopbop_widget_update()
{
    //Check for eula agreement if agreed or not.
    $coreWs = new CoreWebservice;

    if(!$coreWs->eulaCheck())
        return;

    // Run updater
    $updater = new CoreWidgetUpdate();
    $updater->runUpdate();
}

$coreWidgetUpdate = new CoreWidgetUpdate();

add_action(CoreWidgetUpdate::SCHEDULE_HOOK, 'shopbop_widget_update');
add_filter('cron_schedules', array($coreWidgetUpdate, 'addCronSchedule'));

//Register cron schedul event on every page. 
//But this is registered only once and will not register if it already exist.
add_action( 'wp', array($coreWidgetUpdate,'registerScheduledEvent' ));