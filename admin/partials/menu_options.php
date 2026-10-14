<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wams.aztify.com
 * @since      1.0.0
 *
 * @package    wams_Notifications
 * @subpackage wams_Notifications/admin/partials
 */

?>

<?php
if (!defined('ABSPATH'))
  exit;
settings_errors('wams_notifications_main_messages');
settings_errors('wams_notifications_options_messages');

if (!function_exists('is_plugin_active')) {
  include_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$wc_installed = file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php');
$wc_active = class_exists('WooCommerce') && (is_plugin_active('woocommerce/woocommerce.php') || (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('woocommerce/woocommerce.php')));
?>
<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
</div>

<div class="wams-options wrap" data-wc-ready="<?php echo $wc_active ? '1' : '0'; ?>">
  <?php if (!$wc_active): ?>
    <div class="wams-alert wams-alert-warning">
      <button type="button" class="wams-clsbutton" aria-label="Close">&times;</button>
      <div class="wams-alert-header">WooCommerce is required</div>
      <div class="wams-alert-content">
        <?php if ($wc_installed): ?>
          <p>WooCommerce is installed but not active. Please <a
              href="<?php echo esc_url(admin_url('plugins.php')); ?>">activate WooCommerce</a> to use wams Notifications.
          </p>
        <?php else: ?>
          <p>WooCommerce is not installed. Please <a
              href="<?php echo esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')); ?>">install
              WooCommerce</a> and activate it to use wams Notifications.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="wams-toolbar">
    <div class="wams-toolbar-left">
      <span class="dashicons dashicons-admin-generic"></span>
      <div class="wams-toolbar-title">Options</div>
    </div>
    <div class="wams-toolbar-right">
      <button type="button" id="wams-save-top" class="button button-primary" <?php echo $wc_active ? '' : 'disabled title="Disabled until WooCommerce is active"'; ?>>Save</button>
    </div>
  </div>

  <form action="options.php" method="post" id="wams-options-form">
    <?php settings_fields('wams_notifications_form_options'); ?>
    <div class="wams-card <?php echo $wc_active ? '' : 'wams-card-disabled'; ?>">
      <div class="wams-card-header">
        <div class="wams-card-title">General Settings</div>
        <div class="wams-card-subtitle">Configure your wams Notification options</div>
      </div>
      <div class="wams-card-content">
        <?php do_settings_sections('wams-notifications-options'); ?>
      </div>
      <div class="wams-card-footer">
        <?php submit_button(__('Save Settings', 'wams-notifications')); ?>
      </div>
    </div>
  </form>

  <?php
  /*
   * Delivery health card (added in 1.3.0).
   *
   * A failed order notification used to be invisible: the API call returned
   * false and the customer simply never received anything. The hook class now
   * keeps a short failure log, and this card shows it together with the state of
   * the product broadcast queue (opt-in + throttling), so the link in the admin
   * notice has somewhere to land.
   */
  include_once plugin_dir_path(dirname(__FILE__)) . 'class-wams-notifications-admin-wc-hook.php';
  $wams_failures = class_exists('wams_Notifications_Admin_Wc_Hook') ? wams_Notifications_Admin_Wc_Hook::get_failure_log() : array();
  $wams_stats = class_exists('wams_Notifications_Admin_Wc_Hook') ? wams_Notifications_Admin_Wc_Hook::get_stats() : array('sent' => 0, 'failed' => 0);
  $wams_queue = class_exists('wams_Notifications_Admin_Wc_Hook') ? wams_Notifications_Admin_Wc_Hook::get_queue() : array('items' => array());
  $wams_clear_url = wp_nonce_url(add_query_arg('wams_dismiss_failures', '1'), 'wams_dismiss_failures');
  ?>
  <div class="wams-card">
    <div class="wams-card-header">
      <div class="wams-card-title">Delivery Health</div>
      <div class="wams-card-subtitle">
        <?php
        printf(
          /* translators: 1: messages sent today, 2: messages failed today, 3: items waiting in the broadcast queue. */
          esc_html__('Broadcast today: %1$d sent, %2$d failed - items queued: %3$d', 'wams-notifications'),
          (int) $wams_stats['sent'],
          (int) $wams_stats['failed'],
          (int) count($wams_queue['items'])
        );
        ?>
      </div>
    </div>
    <div class="wams-card-content">
      <?php if (empty($wams_failures)): ?>
        <p class="muted"><?php esc_html_e('No delivery failure recorded. Order notifications and broadcasts are going out normally.', 'wams-notifications'); ?></p>
      <?php else: ?>
        <table class="widefat striped">
          <thead>
            <tr>
              <th><?php esc_html_e('Time', 'wams-notifications'); ?></th>
              <th><?php esc_html_e('Event', 'wams-notifications'); ?></th>
              <th><?php esc_html_e('Recipient', 'wams-notifications'); ?></th>
              <th><?php esc_html_e('Error', 'wams-notifications'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($wams_failures as $wams_failure): ?>
              <tr>
                <td><?php echo esc_html($wams_failure['time']); ?></td>
                <td><?php echo esc_html($wams_failure['context']); ?></td>
                <td><?php echo esc_html($wams_failure['phone'] !== '' ? $wams_failure['phone'] : '-'); ?></td>
                <td><?php echo esc_html($wams_failure['error']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p style="margin: 10px 0 0;">
          <a class="button" href="<?php echo esc_url($wams_clear_url); ?>"><?php esc_html_e('Clear log', 'wams-notifications'); ?></a>
        </p>
      <?php endif; ?>
    </div>
  </div>

  <div class="wams-card <?php echo $wc_active ? '' : 'wams-card-disabled'; ?>">
    <div class="wams-card-header">
      <div class="wams-card-title">Test Notification</div>
      <div class="wams-card-subtitle">Send a test message to verify your settings</div>
    </div>
    <div class="wams-card-content">
      <table class="form-table">
        <tr>
          <th><label for="wams-test-number">Test Phone Number</label></th>
          <td>
            <input type="text" id="wams-test-number" class="regular-text" placeholder="e.g. 62888xxxx">
            <p class="description">Include country code without + (e.g., 62 for Indonesia, 1 for USA)</p>
          </td>
        </tr>
      </table>
    </div>
    <div class="wams-card-footer">
      <button type="button" id="wams-send-test" class="button button-secondary" <?php echo $wc_active ? '' : 'disabled'; ?>>Send Test Message</button>
      <div id="wams-test-loader" class="spinner" style="float: none; margin: 10px;"></div>
    </div>
    <div id="wams-test-result" style="padding: 10px 16px; display: none;"></div>
  </div>


</div>

<style>
  .wams-options {
    --gap: 16px;
    margin-top: 10px
  }

  .wams-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 12px
  }

  .wams-toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px
  }

  .wams-toolbar-title {
    font-weight: 600
  }

  .wams-card {
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 12px
  }

  .wams-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc
  }

  .wams-card-title {
    font-weight: 700
  }

  .wams-card-subtitle {
    color: #64748b;
    font-size: 12px
  }

  .wams-card-content {
    padding: 14px 16px
  }

  .wams-card-footer {
    padding: 12px 16px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    background: #fafafa
  }

  .wams-card-disabled {
    opacity: .6
  }

  .form-table th {
    width: 260px
  }

  .form-table th,
  .form-table td {
    padding: 12px 10px
  }

  .form-table input[type="text"],
  .form-table input[type="password"],
  .form-table input[type="url"],
  .form-table input[type="email"],
  .form-table textarea,
  .form-table select {
    max-width: 520px
  }

  .muted {
    color: #64748b
  }

  .wams-alert {
    border: 1px solid transparent;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 12px;
    position: relative
  }

  .wams-alert .wams-clsbutton {
    position: absolute;
    right: 10px;
    top: 8px;
    background: transparent;
    border: 0;
    font-size: 18px;
    cursor: pointer;
    line-height: 1
  }

  .wams-alert-warning {
    background: #fffbeb;
    border-color: #fde68a
  }

  .wams-alert-header {
    font-weight: 700;
    margin-bottom: 4px;
    color: #92400e
  }

  .wams-alert-content {
    color: #7c2d12
  }
</style>

<script>
  (function () {
    var form = document.getElementById('wams-options-form');
    var saveTop = document.getElementById('wams-save-top');
    var wrapper = document.querySelector('.wams-options');
    var wcReady = wrapper && wrapper.getAttribute('data-wc-ready') === '1';

    if (saveTop) {
      saveTop.addEventListener('click', function () {
        if (!wcReady) return;
        if (form) form.requestSubmit();
      });
    }

    if (!wcReady && form) {
      var inputs = form.querySelectorAll('input, select, textarea, button');
      for (var i = 0; i < inputs.length; i++) { inputs[i].disabled = true; }
    }

    var closeBtns = document.querySelectorAll('.wams-clsbutton');
    if (closeBtns && closeBtns.length) {
      for (var j = 0; j < closeBtns.length; j++) {
        closeBtns[j].addEventListener('click', function () { this.parentElement.style.display = 'none'; });
      }
    }
  })();
</script>