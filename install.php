<?php
/**
 * Database installation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wh_sub_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Barter data table
    $table_barter = $wpdb->prefix . 'barter_data';

    $sql_barter = "CREATE TABLE IF NOT EXISTS $table_barter (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        listing_id bigint(20) NOT NULL,
        description text,
        tags text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY listing_id (listing_id)
    ) $charset_collate;";

    // User tokens table
    $table_tokens = $wpdb->prefix . 'user_tokens';

    $sql_tokens = "CREATE TABLE IF NOT EXISTS $table_tokens (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        limited_tokens int(11) DEFAULT 0,
        limited_expiry datetime,
        unlimited_tokens int(11) DEFAULT 0,
        auto_renew tinyint(1) DEFAULT 0,
        renewal_plan_id bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    // Token logs table
    $table_logs = $wpdb->prefix . 'token_logs';

    $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        action_type varchar(20) NOT NULL,
        amount int(11) NOT NULL,
        listing_id bigint(20),
        description text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY listing_id (listing_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Viewed listings table
    $table_viewed = $wpdb->prefix . 'viewed_listings';

    $sql_viewed = "CREATE TABLE IF NOT EXISTS $table_viewed (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        listing_id bigint(20) NOT NULL,
        viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_listing (user_id, listing_id)
    ) $charset_collate;";

    // Subscription plans table
    $table_plans = $wpdb->prefix . 'subscription_plans';

    $sql_plans = "CREATE TABLE IF NOT EXISTS $table_plans (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        image_url varchar(500),
        token_count int(11) NOT NULL,
        duration_days int(11) DEFAULT 0,
        duration_label varchar(100),
        price decimal(10,2) NOT NULL,
        token_type varchar(20) DEFAULT 'limited',
        wc_product_id bigint(20),
        status varchar(20) DEFAULT 'active',
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY wc_product_id (wc_product_id),
        KEY status (status)
    ) $charset_collate;";

    // User subscriptions table
    $table_subscriptions = $wpdb->prefix . 'user_subscriptions';

    $sql_subscriptions = "CREATE TABLE IF NOT EXISTS $table_subscriptions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        plan_id bigint(20) NOT NULL,
        wc_order_id bigint(20),
        status varchar(20) DEFAULT 'active',
        started_at datetime,
        expires_at datetime,
        auto_renew tinyint(1) DEFAULT 0,
        renewal_plan_id bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY plan_id (plan_id),
        KEY status (status)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Create all tables
    dbDelta( $sql_barter );
    dbDelta( $sql_tokens );
    dbDelta( $sql_logs );
    dbDelta( $sql_viewed );
    dbDelta( $sql_plans );
    dbDelta( $sql_subscriptions );
}
