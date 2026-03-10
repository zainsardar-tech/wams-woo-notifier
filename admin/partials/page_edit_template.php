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
if ( ! defined( 'ABSPATH' ) ) exit;

$parse_db_content = null;
if (!empty($current_content_db)) {
    if ($current_content_db->content != null) {
        $parse_db_content = $current_content_db->content;
    }
}
?>
<div class="wrap">
  <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
  <div class="wams-page-subtitle">Edit Template: <?php echo esc_html( $current_content['title'] ) ?></div>
  <?php if ( function_exists('settings_errors') ) { settings_errors(); } ?>
</div>

<div class="wams-admin wrap">
  <div class="wams-header">
    <div class="wams-title-block">
      <div class="wams-title">Template Editor</div>
      <div class="wams-breadcrumb"><span><?php echo esc_html( $current_content['title'] ) ?></span></div>
    </div>
    <div class="wams-actions">
      <button type="button" class="button" onclick="window.location.href='<?php echo esc_url( admin_url('admin.php?page=wams-notifications') ); ?>'">
        <span class="dashicons dashicons-arrow-left-alt"></span> Back
      </button>
      <button type="button" id="wams-save-top" class="button button-primary">Save</button>
    </div>
  </div>

  <div class="wams-grid">
    <div class="wams-main">
      <form id="wams-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
        <input type="hidden" name="action" value="handle_form_template">
        <input type="hidden" name="wams_notifications[code]" value="<?php echo esc_attr( $req_edit_template_code ) ?>">
        <?php wp_nonce_field( 'update_template_code_'.$req_edit_template_code ); ?>

        <div class="wams-card">
          <div class="wams-toolbar">
            <div class="wams-toolbar-left">
              <button type="button" id="wams-emoji-btn" class="button">😊 Emoji</button>
              <div class="wams-stat"><span id="wams-cc">0</span> chars</div>
            </div>
          </div>

          <div class="wams-editor-wrap">
            <textarea name="wams_notifications[content]" id="wams_notifications[content]" class="wams-wp-input-template" spellcheck="false"><?php echo ($parse_db_content != null) ? esc_textarea( $parse_db_content ) : null ?></textarea>
            <div id="wams-emoji-popover" class="wams-emoji-popover">
              <emoji-picker id="wams-emoji"></emoji-picker>
            </div>
          </div>

          <div class="wams-footer">
            <?php submit_button( __( 'Save Settings', 'wams-notifications' ) ); ?>
          </div>
        </div>
      </form>
    </div>

    <div class="wams-side">
      <?php require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/page_edit_template_sidebar.php'; ?>
    </div>
  </div>
</div>

<style>
.wams-page-subtitle{margin-top:6px;color:#64748b}
.wams-admin{--gap:16px;margin-top:8px}
.wams-header{border:1px solid #e2e8f0;border-radius:10px;background:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}
.wams-title-block{display:flex;flex-direction:column;gap:4px}
.wams-title{font-weight:600}
.wams-breadcrumb{font-size:12px;color:#64748b}
.wams-actions .button{margin-left:8px;display:inline-flex;align-items:center;gap:6px}
.wams-actions .button .dashicons{line-height:1;transform:translateY(1px)}
.wams-grid{display:grid;grid-template-columns:1fr 340px;gap:var(--gap)}
@media (max-width:1100px){.wams-grid{grid-template-columns:1fr}}
.wams-card{border:1px solid #e2e8f0;border-radius:10px;background:#fff;overflow:hidden}
.wams-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
.wams-toolbar-left{display:flex;align-items:center;gap:8px}
.wams-stat{font-size:12px;color:#475569;background:#eef2ff;border:1px solid #c7d2fe;border-radius:999px;padding:2px 8px}
.wams-toolbar-right input{width:260px}
.wams-editor-wrap{position:relative}
#wams_notifications\[content\]{width:100%;min-height:420px;border:0;resize:vertical;padding:16px 16px 48px 16px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:14px;line-height:1.6;outline:none}
#wams_notifications\[content\]:focus{box-shadow:inset 0 0 0 1px #3b82f6}
.wams-emoji-popover{position:absolute;right:12px;bottom:12px;display:none;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 28px rgba(0,0,0,.12);overflow:hidden;z-index:20}
.wams-emoji-popover.open{display:block}
.wams-footer{padding:12px;border-top:1px solid #e2e8f0;background:#fafafa;display:flex;justify-content:flex-end}
.wams-side{position:sticky;top:24px;height:fit-content}
.wams-wp-sidebar{border:1px solid #e2e8f0;border-radius:10px;background:#fff;overflow:hidden}
.wams-wp-sidebar-title{padding:12px 12px;font-weight:600;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;gap:8px}
.wams-sc-search{width:100%;margin:10px 0 0 0}
.wams-wp-sidebar-content{padding:0}
.wams-sc-table{width:100%;border-collapse:collapse}
.wams-sc-table tr td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:13px;vertical-align:top}
.wams-sc-table tr:hover{background:#f8fafc}
.wams-sc{cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.wams-sc-badge{font-size:11px;border:1px solid #cbd5e1;border-radius:6px;padding:2px 6px;color:#0f172a;background:#f8fafc}
.wams-sc-desc{color:#64748b;font-size:12px}
.wams-side-footer{padding:10px 12px;background:#fafafa;border-top:1px solid #e2e8f0;font-size:12px;color:#475569}
</style>

<script>
(function(){
  var form=document.getElementById('wams-form');
  var saveTop=document.getElementById('wams-save-top');
  var ta=document.getElementById('wams_notifications[content]');
  var emojiBtn=document.getElementById('wams-emoji-btn');
  var pop=document.getElementById('wams-emoji-popover');
  var picker=document.getElementById('wams-emoji');
  var cc=document.getElementById('wams-cc');

  function insertAtCursor(target,text){
    var start=target.selectionStart||0;
    var end=target.selectionEnd||0;
    var val=target.value||'';
    target.value=val.slice(0,start)+text+val.slice(end);
    var pos=start+text.length;
    target.focus();
    if(target.setSelectionRange) target.setSelectionRange(pos,pos);
    if(cc) cc.textContent=(target.value||'').length;
  }

  if(saveTop&&form){ saveTop.addEventListener('click',function(){ form.requestSubmit(); }); }
  if(ta){ cc&& (cc.textContent=(ta.value||'').length); ta.addEventListener('input',function(){ cc&&(cc.textContent=(ta.value||'').length); }); }

  if(emojiBtn&&pop){ emojiBtn.addEventListener('click',function(e){ e.stopPropagation(); pop.classList.toggle('open'); }); }
  if(picker&&ta){ picker.addEventListener('emoji-click',function(e){
    var d=e.detail||{};
    var emoji=d.unicode||(d.emoji&&(d.emoji.unicode||d.emoji))||'';
    if(emoji) insertAtCursor(ta,emoji);
  }); }

  document.addEventListener('click',function(e){
    var btn=e.target.closest('.wams-sc');
    if(btn){
      e.preventDefault();
      var sc=btn.getAttribute('data-sc')||btn.textContent||'';
      if(sc&&ta) insertAtCursor(ta,sc);
      return;
    }
    if(pop&&pop.classList.contains('open')){
      var clickInsidePop=pop.contains(e.target);
      var clickOnBtn=e.target===emojiBtn||emojiBtn.contains(e.target);
      if(!clickInsidePop&&!clickOnBtn){ pop.classList.remove('open'); }
    }
  });

  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'&&pop&&pop.classList.contains('open')){ pop.classList.remove('open'); }
  });
})();
</script>
