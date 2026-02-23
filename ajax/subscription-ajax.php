<?php
/**
 * Subscription Management AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Toggle auto-renewal for subscription
 */
add_action( 'wp_ajax_wh_sub_toggle_auto_renew', 'wh_sub_ajax_toggle_auto_renew' );

function wh_sub_ajax_toggle_auto_renew() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wh_sub_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed', 'webhoma-subscription' ) ) );
    }

    // Check user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Please login', 'webhoma-subscription' ) ) );
    }

    $subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;
    $enabled = isset( $_POST['enabled'] ) && $_POST['enabled'] === 'true';

    if ( ! $subscription_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid subscription ID', 'webhoma-subscription' ) ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_subscriptions';
    $user_id = get_current_user_id();

    // Verify subscription belongs to user
    $subscription = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $subscription_id,
        $user_id
    ));

    if ( ! $subscription ) {
        wp_send_json_error( array( 'message' => __( 'Subscription not found', 'webhoma-subscription' ) ) );
    }

    // Update auto_renew status
    $result = $wpdb->update(
        $table_name,
        array( 'auto_renew' => $enabled ? 1 : 0 ),
        array( 'id' => $subscription_id, 'user_id' => $user_id ),
        array( '%d' ),
        array( '%d', '%d' )
    );

    if ( $result !== false ) {
        wp_send_json_success( array(
            'message' => $enabled
                ? __( 'Auto-renewal enabled', 'webhoma-subscription' )
                : __( 'Auto-renewal disabled', 'webhoma-subscription' ),
            'enabled' => $enabled
        ));
    } else {
        wp_send_json_error( array( 'message' => __( 'Failed to update subscription', 'webhoma-subscription' ) ) );
    }
}

/**
 * Change renewal plan for subscription
 */
add_action( 'wp_ajax_wh_sub_change_renewal_plan', 'wh_sub_ajax_change_renewal_plan' );

function wh_sub_ajax_change_renewal_plan() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wh_sub_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed', 'webhoma-subscription' ) ) );
    }

    // Check user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Please login', 'webhoma-subscription' ) ) );
    }

    $subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;
    $renew_plan_id = isset( $_POST['renew_plan_id'] ) ? absint( $_POST['renew_plan_id'] ) : null;

    if ( ! $subscription_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid subscription ID', 'webhoma-subscription' ) ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_subscriptions';
    $user_id = get_current_user_id();

    // Verify subscription belongs to user
    $subscription = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $subscription_id,
        $user_id
    ));

    if ( ! $subscription ) {
        wp_send_json_error( array( 'message' => __( 'Subscription not found', 'webhoma-subscription' ) ) );
    }

    // If renewal_plan_id is 0, set to NULL (same plan)
    $renewal_plan_id = $renew_plan_id > 0 ? $renew_plan_id : null;

    // Update renewal_plan_id
    $result = $wpdb->update(
        $table_name,
        array( 'renewal_plan_id' => $renewal_plan_id ),
        array( 'id' => $subscription_id, 'user_id' => $user_id ),
        array( '%d' ),
        array( '%d', '%d' )
    );

    if ( $result !== false ) {
        $message = $renewal_plan_id
            ? __( 'Renewal plan updated', 'webhoma-subscription' )
            : __( 'Will renew with same plan', 'webhoma-subscription' );

        wp_send_json_success( array(
            'message' => $message,
            'renewal_plan_id' => $renewal_plan_id
        ));
    } else {
        wp_send_json_error( array( 'message' => __( 'Failed to update renewal plan', 'webhoma-subscription' ) ) );
    }
}

/**
 * Cancel subscription
 */
add_action( 'wp_ajax_wh_sub_cancel_subscription', 'wh_sub_ajax_cancel_subscription' );

function wh_sub_ajax_cancel_subscription() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wh_sub_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed', 'webhoma-subscription' ) ) );
    }

    // Check user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Please login', 'webhoma-subscription' ) ) );
    }

    $subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;

    if ( ! $subscription_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid subscription ID', 'webhoma-subscription' ) ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_subscriptions';
    $user_id = get_current_user_id();

    // Verify subscription belongs to user
    $subscription = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $subscription_id,
        $user_id
    ));

    if ( ! $subscription ) {
        wp_send_json_error( array( 'message' => __( 'Subscription not found', 'webhoma-subscription' ) ) );
    }

    // Update subscription: disable auto-renewal and set status to cancelled
    $result = $wpdb->update(
        $table_name,
        array(
            'status' => 'cancelled',
            'auto_renew' => 0
        ),
        array( 'id' => $subscription_id, 'user_id' => $user_id ),
        array( '%s', '%d' ),
        array( '%d', '%d' )
    );

    if ( $result !== false ) {
        wp_send_json_success( array(
            'message' => __( 'Subscription cancelled. You can still use your remaining tokens.', 'webhoma-subscription' )
        ));
    } else {
        wp_send_json_error( array( 'message' => __( 'Failed to cancel subscription', 'webhoma-subscription' ) ) );
    }
}
