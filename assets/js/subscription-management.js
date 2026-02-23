/**
 * Subscription Management JavaScript
 */

jQuery(document).ready(function($) {

    /**
     * Toggle Auto-Renewal
     */
    $(document).on('change', '.wh-auto-renew-toggle', function() {
        const $toggle = $(this);
        const subscriptionId = $toggle.data('subscription-id');
        const enabled = $toggle.is(':checked');
        const $card = $toggle.closest('.wh-subscription-card');
        const $message = $card.find('.wh-subscription-message');

        // Show loading state
        $toggle.prop('disabled', true);

        $.ajax({
            url: whSubscriptionManagement.ajax_url,
            type: 'POST',
            data: {
                action: 'wh_sub_toggle_auto_renew',
                nonce: whSubscriptionManagement.nonce,
                subscription_id: subscriptionId,
                enabled: enabled ? 'true' : 'false'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $message
                        .removeClass('error')
                        .addClass('success')
                        .text(response.data.message)
                        .fadeIn();

                    // Hide message after 3 seconds
                    setTimeout(function() {
                        $message.fadeOut();
                    }, 3000);

                    // If disabled, hide renewal plan selector
                    if (!enabled) {
                        $card.find('.wh-renewal-plan-select').closest('.wh-control-group').slideUp();
                    } else {
                        $card.find('.wh-renewal-plan-select').closest('.wh-control-group').slideDown();
                    }
                } else {
                    // Show error and revert toggle
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data.message)
                        .fadeIn();

                    $toggle.prop('checked', !enabled);
                }
            },
            error: function() {
                $message
                    .removeClass('success')
                    .addClass('error')
                    .text('An error occurred. Please try again.')
                    .fadeIn();

                $toggle.prop('checked', !enabled);
            },
            complete: function() {
                $toggle.prop('disabled', false);
            }
        });
    });

    /**
     * Change Renewal Plan
     */
    $(document).on('change', '.wh-renewal-plan-select', function() {
        const $select = $(this);
        const subscriptionId = $select.data('subscription-id');
        const renewPlanId = $select.val();
        const $card = $select.closest('.wh-subscription-card');
        const $message = $card.find('.wh-subscription-message');

        // Show loading state
        $select.prop('disabled', true);

        $.ajax({
            url: whSubscriptionManagement.ajax_url,
            type: 'POST',
            data: {
                action: 'wh_sub_change_renewal_plan',
                nonce: whSubscriptionManagement.nonce,
                subscription_id: subscriptionId,
                renew_plan_id: renewPlanId
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $message
                        .removeClass('error')
                        .addClass('success')
                        .text(response.data.message)
                        .fadeIn();

                    // Hide message after 3 seconds
                    setTimeout(function() {
                        $message.fadeOut();
                    }, 3000);
                } else {
                    // Show error
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data.message)
                        .fadeIn();
                }
            },
            error: function() {
                $message
                    .removeClass('success')
                    .addClass('error')
                    .text('An error occurred. Please try again.')
                    .fadeIn();
            },
            complete: function() {
                $select.prop('disabled', false);
            }
        });
    });

    /**
     * Cancel Subscription
     */
    $(document).on('click', '.wh-btn-cancel', function() {
        const $button = $(this);
        const subscriptionId = $button.data('subscription-id');
        const $card = $button.closest('.wh-subscription-card');
        const $message = $card.find('.wh-subscription-message');

        // Confirm cancellation
        if (!confirm('Are you sure you want to cancel this subscription? You can still use your remaining tokens.')) {
            return;
        }

        // Show loading state
        $button.prop('disabled', true).text('Cancelling...');

        $.ajax({
            url: whSubscriptionManagement.ajax_url,
            type: 'POST',
            data: {
                action: 'wh_sub_cancel_subscription',
                nonce: whSubscriptionManagement.nonce,
                subscription_id: subscriptionId
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $message
                        .removeClass('error')
                        .addClass('success')
                        .text(response.data.message)
                        .fadeIn();

                    // Update UI
                    $card.find('.wh-subscription-status')
                        .removeClass('active')
                        .addClass('expired')
                        .text('Cancelled');

                    // Disable controls
                    $card.find('.wh-auto-renew-toggle').prop('checked', false).prop('disabled', true);
                    $card.find('.wh-renewal-plan-select').prop('disabled', true);
                    $button.hide();

                    // Hide renewal plan selector
                    $card.find('.wh-renewal-plan-select').closest('.wh-control-group').slideUp();
                } else {
                    // Show error
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data.message)
                        .fadeIn();

                    $button.prop('disabled', false).text('Cancel Subscription');
                }
            },
            error: function() {
                $message
                    .removeClass('success')
                    .addClass('error')
                    .text('An error occurred. Please try again.')
                    .fadeIn();

                $button.prop('disabled', false).text('Cancel Subscription');
            }
        });
    });

});
