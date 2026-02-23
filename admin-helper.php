<?php
/**
 * Admin Helper - Add/Reduce/Test Expiring Tokens
 *
 * Usage: Add these URL parameters to any WordPress admin page:
 * - Add unlimited tokens: ?wh_add_tokens=10
 * - Reduce tokens: ?wh_reduce_tokens=5
 * - Add expiring tokens: ?wh_test_tokens=10&wh_test_minutes=2
 * - Test purchase plan: ?wh_test_purchase_plan=5
 *
 * Examples:
 * - http://classima.local/wp-admin/?wh_add_tokens=10
 * - http://classima.local/wp-admin/?wh_reduce_tokens=5
 * - http://classima.local/wp-admin/?wh_test_tokens=5&wh_test_minutes=3
 * - http://classima.local/wp-admin/?wh_test_purchase_plan=5
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', 'wh_sub_admin_add_test_tokens' );
add_action( 'admin_init', 'wh_sub_admin_reduce_test_tokens' );
add_action( 'admin_init', 'wh_sub_admin_add_expiring_tokens' );
add_action( 'admin_init', 'wh_sub_admin_test_purchase_plan' );

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

function wh_sub_admin_add_expiring_tokens() {
    if ( ! isset( $_GET['wh_test_tokens'] ) || ! isset( $_GET['wh_test_minutes'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $amount = absint( $_GET['wh_test_tokens'] );
    $minutes = absint( $_GET['wh_test_minutes'] );
    $user_id = get_current_user_id();

    if ( $amount > 0 && $minutes > 0 && $user_id > 0 ) {
        // Calculate expiry time (current time + X minutes)
        $expiry = date( 'Y-m-d H:i:s', strtotime( '+' . $minutes . ' minutes' ) );

        // Add limited tokens with expiry
        wh_sub_add_tokens( $user_id, $amount, 'limited', $expiry );

        add_action( 'admin_notices', function() use ( $amount, $minutes, $expiry ) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Success!</strong> Added ' . esc_html( $amount ) . ' limited tokens to your account.</p>';
            echo '<p><strong>Expires in:</strong> ' . esc_html( $minutes ) . ' minutes (' . esc_html( $expiry ) . ')</p>';
            echo '<p><em>These tokens will automatically expire and be removed when you access your token balance after the expiry time.</em></p>';
            echo '</div>';
        });
    }
}

function wh_sub_admin_test_purchase_plan() {
    if ( ! isset( $_GET['wh_test_purchase_plan'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $plan_id = absint( $_GET['wh_test_purchase_plan'] );
    $user_id = get_current_user_id();

    if ( $plan_id > 0 && $user_id > 0 ) {
        // Get plan details
        $plan = wh_sub_get_plan( $plan_id );

        if ( ! $plan ) {
            add_action( 'admin_notices', function() use ( $plan_id ) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Error!</strong> Plan ID ' . esc_html( $plan_id ) . ' not found.</p>';
                echo '</div>';
            });
            return;
        }

        if ( ! $plan->wc_product_id ) {
            add_action( 'admin_notices', function() use ( $plan ) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Error!</strong> Plan "' . esc_html( $plan->name ) . '" has no associated WooCommerce product.</p>';
                echo '</div>';
            });
            return;
        }

        // Get user data
        $user = get_userdata( $user_id );

        // Create WooCommerce order
        $order = wc_create_order( array(
            'customer_id' => $user_id,
            'billing_email' => $user->user_email,
            'billing_first_name' => $user->first_name,
            'billing_last_name' => $user->last_name,
        ));

        // Add product to order
        $product = wc_get_product( $plan->wc_product_id );
        if ( ! $product ) {
            add_action( 'admin_notices', function() use ( $plan ) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Error!</strong> WooCommerce product for plan "' . esc_html( $plan->name ) . '" not found.</p>';
                echo '</div>';
            });
            return;
        }

        $order->add_product( $product, 1 );
        $order->calculate_totals();

        // Add note indicating this is a test purchase
        $order->add_order_note( 'Test purchase via admin helper' );

        // Mark order as completed - this triggers our hook that grants tokens
        $order->update_status( 'completed', 'Test purchase completed' );
        $order->save();

        // Redirect with success message
        wp_redirect( add_query_arg( array(
            'wh_test_purchase_success' => '1',
            'plan_name' => urlencode( $plan->name ),
            'order_id' => $order->get_id()
        ), admin_url() ) );
        exit;
    }
}

// Show success message after redirect
add_action( 'admin_notices', function() {
    if ( isset( $_GET['wh_test_purchase_success'] ) && current_user_can( 'manage_options' ) ) {
        $plan_name = isset( $_GET['plan_name'] ) ? sanitize_text_field( urldecode( $_GET['plan_name'] ) ) : 'Unknown';
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Success!</strong> Test purchase completed for plan: ' . esc_html( $plan_name ) . '</p>';
        echo '<p>Order ID: <a href="' . esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ) . '">#' . esc_html( $order_id ) . '</a></p>';
        echo '<p><em>Tokens have been added to your account via the order completion hook.</em></p>';
        echo '</div>';
    }
});
