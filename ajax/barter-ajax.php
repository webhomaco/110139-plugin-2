<?php
/**
 * AJAX Handlers for Barter System
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// AJAX handler for tag suggestions (logged in users)
add_action( 'wp_ajax_wh_sub_search_tags', 'wh_sub_ajax_search_tags' );

// AJAX handler for tag suggestions (non-logged in users)
add_action( 'wp_ajax_nopriv_wh_sub_search_tags', 'wh_sub_ajax_search_tags' );

function wh_sub_ajax_search_tags() {
    check_ajax_referer( 'wh_sub_nonce', 'nonce' );
    
    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    
    $tags = wh_sub_get_all_tags( $search );
    
    // Limit to 10 suggestions
    $tags = array_slice( $tags, 0, 10 );
    
    wp_send_json_success( array( 'tags' => $tags ) );
}
