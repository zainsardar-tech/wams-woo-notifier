<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    wams_Notifications
 * @subpackage wams_Notifications/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    wams_Notifications
 * @subpackage wams_Notifications/includes
 * @author     Zain Sardar <aztifywams@gmail.com>
 */
class wams_Notifications_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        $act = new wams_Notifications_Activator;
        $act->init_db();
	}

    /**
     * Docs: https://codex.wordpress.org/Creating_Tables_with_Plugins
     */
    function init_db(){
        global $wpdb;
        global $wams_notifications_db_version;
        global $wams_notifications_table_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $wams_notifications_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            code varchar(55),
            content longtext,
            is_active boolean,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        add_option( 'wams_notifications_db_version', $wams_notifications_db_version );
    }

}
