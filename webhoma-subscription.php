<?php

/**
 * @package 	      Classima
 * Plugin Name:       Classima VIP Plugin
 * Plugin URI:        
 * Description:       Classima VIP Plugin
 * Version:           1.01
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
define( 'WH_SUB_VERSION', '1.0.0' );
define( 'WH_SUB_FILE', __FILE__ );
define( 'WH_SUB_DIR', plugin_dir_path( __FILE__ ) );
define( 'WH_SUB_URL', plugin_dir_url( __FILE__ ) );

// Activation hook - create database table
register_activation_hook( __FILE__, 'wh_sub_install' );

function wh_sub_install() {
    require_once WH_SUB_DIR . 'install.php';
    wh_sub_create_tables();
}

// Include core files
require_once WH_SUB_DIR . 'functions/barter.php';
require_once WH_SUB_DIR . 'functions/tokens.php';
require_once WH_SUB_DIR . 'functions/phone-reveal.php';
require_once WH_SUB_DIR . 'ajax/barter-ajax.php';
require_once WH_SUB_DIR . 'ajax/phone-ajax.php';

// Include admin helper for testing
if ( is_admin() ) {
    require_once WH_SUB_DIR . 'admin-helper.php';
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
    
    // Add barter filter to search
    add_action( 'rtcl_widget_search_form', 'wh_sub_search_filter', 20 );
    
    // Modify listing query for barter filter
    add_filter( 'rtcl_listing_query_args', 'wh_sub_filter_query' );
    
    // Add barter badge to listing cards
    add_action( 'rtcl_after_listing_loop_thumbnail', 'wh_sub_add_badge' );

    // Override RTCL phone display with token system
    add_action( 'rtcl_single_listing_content_end', 'wh_sub_custom_phone_display', 5 );

    // Register shortcode for token dashboard
    add_shortcode( 'wh_token_dashboard', 'wh_sub_dashboard_shortcode' );

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

            wp_enqueue_script(
                'wh-phone-reveal-script',
                WH_SUB_URL . 'assets/js/phone-reveal.js',
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

    // Enqueue dashboard styles if shortcode is present
    if ( $has_dashboard ) {
        wp_enqueue_style(
            'wh-phone-reveal-style',
            WH_SUB_URL . 'assets/css/phone-reveal.css',
            array(),
            WH_SUB_VERSION
        );
    }
}
