/**
 * Subscription Plans Page JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Handle purchase button click
        $('.wh-purchase-btn').on('click', function(e) {
            e.preventDefault();

            var button = $(this);
            var productId = button.data('product-id');
            var currentUrl = whSubscription.current_url;

            if (!productId) {
                alert('Invalid product. Please try again.');
                return;
            }

            // Disable button to prevent double clicks
            button.prop('disabled', true).text('Processing...');

            // Redirect to purchase flow
            // The woocommerce.php will handle creating order and redirecting to gateway
            window.location.href = '?add-to-cart=' + productId + '&wh_return_url=' + encodeURIComponent(currentUrl);
        });

    });

})(jQuery);
