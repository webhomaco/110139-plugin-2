<?php
/**
 * WooCommerce Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Direct checkout - Add to cart and redirect to payment gateway
 * Skips the checkout page entirely
 */
function wh_sub_direct_checkout( $product_id, $return_url = '' ) {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        return false;
    }

    // Clear cart first
    WC()->cart->empty_cart();

    // Add product to cart
    $cart_item_key = WC()->cart->add_to_cart( $product_id, 1 );

    if ( ! $cart_item_key ) {
        return false;
    }

    // Store return URL in session
    if ( $return_url ) {
        WC()->session->set( 'wh_sub_return_url', $return_url );
    }

    // Redirect to checkout (which will then redirect to gateway)
    return wc_get_checkout_url();
}

/**
 * Handle subscription product purchase - create order and redirect to payment gateway
 */
function wh_sub_handle_subscription_purchase() {
    if ( ! isset( $_GET['add-to-cart'] ) || ! isset( $_GET['wh_return_url'] ) ) {
        return;
    }

    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $product_id = absint( $_GET['add-to-cart'] );
    $return_url = esc_url_raw( $_GET['wh_return_url'] );

    // Check if this is a subscription plan product
    $plan_id = get_post_meta( $product_id, '_wh_subscription_plan_id', true );

    if ( ! $plan_id ) {
        return; // Not a subscription product
    }

    // User must be logged in
    if ( ! is_user_logged_in() ) {
        wp_redirect( wp_login_url( add_query_arg( array(
            'add-to-cart' => $product_id,
            'wh_return_url' => $return_url
        ), home_url() ) ) );
        exit;
    }

    $user = wp_get_current_user();

    // Create order
    $order = wc_create_order( array(
        'customer_id' => get_current_user_id(),
        'billing_email' => $user->user_email,
        'billing_first_name' => $user->first_name,
        'billing_last_name' => $user->last_name,
    ) );

    if ( is_wp_error( $order ) ) {
        wc_add_notice( __( 'Unable to create order. Please try again.', 'webhoma-subscription' ), 'error' );
        wp_redirect( $return_url );
        exit;
    }

    // Add product to order
    $order->add_product( wc_get_product( $product_id ), 1 );

    // Calculate totals
    $order->calculate_totals();

    // Store return URL in order meta
    $order->update_meta_data( '_wh_sub_return_url', $return_url );
    $order->save();

    // Get direct payment URL and redirect
    $payment_url = $order->get_checkout_payment_url( true );
    wp_redirect( $payment_url );
    exit;
}
add_action( 'template_redirect', 'wh_sub_handle_subscription_purchase', 5 );


/**
 * Handle order completion - Grant tokens
 */
function wh_sub_handle_order_complete( $order_id ) {
    // Get order
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    $user_id = $order->get_user_id();

    // Must be logged in user
    if ( ! $user_id ) {
        return;
    }

    // Check if already processed
    if ( get_post_meta( $order_id, '_wh_tokens_processed', true ) ) {
        return;
    }

    // Process each item
    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $quantity = $item->get_quantity();

        // Get linked plan
        $plan_id = get_post_meta( $product_id, '_wh_subscription_plan_id', true );

        if ( ! $plan_id ) {
            continue;
        }

        $plan = wh_sub_get_plan( $plan_id );

        if ( ! $plan ) {
            continue;
        }

        // Calculate total tokens
        $total_tokens = $plan->token_count * $quantity;

        // Calculate expiry for limited tokens
        $expiry = null;
        if ( $plan->token_type === 'limited' && $plan->duration_days > 0 ) {
            $expiry = date( 'Y-m-d H:i:s', strtotime( '+' . $plan->duration_days . ' days' ) );
        }

        // Add tokens
        wh_sub_add_tokens( $user_id, $total_tokens, $plan->token_type, $expiry );

        // Log purchase
        wh_sub_log_token_action(
            $user_id,
            'purchase',
            $total_tokens,
            null,
            sprintf( 'Purchased %s (Order #%d)', $plan->name, $order_id )
        );

        // Add order note
        $order->add_order_note(
            sprintf(
                __( '%d tokens granted to user #%d (%s)', 'webhoma-subscription' ),
                $total_tokens,
                $user_id,
                $plan->token_type
            )
        );
    }

    // Mark as processed
    update_post_meta( $order_id, '_wh_tokens_processed', '1' );
}
add_action( 'woocommerce_order_status_completed', 'wh_sub_handle_order_complete' );
add_action( 'woocommerce_payment_complete', 'wh_sub_handle_order_complete' );

/**
 * Redirect back to referring page after successful payment
 */
function wh_sub_redirect_after_payment( $order_id ) {
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    // Get return URL from order meta
    $return_url = $order->get_meta( '_wh_sub_return_url' );

    if ( $return_url ) {
        // Delete meta to prevent repeated redirects
        $order->delete_meta_data( '_wh_sub_return_url' );
        $order->save();

        // Add success message to URL
        $return_url = add_query_arg( 'wh_purchase', 'success', $return_url );

        // Redirect
        wp_redirect( $return_url );
        exit;
    }
}
add_action( 'woocommerce_thankyou', 'wh_sub_redirect_after_payment', 10, 1 );

/**
 * Show success message on return page
 */
function wh_sub_show_purchase_message() {
    if ( isset( $_GET['wh_purchase'] ) && $_GET['wh_purchase'] === 'success' ) {
        echo '<div class="wh-purchase-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0;">';
        echo '<strong>' . esc_html__( 'Success!', 'webhoma-subscription' ) . '</strong> ';
        echo esc_html__( 'Your tokens have been added to your account.', 'webhoma-subscription' );
        echo '</div>';
    }
}
add_action( 'wp_footer', 'wh_sub_show_purchase_message' );
