=== wams Notifications ===
Contributors: Zain Sardar
link: https://wams.aztify.com
Tags: notifications, whatsapp, woocommerce
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.3.0
Requires PHP: 7.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Automatically send WhatsApp notifications for WooCommerce purchases, ensuring your customers stay updated with instant order status alerts.

== Description ==

### wams Notifications: Free WhatsApp Notifications for WooCommerce orders ###

**[English]**
This plugin can send notifications to your customers' WhatsApp numbers when there is a change in the order status, such as when the order status changes to shipped, processed, or other statuses. You can also customize the notifications messages according to your preference for each type of order status.

You can also use your personal WhatsApp number as the message sender. First, register a wams account and add your WhatsApp number to send messages. After that, you will receive a secret key that will be used by this plugin. For more details, see [wams Plugin Wordpress Notifications](https://wams.aztify.com).

Don't worry, this plugin and service are free.

Since 1.3.0 the "New Product Published" message is treated as marketing: it is only sent to customers who opted in on their account page (or that you ticked on their user profile), it is delivered in small batches with a pause between them, and it stops for the day once a daily limit is reached. Order status notifications are transactional and are still sent immediately. If a message cannot be sent you now get an admin notice, an email and a delivery log on the Options page.

**[Indonesia 🇮🇩]**
Plugin ini dapat mengirim notifikasi ke nomor whatsapp pelanggan anda ketika ada perubahan status pada pesanan, misalnya pesanan berubah
ke pengiriman, diproses atau status lainnya. Anda juga bisa mengubah pesan notifikasi sesuai dengan keinginan anda untuk setiap tipe status pesanan.

Anda juga bisa menggunakan nomor whatsapp pribadi anda sebagai pengirim pesan. Daftar akun wams terlebih dahulu dan tambahkan nomor whatsapp
untuk mengirim pesan, setelah itu anda akan mendapatkan secret key yang nanti digunakan oleh plugin ini, simak lebih detail di [wams Plugin Wordpress Notifications](https://wams.aztify.com)

Jangan khawatir, plugin dan layanan ini gratis.

**📣 How To Set Up Your WhatsApp as Sender & Get Secret Key**

 <a href="https://wams.aztify.com" target="_blank">🔑 How to Setup Your WhatsApp as Sender & Get Secret Key</a> | <a href="https://wams.aztify.com" target="_blank">🪄 How to Setup Plugin</a>

== Installation ==

1. Upload `wams-notifications.zip` to `Plugins->Add Plugin->Upload Plugins`.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to `wams Notif` -> `Options` and set `Secret Key` from your wams, if don't have please see [wams](https://wams.aztify.com)
5. Go to `wams Notif` -> `General` to edit template notifications

== Frequently Asked Questions ==

= Whose WhatsApp number or account will you use later? =

You can use your personal WhatsApp number or WhatsApp Business as the message sender.

= Is this Free WhatsApp API Service official from WhatsApp? =

No, we do not officially collaborate with WhatsApp to connect our system with them.

= Is there a possibility that my WhatsApp account could be blocked (banned)? =

Anyone who violates WhatsApp's rules or guidelines will have the potential to be blocked. For example, sending massive messages will be considered spam, sending messages that contain unlawful content (gambling, sex, pornography) and reports from many users will cause your WhatsApp account to be blocked.

= Will the new product message be sent to all of my customers? =

No. It is only sent to customers who opted in, and it goes out in throttled batches. You can disable the opt in requirement in `wams Notif` -> `Options` under `Notification Safety & Limits`, but keep in mind that sending to a customer list that never agreed is a common reason for a WhatsApp number to be limited or blocked.

= How do I know when a notification fails to send? =

You receive an admin notice in WordPress and an email, and the recent failures are listed on `wams Notif` -> `Options` under `Delivery Health` with the error message. Emails are limited to one every 15 minutes.

= Where can I get the API Key? =

Please visit your wams website and click `setting` in the drop-down menu at the top right and you will find your `API Key`.

= Is this service free? =

Yes, 100%.

If you are still experiencing difficulties or problems, please contact us via email aztifywams@gmail.com or visit the free version of the [Contact Us] (https://wams.aztify.com/contact) site.

== Screenshots ==

1. Create your message template according to the order status conditions
2. Create a message template and combine it with the shortcodes that we have provided
3. Enter your API key and the plugin is ready to use

== Changelog ==

= 1.3.0 =
* Added: opt in for the new product broadcast, with a checkbox on the WooCommerce account page and on the user profile.
* Added: throttled broadcast delivery with a batch size, a pause between batches and a daily limit.
* Added: delivery failure detection with a failure log, an admin notice and a throttled failure email.
* Added: a Delivery Health card on the Options page showing recent failures and the broadcast counters.
* Changed: the new product broadcast now runs from a queue instead of sending every message during the publish request.
* Fixed: the plugin header version now matches the internal version constant.

= 1.2.0 =
* Fixed wordPress security requirements. 

= 1.0.0 =
* First release to public
