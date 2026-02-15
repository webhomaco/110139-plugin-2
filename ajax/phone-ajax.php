<?php
/**
 * AJAX Handlers for Phone Reveal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// AJAX handler for phone reveal
add_action( 'wp_ajax_wh_sub_reveal_phone', 'wh_sub_ajax_reveal_phone' );

function wh_sub_ajax_reveal_phone() {
    check_ajax_referer( 'wh_sub_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Please login to reveal phone number.', 'webhoma-subscription' ) ) );
    }

    $user_id = get_current_user_id();
    $listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;

    if ( ! $listing_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid listing.', 'webhoma-subscription' ) ) );
    }

    // Check if already viewed
    if ( wh_sub_has_viewed_listing( $user_id, $listing_id ) ) {
        $phone = get_post_meta( $listing_id, 'phone', true );
        wp_send_json_success( array(
            'phone' => $phone,
            'message' => __( 'Phone number revealed (already viewed).', 'webhoma-subscription' )
        ) );
    }

    // Check if user has enough tokens
    $available_tokens = wh_sub_get_available_tokens( $user_id );
    if ( $available_tokens < 1 ) {
        wp_send_json_error( array( 'message' => __( 'Insufficient tokens. Please purchase more tokens.', 'webhoma-subscription' ) ) );
    }

    // Deduct token
    $deducted = wh_sub_deduct_tokens( $user_id, 1, $listing_id, 'Phone number revealed' );

    if ( ! $deducted ) {
        wp_send_json_error( array( 'message' => __( 'Failed to deduct tokens.', 'webhoma-subscription' ) ) );
    }

    // Mark as viewed
    wh_sub_mark_listing_viewed( $user_id, $listing_id );

    // Get phone number
    $phone = get_post_meta( $listing_id, 'phone', true );

    if ( ! $phone ) {
        wp_send_json_error( array( 'message' => __( 'Phone number not available.', 'webhoma-subscription' ) ) );
    }

    wp_send_json_success( array(
        'phone' => $phone,
        'remaining_tokens' => wh_sub_get_available_tokens( $user_id ),
        'message' => __( 'Phone number revealed successfully!', 'webhoma-subscription' )
    ) );
}
