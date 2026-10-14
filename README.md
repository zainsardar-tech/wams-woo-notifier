# WAMS Woo Notifier

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/download/)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-blue.svg)](https://www.php.net/)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0.html)

Automatically send **WhatsApp notifications** for WooCommerce purchases, ensuring your customers stay updated with instant order status alerts.

## 🚀 Overview

**WAMS Woo Notifier** allows you to bridge the communication gap with your customers by sending order updates directly to their WhatsApp. Whether an order is placed, processed, or shipped, your customers will receive timely notifications, improving transparency and trust.

### Key Features
- **Instant Notifications:** Automatically trigger WhatsApp messages on order status changes.
- **Customizable Templates:** Tailor messages for each order status (Pending, Processing, Completed, etc.).
- **Consent Based Broadcasts:** The new product announcement goes only to customers who opted in, never to the whole customer list.
- **Safe Sending Rates:** Announcements are delivered in small batches with a pause between them and a daily cap, which keeps your WhatsApp number away from rate limits.
- **Delivery Alerts:** If a message cannot be sent, it is logged, shown in the WordPress admin and emailed to you.
- **Personal Number Integration:** Use your own WhatsApp number as the sender via the WAMS service.
- **Developer Friendly:** Clean code and easy setup.
- **100% Free:** Both the plugin and the WAMS service are free to use.

## 🛠 Installation

1.  **Download:** Download the plugin ZIP file.
2.  **Upload:** Go to `Plugins -> Add New -> Upload Plugin` in your WordPress dashboard and upload the ZIP.
3.  **Activate:** Click "Activate" after the upload is complete.
4.  **Configure API:**
    *   Register for a free account at [wams.aztify.com](https://wams.aztify.com).
    *   Add your WhatsApp number as a sender to get your **Secret Key**.
    *   Navigate to `WAMS Notif -> Options` in WordPress and enter your Secret Key.
5.  **Set Templates:** Go to `WAMS Notif -> General` to customize your notification messages.

## 📖 Broadcasts, Consent and Sending Limits

Order status notifications are transactional: they are always sent right away, and nothing below affects them.

The **New Product Published** template is different. It is marketing, so it is treated carefully:

- **Opt in is required by default.** A customer is only included once they tick the checkbox on `My Account -> Account details`. You can also tick the same checkbox while editing a user under `Users`.
  Consent is stored in the `wams_notifications_optin` user meta, so consent collected somewhere else (a checkout field, a form plugin) can be written to the same key.
- **The message is delivered in batches, not in one shot.** A publish action only queues the message.
  A background job (WP-Cron) then sends a small batch at a time. Defaults are 5 messages per batch with a 60 second pause, and a daily limit of 200 messages.
- **The queue pauses instead of pushing through.** When the daily limit is reached the remaining messages wait until the next day.

You can change all of it in `WAMS Notif -> Options` under **Notification Safety & Limits**. If you would rather keep the old behaviour and message every customer with a phone number, set **Require opt-in** to `Disabled` (batching and the daily limit still apply).

Because the queue runs on WP-Cron, the broadcast moves forward as your site gets traffic. Publishing a product no longer blocks the request, which is intentional on stores with large customer lists.

## 📖 Delivery Alerts

When WhatsApp or the WAMS API rejects a message, or when the phone number cannot be used, the failure is no longer silent:

- A warning notice appears in the WordPress admin with the most recent error.
- An email goes to the address in **Failure notification email** (the site admin email is used when this is empty). Emails are throttled to one every 15 minutes, and repeated configuration problems such as an empty API key or an order without a phone number are reported at most once per hour.
- The last 20 failures are listed on the Options page under **Delivery Health**, together with today's sent and failed counters and the number of messages still queued. Phone numbers are masked there.

## 📖 FAQ

### Whose WhatsApp account will be used?
You use your own personal WhatsApp or WhatsApp Business account. Our system connects to your device to route messages.

### Is this service official from WhatsApp?
No, we are not officially affiliated with WhatsApp. We provide a bridge for automation using your own device.

### Can my account be blocked?
As with any automated tool, you must follow WhatsApp's guidelines. Avoid sending spam or unsolicited messages. Using it for transaction-based order notifications is generally safe.

### Where do I get the API Key?
Log in to [wams.aztify.com](https://wams.aztify.com), click on your profile/settings in the top right, and you will find your **API Key**.

### Do my customers get the new product message without asking?
No. Since 1.3.0 that message is only sent to customers who opted in, and it is delivered in small throttled batches. You can turn the opt in requirement off in `Options`, but for a store with a large customer list it is better to keep it on.

### A notification was not delivered. How do I find out?
Open `WAMS Notif -> Options` and check the **Delivery Health** card. It lists the recent failures with the reason, and you also receive an email about the first failure. Common causes are an API key that is not set, a sender number that is not connected on the WAMS side, and orders that have no billing phone number.

## 🗒️ Changelog

### 1.3.0
- Added a per user opt in for the new product broadcast, plus a checkbox on the WooCommerce account page and on the user profile.
- Added throttled broadcast delivery: batch size, pause between batches and a daily limit, all configurable under `Notification Safety & Limits`.
- Added delivery failure detection, a failure log, an admin notice and a throttled failure email.
- Added a `Delivery Health` card on the Options page with the recent failures and the broadcast counters.
- Changed the product broadcast to run from a queue instead of sending every message during the publish request.
- Fixed the plugin header version, which did not match the internal version constant.
