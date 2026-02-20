<?php
/**
 * Admin Menu Registration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register admin menu
 */
function wh_sub_admin_menu() {
    // Main menu
    add_menu_page(
        __( 'Classima VIP', 'webhoma-subscription' ),
        __( 'Classima VIP', 'webhoma-subscription' ),
        'manage_options',
        'wh-subscription',
        'wh_sub_admin_dashboard_page',
        'dashicons-star-filled',
        30
    );

    // Dashboard submenu (rename first item)
    add_submenu_page(
        'wh-subscription',
        __( 'Dashboard', 'webhoma-subscription' ),
        __( 'Dashboard', 'webhoma-subscription' ),
        'manage_options',
        'wh-subscription',
        'wh_sub_admin_dashboard_page'
    );

    // Subscription plans
    add_submenu_page(
        'wh-subscription',
        __( 'Subscription Plans', 'webhoma-subscription' ),
        __( 'Subscription Plans', 'webhoma-subscription' ),
        'manage_options',
        'wh-subscription-plans',
        'wh_sub_admin_plans_page'
    );

    // Add new plan
    add_submenu_page(
        'wh-subscription',
        __( 'Add New Plan', 'webhoma-subscription' ),
        __( 'Add New Plan', 'webhoma-subscription' ),
        'manage_options',
        'wh-subscription-plan-new',
        'wh_sub_admin_plan_edit_page'
    );

    // Token logs (hidden from menu, accessed via URL)
    add_submenu_page(
        null,
        __( 'Edit Plan', 'webhoma-subscription' ),
        __( 'Edit Plan', 'webhoma-subscription' ),
        'manage_options',
        'wh-subscription-plan-edit',
        'wh_sub_admin_plan_edit_page'
    );

    // Token logs
    add_submenu_page(
        'wh-subscription',
        __( 'Token Logs', 'webhoma-subscription' ),
        __( 'Token Logs', 'webhoma-subscription' ),
        'manage_options',
        'wh-subscription-logs',
        'wh_sub_admin_logs_page'
    );

    // Settings
    add_submenu_page(
        'wh-subscription',
        __( 'Settings', 'webhoma-subscription' ),
        __( 'Settings', 'webhoma-subscription' ),
        'manage_options',
        'wh-subscription-settings',
        'wh_sub_admin_settings_page'
    );
}
add_action( 'admin_menu', 'wh_sub_admin_menu' );

/**
 * Enqueue admin assets
 */
function wh_sub_admin_assets( $hook ) {
    // Only load on our plugin pages
    if ( strpos( $hook, 'wh-subscription' ) === false ) {
        return;
    }

    wp_enqueue_style(
        'wh-admin-style',
        WH_SUB_URL . 'admin/assets/admin.css',
        array(),
        WH_SUB_VERSION
    );

    wp_enqueue_script(
        'wh-admin-script',
        WH_SUB_URL . 'admin/assets/admin.js',
        array( 'jquery' ),
        WH_SUB_VERSION,
        true
    );

    // Add WordPress media uploader
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'wh_sub_admin_assets' );
