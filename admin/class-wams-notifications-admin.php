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

    /**
     * The WooCommerce hook handler.
     *
     * Kept as a property (since 1.3.0) so the broadcast cron worker and the
     * delivery health card can reach it without creating a second instance.
     *
     * @since    1.3.0
     * @access   private
     * @var      wams_Notifications_Admin_Wc_Hook
     */
    private $wc_hook;

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

        /*
         * --- Notification safety fields (1.3.0) ---
         * Every value is cast and clamped here as well as in the worker, so even a
         * hand crafted POST cannot push the batch size or the interval into a
         * range that would spam WhatsApp.
         */
        if (isset($options['wams_notifications_field_require_optin'])) {
            $sanitized['wams_notifications_field_require_optin'] = ($options['wams_notifications_field_require_optin'] === '1') ? '1' : '0';
        }

        if (isset($options['wams_notifications_field_broadcast_batch_size'])) {
            $sanitized['wams_notifications_field_broadcast_batch_size'] = (string) min(50, max(1, absint($options['wams_notifications_field_broadcast_batch_size'])));
        }

        if (isset($options['wams_notifications_field_broadcast_interval'])) {
            $sanitized['wams_notifications_field_broadcast_interval'] = (string) min(HOUR_IN_SECONDS, max(10, absint($options['wams_notifications_field_broadcast_interval'])));
        }

        if (isset($options['wams_notifications_field_broadcast_daily_limit'])) {
            $sanitized['wams_notifications_field_broadcast_daily_limit'] = (string) min(10000, absint($options['wams_notifications_field_broadcast_daily_limit']));
        }

        if (isset($options['wams_notifications_field_notify_admin'])) {
            $sanitized['wams_notifications_field_notify_admin'] = ($options['wams_notifications_field_notify_admin'] === '1') ? '1' : '0';
        }

        if (isset($options['wams_notifications_field_notify_email'])) {
            $notify_email = sanitize_email($options['wams_notifications_field_notify_email']);

            // A typo here would silently drop every failure alert, so reject it loudly.
            if ($notify_email !== '' && !is_email($notify_email)) {
                add_settings_error('wams_notifications_main_messages', 'wams_notifications_invalid_email', __('The failure notification email is not a valid address, so it was not saved.', 'wams-notifications'), 'error');
                $notify_email = '';
            }

            $sanitized['wams_notifications_field_notify_email'] = $notify_email;
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

        /*
         * --- Notification safety section (1.3.0) -------------------------------
         * These fields control the two problems reported by store owners:
         *   1. the product broadcast going out to customers who never agreed to it,
         *   2. no signal at all when a notification fails to send.
         */
        add_settings_section(
            'wams_notifications_section_safety',
            __('Notification Safety & Limits', 'wams-notifications'),
            array($this, 'wams_notifications_section_safety_callback'),
            $this->get_menu_url('options')
        );

        add_settings_field(
            'wams_notifications_field_require_optin',
            __('Require opt-in', 'wams-notifications'),
            array($this, 'wams_notifications_field_require_optin_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_safety',
            array(
                'label_for' => 'wams_notifications_field_require_optin',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_broadcast_batch_size',
            __('Broadcast batch size', 'wams-notifications'),
            array($this, 'wams_notifications_field_broadcast_batch_size_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_safety',
            array(
                'label_for' => 'wams_notifications_field_broadcast_batch_size',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_broadcast_interval',
            __('Pause between batches', 'wams-notifications'),
            array($this, 'wams_notifications_field_broadcast_interval_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_safety',
            array(
                'label_for' => 'wams_notifications_field_broadcast_interval',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_broadcast_daily_limit',
            __('Daily broadcast limit', 'wams-notifications'),
            array($this, 'wams_notifications_field_broadcast_daily_limit_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_safety',
            array(
                'label_for' => 'wams_notifications_field_broadcast_daily_limit',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_notify_admin',
            __('Email me on failure', 'wams-notifications'),
            array($this, 'wams_notifications_field_notify_admin_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_safety',
            array(
                'label_for' => 'wams_notifications_field_notify_admin',
                'class' => 'wams_notifications_row',
                'wams_notifications_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'wams_notifications_field_notify_email',
            __('Failure notification email', 'wams-notifications'),
            array($this, 'wams_notifications_field_notify_email_cb'),
            $this->get_menu_url('options'),
            'wams_notifications_section_safety',
            array(
                'label_for' => 'wams_notifications_field_notify_email',
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

    /**
     * Description for the "Notification Safety & Limits" section.
     *
     * Shows the two numbers that make the feature observable: how many customers
     * actually opted in, and how much of the broadcast queue is still pending.
     *
     * @since    1.3.0
     * @param    array $args Settings section arguments.
     */
    public function wams_notifications_section_safety_callback($args)
    {
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-optin.php';
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-wc-hook.php';

        $opted_in = class_exists('wams_Notifications_Admin_Optin') ? wams_Notifications_Admin_Optin::count_opted_in() : 0;
        $queue = class_exists('wams_Notifications_Admin_Wc_Hook') ? wams_Notifications_Admin_Wc_Hook::get_queue() : array('items' => array());
        $stats = class_exists('wams_Notifications_Admin_Wc_Hook') ? wams_Notifications_Admin_Wc_Hook::get_stats() : array('sent' => 0, 'failed' => 0);
        ?>
        <p id="<?php echo esc_attr($args['id']); ?>">
            <?php esc_html_e('New Product Published messages are marketing, so they are only sent to customers who opted in, and they are delivered in small throttled batches. Order status notifications stay transactional and are never delayed or filtered.', 'wams-notifications'); ?>
        </p>
        <p>
            <?php
            printf(
                /* translators: 1: number of opted-in users, 2: messages sent today, 3: messages pending in the queue. */
                esc_html__('Opted-in customers: %1$d &middot; Broadcast messages sent today: %2$d &middot; Items still queued: %3$d', 'wams-notifications'),
                (int) $opted_in,
                (int) $stats['sent'],
                (int) count($queue['items'])
            );
            ?>
        </p>
        <?php
        if ((int) $opted_in === 0) {
            ?>
            <p class="description">
                <?php esc_html_e('Nobody has opted in yet. Once opt-in is enabled, the broadcast reaches only customers who tick the checkbox on their account page (or that you tick on their user profile).', 'wams-notifications'); ?>
            </p>
            <?php
        }
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

    /**
     * Opt-in gate switch.
     *
     * Enabled by default: the "New Product Published" template is marketing and
     * must not be sent to customers who never asked for it.
     *
     * @since    1.3.0
     * @param    array $args Settings field arguments.
     */
    public function wams_notifications_field_require_optin_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = (isset($options['wams_notifications_field_require_optin']) && $options['wams_notifications_field_require_optin'] !== '') ? $options['wams_notifications_field_require_optin'] : '1';
        ?>
        <select id="wams_notifications_field_require_optin"
            name="wams_notifications_options[wams_notifications_field_require_optin]" style="width: 50%">
            <option value="1" <?php selected($value, '1'); ?>>Enabled</option>
            <option value="0" <?php selected($value, '0'); ?>>Disabled</option>
        </select>
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            If enabled, the new product broadcast is sent only to users who opted in. Order status
            notifications are transactional and are always sent, regardless of this setting.
        </p>
        <?php
    }

    /**
     * How many broadcast messages are sent per cron run.
     *
     * @since    1.3.0
     * @param    array $args Settings field arguments.
     */
    public function wams_notifications_field_broadcast_batch_size_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = (isset($options['wams_notifications_field_broadcast_batch_size']) && $options['wams_notifications_field_broadcast_batch_size'] !== '') ? absint($options['wams_notifications_field_broadcast_batch_size']) : wams_Notifications_Admin_Wc_Hook::DEFAULT_BATCH_SIZE;
        ?>
        <input type="number" min="1" max="50" id="wams_notifications_field_broadcast_batch_size"
            name="wams_notifications_options[wams_notifications_field_broadcast_batch_size]"
            value="<?php echo esc_attr($value); ?>" style="width: 120px">
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            Number of WhatsApp messages sent per batch (1 - 50). Keep this small: the sender number is
            the one that gets rate-limited or blocked when it sends too fast.
        </p>
        <?php
    }

    /**
     * Pause between two batches, in seconds.
     *
     * @since    1.3.0
     * @param    array $args Settings field arguments.
     */
    public function wams_notifications_field_broadcast_interval_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = (isset($options['wams_notifications_field_broadcast_interval']) && $options['wams_notifications_field_broadcast_interval'] !== '') ? absint($options['wams_notifications_field_broadcast_interval']) : wams_Notifications_Admin_Wc_Hook::DEFAULT_INTERVAL;
        ?>
        <input type="number" min="10" max="3600" id="wams_notifications_field_broadcast_interval"
            name="wams_notifications_options[wams_notifications_field_broadcast_interval]"
            value="<?php echo esc_attr($value); ?>" style="width: 120px"> seconds
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            How long the queue waits before sending the next batch (10 - 3600). Batches are processed by
            WP-Cron, so the site needs some traffic for the queue to move.
        </p>
        <?php
    }

    /**
     * Maximum broadcast messages per day (protects the WhatsApp number).
     *
     * @since    1.3.0
     * @param    array $args Settings field arguments.
     */
    public function wams_notifications_field_broadcast_daily_limit_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = (isset($options['wams_notifications_field_broadcast_daily_limit']) && $options['wams_notifications_field_broadcast_daily_limit'] !== '') ? absint($options['wams_notifications_field_broadcast_daily_limit']) : wams_Notifications_Admin_Wc_Hook::DEFAULT_DAILY_CAP;
        ?>
        <input type="number" min="0" max="10000" id="wams_notifications_field_broadcast_daily_limit"
            name="wams_notifications_options[wams_notifications_field_broadcast_daily_limit]"
            value="<?php echo esc_attr($value); ?>" style="width: 120px">
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            Once this many broadcast messages have gone out in a day, the queue pauses and continues
            tomorrow. Set 0 for no limit (not recommended).
        </p>
        <?php
    }

    /**
     * Failure email switch.
     *
     * @since    1.3.0
     * @param    array $args Settings field arguments.
     */
    public function wams_notifications_field_notify_admin_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = (isset($options['wams_notifications_field_notify_admin']) && $options['wams_notifications_field_notify_admin'] !== '') ? $options['wams_notifications_field_notify_admin'] : '1';
        ?>
        <select id="wams_notifications_field_notify_admin"
            name="wams_notifications_options[wams_notifications_field_notify_admin]" style="width: 50%">
            <option value="1" <?php selected($value, '1'); ?>>Enabled</option>
            <option value="0" <?php selected($value, '0'); ?>>Disabled</option>
        </select>
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            When a WhatsApp message cannot be sent, it is logged below and an email is sent to the
            address underneath (at most one email every 15 minutes, so a broken API key cannot flood
            your inbox). A notice also appears in the WordPress admin.
        </p>
        <?php
    }

    /**
     * Where failure emails are sent. Empty falls back to the site admin email.
     *
     * @since    1.3.0
     * @param    array $args Settings field arguments.
     */
    public function wams_notifications_field_notify_email_cb($args)
    {
        $options = get_option('wams_notifications_options');
        $value = !empty($options['wams_notifications_field_notify_email']) ? $options['wams_notifications_field_notify_email'] : '';
        ?>
        <input type="email" id="wams_notifications_field_notify_email"
            name="wams_notifications_options[wams_notifications_field_notify_email]"
            value="<?php echo esc_attr($value); ?>" style="width: 50%"
            placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
        <p class="description" style="font-size: 12px; padding: 0 0 0 3px;">
            Leave empty to use the WordPress administration email address.
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

        $this->render_failure_notice();
    }

    /**
     * Warn the admin when WhatsApp notifications have failed to send.
     *
     * Previously a failed order notification disappeared without a trace: the
     * customer got nothing and nobody was told. Failures are now collected by
     * wams_Notifications_Admin_Wc_Hook and surfaced here on every admin screen,
     * with a link to the full list on the Options page and a dismiss link.
     *
     * @since    1.3.0
     */
    private function render_failure_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-wc-hook.php';

        if (!class_exists('wams_Notifications_Admin_Wc_Hook')) {
            return;
        }

        $failures = wams_Notifications_Admin_Wc_Hook::get_failure_log();

        if (empty($failures)) {
            return;
        }

        $latest = $failures[0];
        $dismiss_url = wp_nonce_url(
            add_query_arg('wams_dismiss_failures', '1'),
            'wams_dismiss_failures'
        );
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('WAMS Woo Notifier: WhatsApp messages failed to send.', 'wams-notifications'); ?></strong>
            </p>
            <p>
                <?php
                printf(
                    /* translators: 1: number of recent failures, 2: what failed, 3: error message, 4: date and time. */
                    esc_html__('%1$d failure(s) recorded. Latest: %2$s - %3$s (at %4$s).', 'wams-notifications'),
                    (int) count($failures),
                    esc_html($latest['context']),
                    esc_html($latest['error']),
                    esc_html($latest['time'])
                );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wams-notifications-options')); ?>">
                    <?php esc_html_e('Review the delivery log', 'wams-notifications'); ?>
                </a>
                &nbsp;|&nbsp;
                <a href="<?php echo esc_url($dismiss_url); ?>"><?php esc_html_e('Dismiss', 'wams-notifications'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle the "Dismiss" link of the failure notice.
     *
     * Clears the stored failure log after a nonce and capability check.
     *
     * @since    1.3.0
     */
    public function handle_dismiss_failure_notice()
    {
        if (!isset($_GET['wams_dismiss_failures'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, 'wams_dismiss_failures')) {
            return;
        }

        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-wc-hook.php';

        if (class_exists('wams_Notifications_Admin_Wc_Hook')) {
            wams_Notifications_Admin_Wc_Hook::clear_failure_log();
        }

        // Reload the screen without the query argument so the notice disappears.
        wp_safe_redirect(remove_query_arg(array('wams_dismiss_failures', '_wpnonce')));
        exit;
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

        // Keep the instance: the cron worker below runs on the same object, so the
        // queue logic, settings and failure log all share one place.
        $this->wc_hook = $wc;
    }

    /**
     * Register the opt-in hooks (consent collection).
     *
     * @since    1.3.0
     */
    public function register_optin_hooks()
    {
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-optin.php';

        $optin = new wams_Notifications_Admin_Optin($this->wams_notifications, $this->version);
        $optin->register_hooks($this->loader);
    }

    /**
     * Cron callback: send the next product broadcast batch.
     *
     * Hooked to wams_notifications_process_broadcast. WP-Cron fires it once, the
     * worker sends one batch of opted-in recipients and schedules the next run
     * itself, so a store with a large list never sends everything at once.
     *
     * @since    1.3.0
     */
    public function process_broadcast_queue()
    {
        if (!$this->wc_hook instanceof wams_Notifications_Admin_Wc_Hook) {
            return;
        }

        $this->wc_hook->process_broadcast_queue();
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
