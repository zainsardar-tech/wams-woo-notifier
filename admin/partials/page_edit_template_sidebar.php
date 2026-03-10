<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wams-wp-sidebar">
  <div class="wams-wp-sidebar-title">
    <span>📜 List of Shortcodes</span>
    <input type="search" id="wams-sc-filter" class="wams-sc-search" placeholder="Filter shortcodes...">
  </div>
  <div class="wams-wp-sidebar-content">
    <table class="wams-sc-table">
      <tbody id="wams-sc-list">
        <?php foreach ($wc_shortcodes as $key => $shortcode): ?>
          <tr>
            <td style="width:45%">
              <a onclick="javascript:void(0)" class="wams-sc" data-sc="<?php echo esc_attr( $shortcode[0] ); ?>">
                <span class="wams-sc-badge"><?php echo esc_html( $shortcode[0] ) ?></span>
              </a>
            </td>
            <td class="wams-sc-desc"><?php echo esc_html( $shortcode[2] ) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="wams-side-footer">Click any shortcode to insert into the editor</div>
</div>

<script>
(function(){
  var f=document.getElementById('wams-sc-filter');
  var list=document.getElementById('wams-sc-list');
  if(!f||!list) return;
  f.addEventListener('input',function(){
    var q=this.value.toLowerCase();
    var rows=list.querySelectorAll('tr');
    for(var i=0;i<rows.length;i++){
      var code=rows[i].querySelector('.wams-sc-badge')?.textContent.toLowerCase()||'';
      var desc=rows[i].querySelector('.wams-sc-desc')?.textContent.toLowerCase()||'';
      rows[i].style.display=(code.includes(q)||desc.includes(q))?'table-row':'none';
    }
  });
})();
</script>
