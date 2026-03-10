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

if (!function_exists('is_plugin_active')) {
  include_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$wc_installed = file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php');
$wc_active = class_exists('WooCommerce') && (is_plugin_active('woocommerce/woocommerce.php') || (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('woocommerce/woocommerce.php')));

$is_secret_key_set = isset($wams_options['wams_notifications_field_secret_key']) && trim($wams_options['wams_notifications_field_secret_key']) != '';
?>
<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
</div>

<div class="wams-admin-list wrap" data-wc-ready="<?php echo $wc_active ? '1' : '0'; ?>">
  <?php if (!$is_secret_key_set): ?>
    <div class="wams-alert wams-alert-danger">
      <button type="button" class="wams-clsbutton" aria-label="Close">&times;</button>
      <div class="wams-alert-header">[Action Required] Secret Key is not set.</div>
      <div class="wams-alert-content">
        <p>The secret key must be set for message notification to work. Please visit the <b><a
              href="<?php echo esc_url(admin_url('admin.php?page=wams-notifications-options')) ?>">Options</a></b> page
          to configure the Secret Key.</p>
      </div>
    </div>
  <?php endif; ?>

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
      <div class="wams-title">WAMS Woo Notifier</div>
    </div>
    <div class="wams-toolbar-right">
      <input type="search" id="wams-filter" class="regular-text wams-toolbar-right" placeholder="Filter events...">
      <button type="button" id="wams-enable-all" class="button" <?php echo $wc_active ? '' : 'disabled'; ?>>Enable
        all</button>
      <button type="button" id="wams-disable-all" class="button" <?php echo $wc_active ? '' : 'disabled'; ?>>Disable
        all</button>
    </div>
  </div>

  <form action="options.php" method="post" id="wams-main-form">
    <div class="wams-card <?php echo $wc_active ? '' : 'wams-card-disabled'; ?>">
      <div class="wams-wp-loading-overlay" id="wams_loading_overlay">
        <div class="wams-spinner"></div>Loading...
      </div>
      <table class="widefat striped wams-wp-table wams-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Status</th>
            <th style="width:160px">Action</th>
          </tr>
        </thead>
        <tbody id="wams-table-body">
          <?php foreach ($template_contents as $key => $content): ?>
            <?php $current_content_db = $wams_db->get_content($content['code']); ?>
            <tr>
              <td class="wams-evt">
                <div class="wams-evt-title"><?php echo esc_html($content['title']) ?></div>
                <div class="wams-evt-meta"><?php echo esc_html($content['code']) ?></div>
              </td>
              <td>
                <label class="wams-wp-switch">
                  <input class="wams-content-status" type="checkbox" name="<?php echo esc_attr($content['code']) ?>"
                    <?php if (!empty($current_content_db)) {
                      if ($current_content_db->is_active == '1')
                        echo 'checked';
                    } ?>
                    <?php echo $wc_active ? '' : 'disabled'; ?>
                    title="<?php echo $wc_active ? '' : 'Disabled until WooCommerce is active'; ?>">
                  <span class="wams-wp-slider round"></span>
                </label>
              </td>
              <td>
                <a class="button button-primary wams-edit-btn <?php echo $wc_active ? '' : 'is-disabled'; ?>"
                  href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wams-notifications&template_code=' . urlencode($content['code'])), 'wams_edit_template')); ?>"
                  <?php echo $wc_active ? '' : 'tabindex="-1" aria-disabled="true"'; ?>
                  title="<?php echo $wc_active ? 'Edit Template' : 'Enable WooCommerce to edit templates'; ?>">
                  <span class="dashicons dashicons-edit"></span>
                  Edit Template
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>

<style>
  .wams-admin-list {
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

  .wams-title {
    font-weight: 600
  }

  .wams-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px
  }

  .wams-card {
    position: relative;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 10px;
    overflow: hidden
  }

  .wams-card-disabled {
    opacity: .6
  }

  .wams-card-disabled .wams-edit-btn {
    pointer-events: none
  }

  .wams-table thead th {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0
  }

  .wams-table td,
  .wams-table th {
    vertical-align: middle
  }

  .wams-evt-title {
    font-weight: 600
  }

  .wams-evt-meta {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px
  }

  .wams-edit-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px
  }

  .wams-edit-btn.is-disabled {
    pointer-events: none;
    opacity: .6
  }

  .wams-edit-btn .dashicons {
    line-height: 1;
    transform: translateY(1px)
  }

  .wams-wp-loading-overlay {
    position: absolute;
    inset: 0;
    background-color: rgba(255, 255, 255, .75);
    display: none;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
    z-index: 5
  }

  .wams-wp-loading-overlay.show {
    display: flex
  }

  .wams-spinner {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    border-top-color: #3b82f6;
    animation: wams-spin 1s linear infinite
  }

  @keyframes wams-spin {
    to {
      transform: rotate(360deg)
    }
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

  .wams-alert-danger {
    background: #fef2f2;
    border-color: #fecaca
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

  .wams-alert-danger .wams-alert-header {
    color: #991b1b
  }

  .wams-alert-content {
    color: #7c2d12
  }

  .wams-alert-danger .wams-alert-content {
    color: #7f1d1d
  }

  .wams-wp-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px
  }

  .wams-wp-switch input {
    opacity: 0;
    width: 0;
    height: 0
  }

  .wams-wp-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e2e8f0;
    transition: .2s;
    border-radius: 24px
  }

  .wams-wp-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .2s;
    border-radius: 50%
  }

  .wams-wp-switch input:checked+.wams-wp-slider {
    background-color: #22c55e
  }

  .wams-wp-switch input:checked+.wams-wp-slider:before {
    transform: translateX(20px)
  }

  @media (max-width:900px) {
    .wams-toolbar {
      flex-direction: column;
      align-items: flex-start
    }

    .wams-toolbar-right {
      width: 100%
    }

    .wams-toolbar-right input {
      flex: 1
    }
  }
</style>

<script>
  (function () {
    var filter = document.getElementById('wams-filter');
    var tbody = document.getElementById('wams-table-body');
    var rows = tbody ? tbody.querySelectorAll('tr') : [];
    var enableAll = document.getElementById('wams-enable-all');
    var disableAll = document.getElementById('wams-disable-all');
    var overlay = document.getElementById('wams_loading_overlay');
    var closeBtns = document.querySelectorAll('.wams-clsbutton');
    var wcReady = document.querySelector('.wams-admin-list')?.getAttribute('data-wc-ready') === '1';

    function setOverlay(v) { if (overlay) { overlay.classList.toggle('show', !!v); } }

    function doFilter(q) {
      if (!rows) return;
      var qq = (q || '').toLowerCase();
      for (var i = 0; i < rows.length; i++) {
        var t = rows[i].querySelector('.wams-evt-title')?.textContent.toLowerCase() || '';
        var m = rows[i].querySelector('.wams-evt-meta')?.textContent.toLowerCase() || '';
        rows[i].style.display = (t.includes(qq) || m.includes(qq)) ? 'table-row' : 'none';
      }
    }

    function bulkToggle(toState) {
      if (!wcReady) return;
      if (!tbody) return;
      setOverlay(true);
      var inputs = tbody.querySelectorAll('input.wams-content-status[type="checkbox"]:not([disabled])');
      for (var i = 0; i < inputs.length; i++) {
        var el = inputs[i];
        if (!!el.checked !== toState) {
          el.checked = toState;
          var evt = document.createEvent('HTMLEvents');
          evt.initEvent('change', true, false);
          el.dispatchEvent(evt);
        }
      }
      setTimeout(function () { setOverlay(false); }, 500);
    }

    if (filter) { filter.addEventListener('input', function () { doFilter(this.value); }); }
    if (enableAll) { enableAll.addEventListener('click', function () { bulkToggle(true); }); }
    if (disableAll) { disableAll.addEventListener('click', function () { bulkToggle(false); }); }
    if (closeBtns && closeBtns.length) { for (var i = 0; i < closeBtns.length; i++) { closeBtns[i].addEventListener('click', function () { this.parentElement.style.display = 'none'; }); } }

    if (!wcReady) {
      if (tbody) {
        tbody.addEventListener('click', function (e) {
          var isCheckbox = e.target && e.target.matches('input.wams-content-status[type="checkbox"]');
          var isEdit = e.target && (e.target.closest('.wams-edit-btn'));
          if (isCheckbox) { e.preventDefault(); e.stopPropagation(); }
          if (isEdit) { e.preventDefault(); }
        }, true);
      }
    }
  })();
</script>