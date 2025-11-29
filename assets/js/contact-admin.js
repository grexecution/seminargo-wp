/**
 * Contact Form Admin Scripts
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        var $btn = $('#send-test-email');
        var $result = $('#test-email-result');

        $btn.on('click', function() {
            $btn.prop('disabled', true).text(seminargoContact.strings.sending);
            $result.html('');

            $.ajax({
                url: seminargoContact.ajaxurl,
                type: 'POST',
                data: {
                    action: 'seminargo_send_test_email',
                    nonce: seminargoContact.nonce,
                    page_id: $('#post_ID').val()
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span style="color: green;">' + response.data + '</span>');
                    } else {
                        $result.html('<span style="color: red;">' + response.data + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span style="color: red;">' + seminargoContact.strings.error + '</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(seminargoContact.strings.send);
                }
            });
        });
    });

})(jQuery);
