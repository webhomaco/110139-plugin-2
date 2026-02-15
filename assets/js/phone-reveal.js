jQuery(document).ready(function($) {

    // Phone reveal button click
    $(document).on('click', '.wh-reveal-phone-btn', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var listingId = $btn.data('listing-id');

        if ($btn.prop('disabled')) {
            return;
        }

        // Disable button during request
        $btn.prop('disabled', true).text('Revealing...');

        $.ajax({
            url: whTokens.ajax_url,
            type: 'POST',
            data: {
                action: 'wh_sub_reveal_phone',
                nonce: whTokens.nonce,
                listing_id: listingId
            },
            success: function(response) {
                if (response.success) {
                    // Show phone number
                    var $revealed = $btn.siblings('.wh-phone-revealed');
                    $revealed.find('.wh-phone-number').text(response.data.phone);
                    $revealed.find('.phone-number-link').attr('href', 'tel:' + response.data.phone);
                    $revealed.show();
                    $btn.hide();

                    // Update token display if exists on page
                    if ($('.wh-token-count').length) {
                        $('.wh-token-count').text(response.data.remaining_tokens);
                    }
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).html('<i class="rtcl-icon rtcl-icon-phone"></i> Reveal Phone (1 Token)');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).html('<i class="rtcl-icon rtcl-icon-phone"></i> Reveal Phone (1 Token)');
            }
        });
    });

});
