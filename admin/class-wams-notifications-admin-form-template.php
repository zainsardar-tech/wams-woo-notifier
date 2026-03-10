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

class wams_Notifications_Admin_Form_Template{
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

    private $wams_db;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $wams_notifications       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $wams_notifications, $version ) {
		$this->wams_notifications = $wams_notifications;
		$this->version = $version;

        include_once plugin_dir_path( __FILE__ ) . 'class-wams-notifications-admin-db.php';
        $wams_db = new wams_Notifications_Admin_Db( $wams_notifications, $version );
        $this->wams_db = $wams_db;
	}

    /**
     * Function for handle edit template or content for notification
     */
    public function handle_submit_edit_template(){
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        $code = isset( $_POST['wams_notifications']['code'] ) ? sanitize_text_field( wp_unslash( $_POST['wams_notifications']['code'] ) ) : '' ;
        $content = isset( $_POST['wams_notifications']['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wams_notifications']['content'] ) ) : '';
        $url = isset( $_POST['_wp_http_referer'] ) ? sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ) : '';

		if (!check_admin_referer( "update_template_code_".$code )) {
			wp_die();
		}

        $res = $this->wams_db->update_content($code, $content, null);

        $redirect_url = add_query_arg('wams_notifications_status', 'success', $url);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Function for handle update status template active or not from ajax
     */
    public function handle_ajax_status_template() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if (!isset($nonce) || !wp_verify_nonce($nonce, 'wams_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            wp_die();
        }

        $template_status = isset($_POST['template_status']) ? sanitize_text_field( wp_unslash( $_POST['template_status'] ) ) : '';
        $template_code = isset($_POST['template_code']) ? sanitize_text_field( wp_unslash( $_POST['template_code'] ) ) : '';
        $template_payload = array(
            'template_status' => $template_status,
            'template_code' => $template_code
        );

        if ($template_status == '1') {
            $template = $this->wams_db->get_content($template_code);
            if (!isset($template->content) || (trim($template->content) === '')) {
                wp_send_json_error(array('message' => "Please Edit Template and fill in the message before activating."));
                wp_die();
            }
        }

        $res_db = $this->wams_db->update_content($template_code, null, $template_status);
        $res = array(
            'is_success' => true,
            'data' => array(
                'template_status' => $template_status,
                'template_code' => $template_code
            )
        );

        if ($res_db == false) {
            wp_send_json_error( $template_payload );
        }

        wp_send_json_success( $template_payload );
        wp_die();
    }
}
?>
