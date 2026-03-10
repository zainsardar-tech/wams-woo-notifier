(function ($) {
  'use strict';

  $(function () {
    // Dashboard Status Toggle
    $('.wams-content-status').change(function () {
      $('#wams_loading_overlay').css('display', 'flex');

      var template = $(this);
      var templateCode = $(this).attr('name');
      var templateStatus = $(this).is(':checked') ? 1 : 0;

      $.ajax({
        url: wams_ajax_obj.ajax_url,
        type: 'POST',
        data: {
          action: 'wams_ajax_status_action',
          nonce: wams_ajax_obj.nonce,
          template_status: templateStatus,
          template_code: templateCode
        },
        success: function (response) {
          if (!response.success) {
            alert(response?.data?.message || 'Failed to update status.');
            template.prop('checked', !templateStatus);
          }
          $('#wams_loading_overlay').css('display', 'none');
        },
        error: function () {
          alert('Request failed.');
          template.prop('checked', !templateStatus);
          $('#wams_loading_overlay').css('display', 'none');
        }
      });
    });

    // Send Test Message
    $('#wams-send-test').on('click', function (e) {
      e.preventDefault();
      var number = $('#wams-test-number').val();
      if (!number) {
        alert('Please enter a test phone number.');
        return;
      }

      $('#wams-send-test').prop('disabled', true);
      $('#wams-test-loader').addClass('is-active');
      $('#wams-test-result').hide();

      $.ajax({
        url: wams_ajax_obj.ajax_url,
        type: 'POST',
        data: {
          action: 'wams_send_test_message',
          nonce: wams_ajax_obj.nonce,
          test_number: number
        },
        success: function (response) {
          $('#wams-send-test').prop('disabled', false);
          $('#wams-test-loader').removeClass('is-active');
          $('#wams-test-result').show();
          if (response.success && response.data.status) {
            $('#wams-test-result').html('<div class="notice notice-success inline"><p>Test message sent successfully!</p></div>');
          } else {
            $('#wams-test-result').html('<div class="notice notice-error inline"><p>' + (response.data.msg || 'Failed to send test message.') + '</p></div>');
          }
        },
        error: function () {
          $('#wams-send-test').prop('disabled', false);
          $('#wams-test-loader').removeClass('is-active');
          $('#wams-test-result').show().html('<div class="notice notice-error inline"><p>Communication error.</p></div>');
        }
      });
    });
  });

})(jQuery);
