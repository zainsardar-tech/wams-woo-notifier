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

class wams_Notifications_Admin
{

    private $wams_notifications;

    private $version;
    private $loader;

    private $page_url;
    private $menu_title;

    private $wams_db;

    public function __construct($wams_notifications, $version, $loader)
    {

        $this->wams_notifications = $wams_notifications;
        $this->version = $version;
        $this->loader = $loader;
        $this->page_url = 'wams-notifications';
        $this->menu_title = 'WAMS Notifier';

        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-db.php';
        $wams_db = new wams_Notifications_Admin_Db($wams_notifications, $version);
        $this->wams_db = $wams_db;
    }

    public function enqueue_styles()
    {
        wp_enqueue_style('dashicons');
        wp_enqueue_style($this->wams_notifications, plugin_dir_url(__FILE__) . 'css/wams-notifications-admin.css', array(), $this->version, 'all');

    }

    public function enqueue_scripts()
    {
        $base = plugin_dir_url(__FILE__) . 'js/';

        wp_register_script('wams-emoji-picker', $base . 'emoji/picker.min.js', array(), $this->version, true);
        wp_register_script('wams-notifications-admin', $base . 'wams-notifications-admin.js', array('jquery', 'wams-emoji-picker'), $this->version, true);

        add_filter('script_loader_tag', array($this, 'script_type_module'), 10, 3);

        wp_enqueue_script('wams-emoji-picker');
        wp_enqueue_script('wams-notifications-admin');

        wp_localize_script('wams-notifications-admin', 'wams_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wams_ajax_nonce')
        ));
    }

    public function script_type_module($tag, $handle, $src)
    {
        if ('wams-emoji-picker' === $handle) {
            $tag = str_replace(' src=', ' type="module" src=', $tag);
        }
        return $tag;
    }

    private function get_menu_title($menu)
    {
        return $menu . ' - WAMS Woo Notifier';
    }

    private function get_menu_url($menu = null)
    {
        switch ($menu) {
            case 'options':
                return $this->page_url . '-options';
                break;
                return $this->page_url . '-options';
                break;

            default:
                return $this->page_url;
                break;
        }
    }

    private function get_template_contents()
    {
        $templates = [
            [
                'title' => 'Order Pending',
                'code' => 'WC_ORDER_PENDING'
            ],
            [
                'title' => 'Order Processing',
                'code' => 'WC_ORDER_PROCESSING'
            ],
            [
                'title' => 'Order On Hold',
                'code' => 'WC_ORDER_ON_HOLD'
            ],
            [
                'title' => 'Order Completed',
                'code' => 'WC_ORDER_COMPLETED'
            ],
            [
                'title' => 'Order Canceled',
                'code' => 'WC_ORDER_CANCELED'
            ],
            [
                'title' => 'Order Refunded',
                'code' => 'WC_ORDER_REFUNDED'
            ],
            [
                'title' => 'Order Failed',
                'code' => 'WC_ORDER_FAILED'
            ],
            [
                'title' => 'New Product Published',
                'code' => 'WC_PRODUCT_ADDED'
            ]
        ];

        return $templates;
    }

    function callback_menu_main_view()
    {

        $req_edit_template_code = '';
        if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wams_edit_template')) {
            $req_edit_template_code = isset($_GET['template_code']) ? sanitize_text_field(wp_unslash($_GET['template_code'])) : '';
        }
        $template_contents = $this->get_template_contents();

        if (!empty($req_edit_template_code)) {
            $current_content = array_filter($template_contents, function ($val) use ($req_edit_template_code) {
                return $val['code'] == $req_edit_template_code;
            });
            $current_content = array_shift($current_content);
            $current_content_db = $this->wams_db->get_content($req_edit_template_code);

            include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-shortcode.php';
            $shortcode = new wams_Notifications_Admin_Shortcode($this->wams_notifications, $this->version);
            $wc_shortcodes = $shortcode->get_wc_shortcodes();

            if (is_file(plugin_dir_path(__FILE__) . 'partials/page_edit_template.php')) {
                include_once plugin_dir_path(__FILE__) . 'partials/page_edit_template.php';
            }
        } else {
            $wams_db = $this->wams_db;
            $wams_options = get_option('wams_notifications_options');
            if (is_file(plugin_dir_path(__FILE__) . 'partials/menu_main.php')) {
                include_once plugin_dir_path(__FILE__) . 'partials/menu_main.php';
            }
        }
    }

    public function register_menu_page()
    {
        add_menu_page(
            'WAMS Woo Notifier',
            $this->menu_title,
            'manage_options',
            $this->get_menu_url(),
            array($this, 'callback_menu_main_view'),
            'dashicons-whatsapp',
            null
        );
    }

    public function register_submenu_main_page()
    {
        add_submenu_page(
            $this->page_url,
            'WAMS Woo Notifier',
            'WAMS Woo Notifier',
            'manage_options',
            $this->get_menu_url(),
            array($this, "callback_menu_main_view"),
            null
        );
    }

    function callback_submenu_options_view()
    {
        if (is_file(plugin_dir_path(__FILE__) . 'partials/menu_options.php')) {
            include_once plugin_dir_path(__FILE__) . 'partials/menu_options.php';
        }
    }

    public function register_submenu_options_page()
    {
        add_submenu_page(
            $this->page_url,
            $this->get_menu_title('Options'),
            'Options',
            'manage_options',
            $this->get_menu_url('options'),
            array($this, 'callback_submenu_options_view')
        );
    }



    public function sanitize_options($options)
    {
        $sanitized = array();

        if (isset($options['wams_notifications_field_secret_key'])) {
            $sanitized['wams_notifications_field_secret_key'] = sanitize_text_field($options['wams_notifications_field_secret_key']);
        }
        if (isset($options['wams_notifications_field_sender_number'])) {
            $sanitized['wams_notifications_field_sender_number'] = sanitize_text_field($options['wams_notifications_field_sender_number']);
        }
        if (isset($options['wams_notifications_field_api_link'])) {
            $sanitized['wams_notifications_field_api_link'] = 'https://wams.aztify.com';
        }
        if (isset($options['wams_notifications_field_footer'])) {
            $sanitized['wams_notifications_field_footer'] = sanitize_text_field($options['wams_notifications_field_footer']);
        }
        if (isset($options['wams_notifications_field_full_response'])) {
            $sanitized['wams_notifications_field_full_response'] = sanitize_text_field($options['wams_notifications_field_full_response']);
        }

        add_settings_error('wams_notifications_main_messages', 'wams_notifications_updated', __('Settings Saved', 'wams-notifications'), 'updated');
        return $sanitized;
    }

    public function form_options_init()
    {

        register_setting(
            'wams_notifications_form_options',
            'wams_notifications_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_options')
            )
        );

        add_settings_section(
            'wams_notifications_section_credential',
            __('Configuration Credential', 'wams-notifications'),
            array($this, 'wams_notifications_section_credential_callback'),
            $this->get_menu_url('options')
        );

        add_settings_field(
            'wams_notifications_field_secret_key',
            __('API Key', 'wams-notifications'),
            array($this, 'wams_notifications_field_secret_key_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_credential',
            array(
                'label_for' => 'wams_notifications_field_secret_key',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_sender_number',
            __('Sender Number', 'wams-notifications'),
            array($this, 'wams_notifications_field_sender_number_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_credential',
            array(
                'label_for' => 'wams_notifications_field_sender_number',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_footer',
            __('Footer', 'wams-notifications'),
            array($this, 'wams_notifications_field_footer_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_credential',
            array(
                'label_for' => 'wams_notifications_field_footer',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_full_response',
            __('Full Response', 'wams-notifications'),
            array($this, 'wams_notifications_field_full_response_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_credential',
            array(
                'label_for' => 'wams_notifications_field_full_response',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );
    }

    public function wams_notifications_section_credential_callback($args)
    {
        ?>
        <p id="<?php echo esc_attr($args['id']); ?>">
            <?php esc_html_e('Please enter the Api Key from your (wams) site. If you do not have one, please register and set up your WhatsApp account as the message sender.', 'wams-notifications'); ?>
        </p>
        <?php
    }

    public function wams_notifications_field_secret_key_cb($args)
    {

        $options = get_option('wams_notifications_options');
        $options_value = !empty($options) ? $options[$args['label_for']] : null;

        ?>
        <input type="text" id="<?php echo esc_attr($args['label_for']); ?>"
            data-custom="<?php echo esc_attr($args['wams_notifications_custom_data']); ?>"
            name="wams_notifications_options[<?php echo esc_attr($args['label_for']); ?>]"
            value="<?php echo esc_attr($options_value) ?>" style="width: 50%">
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            Get the Api Key from the <b>Setting</b> -> <b>API Key</b> In the right menu at the top of your (wams) site.
        </p>
        <?php
    }

    public function wams_notifications_field_sender_number_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = !empty($options) ? @$options['wams_notifications_field_sender_number'] : '';
        ?>
        <input type="text" id="wams_notifications_field_sender_number"
            name="wams_notifications_options[wams_notifications_field_sender_number]" value="<?php echo esc_attr($value); ?>"
            style="width: 50%" placeholder="92">
        <?php
    }


    public function wams_notifications_field_footer_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = !empty($options) ? @$options['wams_notifications_field_footer'] : '';
        ?>
        <input type="text" id="wams_notifications_field_footer"
            name="wams_notifications_options[wams_notifications_field_footer]" value="<?php echo esc_attr($value); ?>"
            style="width: 50%" placeholder="Ex: --Bot">
        <?php
    }

    public function wams_notifications_field_full_response_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = !empty($options) ? @$options['wams_notifications_field_full_response'] : '0';
        ?>
        <select id="wams_notifications_field_full_response"
            name="wams_notifications_options[wams_notifications_field_full_response]" style="width: 50%">
            <option value="0" <?php selected($value, '0'); ?>>Disabled</option>
            <option value="1" <?php selected($value, '1'); ?>>Enabled</option>
        </select>
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            If enabled, the API will return full response data from WhatsApp.
        </p>
        <?php
    }

    public function register_handle_form_template()
    {
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-form-template.php';

        $form_template = new wams_Notifications_Admin_Form_Template($this->wams_notifications, $this->version);
        $form_template->handle_submit_edit_template();
    }

    public function wams_notifications_admin_notices()
    {
        if (
            isset($_GET['wams_notifications_status'], $_GET['_wpnonce']) &&
            'success' === sanitize_text_field(wp_unslash($_GET['wams_notifications_status'])) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wams_edit_template')
        ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Options saved successfully.', 'wams-notifications') . '</p></div>';
        }
    }

    public function register_handle_ajax_status_template()
    {
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-form-template.php';

        $form_template = new wams_Notifications_Admin_Form_Template($this->wams_notifications, $this->version);
        $form_template->handle_ajax_status_template();
    }

    public function register_wc_hooks()
    {
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-wc-hook.php';

        $wc = new wams_Notifications_Admin_Wc_Hook($this->wams_notifications, $this->version, $this->loader, $this->wams_db);
        $wc->register_hooks();
    }



    public function ajax_send_test_message()
    {
        check_ajax_referer('wams_ajax_nonce', 'nonce');

        $test_number = isset($_POST['test_number']) ? sanitize_text_field($_POST['test_number']) : '';
        if (empty($test_number)) {
            wp_send_json_error(['msg' => 'Test number is required.']);
        }

        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-api.php';
        $api = new wams_Notifications_Admin_Api($this->wams_notifications, $this->version);

        $options = get_option('wams_notifications_options');
        $sender = str_replace("+", "", isset($options['wams_notifications_field_sender_number']) ? $options['wams_notifications_field_sender_number'] : '');
        $api_key = isset($options['wams_notifications_field_secret_key']) ? $options['wams_notifications_field_secret_key'] : '';

        $response = $api->wams_post('send-message', [
            'api_key' => $api_key,
            'sender' => $sender,
            'number' => $test_number,
            'message' => 'Hello! This is a test message from WAMS Woo Notifier.'
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['msg' => $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body, true));
    }
}
