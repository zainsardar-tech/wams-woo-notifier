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

class wams_Notifications_Admin_Wc_Hook
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

    private $loader;
    private $wams_db;
    private $wams_api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $wams_notifications       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($wams_notifications, $version, $loader, $wams_db)
    {
        $this->wams_notifications = $wams_notifications;
        $this->version = $version;
        $this->loader = $loader;
        $this->wams_db = $wams_db;

        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-api.php';
        $this->wams_api = new wams_Notifications_Admin_Api($wams_notifications, $version);
    }

    /**
     * This function to handle register hook WooCommerce order
     */
    public function register_hooks()
    {
        $this->loader->add_action('woocommerce_order_status_pending', $this, 'handle_order_pending');
        $this->loader->add_action('woocommerce_order_status_processing', $this, 'handle_order_processing');
        $this->loader->add_action('woocommerce_order_status_on-hold', $this, 'handle_order_on_hold');
        $this->loader->add_action('woocommerce_order_status_completed', $this, 'handle_order_completed');
        $this->loader->add_action('woocommerce_order_status_cancelled', $this, 'handle_order_canceled');
        $this->loader->add_action('woocommerce_order_status_refunded', $this, 'handle_order_refunded');
        $this->loader->add_action('woocommerce_order_status_failed', $this, 'handle_order_failed');
        $this->loader->add_action('transition_post_status', $this, 'handle_product_publication', 10, 3);
        $this->loader->add_action('woocommerce_product_import_inserted_product_object', $this, 'handle_imported_product', 10, 2);
    }

    public function handle_imported_product($product, $is_new)
    {
        if ($is_new && $product->get_status() === 'publish') {
            $this->handle_product_notification($product);
        }
    }

    public function handle_order_pending($order_id)
    {
        $this->handle_order($order_id, 'WC_ORDER_PENDING');
    }
    public function handle_order_processing($order_id)
    {
        $this->handle_order($order_id, 'WC_ORDER_PROCESSING');
    }
    public function handle_order_on_hold($order_id)
    {
        $this->handle_order($order_id, 'WC_ORDER_ON_HOLD');
    }
    public function handle_order_completed($order_id)
    {
        $this->handle_order($order_id, 'WC_ORDER_COMPLETED');
    }
    public function handle_order_canceled($order_id)
    {
        $this->handle_order($order_id, 'WC_ORDER_CANCELED');
    }
    public function handle_order_refunded($order_id)
    {
        $this->handle_order($order_id, 'WC_ORDER_REFUNDED');
    }
    public function handle_order_failed($order_id)
    {
        $this->handle_order($order_id, 'WC_ORDER_FAILED');
    }

    /**
     * Handle product publication to send notifications
     */
    public function handle_product_publication($new_status, $old_status, $post)
    {
        if ($new_status !== 'publish' || $old_status === 'publish' || $post->post_type !== 'product') {
            return;
        }

        if (!function_exists('wc_get_product')) {
            return;
        }

        $product = wc_get_product($post->ID);
        if (!$product) {
            return;
        }

        $this->handle_product_notification($product);
    }

    /**
     * Common logic to send notification for a product
     */
    public function handle_product_notification($product)
    {
        $template_code = 'WC_PRODUCT_ADDED';
        $template_notification = $this->wams_db->get_content($template_code);

        if (!isset($template_notification) || $template_notification->is_active == 0) {
            return;
        }

        $message = $this->parse_product_template($template_notification->content, $product);

        // Get all customers, subscribers and administrators
        $users = get_users([
            'role__in' => ['customer', 'subscriber', 'administrator'],
            'fields' => 'ID',
        ]);

        if (empty($users)) {
            return;
        }

        foreach ($users as $user_id) {
            $phone = get_user_meta($user_id, 'billing_phone', true);
            if (empty($phone)) {
                $phone = get_user_meta($user_id, 'phone', true);
            }
            if (empty($phone)) {
                continue;
            }

            $country = get_user_meta($user_id, 'billing_country', true) ?: 'PK';

            if (!class_exists('\libphonenumber\PhoneNumberUtil')) {
                break;
            }

            try {
                $this->wams_send_message_direct($phone, $country, $message);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
    /**
     * This function for handle all event WooCommerce by shortcode and send notification to wams API.
     *
     * @param string $order_id from WooCommerce
     * @param string $template_code from list shortcodes
     */
    public function handle_order($order_id, $template_code)
    {
        $template_notification = $this->wams_db->get_content($template_code);
        if (!isset($template_notification)) {
            return;
        }
        if ($template_notification->is_active == 0) {
            return;
        }

        if (!function_exists('wc_get_order')) {
            return;
        }

        $wc_order = wc_get_order($order_id);
        if (!isset($wc_order)) {
            return;
        }

        $template_content = $this->parse_template_notification($template_notification->content, $wc_order);
        $this->wams_send_message($wc_order, $template_content);
    }

    public function wams_send_message_direct($phone, $country, $message)
    {
        if (!class_exists('\libphonenumber\PhoneNumberUtil')) {
            return false;
        }

        try {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $parse_phone = $phoneUtil->parse($phone, $country);
            $parse_phone_e = $phoneUtil->format($parse_phone, \libphonenumber\PhoneNumberFormat::E164);

            $options = get_option('wams_notifications_options');
            if (!$options)
                return false;

            $payload = [
                "api_key" => isset($options['wams_notifications_field_secret_key']) ? $options['wams_notifications_field_secret_key'] : '',
                "sender" => str_replace("+", "", isset($options['wams_notifications_field_sender_number']) ? $options['wams_notifications_field_sender_number'] : ''),
                "number" => str_replace("+", "", $parse_phone_e),
                "message" => $message,
                "footer" => isset($options['wams_notifications_field_footer']) ? $options['wams_notifications_field_footer'] : '',
                "full" => isset($options['wams_notifications_field_full_response']) ? (int) $options['wams_notifications_field_full_response'] : 0,
            ];

            return $this->wams_api->wams_post("send-message", $payload);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * This function for handle send notification to wamsAPI.
     *
     * @param array $wc_order_data get data from order WooCommerce
     * @param string $message to be send to end user
     */
    public function wams_send_message($wc_order, $message)
    {
        if (!class_exists('\libphonenumber\PhoneNumberUtil')) {
            return false;
        }

        $phone = $wc_order->get_billing_phone();
        $country = $wc_order->get_billing_country();

        if (empty($phone)) {
            return false;
        }

        try {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $parse_phone = $phoneUtil->parse($phone, $country);
            $parse_phone_e = $phoneUtil->format($parse_phone, \libphonenumber\PhoneNumberFormat::E164);

            $options = get_option('wams_notifications_options');
            if (!$options)
                return false;

            $payload = [
                "api_key" => isset($options['wams_notifications_field_secret_key']) ? $options['wams_notifications_field_secret_key'] : '',
                "sender" => str_replace("+", "", isset($options['wams_notifications_field_sender_number']) ? $options['wams_notifications_field_sender_number'] : ''),
                "number" => str_replace("+", "", $parse_phone_e),
                "message" => $message,
                "footer" => isset($options['wams_notifications_field_footer']) ? $options['wams_notifications_field_footer'] : '',
                "full" => isset($options['wams_notifications_field_full_response']) ? (int) $options['wams_notifications_field_full_response'] : 0,
            ];

            return $this->wams_api->wams_post("send-message", $payload);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * This function for getter WooCommerce shortcodes
     */
    public function get_shortcodes()
    {
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-shortcode.php';

        $shortcode = new wams_Notifications_Admin_Shortcode($this->wams_notifications, $this->version);
        return $shortcode->get_wc_shortcodes();
    }

    /**
     * This function for parse shortcode in template notification with data order WooCoomerce.
     *
     * @param   string  $template_content
     * @param   array   $wc_order data order WooCommerce
     */
    public function parse_template_notification($template_content, $wc_order)
    {
        $shortcodes = $this->get_shortcodes();
        $wc_order_data = $wc_order->get_data();
        $order_products = $this->get_products_from_order($wc_order);
        $new_template_content = $template_content;

        // Special codes for first product
        $first_product_codes = ['{Product_Name}', '{Product_Qty}'];

        foreach ($shortcodes as $key => $shortcode) {
            if (count($shortcode[1]) != 0) {
                $new_template_content = $this->replace_shortcode($new_template_content, $wc_order_data, $shortcode[0], $shortcode[1][0], $shortcode[1][1]);
            }
            if (in_array($shortcode[0], $first_product_codes)) {
                $new_template_content = $this->replace_shortcode_product($new_template_content, $order_products, $shortcode[0]);
            }
            if ($shortcode[0] == '{Products}') {
                $new_template_content = $this->replace_shortcode_products($new_template_content, $order_products, $shortcode[0]);
            }
        }

        return $new_template_content;
    }

    /**
     * Parse template for new product notification
     */
    public function parse_product_template($template_content, $product)
    {
        $new_template_content = $template_content;

        $description = $product->get_short_description();
        if (empty($description)) {
            $description = $product->get_description();
        }

        // Strip all HTML tags and decode entities
        $description = wp_strip_all_tags(html_entity_decode($description));
        // Remove any residual tags that might look like <en> or similar
        $description = preg_replace('/<[^>]*>/', '', $description);
        $description = trim($description);

        $replacements = [
            '{Product_Name}' => $product->get_name(),
            '{Product_Url}' => $product->get_permalink(),
            '{Product_Description}' => $description,
            '{Url}' => $product->get_permalink(),
            '{ID}' => $product->get_id(),
        ];

        foreach ($replacements as $shortcode => $value) {
            $new_template_content = str_replace($shortcode, $value, $new_template_content);
        }

        return $new_template_content;
    }
    /**
     * This function helper for parse_template_notification to handle replace shortcode with data WooCommerce
     *
     * @param   string  $template_content
     * @param   array   $wc_order_data get_data from order WooCommerce
     * @param   string  $code shortcode
     * @param   string  $parent key from shortcode index 1
     * @param   string  $children key from shortcode index 1
     *
     * @return  string  new template notification
     */
    public function replace_shortcode($template_content, $wc_order_data, $code, $parent, $children = null)
    {
        $wc_value = $wc_order_data[$parent];

        if ($children != '') {
            $wc_value = $wc_order_data[$parent][$children];
        }

        return str_replace($code, $wc_value, $template_content);
    }

    /**
     * This function is helper for parse_template_notification to handle replace shortcode single product with data single item product from WooCommerce.
     *
     * @param   string  $template_content
     * @param   array   $order_products from get list items or product from data Order WooCommerce
     * @param   string  $code from shortcode
     *
     * @return  string  new template notification
     *
     */
    public function replace_shortcode_product($template_content, $order_products, $code)
    {
        if (empty($order_products)) {
            return $template_content;
        }

        $order_product = $order_products[0];
        if (!isset($order_product)) {
            return $template_content;
        }

        $product_value = '';
        switch ($code) {
            case '{Product_Name}':
                $product_value = $order_product['name'];
                break;
            case '{Product_Qty}':
                $product_value = $order_product['quantity'];
                break;
            default:
                break;
        }
        if ($product_value == '') {
            return $template_content;
        }

        return str_replace($code, $product_value, $template_content);
    }

    /**
     * This function is helper for parse_template_notification to handle replace shorcode products with all data items from data order WooCommerce.
     *
     * @param   string  $template_content
     * @param   array   $order_products from get list items or product from data order WooCommerce
     * @param   string  $code from shortcode
     *
     * @return  string  new template notification
     */
    public function replace_shortcode_products($template_content, $order_products, $code)
    {
        if (empty($order_products)) {
            return $template_content;
        }

        $message_products = '';
        foreach ($order_products as $index => $order_product) {
            $number = $index + 1;
            $message_products .= "{$number}. {$order_product['name']} ({$order_product['quantity']} items)\n";
        }

        return str_replace($code, $message_products, $template_content);
    }

    /**
     * This function is helper for get products from data order WooCommerce
     *
     * @param   array   $wc_order_data get_data from data order WooCommerce
     *
     * @return  array   list products from data order WooCommerce
     */
    public function get_products_from_order($wc_order_data)
    {
        $products = [];

        foreach ($wc_order_data->get_items() as $item_id => $item) {
            $product = $item->get_product();

            if ($product) {
                $product_data = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total' => $item->get_total(),
                ];

                array_push($products, $product_data);
            }
        }

        return $products;
    }
}

?>