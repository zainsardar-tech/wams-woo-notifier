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

class wams_Notifications_Admin_Shortcode
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
    }

    /**
     * This function to get list WooCommerce shortcodes
     *
     * @return array Shortcodes
     */
    public function get_wc_shortcodes()
    {
        $res = array(
            [
                "{ID}",
                ['id', ''],
                "Data order ID"
            ],
            [
                "{Currency}",
                ['currency', ''],
                "Data order currency"
            ],
            [
                "{CreatedAt}",
                ['date_created', ''],
                "Data order created"
            ],
            [
                "{Discount_Total}",
                ['discount_total', ''],
                "Data total discount"
            ],
            [
                "{Discount_Tax}",
                ['discount_tax', ''],
                "Data discount tax"
            ],
            [
                "{Shipping_Total}",
                ['shipping_total', ''],
                "Data total shipping"
            ],
            [
                "{Shipping_Tax}",
                ['shipping_tax', ''],
                "Data shipping tax"
            ],
            [
                "{Total}",
                ['total', ''],
                "Data total price"
            ],
            [
                "{Total_Tax}",
                ['total_tax', ''],
                "Data total tax price"
            ],
            [
                "{Billing_FirstName}",
                ['billing', 'first_name'],
                "Data first name from billing"
            ],
            [
                "{Billing_LastName}",
                ['billing', 'last_name'],
                "Data last name from billing"
            ],
            [
                "{Billing_Company}",
                ['billing', 'company'],
                "Data company from billing"
            ],
            [
                "{Billing_Address1}",
                ['billing', 'address_1'],
                "Data address 1 from billing"
            ],
            [
                "{Billing_Address2}",
                ['billing', 'address_2'],
                "Data address 2 from billing"
            ],
            [
                "{Billing_City}",
                ['billing', 'city'],
                "Data city from billing"
            ],
            [
                "{Billing_State}",
                ['billing', 'state'],
                "Data state from billing"
            ],
            [
                "{Billing_PostCode}",
                ['billing', 'postcode'],
                "Data postcode from billing"
            ],
            [
                "{Billing_Country}",
                ['billing', 'country'],
                "Data country from billing"
            ],
            [
                "{Billing_Email}",
                ['billing', 'email'],
                "Data email from billing"
            ],
            [
                "{Billing_Phone}",
                ['billing', 'phone'],
                "Data phone from billing"
            ],
            [
                "{Shipping_FirstName}",
                ['shipping', 'first_name'],
                "Data first name from shipping"
            ],
            [
                "{Shipping_LastName}",
                ['shipping', 'last_name'],
                "Data last name from shipping"
            ],
            [
                "{Shipping_Company}",
                ['shipping', 'company'],
                "Data company from shipping"
            ],
            [
                "{Shipping_Address1}",
                ['shipping', 'address_1'],
                "Data address 1 from shipping"
            ],
            [
                "{Shipping_Address2}",
                ['shipping', 'address_2'],
                "Data address 2 from shipping"
            ],
            [
                "{Shipping_City}",
                ['shipping', 'city'],
                "Data city from shipping"
            ],
            [
                "{Shipping_State}",
                ['shipping', 'state'],
                "Data state from shipping"
            ],
            [
                "{Shipping_PostCode}",
                ['shipping', 'postcode'],
                "Data postcode from shipping"
            ],
            [
                "{Shipping_Country}",
                ['shipping', 'country'],
                "Data country from shipping"
            ],
            [
                "{Shipping_Phone}",
                ['shipping', 'phone'],
                "Data phone from shipping"
            ],
            [
                "{Payment_Method}",
                ['payment_method', ''],
                "Data payment method or symbol (ex: COD, VA)"
            ],
            [
                "{Payment_Method_Title}",
                ['payment_method_title', ''],
                "Data payment method name (ex: Cash On Delivery)"
            ],
            [
                "{Customer_Note}",
                ['customer_note', ''],
                "Data customer note when checkout"
            ],
            [
                "{PaidAt}",
                ['date_paid', ''],
                "Data paid date or completed payment"
            ],
            [
                "{Number}",
                ['number', ''],
                "Data order number like order ID"
            ],
            [
                "{Product_Name}",
                [],
                "Get only first item product name from order"
            ],
            [
                "{Product_Qty}",
                [],
                "Get only first item product quantity from order"
            ],
            [
                "{Products}",
                [],
                "Get all order items (products) from order, the default display is order list."
            ],
            [
                "{Product_Url}",
                [],
                "Get product permanent link (URL)"
            ],
            [
                "{Product_Description}",
                [],
                "Get product short description"
            ],
            [
                "{Url}",
                [],
                "Get product URL (shorthand)"
            ]
        );

        return $res;
    }
}
?>