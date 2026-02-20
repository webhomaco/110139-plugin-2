<?php
/**
 * Subscription Plans Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all subscription plans
 */
function wh_sub_get_all_plans( $status = 'all' ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'subscription_plans';

    if ( $status === 'all' ) {
        $plans = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY sort_order ASC, id ASC" );
    } else {
        $plans = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = %s ORDER BY sort_order ASC, id ASC",
            $status
        ) );
    }

    return $plans;
}

/**
 * Get single plan by ID
 */
function wh_sub_get_plan( $plan_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'subscription_plans';

    $plan = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $plan_id
    ) );

    return $plan;
}

/**
 * Create new subscription plan
 */
function wh_sub_create_plan( $data ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'subscription_plans';

    $defaults = array(
        'name' => '',
        'description' => '',
        'image_url' => '',
        'token_count' => 0,
        'duration_days' => 0,
        'duration_label' => '',
        'price' => 0,
        'token_type' => 'limited',
        'wc_product_id' => null,
        'status' => 'active',
        'sort_order' => 0,
    );

    $data = wp_parse_args( $data, $defaults );

    $inserted = $wpdb->insert(
        $table_name,
        array(
            'name' => sanitize_text_field( $data['name'] ),
            'description' => wp_kses_post( $data['description'] ),
            'image_url' => esc_url_raw( $data['image_url'] ),
            'token_count' => absint( $data['token_count'] ),
            'duration_days' => absint( $data['duration_days'] ),
            'duration_label' => sanitize_text_field( $data['duration_label'] ),
            'price' => floatval( $data['price'] ),
            'token_type' => sanitize_text_field( $data['token_type'] ),
            'wc_product_id' => null,
            'status' => sanitize_text_field( $data['status'] ),
            'sort_order' => absint( $data['sort_order'] ),
        ),
        array( '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%s', '%d', '%s', '%d' )
    );

    if ( $inserted ) {
        $plan_id = $wpdb->insert_id;

        // Auto-create WooCommerce product
        wh_sub_sync_plan_to_wc_product( $plan_id );

        return $plan_id;
    }

    return false;
}

/**
 * Update subscription plan
 */
function wh_sub_update_plan( $plan_id, $data ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'subscription_plans';

    $update_data = array();
    $update_format = array();

    // Only update fields that are provided
    if ( isset( $data['name'] ) ) {
        $update_data['name'] = sanitize_text_field( $data['name'] );
        $update_format[] = '%s';
    }
    if ( isset( $data['description'] ) ) {
        $update_data['description'] = wp_kses_post( $data['description'] );
        $update_format[] = '%s';
    }
    if ( isset( $data['image_url'] ) ) {
        $update_data['image_url'] = esc_url_raw( $data['image_url'] );
        $update_format[] = '%s';
    }
    if ( isset( $data['token_count'] ) ) {
        $update_data['token_count'] = absint( $data['token_count'] );
        $update_format[] = '%d';
    }
    if ( isset( $data['duration_days'] ) ) {
        $update_data['duration_days'] = absint( $data['duration_days'] );
        $update_format[] = '%d';
    }
    if ( isset( $data['duration_label'] ) ) {
        $update_data['duration_label'] = sanitize_text_field( $data['duration_label'] );
        $update_format[] = '%s';
    }
    if ( isset( $data['price'] ) ) {
        $update_data['price'] = floatval( $data['price'] );
        $update_format[] = '%f';
    }
    if ( isset( $data['token_type'] ) ) {
        $update_data['token_type'] = sanitize_text_field( $data['token_type'] );
        $update_format[] = '%s';
    }
    if ( isset( $data['wc_product_id'] ) ) {
        $update_data['wc_product_id'] = $data['wc_product_id'] ? absint( $data['wc_product_id'] ) : null;
        $update_format[] = '%d';
    }
    if ( isset( $data['status'] ) ) {
        $update_data['status'] = sanitize_text_field( $data['status'] );
        $update_format[] = '%s';
    }
    if ( isset( $data['sort_order'] ) ) {
        $update_data['sort_order'] = absint( $data['sort_order'] );
        $update_format[] = '%d';
    }

    if ( empty( $update_data ) ) {
        return false;
    }

    $updated = $wpdb->update(
        $table_name,
        $update_data,
        array( 'id' => $plan_id ),
        $update_format,
        array( '%d' )
    );

    if ( $updated !== false ) {
        // Sync changes to WooCommerce product
        wh_sub_sync_plan_to_wc_product( $plan_id );
        return true;
    }

    return false;
}

/**
 * Delete subscription plan
 */
function wh_sub_delete_plan( $plan_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'subscription_plans';

    // Check if plan is linked to any active subscriptions
    $table_subscriptions = $wpdb->prefix . 'user_subscriptions';
    $active_subscriptions = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_subscriptions WHERE plan_id = %d AND status = 'active'",
        $plan_id
    ) );

    if ( $active_subscriptions > 0 ) {
        return new WP_Error( 'active_subscriptions', __( 'Cannot delete plan with active subscriptions', 'webhoma-subscription' ) );
    }

    $deleted = $wpdb->delete(
        $table_name,
        array( 'id' => $plan_id ),
        array( '%d' )
    );

    return $deleted !== false;
}

/**
 * Link plan to WooCommerce product
 */
function wh_sub_link_plan_to_product( $plan_id, $product_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'subscription_plans';

    $updated = $wpdb->update(
        $table_name,
        array( 'wc_product_id' => absint( $product_id ) ),
        array( 'id' => $plan_id ),
        array( '%d' ),
        array( '%d' )
    );

    return $updated !== false;
}

/**
 * Get plan by WooCommerce product ID
 */
function wh_sub_get_plan_by_product( $product_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'subscription_plans';

    $plan = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE wc_product_id = %d",
        $product_id
    ) );

    return $plan;
}

/**
 * Auto-create or update WooCommerce product for a plan
 */
function wh_sub_sync_plan_to_wc_product( $plan_id ) {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        return false;
    }

    $plan = wh_sub_get_plan( $plan_id );
    if ( ! $plan ) {
        return false;
    }

    // Check if product already exists
    if ( $plan->wc_product_id ) {
        // Update existing product
        $product = wc_get_product( $plan->wc_product_id );
        if ( $product ) {
            $product->set_name( $plan->name );
            $product->set_description( $plan->description );
            $product->set_regular_price( $plan->price );
            $product->set_virtual( true );
            $product->set_catalog_visibility( 'hidden' );
            $product->save();

            return $plan->wc_product_id;
        }
    }

    // Create new product
    $product = new WC_Product_Simple();
    $product->set_name( $plan->name );
    $product->set_description( $plan->description );
    $product->set_regular_price( $plan->price );
    $product->set_virtual( true );
    $product->set_catalog_visibility( 'hidden' );
    $product->set_status( 'publish' );

    // Save product
    $product_id = $product->save();

    if ( $product_id ) {
        // Link product to plan
        wh_sub_link_plan_to_product( $plan_id, $product_id );

        // Store plan ID in product meta for reverse lookup
        update_post_meta( $product_id, '_wh_subscription_plan_id', $plan_id );

        return $product_id;
    }

    return false;
}
