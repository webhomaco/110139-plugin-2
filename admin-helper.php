<?php
/**
 * Admin Helper - Add Test Tokens
 *
 * Usage: Add this URL parameter to any WordPress admin page: ?wh_add_tokens=10
 * Example: http://classima.local/wp-admin/?wh_add_tokens=10
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', 'wh_sub_admin_add_test_tokens' );

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
