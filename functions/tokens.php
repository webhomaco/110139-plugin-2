<?php
/**
 * Token System Core Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get user token balance
 */
function wh_sub_get_user_tokens( $user_id ) {
    global $wpdb;

    if ( ! $user_id ) {
        return null;
    }

    $table_name = $wpdb->prefix . 'user_tokens';

    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d",
        $user_id
    ));
}

/**
 * Get total available tokens for user
 * Includes real-time expiration check
 */
function wh_sub_get_available_tokens( $user_id ) {
    $token_data = wh_sub_get_user_tokens( $user_id );

    if ( ! $token_data ) {
        return 0;
    }

    $total = $token_data->unlimited_tokens;

    // Check and expire limited tokens in real-time
    if ( $token_data->limited_tokens > 0 && $token_data->limited_expiry ) {
        $expiry = strtotime( $token_data->limited_expiry );

        if ( $expiry > time() ) {
            // Not expired yet - add to total
            $total += $token_data->limited_tokens;
        } else {
            // Expired - zero out and log
            wh_sub_expire_user_tokens( $user_id, $token_data->limited_tokens );
        }
    }

    return $total;
}

/**
 * Add tokens to user
 */
function wh_sub_add_tokens( $user_id, $amount, $type = 'unlimited', $expiry = null ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'user_tokens';
    $existing = wh_sub_get_user_tokens( $user_id );

    if ( $existing ) {
        // Update existing
        if ( $type === 'limited' ) {
            $wpdb->update(
                $table_name,
                array(
                    'limited_tokens' => $existing->limited_tokens + $amount,
                    'limited_expiry' => $expiry
                ),
                array( 'user_id' => $user_id ),
                array( '%d', '%s' ),
                array( '%d' )
            );
        } else {
            $wpdb->update(
                $table_name,
                array( 'unlimited_tokens' => $existing->unlimited_tokens + $amount ),
                array( 'user_id' => $user_id ),
                array( '%d' ),
                array( '%d' )
            );
        }
    } else {
        // Insert new
        $data = array(
            'user_id' => $user_id,
            'unlimited_tokens' => 0,
            'limited_tokens' => 0
        );

        if ( $type === 'limited' ) {
            $data['limited_tokens'] = $amount;
            $data['limited_expiry'] = $expiry;
        } else {
            $data['unlimited_tokens'] = $amount;
        }

        $wpdb->insert( $table_name, $data );
    }

    // Log the action
    wh_sub_log_token_action( $user_id, 'add', $amount, null, "Added $amount $type tokens" );

    return true;
}

/**
 * Deduct tokens from user
 */
function wh_sub_deduct_tokens( $user_id, $amount, $listing_id = null, $description = '' ) {
    global $wpdb;

    $token_data = wh_sub_get_user_tokens( $user_id );

    if ( ! $token_data ) {
        return false;
    }

    $table_name = $wpdb->prefix . 'user_tokens';
    $remaining = $amount;

    // First, use limited tokens if available and not expired
    if ( $token_data->limited_tokens > 0 && $token_data->limited_expiry ) {
        $expiry = strtotime( $token_data->limited_expiry );
        if ( $expiry > time() ) {
            $deduct_limited = min( $token_data->limited_tokens, $remaining );
            $token_data->limited_tokens -= $deduct_limited;
            $remaining -= $deduct_limited;
        }
    }

    // Then use unlimited tokens
    if ( $remaining > 0 ) {
        if ( $token_data->unlimited_tokens < $remaining ) {
            return false; // Not enough tokens
        }
        $token_data->unlimited_tokens -= $remaining;
    }

    // Update database
    $wpdb->update(
        $table_name,
        array(
            'limited_tokens' => $token_data->limited_tokens,
            'unlimited_tokens' => $token_data->unlimited_tokens
        ),
        array( 'user_id' => $user_id ),
        array( '%d', '%d' ),
        array( '%d' )
    );

    // Log the action
    wh_sub_log_token_action( $user_id, 'deduct', $amount, $listing_id, $description );

    return true;
}

/**
 * Check if user has viewed a listing
 */
function wh_sub_has_viewed_listing( $user_id, $listing_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'viewed_listings';

    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table_name WHERE user_id = %d AND listing_id = %d",
        $user_id,
        $listing_id
    ));

    return $exists ? true : false;
}

/**
 * Mark listing as viewed by user
 */
function wh_sub_mark_listing_viewed( $user_id, $listing_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'viewed_listings';

    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'listing_id' => $listing_id
        ),
        array( '%d', '%d' )
    );

    return true;
}

/**
 * Log token action
 */
function wh_sub_log_token_action( $user_id, $action_type, $amount, $listing_id = null, $description = '' ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'token_logs';

    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'action_type' => $action_type,
            'amount' => $amount,
            'listing_id' => $listing_id,
            'description' => $description
        ),
        array( '%d', '%s', '%d', '%d', '%s' )
    );
}

/**
 * Get user token logs
 */
function wh_sub_get_user_logs( $user_id, $limit = 20 ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'token_logs';

    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
        $user_id,
        $limit
    ));
}

/**
 * Expire tokens for a specific user (real-time check)
 */
function wh_sub_expire_user_tokens( $user_id, $expired_amount ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'user_tokens';

    // Zero out limited tokens
    $wpdb->update(
        $table_name,
        array( 'limited_tokens' => 0 ),
        array( 'user_id' => $user_id ),
        array( '%d' ),
        array( '%d' )
    );

    // Log expiration
    wh_sub_log_token_action(
        $user_id,
        'expire',
        $expired_amount,
        null,
        sprintf( '%d limited tokens expired', $expired_amount )
    );
}

/**
 * Background cleanup - expire tokens for all users
 * Runs once per hour via transient throttling
 */
function wh_sub_background_expire_tokens() {
    // Only run once per hour
    if ( get_transient( 'wh_sub_token_expiration_running' ) ) {
        return;
    }

    // Set transient for 1 hour
    set_transient( 'wh_sub_token_expiration_running', 1, HOUR_IN_SECONDS );

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_tokens';
    $now = current_time( 'mysql' );

    // Find users with expired limited tokens
    $expired_users = $wpdb->get_results( $wpdb->prepare(
        "SELECT user_id, limited_tokens
         FROM $table_name
         WHERE limited_tokens > 0
         AND limited_expiry IS NOT NULL
         AND limited_expiry < %s",
        $now
    ));

    $count = 0;
    foreach ( $expired_users as $user ) {
        wh_sub_expire_user_tokens( $user->user_id, $user->limited_tokens );
        $count++;
    }

    // Optional: Log background run for debugging
    if ( $count > 0 ) {
        error_log( sprintf( 'WH Token Expiration: Expired tokens for %d users', $count ) );
    }
}
add_action( 'init', 'wh_sub_background_expire_tokens' );
