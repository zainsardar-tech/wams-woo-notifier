<?php

/**
 * Consent (opt-in) management for the "New Product Published" broadcast.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * The product broadcast used to be sent to *every* user with the roles
 * customer/subscriber/administrator that had a phone number in their profile.
 * That is a cold, non-consented broadcast: it annoys customers and, more
 * importantly, it is the fastest way to get a WhatsApp sender number
 * rate-limited or blocked by WhatsApp when a store has thousands of users.
 *
 * This class stores the customer's consent in user meta and provides the two
 * places where that consent is collected:
 *
 *   1. WooCommerce > My Account > Account details - the real, customer facing
 *      opt-in checkbox (this is the consent we actually want).
 *   2. Users > Edit user - an admin facing checkbox so an existing customer list
 *      can be migrated manually without touching the database.
 *
 * The broadcast itself only asks "is this user opted in?" (see
 * wams_Notifications_Admin_Wc_Hook::get_broadcast_recipients()), so a store can
 * also collect consent somewhere else and simply store the same user meta key.
 *
 * @link       https://wams.aztify.com
 * @since      1.3.0
 *
 * @package    wams_Notifications
 * @subpackage wams_Notifications/admin
 */

/**
 * Opt-in / consent storage and the form fields that collect it.
 *
 * @since      1.3.0
 * @package    wams_Notifications
 * @subpackage wams_Notifications/admin
 */
class wams_Notifications_Admin_Optin
{
    /**
     * User meta key holding the consent flag.
     *
     * Stored as the string '1' when the user agreed and as an empty string when
     * they did not, so that a simple meta_query meta_key + meta_value='1' lookup
     * (used for the paginated recipient query) matches exactly the opted-in users.
     *
     * @since    1.3.0
     * @access   public
     * @var      string
     */
    const OPTIN_META = 'wams_notifications_optin';

    /**
     * The ID of this plugin.
     *
     * @since    1.3.0
     * @access   private
     * @var      string
     */
    private $wams_notifications;

    /**
     * The version of this plugin.
     *
     * @since    1.3.0
     * @access   private
     * @var      string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.3.0
     * @param    string $wams_notifications The name of this plugin.
     * @param    string $version            The version of this plugin.
     */
    public function __construct($wams_notifications, $version)
    {
        $this->wams_notifications = $wams_notifications;
        $this->version = $version;
    }

    /**
     * Register every hook that collects or saves consent.
     *
     * The loader is passed in (instead of being injected in the constructor) to
     * follow the pattern already used by register_wc_hooks().
     *
     * @since    1.3.0
     * @param    wams_Notifications_Loader $loader The plugin hook loader.
     */
    public function register_hooks($loader)
    {
        /*
         * Admin side: a checkbox on the user profile screens. WordPress core
         * already verifies the update-user nonce and the edit_user capability
         * before these actions fire, so we only need to re-check the capability
         * (done inside the callbacks below).
         */
        $loader->add_action('show_user_profile', $this, 'render_profile_field');
        $loader->add_action('edit_user_profile', $this, 'render_profile_field');
        $loader->add_action('personal_options_update', $this, 'save_profile_field');
        $loader->add_action('edit_user_profile_update', $this, 'save_profile_field');

        /*
         * Customer side: the WooCommerce "Account details" form. This is the
         * consent we actually rely on for the broadcast. WooCommerce verifies the
         * nonce before firing woocommerce_save_account_details.
         */
        $loader->add_action('woocommerce_edit_account_form', $this, 'render_account_field');
        $loader->add_action('woocommerce_save_account_details', $this, 'save_account_field');
    }

    /**
     * Is the given user opted in to broadcast notifications?
     *
     * Static helper so the broadcast queue (which runs from WP-Cron, outside of
     * the current screen context) can use it without an instance.
     *
     * @since    1.3.0
     * @param    int $user_id WordPress user ID.
     * @return   bool        True when the user gave consent.
     */
    public static function is_opted_in($user_id)
    {
        return (bool) get_user_meta((int) $user_id, self::OPTIN_META, true);
    }

    /**
     * Store the consent flag for a user.
     *
     * @since    1.3.0
     * @param    int  $user_id  WordPress user ID.
     * @param    bool $opted_in True to opt in, false to opt out.
     */
    public static function set_optin($user_id, $opted_in)
    {
        // Store '1' or '' (never delete) so opt-outs are explicit and auditable.
        update_user_meta((int) $user_id, self::OPTIN_META, $opted_in ? '1' : '');
    }

    /**
     * How many users have opted in so far.
     *
     * Used on the Options screen to make it obvious that the broadcast audience
     * is (or is not) actually being filled by customers.
     *
     * @since    1.3.0
     * @return   int Number of opted-in users.
     */
    public static function count_opted_in()
    {
        $query = new WP_User_Query(array(
            'fields' => 'ID',
            'number' => 1,
            'count_total' => true,
            'meta_query' => array(
                array(
                    'key' => self::OPTIN_META,
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        ));

        return (int) $query->get_total();
    }

    /**
     * Render the opt-in checkbox on the WordPress user profile screens.
     *
     * @since    1.3.0
     * @param    WP_User $user The user being edited.
     */
    public function render_profile_field($user)
    {
        if (!current_user_can('manage_options') || !($user instanceof WP_User)) {
            return;
        }

        $opted_in = self::is_opted_in($user->ID);
        ?>
        <h2><?php esc_html_e('WAMS Woo Notifier', 'wams-notifications'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th>
                    <label for="wams_notifications_optin">
                        <?php esc_html_e('WhatsApp broadcasts', 'wams-notifications'); ?>
                    </label>
                </th>
                <td>
                    <label for="wams_notifications_optin">
                        <input name="wams_notifications_optin" type="checkbox" id="wams_notifications_optin" value="1"
                            <?php checked($opted_in); ?> />
                        <?php esc_html_e('Send this user WhatsApp updates when a new product is published.', 'wams-notifications'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Only users that are ticked here receive the "New Product Published" broadcast. Order status notifications are transactional and are always sent.', 'wams-notifications'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the profile opt-in checkbox.
     *
     * An unchecked checkbox is simply absent from $_POST, which is exactly the
     * "opt out" case we want, so no hidden companion field is needed.
     *
     * @since    1.3.0
     * @param    int $user_id The user being saved.
     */
    public function save_profile_field($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $opted_in = !empty($_POST['wams_notifications_optin']);
        self::set_optin($user_id, $opted_in);
    }

    /**
     * Render the opt-in checkbox on WooCommerce > My Account > Account details.
     *
     * @since    1.3.0
     */
    public function render_account_field()
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $opted_in = self::is_opted_in($user_id);
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input type="checkbox"
                    class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                    name="wams_notifications_optin" value="1" <?php checked($opted_in); ?> />
                <span><?php esc_html_e('Send me WhatsApp updates when new products are published.', 'wams-notifications'); ?></span>
            </label>
        </p>
        <?php
    }

    /**
     * Save the account page opt-in checkbox.
     *
     * @since    1.3.0
     * @param    int $user_id The user whose account details were saved.
     */
    public function save_account_field($user_id)
    {
        if (!$user_id) {
            return;
        }

        $opted_in = !empty($_POST['wams_notifications_optin']);
        self::set_optin($user_id, $opted_in);
    }
}
