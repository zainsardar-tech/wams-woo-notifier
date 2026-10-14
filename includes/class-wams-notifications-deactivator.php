<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    wams_Notifications
 * @subpackage wams_Notifications/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    wams_Notifications
 * @subpackage wams_Notifications/includes
 * @author     Zain Sardar <aztifywams@gmail.com>
 */
class wams_Notifications_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		/*
		 * Drop the pending product broadcast worker (added in 1.3.0).
		 * The literal hook name is used on purpose: the class that defines the
		 * CRON_HOOK constant is not loaded during deactivation, and a scheduled
		 * event that points at a deactivated plugin would just fire into nothing.
		 * The queued option is intentionally kept, so reactivating the plugin
		 * resumes the broadcast instead of silently losing it.
		 */
		wp_clear_scheduled_hook('wams_notifications_process_broadcast');
	}

}
