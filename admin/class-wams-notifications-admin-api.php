<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wams.aztify.com
 * @since      1.0.0
 *
 * @package    wams_Notifications
 * @subpackage wams_Notifications/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    wams_Notifications
 * @subpackage wams_Notifications/admin
 * @author     Zain Sardar <aztifywams@gmail.com>
 */

class wams_Notifications_Admin_Api
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $wams_notifications    The ID of this plugin.
     */
    private $wams_notifications;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $wams_notif_api_url;
    private $wams_notif_api_headers;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $wams_notifications       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($wams_notifications, $version)
    {
        $this->wams_notifications = $wams_notifications;
        $this->version = $version;

        $this->wams_notif_api_url = 'https://wams.aztify.com';
        $this->wams_notif_api_headers = array();

        $options = get_option('wams_notifications_options');
        if ($options) {
            $this->wams_notif_api_headers = array(
                'Content-Type' => 'application/json',
                'X-SOURCE-FROM' => 'wordpress-plugin',
                'X-WP-PLUGIN-NAME' => $wams_notifications,
                'X-WP-PLUGIN-VERSION' => $version,
            );
        }
    }

    /**
     * Method POST for sending payload to wams.
     *
     * @param   string  $path from feature wams.
     * @param   array   $body payload.
     */
    function wams_post($path, $body)
    {
        $args = array(
            'body' => wp_json_encode($body),
            'timeout' => '15',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array_merge($this->wams_notif_api_headers, [
                'Content-Type' => 'application/json',
            ]),
            'cookies' => array(),
        );

        $response = wp_remote_post(rtrim($this->wams_notif_api_url, '/') . '/' . ltrim($path, '/'), $args);

        return $response;
    }

    /**
     * Method GET for fetching data from wams.
     *
     * @param   string  $path from feature wams.
     * @param   array   $query_args query parameters.
     */
    function wams_get($path, $query_args = [])
    {
        $url = rtrim($this->wams_notif_api_url, '/') . '/' . ltrim($path, '/');
        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }

        $args = array(
            'timeout' => '15',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $this->wams_notif_api_headers,
            'cookies' => array(),
        );

        $response = wp_remote_get($url, $args);

        return $response;
    }
}
?>