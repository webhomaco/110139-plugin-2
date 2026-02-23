<?php
/**
 * Subscription Auto-Renewal Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Process auto-renewals for expired subscriptions
 * This function checks for expired subscriptions with auto_renew enabled
 * and creates new WooCommerce orders for them
 */
function wh_sub_process_auto_renewals() {
    global $wpdb;

    $subscriptions_table = $wpdb->prefix . 'user_subscriptions';
    $now = current_time( 'mysql' );

    // Find eligible subscriptions for renewal
    // Criteria: expired, auto-renew enabled, status active
    $eligible_subscriptions = $wpdb->get_results( $wpdb->prepare(
        "SELECT s.*, p.name as plan_name, p.wc_product_id, p.token_count, p.duration_days, p.token_type
         FROM $subscriptions_table s
         LEFT JOIN {$wpdb->prefix}subscription_plans p ON s.plan_id = p.id
         WHERE s.expires_at < %s
         AND s.auto_renew = 1
         AND s.status = 'active'
         ORDER BY s.expires_at ASC",
        $now
    ));

    if ( empty( $eligible_subscriptions ) ) {
        return array(
            'success' => true,
            'message' => 'No subscriptions to renew',
            'processed' => 0
        );
    }

    $processed = 0;
    $errors = array();

    foreach ( $eligible_subscriptions as $subscription ) {
        $result = wh_sub_renew_subscription( $subscription );

        if ( $result['success'] ) {
            $processed++;
        } else {
            $errors[] = sprintf(
                'Subscription #%d (User #%d): %s',
                $subscription->id,
                $subscription->user_id,
                $result['message']
            );
        }
    }

    // Log the results
    if ( $processed > 0 ) {
        error_log( sprintf( 'WH Auto-Renewal: Successfully processed %d subscription(s)', $processed ) );
    }

    if ( ! empty( $errors ) ) {
        error_log( 'WH Auto-Renewal Errors: ' . implode( ' | ', $errors ) );
    }

    return array(
        'success' => true,
        'message' => sprintf( 'Processed %d renewal(s)', $processed ),
        'processed' => $processed,
        'errors' => $errors
    );
}

/**
 * Renew a single subscription
 * Creates WooCommerce order and updates subscription record
 */
function wh_sub_renew_subscription( $subscription ) {
    global $wpdb;

    // Determine which plan to renew with
    $renew_plan_id = $subscription->renewal_plan_id ? $subscription->renewal_plan_id : $subscription->plan_id;
    $renew_plan = wh_sub_get_plan( $renew_plan_id );

    if ( ! $renew_plan ) {
        return array(
            'success' => false,
            'message' => sprintf( 'Plan #%d not found', $renew_plan_id )
        );
    }

    if ( ! $renew_plan->wc_product_id ) {
        return array(
            'success' => false,
            'message' => sprintf( 'Plan "%s" has no WooCommerce product', $renew_plan->name )
        );
    }

    // Get user data
    $user = get_userdata( $subscription->user_id );
    if ( ! $user ) {
        return array(
            'success' => false,
            'message' => sprintf( 'User #%d not found', $subscription->user_id )
        );
    }

    // Create WooCommerce order
    $order = wc_create_order( array(
        'customer_id' => $subscription->user_id,
        'billing_email' => $user->user_email,
        'billing_first_name' => $user->first_name,
        'billing_last_name' => $user->last_name,
    ));

    if ( is_wp_error( $order ) ) {
        return array(
            'success' => false,
            'message' => 'Failed to create order: ' . $order->get_error_message()
        );
    }

    // Add product to order
    $product = wc_get_product( $renew_plan->wc_product_id );
    if ( ! $product ) {
        return array(
            'success' => false,
            'message' => sprintf( 'Product #%d not found', $renew_plan->wc_product_id )
        );
    }

    $order->add_product( $product, 1 );
    $order->calculate_totals();

    // Add order notes
    $order->add_order_note(
        sprintf(
            'Auto-renewal for subscription #%d. Previous plan: %s, New plan: %s',
            $subscription->id,
            $subscription->plan_name,
            $renew_plan->name
        )
    );

    // Store subscription reference in order meta
    $order->update_meta_data( '_wh_subscription_renewal_id', $subscription->id );
    $order->update_meta_data( '_wh_is_renewal', '1' );

    // For testing/auto-complete: mark order as completed
    // In production, you might want to set this to 'pending' and send payment link
    $order->update_status( 'completed', 'Auto-renewal completed automatically' );
    $order->save();

    // The wh_sub_handle_order_complete() hook will:
    // - Grant tokens
    // - Create new subscription record

    // Mark old subscription as renewed (we'll update this after new subscription is created)
    $wpdb->update(
        $wpdb->prefix . 'user_subscriptions',
        array(
            'status' => 'renewed',
            'wc_order_id' => $order->get_id()
        ),
        array( 'id' => $subscription->id ),
        array( '%s', '%d' ),
        array( '%d' )
    );

    return array(
        'success' => true,
        'message' => sprintf( 'Renewal order #%d created', $order->get_id() ),
        'order_id' => $order->get_id()
    );
}

/**
 * Cron job to process renewals
 * Runs daily and uses transient throttling
 */
function wh_sub_renewal_cron_job() {
    // Throttle: Only run once per hour (even though cron is daily)
    if ( get_transient( 'wh_sub_renewal_running' ) ) {
        return;
    }

    // Set transient for 1 hour
    set_transient( 'wh_sub_renewal_running', 1, HOUR_IN_SECONDS );

    // Process renewals
    wh_sub_process_auto_renewals();
}
add_action( 'wh_sub_daily_renewal_cron', 'wh_sub_renewal_cron_job' );

/**
 * Schedule the renewal cron job on plugin activation
 */
function wh_sub_schedule_renewal_cron() {
    if ( ! wp_next_scheduled( 'wh_sub_daily_renewal_cron' ) ) {
        wp_schedule_event( time(), 'daily', 'wh_sub_daily_renewal_cron' );
    }
}

/**
 * Unschedule the renewal cron job on plugin deactivation
 */
function wh_sub_unschedule_renewal_cron() {
    $timestamp = wp_next_scheduled( 'wh_sub_daily_renewal_cron' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'wh_sub_daily_renewal_cron' );
    }
}
