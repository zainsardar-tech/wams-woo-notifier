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
     * --- Product broadcast tuning -------------------------------------------------
     * The "New Product Published" template is not a transactional message, it is
     * a marketing broadcast. Sending it to the whole customer list in one request
     * (which is what this class used to do) gets the sender number rate-limited or
     * blocked by WhatsApp on any store with a few hundred users, so the broadcast
     * now runs through a WP-Cron queue with a batch size, a pause between batches
     * and a daily cap. All four values are configurable on the Options screen.
     */
    const DEFAULT_BATCH_SIZE = 5;
    const DEFAULT_INTERVAL   = 60;
    const DEFAULT_DAILY_CAP  = 200;

    /**
     * How long the worker waits between two messages inside the same batch.
     * Even in batches, back to back requests are unnecessarily aggressive.
     */
    const INNER_BATCH_PAUSE_MICROSECONDS = 400000;

    /** Country used to parse a phone number when the user has no billing country. */
    const DEFAULT_BROADCAST_COUNTRY = 'PK';

    /** WP option holding the pending broadcast queue (autoload disabled). */
    const QUEUE_OPTION = 'wams_notifications_broadcast_queue';

    /** WP option holding today's broadcast counters (used for the daily cap). */
    const STATS_OPTION = 'wams_notifications_broadcast_stats';

    /** WP option holding the last few delivery failures. */
    const FAILURES_OPTION = 'wams_notifications_failure_log';

    /** Transient lock that throttles the "notification failed" admin email. */
    const FAILURE_EMAIL_LOCK = 'wams_notifications_failure_email_lock';

    /** User meta storing the last product broadcast a user received (de-duplication). */
    const LAST_BROADCAST_META = 'wams_notifications_last_broadcast';

    /** Cron action fired to send the next broadcast batch. */
    const CRON_HOOK = 'wams_notifications_process_broadcast';

    /** Upper bound for the stored failure history. */
    const MAX_LOGGED_FAILURES = 20;

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

        // Needed for the opt-in check that gates the product broadcast.
        include_once plugin_dir_path(__FILE__) . 'class-wams-notifications-admin-optin.php';
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
     * Common logic to queue the notification for a product.
     *
     * @since    1.0.0
     * @since    1.3.0 No longer sends inline. The old implementation loaded every
     *                 customer and fired one API request per user inside the
     *                 publish request, with no consent check and no rate limit.
     *                 It now only queues the rendered message; the throttled
     *                 cron worker (process_broadcast_queue) delivers it in small
     *                 batches to opted-in users only.
     *
     * @param    WC_Product $product The product that was just published.
     */
    public function handle_product_notification($product)
    {
        $template_code = 'WC_PRODUCT_ADDED';
        $template_notification = $this->wams_db->get_content($template_code);

        if (!isset($template_notification) || $template_notification->is_active == 0) {
            return;
        }

        $message = $this->parse_product_template($template_notification->content, $product);

        $this->queue_broadcast($product->get_id(), $message);
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

        // The context travels into the failure log/admin email, so an admin can
        // tell which event and which order failed without digging in the DB.
        $this->wams_send_message($wc_order, $template_content, array(
            'type' => 'order',
            'template' => $template_code,
            'order_id' => (int) $order_id,
        ));
    }

    /**
     * Send a message to an arbitrary phone number (used by the product broadcast).
     *
     * @since    1.0.0
     * @since    1.3.0 Added the $context argument and failure reporting; the body
     *                 now lives in wams_dispatch().
     *
     * @param    string $phone   Phone number as stored on the user profile.
     * @param    string $country ISO country code used to parse national numbers.
     * @param    string $message Final message body.
     * @param    array  $context Extra data for the failure log/admin email.
     * @return   bool            True when WAMS accepted the message.
     */
    public function wams_send_message_direct($phone, $country, $message, $context = array())
    {
        return $this->wams_dispatch($phone, $country, $message, $context);
    }

    /**
     * This function for handle send notification to wamsAPI.
     *
     * @param array $wc_order_data get data from order WooCommerce
     * @param string $message to be send to end user
     */
    public function wams_send_message($wc_order, $message, $context = array())
    {
        if (!method_exists($wc_order, 'get_billing_phone')) {
            return false;
        }

        $phone = $wc_order->get_billing_phone();

        if (empty($phone)) {
            /*
             * No phone on the order means the customer will never receive that
             * notification. That is a real problem the admin should know about,
             * but it must not send one email per order, hence the "once"
             * (throttled) variant below.
             */
            $this->record_send_failure_once(
                'missing_phone',
                array_merge($context, array('reason' => __('Order notification skipped: the order has no billing phone number.', 'wams-notifications'))),
                '',
                __('Order has no billing phone number, so no WhatsApp message was sent.', 'wams-notifications')
            );

            return false;
        }

        $country = $wc_order->get_billing_country();

        return $this->wams_dispatch($phone, $country, $message, $context);
    }

    /**
     * Shared send path: build the payload, POST it, and report failures.
     *
     * @since    1.3.0
     * @param    string $phone   Phone number as stored on the order/user.
     * @param    string $country ISO country code used to parse the number.
     * @param    string $message Final message body.
     * @param    array  $context Extra data for the failure log/admin email.
     * @return   bool            True when WAMS accepted the message.
     */
    private function wams_dispatch($phone, $country, $message, $context = array())
    {
        $payload = $this->wams_build_payload($phone, $country, $message);

        if (is_wp_error($payload)) {
            /*
             * Configuration problems (missing library, missing API key, bad
             * number) are reported once per hour per error code: otherwise one
             * broken setting would add a log line and an email for every order.
             */
            $this->record_send_failure_once(
                $payload->get_error_code(),
                $context,
                $phone,
                $payload->get_error_message()
            );

            return false;
        }

        $response = $this->wams_api->wams_post('send-message', $payload);
        $error = $this->get_response_error($response);

        if ($error === false) {
            return true;
        }

        // A real delivery failure: always logged, admin emailed max every 15 min.
        $this->record_send_failure($context, $error, $phone);

        return false;
    }

    /**
     * Build the WAMS "send-message" payload.
     *
     * @since    1.3.0
     * @param    string $phone   Phone number as stored on the order/user.
     * @param    string $country ISO country code used to parse the number.
     * @param    string $message Final message body.
     * @return   array|WP_Error  Payload on success, WP_Error when a local
     *                           precondition (library/API key/phone) is not met.
     */
    private function wams_build_payload($phone, $country, $message)
    {
        if (!class_exists('\libphonenumber\PhoneNumberUtil')) {
            return new WP_Error(
                'libphonenumber_missing',
                __('The bundled libphonenumber library is missing, so phone numbers cannot be normalised and no message was sent.', 'wams-notifications')
            );
        }

        $options = get_option('wams_notifications_options');

        if (empty($options['wams_notifications_field_secret_key'])) {
            return new WP_Error(
                'missing_api_key',
                __('The WAMS API key is empty, so no message was sent.', 'wams-notifications')
            );
        }

        try {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $parse_phone = $phoneUtil->parse($phone, $country);
            $parse_phone_e = $phoneUtil->format($parse_phone, \libphonenumber\PhoneNumberFormat::E164);
        } catch (\Exception $e) {
            return new WP_Error('invalid_phone', $e->getMessage());
        }

        return array(
            'api_key' => $options['wams_notifications_field_secret_key'],
            'sender' => str_replace('+', '', isset($options['wams_notifications_field_sender_number']) ? $options['wams_notifications_field_sender_number'] : ''),
            'number' => str_replace('+', '', $parse_phone_e),
            'message' => $message,
            'footer' => isset($options['wams_notifications_field_footer']) ? $options['wams_notifications_field_footer'] : '',
            'full' => isset($options['wams_notifications_field_full_response']) ? (int) $options['wams_notifications_field_full_response'] : 0,
        );
    }

    /*
     * =====================================================================
     * Product broadcast queue: opt-in filtering + rate limiting
     * =====================================================================
     */

    /**
     * Queue a product broadcast message for throttled delivery.
     *
     * @since    1.3.0
     * @param    int    $product_id Product ID used for de-duplication.
     * @param    string $message    Rendered message body.
     * @return   bool               True when a new item was queued.
     */
    public function queue_broadcast($product_id, $message)
    {
        // Without an API key nothing could be delivered, so do not grow a queue.
        $options = get_option('wams_notifications_options');
        if (empty($options['wams_notifications_field_secret_key'])) {
            return false;
        }

        $queue = self::get_queue();

        if (empty($queue['created_at'])) {
            $queue['created_at'] = time();
        }

        // Never queue the same product twice (status flips, importer re-runs...).
        foreach ($queue['items'] as $item) {
            if ((int) $item['product_id'] === (int) $product_id) {
                return false;
            }
        }

        $queue['items'][] = array(
            'product_id' => (int) $product_id,
            'message' => (string) $message,
            'offset' => 0,
        );

        self::save_queue($queue);

        // Start the worker if it is not already waiting for its next run.
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 1, self::CRON_HOOK);
        }

        return true;
    }

    /**
     * Cron worker: send one batch, then reschedule itself.
     *
     * Registered on the wams_notifications_process_broadcast action.
     *
     * @since    1.3.0
     * @return   array|false Summary of the batch, or false when nothing was sent.
     */
    public function process_broadcast_queue()
    {
        $queue = self::get_queue();

        if (empty($queue['items'])) {
            delete_option(self::QUEUE_OPTION);
            return false;
        }

        $configured_batch = $this->get_number_setting('wams_notifications_field_broadcast_batch_size', self::DEFAULT_BATCH_SIZE, 1, 50);
        $batch_size = $configured_batch;
        $interval = $this->get_number_setting('wams_notifications_field_broadcast_interval', self::DEFAULT_INTERVAL, 10, HOUR_IN_SECONDS);
        $daily_cap = $this->get_number_setting('wams_notifications_field_broadcast_daily_limit', self::DEFAULT_DAILY_CAP, 0, 10000);

        $stats = self::get_stats();
        $capped = false;

        /*
         * Daily cap: pause instead of burning through the whole customer list in
         * one day. 0 means "no cap".
         */
        if ($daily_cap > 0) {
            $remaining_today = max(0, $daily_cap - (int) $stats['sent']);

            if ($remaining_today === 0) {
                $this->record_send_failure_once(
                    'broadcast_daily_cap',
                    array('type' => 'product_broadcast', 'reason' => __('Product broadcast paused: daily limit reached.', 'wams-notifications')),
                    '',
                    sprintf(
                        /* translators: %d: number of broadcast messages allowed per day. */
                        __('The daily product broadcast limit of %d messages has been reached. The queue is paused and will continue tomorrow.', 'wams-notifications'),
                        $daily_cap
                    )
                );

                wp_schedule_single_event(time() + $this->seconds_until_next_day(), self::CRON_HOOK);
                return false;
            }

            if ($remaining_today < $batch_size) {
                $batch_size = $remaining_today;
                $capped = true;
            }
        }

        $item = $queue['items'][0];
        $recipients = self::get_broadcast_recipients((int) $item['offset'], $batch_size);

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $user_id) {
            // Already received this exact product broadcast: skip. This keeps a
            // partially processed batch from sending duplicates if it is retried.
            if ((int) get_user_meta($user_id, self::LAST_BROADCAST_META, true) === (int) $item['product_id']) {
                continue;
            }

            $phone = $this->get_user_phone($user_id);
            if ($phone === '') {
                $failed++;
                continue;
            }

            $country = get_user_meta($user_id, 'billing_country', true);
            if (empty($country)) {
                $country = self::DEFAULT_BROADCAST_COUNTRY;
            }

            $ok = $this->wams_dispatch($phone, $country, $item['message'], array(
                'type' => 'product_broadcast',
                'product_id' => (int) $item['product_id'],
                'user_id' => (int) $user_id,
            ));

            if ($ok) {
                // Also acts as the delivery record for this product.
                update_user_meta($user_id, self::LAST_BROADCAST_META, (int) $item['product_id']);
                $sent++;
            } else {
                $failed++;
            }

            // Gentle pause between the messages of a single batch.
            usleep(self::INNER_BATCH_PAUSE_MICROSECONDS);
        }

        self::update_stats($sent, $failed);

        // Fewer rows than requested means the (opt-in filtered) user list is done.
        if (count($recipients) < $batch_size) {
            array_shift($queue['items']);
        } else {
            $queue['items'][0]['offset'] = (int) $item['offset'] + count($recipients);
        }

        // Queue drained: nothing left to schedule.
        if (empty($queue['items'])) {
            delete_option(self::QUEUE_OPTION);

            return array('sent' => $sent, 'failed' => $failed, 'pending_items' => 0);
        }

        self::save_queue($queue);

        // Normal case: wait `interval` seconds. When this batch used up the
        // daily budget, wait until tomorrow instead of hammering the API.
        $delay = $capped ? $this->seconds_until_next_day() : $interval;
        wp_schedule_single_event(time() + $delay, self::CRON_HOOK);

        return array('sent' => $sent, 'failed' => $failed, 'pending_items' => count($queue['items']));
    }

    /**
     * Read the normalised broadcast queue.
     *
     * @since    1.3.0
     * @return   array { created_at: int, items: array }
     */
    public static function get_queue()
    {
        $queue = get_option(self::QUEUE_OPTION, array());

        if (!is_array($queue) || !isset($queue['items']) || !is_array($queue['items'])) {
            return array('created_at' => 0, 'items' => array());
        }

        return $queue;
    }

    /**
     * Persist the broadcast queue (autoload disabled: only the worker reads it).
     *
     * @since    1.3.0
     * @param    array $queue Queue array.
     */
    private static function save_queue($queue)
    {
        update_option(self::QUEUE_OPTION, $queue, false);
    }

    /**
     * Fetch one page of broadcast recipients.
     *
     * Uses WP_User_Query pagination instead of loading every user at once, so a
     * store with 50k customers does not have to hold 50k IDs in memory.
     * When the opt-in requirement is on, the meta_query narrows the result set to
     * users that gave consent, which is what keeps a cold broadcast from going out.
     *
     * @since    1.3.0
     * @param    int $offset Offset to start from (matches the queue cursor).
     * @param    int $limit  Maximum number of users to return.
     * @return   array       List of user IDs.
     */
    public static function get_broadcast_recipients($offset, $limit)
    {
        $limit = (int) $limit;
        if ($limit < 1) {
            return array();
        }

        $args = array(
            'role__in' => array('customer', 'subscriber', 'administrator'),
            'fields' => 'ID',
            'number' => $limit,
            'offset' => (int) $offset,
            'orderby' => 'ID',
            'order' => 'ASC',
            'count_total' => false,
        );

        /*
         * The opt-in gate. Enabled by default: the broadcast is marketing, and
         * sending marketing to a customer list that never asked for it is both a
         * compliance problem and the quickest route to a WhatsApp block.
         */
        $options = get_option('wams_notifications_options');
        $optin_required = !isset($options['wams_notifications_field_require_optin']) || $options['wams_notifications_field_require_optin'] === '1';

        if ($optin_required) {
            $args['meta_query'] = array(
                array(
                    'key' => wams_Notifications_Admin_Optin::OPTIN_META,
                    'value' => '1',
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_User_Query($args);

        return array_map('intval', (array) $query->get_results());
    }

    /**
     * Read today's broadcast counters (reset automatically on a new day).
     *
     * @since    1.3.0
     * @return   array { date: string, sent: int, failed: int }
     */
    public static function get_stats()
    {
        $today = current_time('Y-m-d');
        $stats = get_option(self::STATS_OPTION, array());

        if (!is_array($stats) || !isset($stats['date']) || $stats['date'] !== $today) {
            $stats = array('date' => $today, 'sent' => 0, 'failed' => 0);
        }

        return $stats;
    }

    /**
     * Add the last batch result to today's counters.
     *
     * @since    1.3.0
     * @param    int $sent   Messages accepted by WAMS.
     * @param    int $failed Messages that could not be delivered.
     */
    private static function update_stats($sent, $failed)
    {
        $stats = self::get_stats();
        $stats['sent'] = (int) $stats['sent'] + (int) $sent;
        $stats['failed'] = (int) $stats['failed'] + (int) $failed;

        update_option(self::STATS_OPTION, $stats, false);
    }

    /**
     * Seconds until midnight (site time), used to pause a capped queue.
     *
     * @since    1.3.0
     * @return   int Minimum one minute so a cron pass can never spin.
     */
    private function seconds_until_next_day()
    {
        $now = current_time('timestamp');
        $tomorrow = strtotime('tomorrow', $now);

        return max(MINUTE_IN_SECONDS, (int) $tomorrow - (int) $now);
    }

    /**
     * Phone number stored on a user profile.
     *
     * @since    1.3.0
     * @param    int $user_id WordPress user ID.
     * @return   string Phone number, or an empty string when the user has none.
     */
    private function get_user_phone($user_id)
    {
        $phone = get_user_meta($user_id, 'billing_phone', true);

        if (empty($phone)) {
            $phone = get_user_meta($user_id, 'phone', true);
        }

        return is_string($phone) ? trim($phone) : '';
    }

    /**
     * Read a numeric setting with a default and hard bounds.
     *
     * @since    1.3.0
     * @param    string   $key     Option key inside wams_notifications_options.
     * @param    int      $default Fallback when the option is empty.
     * @param    int|null $min     Lower bound (null to skip).
     * @param    int|null $max     Upper bound (null to skip).
     * @return   int               Sanitised value.
     */
    private function get_number_setting($key, $default, $min = null, $max = null)
    {
        $options = get_option('wams_notifications_options');
        $value = (isset($options[$key]) && $options[$key] !== '') ? absint($options[$key]) : (int) $default;

        if ($min !== null && $value < $min) {
            $value = $min;
        }

        if ($max !== null && $value > $max) {
            $value = $max;
        }

        return (int) $value;
    }

    /**
     * Is a yes/no setting enabled? Defaults to "enabled" when never saved.
     *
     * @since    1.3.0
     * @param    string $key Option key inside wams_notifications_options.
     * @return   bool
     */
    private function is_switch_enabled($key)
    {
        $options = get_option('wams_notifications_options');
        $value = isset($options[$key]) && $options[$key] !== '' ? $options[$key] : '1';

        return (string) $value === '1';
    }

    /*
     * =====================================================================
     * Delivery failure reporting (log + admin notice + admin email)
     * =====================================================================
     */

    /**
     * Translate a WAMS API response into an error message.
     *
     * A "successful" HTTP round trip is not the same as a delivered message: the
     * API can answer 200 with status = false (invalid API key, sender not
     * connected, WhatsApp refused the message...). Both cases are treated as
     * failures so the admin is actually told about them.
     *
     * @since    1.3.0
     * @param    array|WP_Error $response Response from wams_post().
     * @return   string|false             Error text, or false when it succeeded.
     */
    private function get_response_error($response)
    {
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            return sprintf('HTTP %d %s', $code, wp_remote_retrieve_response_message($response));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body) || !array_key_exists('status', $body)) {
            return false;
        }

        $status = $body['status'];

        $rejected = ($status === false || $status === 0 || $status === '' || $status === '0' || $status === null);

        if (!$rejected && is_string($status)) {
            $rejected = in_array(strtolower($status), array('failed', 'error', 'false'), true);
        }

        if (!$rejected) {
            return false;
        }

        $message = isset($body['message']) ? $body['message'] : '';

        if (is_array($message)) {
            $message = wp_json_encode($message);
        }

        return ($message !== '') ? (string) $message : __('WAMS rejected the message.', 'wams-notifications');
    }

    /**
     * Store a delivery failure and notify the admin about it.
     *
     * @since    1.3.0
     * @param    array  $context Extra data (type/template/order_id/...).
     * @param    string $error   Error message from the API or from the local checks.
     * @param    string $phone   Recipient number (masked before it is stored).
     */
    private function record_send_failure($context, $error, $phone = '')
    {
        $entry = array(
            'time' => current_time('mysql'),
            'type' => isset($context['type']) ? $context['type'] : 'notification',
            'context' => $this->describe_context($context),
            'phone' => $this->mask_phone($phone),
            'error' => $this->truncate_text($error, 200),
        );

        // Bounded history: a broken gateway must not be able to fill the DB.
        $log = self::get_failure_log();
        array_unshift($log, $entry);
        $log = array_slice($log, 0, self::MAX_LOGGED_FAILURES);
        update_option(self::FAILURES_OPTION, $log, false);

        $this->maybe_email_admin_about_failure($entry);
    }

    /**
     * Record a failure only once per hour for the given error key.
     *
     * Used for configuration problems (no API key, missing library, order without
     * phone) that would otherwise repeat for every single order.
     *
     * @since    1.3.0
     * @param    string $key     Short identifier of the problem.
     * @param    array  $context Extra data (type/template/order_id/...).
     * @param    string $phone   Recipient number.
     * @param    string $error   Error message.
     */
    private function record_send_failure_once($key, $context, $phone, $error)
    {
        $lock = 'wams_notifications_notice_' . $key;

        if (get_transient($lock)) {
            return;
        }

        set_transient($lock, 1, HOUR_IN_SECONDS);

        $this->record_send_failure($context, $error, $phone);
    }

    /**
     * Email the admin about a failure, throttled to one email per 15 minutes.
     *
     * Without the throttle a broken API key would mail the admin on every order.
     *
     * @since    1.3.0
     * @param    array $entry Prepared failure log entry.
     */
    private function maybe_email_admin_about_failure($entry)
    {
        // Respect the "notify admin on failure" switch (enabled by default).
        if (!$this->is_switch_enabled('wams_notifications_field_notify_admin')) {
            return;
        }

        if (get_transient(self::FAILURE_EMAIL_LOCK)) {
            return;
        }

        set_transient(self::FAILURE_EMAIL_LOCK, 1, 15 * MINUTE_IN_SECONDS);

        $subject = sprintf(
            /* translators: %s: site name. */
            __('[%s] WhatsApp notification failed', 'wams-notifications'),
            wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
        );

        $lines = array(
            __('A WhatsApp notification could not be delivered by WAMS Woo Notifier.', 'wams-notifications'),
            '',
            sprintf(__('Event: %s', 'wams-notifications'), $entry['context']),
            sprintf(
                __('Recipient number: %s', 'wams-notifications'),
                ($entry['phone'] !== '') ? $entry['phone'] : __('unknown', 'wams-notifications')
            ),
            sprintf(__('Error: %s', 'wams-notifications'), $entry['error']),
            sprintf(__('Time: %s', 'wams-notifications'), $entry['time']),
            '',
            sprintf(__('Site: %s', 'wams-notifications'), home_url()),
            sprintf(__('Settings: %s', 'wams-notifications'), admin_url('admin.php?page=wams-notifications-options')),
        );

        // A wp_mail() failure is not reported again: that would recurse.
        wp_mail($this->get_admin_notification_email(), $subject, implode("\n", $lines));
    }

    /**
     * Where should failure emails go?
     *
     * @since    1.3.0
     * @return   string Valid email address.
     */
    private function get_admin_notification_email()
    {
        $options = get_option('wams_notifications_options');
        $configured = isset($options['wams_notifications_field_notify_email']) ? $options['wams_notifications_field_notify_email'] : '';

        if (!empty($configured) && is_email($configured)) {
            return $configured;
        }

        // Fall back to the site administration email.
        return get_option('admin_email');
    }

    /**
     * Stored failure history, newest first.
     *
     * @since    1.3.0
     * @return   array List of failure entries.
     */
    public static function get_failure_log()
    {
        $log = get_option(self::FAILURES_OPTION, array());

        return is_array($log) ? $log : array();
    }

    /**
     * Clear the stored failure history (used by the admin notice dismiss link).
     *
     * @since    1.3.0
     */
    public static function clear_failure_log()
    {
        delete_option(self::FAILURES_OPTION);
    }

    /**
     * Human readable description of what failed, used in the log and the email.
     *
     * @since    1.3.0
     * @param    array $context Context array passed to the send methods.
     * @return   string
     */
    private function describe_context($context)
    {
        if (!empty($context['reason'])) {
            return $context['reason'];
        }

        $type = isset($context['type']) ? $context['type'] : '';

        if ($type === 'order') {
            return sprintf(
                /* translators: 1: template code, 2: order ID. */
                __('Order notification (%1$s, order #%2$d)', 'wams-notifications'),
                isset($context['template']) ? $context['template'] : __('unknown template', 'wams-notifications'),
                isset($context['order_id']) ? (int) $context['order_id'] : 0
            );
        }

        if ($type === 'product_broadcast') {
            return sprintf(
                /* translators: 1: product ID, 2: user ID. */
                __('New product broadcast (product #%1$d, user #%2$d)', 'wams-notifications'),
                isset($context['product_id']) ? (int) $context['product_id'] : 0,
                isset($context['user_id']) ? (int) $context['user_id'] : 0
            );
        }

        return __('WhatsApp notification', 'wams-notifications');
    }

    /**
     * Mask a phone number before it is written to the log.
     *
     * Keeps the last three digits so an admin can still recognise the recipient
     * without storing a full customer number in a plain option.
     *
     * @since    1.3.0
     * @param    string $phone Phone number.
     * @return   string Masked number (e.g. *******322).
     */
    private function mask_phone($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        $length = strlen($digits);

        if ($length === 0) {
            return '';
        }

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 3) . substr($digits, -3);
    }

    /**
     * Trim a string to a maximum length for log/email storage.
     *
     * @since    1.3.0
     * @param    string $text   Raw text.
     * @param    int    $length Maximum length.
     * @return   string
     */
    private function truncate_text($text, $length)
    {
        $text = trim(wp_strip_all_tags((string) $text));

        if (function_exists('mb_strlen')) {
            return (mb_strlen($text) > $length) ? mb_substr($text, 0, $length) . '...' : $text;
        }

        return (strlen($text) > $length) ? substr($text, 0, $length) . '...' : $text;
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