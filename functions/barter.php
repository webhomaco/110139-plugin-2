<?php
/**
 * Barter Core Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add barter fields to listing form
 */
function wh_sub_add_form_fields( $post_id ) {
    // Get existing data if editing
    $barter_data = wh_sub_get_data( $post_id );
    $description = $barter_data ? $barter_data->description : '';
    $tags = $barter_data && $barter_data->tags ? json_decode( $barter_data->tags, true ) : array();
    
    ?>
    <div class="rtcl-post-section wh-barter-section">
        <div class="classified-listing-form-title">
            <i class="fa fa-exchange" aria-hidden="true"></i>
            <h3><?php esc_html_e( 'Trade Option (Barter)', 'webhoma-barter' ); ?></h3>
        </div>
        
        <div class="row">
            <div class="col-sm-3 col-12">
                <label class="control-label"><?php esc_html_e( 'Trade Description', 'webhoma-barter' ); ?></label>
            </div>
            <div class="col-sm-9 col-12">
                <div class="form-group">
                    <textarea 
                        name="wh_barter_description" 
                        id="wh-barter-description" 
                        class="form-control" 
                        rows="3"
                        placeholder="<?php esc_attr_e( 'Describe what you want to trade for...', 'webhoma-barter' ); ?>"
                    ><?php echo esc_textarea( $description ); ?></textarea>
                    <small class="form-text text-muted">
                        <?php esc_html_e( 'e.g., "Willing to trade for a smartphone or laptop"', 'webhoma-barter' ); ?>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-3 col-12">
                <label class="control-label"><?php esc_html_e( 'Trade Tags', 'webhoma-barter' ); ?></label>
            </div>
            <div class="col-sm-9 col-12">
                <div class="form-group">
                    <div class="wh-barter-tags-wrap">
                        <input 
                            type="text" 
                            id="wh-barter-tag-input" 
                            class="form-control" 
                            placeholder="<?php esc_attr_e( 'Type and press Enter to add tags...', 'webhoma-barter' ); ?>"
                            autocomplete="off"
                        />
                        <div id="wh-barter-tag-suggestions" class="wh-tag-suggestions"></div>
                        <div class="wh-barter-tags-list">
                            <?php if ( ! empty( $tags ) ) : ?>
                                <?php foreach ( $tags as $tag ) : ?>
                                    <span class="wh-barter-tag">
                                        <?php echo esc_html( $tag ); ?>
                                        <span class="wh-remove-tag" data-tag="<?php echo esc_attr( $tag ); ?>">×</span>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="wh_barter_tags" id="wh-barter-tags-hidden" value="<?php echo esc_attr( json_encode( $tags ) ); ?>"/>
                        <small class="form-text text-muted">
                            <?php esc_html_e( 'e.g., smartphone, laptop, camera', 'webhoma-barter' ); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Save barter data
 */
function wh_sub_save_data( $post_id, $args ) {
    global $wpdb;

    // Handle if $post_id is an object (Listing object)
    if ( is_object( $post_id ) && method_exists( $post_id, 'get_id' ) ) {
        $post_id = $post_id->get_id();
    }
    
    // Ensure we have a valid post ID
    if ( ! $post_id || $post_id == 0 ) {
        return;
    }
    
    $description = isset( $_POST['wh_barter_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wh_barter_description'] ) ) : '';
    $tags = isset( $_POST['wh_barter_tags'] ) ? wp_unslash( $_POST['wh_barter_tags'] ) : '';
    
    // Validate and sanitize tags
    if ( $tags ) {
        $tags_array = json_decode( $tags, true );
        if ( is_array( $tags_array ) ) {
            $tags_array = array_map( 'sanitize_text_field', $tags_array );
            $tags = json_encode( $tags_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        } else {
            $tags = '';
        }
    }
    
    $table_name = $wpdb->prefix . 'barter_data';
    
    // Check if entry exists
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table_name WHERE listing_id = %d",
        $post_id
    ));
    
    if ( $description || $tags ) {
        // Has barter data
        if ( $existing ) {
            // Update
            $wpdb->update(
                $table_name,
                array(
                    'description' => $description,
                    'tags' => $tags
                ),
                array( 'listing_id' => $post_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        } else {
            // Insert
            $wpdb->insert(
                $table_name,
                array(
                    'listing_id' => $post_id,
                    'description' => $description,
                    'tags' => $tags
                ),
                array( '%d', '%s', '%s' )
            );
        }
    } else {
        // No barter data, delete if exists
        if ( $existing ) {
            $wpdb->delete( $table_name, array( 'listing_id' => $post_id ), array( '%d' ) );
        }
    }
}

/**
 * Get barter data for a listing
 */
function wh_sub_get_data( $listing_id ) {
    global $wpdb;
    
    if ( ! $listing_id ) {
        return null;
    }
    
    $table_name = $wpdb->prefix . 'barter_data';
    
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE listing_id = %d",
        $listing_id
    ));
}

/**
 * Display barter info on single listing page
 * Uses frontend template design from trade-description.html
 */
function wh_sub_display_info( $listing ) {
    $barter_data = wh_sub_get_data( $listing->get_id() );

    if ( ! $barter_data || ( empty( $barter_data->description ) && empty( $barter_data->tags ) ) ) {
        return;
    }

    $tags = $barter_data->tags ? json_decode( $barter_data->tags, true ) : array();
    ?>
    <div class="content-block-gap"></div>
    <section class="trade-option-area mt-5">
        <div class="trade-option-description tod">
            <h2><?php esc_html_e( 'Trade Description', 'webhoma-barter' ); ?></h2>

            <?php if ( $barter_data->description ) : ?>
                <p><?php echo esc_html( $barter_data->description ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $tags ) ) : ?>
                <ul id="tags-ul">
                    <?php foreach ( $tags as $tag ) : ?>
                        <li>
                            <a href="#">
                                <?php echo esc_html( $tag ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Add barter filter to search form
 */
function wh_sub_search_filter() {
    $selected_tags = isset( $_GET['barter_tags'] ) ? (array) $_GET['barter_tags'] : array();
    $selected_tags = array_map( 'sanitize_text_field', $selected_tags );
    ?>
    <div class="form-group wh-barter-search-filter">
        <label><?php esc_html_e( 'Trade Tags', 'webhoma-barter' ); ?></label>
        <input 
            type="text" 
            id="wh-barter-search-input" 
            class="form-control" 
            placeholder="<?php esc_attr_e( 'Search by trade tags...', 'webhoma-barter' ); ?>"
            autocomplete="off"
        />
        <div id="wh-barter-search-suggestions" class="wh-tag-suggestions"></div>
        <div class="wh-selected-tags">
            <?php foreach ( $selected_tags as $tag ) : ?>
                <span class="wh-selected-tag">
                    <?php echo esc_html( $tag ); ?>
                    <span class="wh-remove-search-tag" data-tag="<?php echo esc_attr( $tag ); ?>">×</span>
                    <input type="hidden" name="barter_tags[]" value="<?php echo esc_attr( $tag ); ?>"/>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Filter listings by barter tags
 */
function wh_sub_filter_query( $args ) {
    if ( isset( $_GET['barter_tags'] ) && ! empty( $_GET['barter_tags'] ) ) {
        global $wpdb;

        $tags = (array) $_GET['barter_tags'];
        $tags = array_map( 'sanitize_text_field', $tags );

        if ( ! empty( $tags ) ) {
            $table_name = $wpdb->prefix . 'barter_data';

            // Build LIKE conditions for each tag
            $like_conditions = array();
            foreach ( $tags as $tag ) {
                $like_conditions[] = $wpdb->prepare( "tags LIKE %s", '%' . $wpdb->esc_like( $tag ) . '%' );
            }

            $where = implode( ' OR ', $like_conditions );

            // Get listing IDs that match
            $listing_ids = $wpdb->get_col( "SELECT listing_id FROM $table_name WHERE $where" );

            if ( ! empty( $listing_ids ) ) {
                // If post__in already exists, intersect with our results
                if ( isset( $args['post__in'] ) && ! empty( $args['post__in'] ) ) {
                    $args['post__in'] = array_intersect( $args['post__in'], $listing_ids );
                    // If no intersection, return empty result
                    if ( empty( $args['post__in'] ) ) {
                        $args['post__in'] = array( 0 );
                    }
                } else {
                    $args['post__in'] = $listing_ids;
                }
            } else {
                // No matches, return empty result
                $args['post__in'] = array( 0 );
            }
        }
    }

    return $args;
}

/**
 * Add barter badge to listing cards
 */
function wh_sub_add_badge() {
    global $post;
    
    $barter_data = wh_sub_get_data( $post->ID );
    
    if ( $barter_data && ( $barter_data->description || $barter_data->tags ) ) {
        echo '<span class="wh-barter-badge">' . esc_html__( 'Trade Available', 'webhoma-barter' ) . '</span>';
    }
}

/**
 * Get all unique tags from database
 */
function wh_sub_get_all_tags( $search = '' ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'barter_data';
    
    $results = $wpdb->get_col( "SELECT tags FROM $table_name WHERE tags IS NOT NULL AND tags != ''" );
    
    $all_tags = array();
    foreach ( $results as $tags_json ) {
        $tags = json_decode( $tags_json, true );
        if ( is_array( $tags ) ) {
            $all_tags = array_merge( $all_tags, $tags );
        }
    }
    
    $all_tags = array_unique( $all_tags );
    
    // Filter by search term if provided
    if ( $search ) {
        $all_tags = array_filter( $all_tags, function( $tag ) use ( $search ) {
            return stripos( $tag, $search ) !== false;
        });
    }
    
    return array_values( $all_tags );
}
