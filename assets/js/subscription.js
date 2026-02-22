/**
 * Subscription Plans Page JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Handle modal close
        $('.wh-close-modal').on('click', function(e) {
            e.preventDefault();
            $('.wh-modal').removeClass('active-modal');
        });

        // Close modal on background click
        $('.wh-modal').on('click', function(e) {
            if ($(e.target).hasClass('wh-modal')) {
                $(this).removeClass('active-modal');
            }
        });

        // Handle purchase button click (using event delegation for dynamic content)
        $(document).on('click', '.wh-purchase-btn', function(e) {
            e.preventDefault();

            var button = $(this);
            var productId = button.data('product-id');
            var currentUrl = typeof whSubscription !== 'undefined' ? whSubscription.current_url : window.location.href;

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
