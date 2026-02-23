<?php

/**
 * @package 	      Classima
 * Plugin Name:       Classima VIP Plugin
 * Plugin URI:        
 * Description:       Classima VIP Plugin
 * Version:           1.07
 * Author:            Classima
 * Author URI:	      
 * Text Domain:       Classima-VIP-Plugin
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WH_SUB_VERSION', '1.0.7' );
define( 'WH_SUB_FILE', __FILE__ );
define( 'WH_SUB_DIR', plugin_dir_path( __FILE__ ) );
define( 'WH_SUB_URL', plugin_dir_url( __FILE__ ) );

// Activation hook - create database table and schedule cron
register_activation_hook( __FILE__, 'wh_sub_install' );

function wh_sub_install() {
    require_once WH_SUB_DIR . 'install.php';
    wh_sub_create_tables();

    // Schedule renewal cron job
    if ( ! wp_next_scheduled( 'wh_sub_daily_renewal_cron' ) ) {
        wp_schedule_event( time(), 'daily', 'wh_sub_daily_renewal_cron' );
    }
}

// Deactivation hook - unschedule cron
register_deactivation_hook( __FILE__, 'wh_sub_deactivate' );

function wh_sub_deactivate() {
    $timestamp = wp_next_scheduled( 'wh_sub_daily_renewal_cron' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'wh_sub_daily_renewal_cron' );
    }
}

// Include core files
require_once WH_SUB_DIR . 'functions/barter.php';
require_once WH_SUB_DIR . 'functions/tokens.php';
require_once WH_SUB_DIR . 'functions/plans.php';
require_once WH_SUB_DIR . 'functions/phone-reveal.php';
require_once WH_SUB_DIR . 'functions/woocommerce.php';
require_once WH_SUB_DIR . 'functions/subscriptions.php';
require_once WH_SUB_DIR . 'ajax/barter-ajax.php';
require_once WH_SUB_DIR . 'ajax/phone-ajax.php';
require_once WH_SUB_DIR . 'ajax/subscription-ajax.php';

// Include admin files
if ( is_admin() ) {
    require_once WH_SUB_DIR . 'admin-helper.php';
    require_once WH_SUB_DIR . 'admin/admin-menu.php';
    require_once WH_SUB_DIR . 'admin/page-dashboard.php';
    require_once WH_SUB_DIR . 'admin/page-plans.php';
    require_once WH_SUB_DIR . 'admin/page-plan-edit.php';
    require_once WH_SUB_DIR . 'admin/page-logs.php';
    require_once WH_SUB_DIR . 'admin/page-settings.php';
}

// Check and update database if needed
function wh_sub_check_database() {
    global $wpdb;

    // Check if token tables exist
    $table_tokens = $wpdb->prefix . 'user_tokens';
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_tokens'" );

    // If token table doesn't exist, create all tables
    if ( ! $table_exists ) {
        require_once WH_SUB_DIR . 'install.php';
        wh_sub_create_tables();
    }
}

// Initialize plugin
add_action( 'plugins_loaded', 'wh_sub_init' );

function wh_sub_init() {
    // Check if tables need to be created/updated
    wh_sub_check_database();

    // Add barter fields to listing form
    add_action( 'rtcl_listing_form', 'wh_sub_add_form_fields', 25 );
    
    // Save barter data
    add_action( 'rtcl_listing_form_after_save_or_update', 'wh_sub_save_data', 10, 2 );
    
    // Display barter info on single listing
    add_action( 'rtcl_single_listing_content_end', 'wh_sub_display_info', 15 );
    
    // Add barter filter to search (both inline and vertical layouts)
    add_action( 'rtcl_widget_search_inline_form', 'wh_sub_search_filter', 20 );
    add_action( 'rtcl_widget_search_vertical_form', 'wh_sub_search_filter', 20 );

    // Modify listing query for barter filter (priority 999 to run after all RTCL filters)
    add_action( 'pre_get_posts', 'wh_sub_filter_query', 999 );
    
    // Add barter badge to listing cards
    add_action( 'rtcl_after_listing_loop_thumbnail', 'wh_sub_add_badge' );

    // Override RTCL phone display with token system (display in sidebar after seller info)
    add_action( 'rtcl_after_single_listing_sidebar', 'wh_sub_custom_phone_display', 5 );

    // Register shortcodes
    add_shortcode( 'wh_token_dashboard', 'wh_sub_dashboard_shortcode' );
    add_shortcode( 'wh_subscription_plans', 'wh_sub_subscription_plans_shortcode' );

    // RTCL My Account integration (Classima uses RTCL, not WooCommerce)
    add_filter( 'rtcl_my_account_endpoint', 'wh_sub_add_rtcl_endpoint' );
    add_filter( 'rtcl_account_menu_items', 'wh_sub_add_rtcl_menu_item', 10, 3 );
    add_action( 'rtcl_account_my-tokens_endpoint', 'wh_sub_my_account_endpoint_content' );

    // Enqueue assets
    add_action( 'wp_enqueue_scripts', 'wh_sub_enqueue_assets' );

    // Also enqueue when listing form action fires (ensures it loads on form pages)
    add_action( 'rtcl_listing_form_start', 'wh_sub_force_enqueue_assets' );
}

function wh_sub_force_enqueue_assets() {
    wh_sub_enqueue_assets();
}


function wh_sub_dashboard_shortcode() {
    ob_start();
    wh_sub_dashboard_content();
    return ob_get_clean();
}

function wh_sub_subscription_plans_shortcode() {
    ob_start();
    include WH_SUB_DIR . 'templates/subscription-page.php';
    return ob_get_clean();
}

/**
 * Register My Tokens endpoint with RTCL
 */
function wh_sub_add_rtcl_endpoint( $endpoints ) {
    $endpoints['my-tokens'] = 'my-tokens';
    return $endpoints;
}

/**
 * Add My Tokens menu item to RTCL My Account menu
 */
function wh_sub_add_rtcl_menu_item( $menu_items, $default_menu_items, $endpoints ) {
    // Insert after dashboard
    $new_items = array();
    foreach ( $menu_items as $key => $label ) {
        $new_items[ $key ] = $label;
        if ( $key === 'dashboard' ) {
            $new_items['my-tokens'] = __( 'My Tokens', 'webhoma-subscription' );
        }
    }
    return $new_items;
}

/**
 * Display My Tokens endpoint content
 */
function wh_sub_my_account_endpoint_content() {
    wh_sub_dashboard_content();
}

function wh_sub_enqueue_assets() {
    // Check if we're on any RTCL-related page or if rtcl_listing_form shortcode is present
    global $post;
    $is_rtcl_page = is_singular( 'rtcl_listing' )
                    || is_post_type_archive( 'rtcl_listing' )
                    || is_tax( 'rtcl_category' )
                    || is_tax( 'rtcl_location' )
                    || ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'rtcl_listing_form' ) );

    // Check if token dashboard shortcode is present
    $has_dashboard = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wh_token_dashboard' );

    // Check if subscription plans shortcode is present
    $has_subscription_page = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wh_subscription_plans' );

    if ( $is_rtcl_page ) {
        // Barter assets
        wp_enqueue_style(
            'wh-barter-style',
            WH_SUB_URL . 'assets/css/barter.css',
            array(),
            WH_SUB_VERSION
        );

        wp_enqueue_script(
            'wh-barter-script',
            WH_SUB_URL . 'assets/js/barter.js',
            array( 'jquery' ),
            WH_SUB_VERSION,
            true
        );

        wp_localize_script( 'wh-barter-script', 'whBarter', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wh_sub_nonce' )
        ));

        // Phone reveal assets (only on single listing pages)
        if ( is_singular( 'rtcl_listing' ) ) {
            wp_enqueue_style(
                'wh-phone-reveal-style',
                WH_SUB_URL . 'assets/css/phone-reveal.css',
                array(),
                WH_SUB_VERSION
            );

            // Enqueue subscription CSS for modal styles
            wp_enqueue_style(
                'wh-subscription-style',
                WH_SUB_URL . 'assets/css/subscription.css',
                array(),
                WH_SUB_VERSION
            );

            wp_enqueue_script(
                'wh-phone-reveal-script',
                WH_SUB_URL . 'assets/js/phone-reveal.js',
                array( 'jquery' ),
                WH_SUB_VERSION,
                true
            );

            // Enqueue subscription JS for modal functionality
            wp_enqueue_script(
                'wh-subscription-script',
                WH_SUB_URL . 'assets/js/subscription.js',
                array( 'jquery' ),
                WH_SUB_VERSION,
                true
            );

            wp_localize_script( 'wh-phone-reveal-script', 'whTokens', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wh_sub_nonce' )
            ));
        }
    }

    // Enqueue dashboard styles if shortcode is present or on RTCL My Account page
    $is_my_account = false;
    if ( class_exists( 'Rtcl\Helpers\Functions' ) ) {
        $is_my_account = \Rtcl\Helpers\Functions::is_account_page( 'my-tokens' );
    }
    if ( $has_dashboard || $is_my_account ) {
        wp_enqueue_style(
            'wh-token-dashboard-style',
            WH_SUB_URL . 'assets/css/token-dashboard.css',
            array(),
            WH_SUB_VERSION
        );

        wp_enqueue_script(
            'wh-subscription-management-script',
            WH_SUB_URL . 'assets/js/subscription-management.js',
            array( 'jquery' ),
            WH_SUB_VERSION,
            true
        );

        wp_localize_script( 'wh-subscription-management-script', 'whSubscriptionManagement', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wh_sub_nonce' )
        ));
    }

    // Enqueue My Account icon styles on RTCL account pages
    if ( class_exists( 'Rtcl\Helpers\Functions' ) && \Rtcl\Helpers\Functions::is_account_page() ) {
        wp_enqueue_style(
            'wh-my-account-icons',
            WH_SUB_URL . 'assets/css/my-account-icons.css',
            array(),
            WH_SUB_VERSION
        );
    }

    // Enqueue subscription page assets if shortcode is present
    if ( $has_subscription_page ) {
        wp_enqueue_style(
            'wh-subscription-style',
            WH_SUB_URL . 'assets/css/subscription.css',
            array(),
            WH_SUB_VERSION
        );

        wp_enqueue_script(
            'wh-subscription-script',
            WH_SUB_URL . 'assets/js/subscription.js',
            array( 'jquery' ),
            WH_SUB_VERSION,
            true
        );

        wp_localize_script( 'wh-subscription-script', 'whSubscription', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wh_sub_nonce' ),
            'current_url' => get_permalink()
        ));
    }
}
