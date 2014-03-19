<?php
/**
 * XMLRPC methods for the widget.
 *
 * @package Corewidget
 *
 * @author  widget <widget@stickyeyes.com>
 */
class CoreWidgetXmlRpc
{
	/**
	 * Constructor.
     *
     * @return void
	 */
	public function __construct()
	{

	}

	/**
	 * Flush all.
	 *
	 * @return boolean
	 */
	public function flushAll()
	{
		_shopbop_widget_log('in flushAll');
		global $wpdb;
		$wpdb->query("TRUNCATE " . $wpdb->prefix . "shopbop_cache");

		return true;
	}

	/**
	 * Flush all.
	 *
	 * @return boolean
	 */
	public function flushMarketingMessages()
	{
		_shopbop_widget_log('in flushMarketingMessages');
		global $wpdb;
		$wpdb->query("DELETE
				      FROM " . $wpdb->prefix . "shopbop_cache
					  WHERE type = 'marketing'" );
		return true;
	}

	/**
	 * FlushPane1.
	 *
	 * @return boolean
	 */
	public function flushPane1()
	{
		_shopbop_widget_log('in flushPane1');
		global $wpdb;
		$wpdb->query("DELETE
				FROM " . $wpdb->prefix . "shopbop_cache
				WHERE type = 'pane1'" );
		return true;
	}

	/**
	 * FlushPane2.
	 *
	 * @return boolean
	 */
	public function flushPane2()
	{
		_shopbop_widget_log('in flushPane2');
		global $wpdb;
		$wpdb->query("DELETE
				FROM " . $wpdb->prefix . "shopbop_cache
				WHERE type = 'pane2'" );
		return true;
	}

	/**
	 * FlushPromotion.
	 *
	 * @return boolean
	 */
	public function flushPromotion()
	{
		_shopbop_widget_log('in flushPromotion');
		global $wpdb;
		$wpdb->query("DELETE
				FROM " . $wpdb->prefix . "shopbop_cache
				WHERE type = 'promotion'" );
		return true;
	}
}