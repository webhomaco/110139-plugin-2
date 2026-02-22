<?php
/**
 * Admin Helper - Add/Reduce Test Tokens
 *
 * Usage: Add these URL parameters to any WordPress admin page:
 * - Add tokens: ?wh_add_tokens=10
 * - Reduce tokens: ?wh_reduce_tokens=5
 *
 * Examples:
 * - http://classima.local/wp-admin/?wh_add_tokens=10
 * - http://classima.local/wp-admin/?wh_reduce_tokens=5
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', 'wh_sub_admin_add_test_tokens' );
add_action( 'admin_init', 'wh_sub_admin_reduce_test_tokens' );

function wh_sub_admin_add_test_tokens() {
    if ( ! isset( $_GET['wh_add_tokens'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $amount = absint( $_GET['wh_add_tokens'] );
    $user_id = get_current_user_id();

    if ( $amount > 0 && $user_id > 0 ) {
        wh_sub_add_tokens( $user_id, $amount, 'unlimited' );

        add_action( 'admin_notices', function() use ( $amount ) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Success!</strong> Added ' . esc_html( $amount ) . ' tokens to your account.</p>';
            echo '</div>';
        });
    }
}

function wh_sub_admin_reduce_test_tokens() {
    if ( ! isset( $_GET['wh_reduce_tokens'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $amount = absint( $_GET['wh_reduce_tokens'] );
    $user_id = get_current_user_id();

    if ( $amount > 0 && $user_id > 0 ) {
        // Get current token data
        $token_data = wh_sub_get_user_tokens( $user_id );

        if ( ! $token_data ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Error!</strong> No token record found for your account.</p>';
                echo '</div>';
            });
            return;
        }

        // Try to deduct tokens (will use limited first, then unlimited)
        $success = wh_sub_deduct_tokens( $user_id, $amount, null, 'Test token reduction (admin helper)' );

        if ( $success ) {
            add_action( 'admin_notices', function() use ( $amount ) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>Success!</strong> Reduced ' . esc_html( $amount ) . ' tokens from your account.</p>';
                echo '</div>';
            });
        } else {
            add_action( 'admin_notices', function() use ( $amount ) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Error!</strong> Insufficient tokens. You need ' . esc_html( $amount ) . ' tokens.</p>';
                echo '</div>';
            });
        }
    }
}
