<?php
/**
 * Phone Reveal with Token System
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render insufficient tokens modal in footer with subscription plans
 */
function wh_sub_render_insufficient_modal() {
    if ( ! is_singular( 'rtcl_listing' ) ) {
        return;
    }

    // Get active plans
    $plans = wh_sub_get_all_plans( 'active' );

    if ( empty( $plans ) ) {
        return;
    }

    // Separate plans by type
    $monthly_plans = array();
    foreach ( $plans as $plan ) {
        if ( $plan->duration_days > 0 ) {
            $monthly_plans[] = $plan;
        }
    }
    ?>
    <!-- Insufficient Tokens Modal with Subscription Plans -->
    <div class="wh-insufficient-modal wh-modal">
        <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i4.svg' ); ?>" alt="" class="wh-vector1">

        <div class="wh-subscription-container">
            <h1 class="wh-subscription-title"><?php esc_html_e( 'Insufficient Tokens', 'webhoma-subscription' ); ?></h1>
            <p class="wh-subscription-subtitle">
                <?php esc_html_e( 'You need tokens to view phone numbers. Select from best plan, ensuring perfect match.', 'webhoma-subscription' ); ?>
            </p>

            <?php if ( ! empty( $monthly_plans ) ) : ?>
            <div class="wh-subscription-headers">
                <div class="wh-header-item wh-header-monthly">
                    <h2 class="wh-sub-title-2"><?php esc_html_e( 'Subscription Packages', 'webhoma-subscription' ); ?></h2>
                </div>
            </div>

            <div class="wh-subscription-plans">
                <?php
                foreach ( $monthly_plans as $plan ) :
                    $price_display = wc_price( $plan->price );
                ?>
                <div class="wh-plan-col">
                    <div class="wh-sub-box wh-sub-box-2">
                        <div class="wh-sub-box-items">
                            <div>
                                <?php if ( $plan->image_url ) : ?>
                                    <img src="<?php echo esc_url( $plan->image_url ); ?>" alt="<?php echo esc_attr( $plan->name ); ?>" class="wh-plan-icon">
                                <?php else : ?>
                                    <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i1.svg' ); ?>" alt="" class="wh-plan-icon">
                                <?php endif; ?>

                                <h3 class="wh-plan-name"><?php echo esc_html( $plan->name ); ?></h3>

                                <?php if ( $plan->description ) : ?>
                                    <p class="wh-plan-text1"><?php echo esc_html( wp_trim_words( $plan->description, 10 ) ); ?></p>
                                <?php endif; ?>

                                <h4 class="wh-plan-badge"><?php echo esc_html( $plan->name ); ?></h4>

                                <p class="wh-plan-text2"><?php esc_html_e( 'Plan includes:', 'webhoma-subscription' ); ?></p>

                                <ul class="wh-plan-features">
                                    <li>
                                        <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i5.svg' ); ?>" alt="">
                                        <span><?php echo esc_html( number_format( $plan->token_count ) ); ?> <?php esc_html_e( 'Tokens', 'webhoma-subscription' ); ?></span>
                                    </li>
                                    <?php if ( $plan->duration_label ) : ?>
                                    <li>
                                        <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i5.svg' ); ?>" alt="">
                                        <span><?php echo esc_html( $plan->duration_label ); ?></span>
                                    </li>
                                    <?php endif; ?>
                                    <li>
                                        <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i5.svg' ); ?>" alt="">
                                        <span><?php echo esc_html( $plan->token_type === 'unlimited' ? __( 'Never Expires', 'webhoma-subscription' ) : __( 'Limited Duration', 'webhoma-subscription' ) ); ?></span>
                                    </li>
                                </ul>
                            </div>

                            <button class="wh-btn wh-btn-gold wh-purchase-btn" data-product-id="<?php echo esc_attr( $plan->wc_product_id ); ?>">
                                <?php esc_html_e( 'Select Plan', 'webhoma-subscription' ); ?> - <?php echo wp_kses_post( $price_display ); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <button class="wh-btn-close wh-close-modal" style="margin-top: 2rem;"><?php esc_html_e( 'Close', 'webhoma-subscription' ); ?></button>
        </div>

        <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i6.svg' ); ?>" alt="" class="wh-vector2">
    </div>
    <?php
}
add_action( 'wp_footer', 'wh_sub_render_insufficient_modal' );

/**
 * Override RTCL phone display to hide phone and show reveal button
 */
function wh_sub_custom_phone_display( $listing ) {
    // Handle both listing object and listing ID
    if ( is_object( $listing ) && method_exists( $listing, 'get_id' ) ) {
        $listing_id = $listing->get_id();
    } elseif ( is_numeric( $listing ) ) {
        $listing_id = absint( $listing );
    } else {
        return; // Invalid input
    }

    $phone = get_post_meta( $listing_id, 'phone', true );

    if ( ! $phone ) {
        return;
    }

    // Show login message for non-logged-in users
    if ( ! is_user_logged_in() ) {
        ?>
        <div class="wh-phone-reveal-section">
            <div class="phone-number">
                <span class="phone-label"><?php esc_html_e( 'Phone', 'webhoma-subscription' ); ?></span>
                <small class="wh-login-required">
                    <?php esc_html_e( 'Please login to reveal phone number', 'webhoma-subscription' ); ?>
                </small>
            </div>
        </div>
        <?php
        return;
    }

    $user_id = get_current_user_id();

    // Check if user already viewed this listing
    $already_viewed = wh_sub_has_viewed_listing( $user_id, $listing_id );

    // Get user's available tokens
    $available_tokens = wh_sub_get_available_tokens( $user_id );

    // Get tokens required per reveal from settings
    $tokens_per_reveal = get_option( 'wh_sub_tokens_per_reveal', 1 );

    ?>
    <div class="wh-phone-reveal-section">
        <div class="phone-number">
            <span class="phone-label"><?php esc_html_e( 'Phone', 'webhoma-subscription' ); ?></span>
            <?php if ( $already_viewed ) : ?>
                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="phone-number-link">
                    <i class="rtcl-icon rtcl-icon-phone"></i>
                    <?php echo esc_html( $phone ); ?>
                </a>
            <?php else : ?>
                <button
                    class="wh-reveal-phone-btn <?php echo $available_tokens < $tokens_per_reveal ? 'wh-insufficient-tokens' : ''; ?>"
                    data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
                >
                    <i class="rtcl-icon rtcl-icon-phone"></i>
                    <?php echo sprintf( esc_html__( 'Reveal Phone (%d %s)', 'webhoma-subscription' ), $tokens_per_reveal, _n( 'Token', 'Tokens', $tokens_per_reveal, 'webhoma-subscription' ) ); ?>
                </button>
                <span class="wh-phone-revealed" style="display: none;">
                    <a href="tel:" class="phone-number-link">
                        <i class="rtcl-icon rtcl-icon-phone"></i>
                        <span class="wh-phone-number"></span>
                    </a>
                </span>
                <?php if ( $available_tokens < $tokens_per_reveal ) : ?>
                    <small class="wh-no-tokens">
                        <?php echo sprintf( esc_html__( 'Insufficient tokens (need %d)', 'webhoma-subscription' ), $tokens_per_reveal ); ?>
                    </small>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Get human-readable time remaining until expiry
 */
function wh_sub_get_time_remaining( $expiry_timestamp ) {
    $now = time();
    $diff = $expiry_timestamp - $now;

    if ( $diff <= 0 ) {
        return __( 'Expired', 'webhoma-subscription' );
    }

    $days = floor( $diff / DAY_IN_SECONDS );
    $hours = floor( ( $diff % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
    $minutes = floor( ( $diff % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

    $parts = array();

    if ( $days > 0 ) {
        $parts[] = sprintf( _n( '%d day', '%d days', $days, 'webhoma-subscription' ), $days );
    }
    if ( $hours > 0 ) {
        $parts[] = sprintf( _n( '%d hour', '%d hours', $hours, 'webhoma-subscription' ), $hours );
    }
    if ( $minutes > 0 && $days == 0 ) { // Only show minutes if less than a day
        $parts[] = sprintf( _n( '%d minute', '%d minutes', $minutes, 'webhoma-subscription' ), $minutes );
    }

    if ( empty( $parts ) ) {
        return __( 'Less than 1 minute', 'webhoma-subscription' );
    }

    return implode( ', ', $parts );
}

/**
 * Get user's active subscriptions
 */
function wh_sub_get_user_subscriptions( $user_id, $status = 'active' ) {
    global $wpdb;

    $subscriptions_table = $wpdb->prefix . 'user_subscriptions';
    $plans_table = $wpdb->prefix . 'subscription_plans';

    $query = "SELECT s.*, p.name as plan_name, p.token_count, p.token_type, p.duration_days, p.price
              FROM $subscriptions_table s
              LEFT JOIN $plans_table p ON s.plan_id = p.id
              WHERE s.user_id = %d";

    if ( $status ) {
        $query .= " AND s.status = %s";
        $results = $wpdb->get_results( $wpdb->prepare( $query . " ORDER BY s.expires_at DESC", $user_id, $status ) );
    } else {
        $results = $wpdb->get_results( $wpdb->prepare( $query . " ORDER BY s.expires_at DESC", $user_id ) );
    }

    return $results;
}

/**
 * Get user dashboard page content
 */
function wh_sub_dashboard_content() {
    if ( ! is_user_logged_in() ) {
        echo '<p>' . esc_html__( 'Please login to view your token balance.', 'webhoma-subscription' ) . '</p>';
        return;
    }

    $user_id = get_current_user_id();
    $token_data = wh_sub_get_user_tokens( $user_id );
    $available_tokens = wh_sub_get_available_tokens( $user_id );

    // Pagination
    $paged = isset( $_GET['token_page'] ) ? absint( $_GET['token_page'] ) : 1;
    $per_page = 10;
    $offset = ( $paged - 1 ) * $per_page;

    // Get total count for pagination
    global $wpdb;
    $table_name = $wpdb->prefix . 'token_logs';
    $total_logs = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    $total_pages = ceil( $total_logs / $per_page );

    $logs = wh_sub_get_user_logs( $user_id, $per_page, $offset );

    ?>
    <div class="wh-token-dashboard">
        <div class="wh-token-balance-card">
            <h3><?php esc_html_e( 'Token Balance', 'webhoma-subscription' ); ?></h3>
            <div class="wh-balance-amount">
                <span class="wh-token-count"><?php echo esc_html( $available_tokens ); ?></span>
                <span class="wh-token-label"><?php esc_html_e( 'Tokens Available', 'webhoma-subscription' ); ?></span>
            </div>
            <?php if ( $token_data ) : ?>
                <div class="wh-balance-breakdown">
                    <?php if ( $token_data->unlimited_tokens > 0 ) : ?>
                        <div class="wh-balance-item">
                            <span class="wh-label"><?php esc_html_e( 'Unlimited Tokens:', 'webhoma-subscription' ); ?></span>
                            <span class="wh-value"><?php echo esc_html( $token_data->unlimited_tokens ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $token_data->limited_tokens > 0 && $token_data->limited_expiry ) : ?>
                        <?php
                        $expiry = strtotime( $token_data->limited_expiry );
                        $is_expired = $expiry < time();
                        $time_remaining = wh_sub_get_time_remaining( $expiry );
                        ?>
                        <div class="wh-balance-item">
                            <span class="wh-label"><?php esc_html_e( 'Limited Tokens:', 'webhoma-subscription' ); ?></span>
                            <span class="wh-value"><?php echo esc_html( $token_data->limited_tokens ); ?></span>
                            <small class="<?php echo $is_expired ? 'wh-expired' : 'wh-expiry'; ?>">
                                <?php
                                if ( $is_expired ) {
                                    esc_html_e( '(Expired)', 'webhoma-subscription' );
                                } else {
                                    echo esc_html( sprintf( __( 'Expires in: %s', 'webhoma-subscription' ), $time_remaining ) );
                                }
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php
        // Get user's active subscriptions
        $subscriptions = wh_sub_get_user_subscriptions( $user_id, 'active' );
        $all_plans = wh_sub_get_all_plans( 'active' ); // For renewal plan selection
        ?>

        <div class="wh-subscription-management">
            <h3><?php esc_html_e( 'Active Subscriptions', 'webhoma-subscription' ); ?></h3>

            <?php if ( ! empty( $subscriptions ) ) : ?>

            <?php foreach ( $subscriptions as $subscription ) :
                $is_expired = $subscription->expires_at && strtotime( $subscription->expires_at ) < time();
                $time_remaining = $subscription->expires_at ? wh_sub_get_time_remaining( strtotime( $subscription->expires_at ) ) : __( 'Never', 'webhoma-subscription' );
            ?>
            <div class="wh-subscription-card" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>">
                <div class="wh-subscription-header">
                    <h4><?php echo esc_html( $subscription->plan_name ); ?></h4>
                    <span class="wh-subscription-status <?php echo $is_expired ? 'expired' : 'active'; ?>">
                        <?php echo $is_expired ? esc_html__( 'Expired', 'webhoma-subscription' ) : esc_html__( 'Active', 'webhoma-subscription' ); ?>
                    </span>
                </div>

                <div class="wh-subscription-details">
                    <div class="wh-subscription-detail">
                        <span class="wh-detail-label"><?php esc_html_e( 'Tokens:', 'webhoma-subscription' ); ?></span>
                        <span class="wh-detail-value"><?php echo esc_html( $subscription->token_count ); ?> <?php echo esc_html( ucfirst( $subscription->token_type ) ); ?></span>
                    </div>
                    <div class="wh-subscription-detail">
                        <span class="wh-detail-label"><?php esc_html_e( 'Expiry:', 'webhoma-subscription' ); ?></span>
                        <span class="wh-detail-value <?php echo $is_expired ? 'expired' : ''; ?>">
                            <?php echo esc_html( $time_remaining ); ?>
                        </span>
                    </div>
                </div>

                <div class="wh-subscription-controls">
                    <!-- Auto-Renewal Toggle -->
                    <div class="wh-control-group">
                        <label class="wh-toggle-label">
                            <input type="checkbox"
                                   class="wh-auto-renew-toggle"
                                   data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>"
                                   <?php checked( $subscription->auto_renew, 1 ); ?>>
                            <span class="wh-toggle-switch"></span>
                            <span class="wh-toggle-text"><?php esc_html_e( 'Auto-Renewal', 'webhoma-subscription' ); ?></span>
                        </label>
                    </div>

                    <?php if ( $subscription->auto_renew ) : ?>
                    <!-- Renewal Plan Selection -->
                    <div class="wh-control-group">
                        <label><?php esc_html_e( 'Renew with:', 'webhoma-subscription' ); ?></label>
                        <select class="wh-renewal-plan-select" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>">
                            <option value="" <?php selected( $subscription->renewal_plan_id, null ); ?>>
                                <?php echo esc_html( sprintf( __( 'Same Plan (%s)', 'webhoma-subscription' ), $subscription->plan_name ) ); ?>
                            </option>
                            <?php foreach ( $all_plans as $plan ) :
                                if ( $plan->id != $subscription->plan_id ) : ?>
                                    <option value="<?php echo esc_attr( $plan->id ); ?>" <?php selected( $subscription->renewal_plan_id, $plan->id ); ?>>
                                        <?php echo esc_html( sprintf( '%s (%d tokens)', $plan->name, $plan->token_count ) ); ?>
                                    </option>
                                <?php endif;
                            endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Cancel Subscription Button -->
                    <button class="wh-btn wh-btn-cancel" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>">
                        <?php esc_html_e( 'Cancel Subscription', 'webhoma-subscription' ); ?>
                    </button>
                </div>

                <div class="wh-subscription-message" style="display: none;"></div>
            </div>
            <?php endforeach; ?>

            <?php else : ?>
                <div class="wh-no-subscriptions">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4m0 4h.01"/>
                    </svg>
                    <p><?php esc_html_e( 'You currently don\'t have any active subscriptions.', 'webhoma-subscription' ); ?></p>
                    <a href="<?php echo esc_url( home_url( '/subscription-plans' ) ); ?>" class="wh-btn-view-plans">
                        <?php esc_html_e( 'View Available Plans', 'webhoma-subscription' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="wh-token-logs">
            <h3><?php esc_html_e( 'Token Usage History', 'webhoma-subscription' ); ?></h3>
            <?php if ( ! empty( $logs ) ) : ?>
                <ul class="wh-logs-table">
                    <ul class="thead">
                        <li><?php esc_html_e( 'Listing', 'webhoma-subscription' ); ?></li>
                        <li><?php esc_html_e( 'Phone Number', 'webhoma-subscription' ); ?></li>
                        <li><?php esc_html_e( 'Date', 'webhoma-subscription' ); ?></li>
                        <li><?php esc_html_e( 'Credit', 'webhoma-subscription' ); ?></li>
                    </ul>
                    <ul class="tbody">
                        <?php foreach ( $logs as $log ) :
                            $listing_id = $log->listing_id;
                            $listing_title = $listing_id ? get_the_title( $listing_id ) : '-';
                            $listing_link = $listing_id ? get_permalink( $listing_id ) : '#';
                            $phone_number = $listing_id ? get_post_meta( $listing_id, 'phone', true ) : '-';
                        ?>
                            <li>
                                <span class="table-title">
                                    <?php if ( $listing_id ) : ?>
                                        <a href="<?php echo esc_url( $listing_link ); ?>"><?php echo esc_html( $listing_title ); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html( $log->description ); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="table-number"><?php echo esc_html( $phone_number ); ?></span>
                                <span class="table-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $log->created_at ) ) ); ?></span>
                                <span class="table-credit <?php echo $log->action_type === 'add' || $log->action_type === 'purchase' ? 'positive' : ''; ?>">
                                    <?php echo $log->action_type === 'add' || $log->action_type === 'purchase' ? '+' : '-'; ?><?php echo esc_html( $log->amount ); ?>
                                </span>
                                <div class="wh-logs-mobile">
                                    <div>
                                        <span class="table-number"><?php echo esc_html( $phone_number ); ?></span>
                                    </div>
                                    <div>
                                        <span class="table-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $log->created_at ) ) ); ?></span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </ul>

                <?php if ( $total_pages > 1 ) : ?>
                    <div class="wh-pagination">
                        <?php
                        $current_url = remove_query_arg( 'token_page' );

                        if ( $paged > 1 ) :
                            ?>
                            <a href="<?php echo esc_url( add_query_arg( 'token_page', $paged - 1, $current_url ) ); ?>" class="wh-pagination-prev">
                                &laquo; <?php esc_html_e( 'Previous', 'webhoma-subscription' ); ?>
                            </a>
                        <?php endif; ?>

                        <span class="wh-pagination-info">
                            <?php echo sprintf( __( 'Page %d of %d', 'webhoma-subscription' ), $paged, $total_pages ); ?>
                        </span>

                        <?php if ( $paged < $total_pages ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( 'token_page', $paged + 1, $current_url ) ); ?>" class="wh-pagination-next">
                                <?php esc_html_e( 'Next', 'webhoma-subscription' ); ?> &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else : ?>
                <p class="wh-no-logs"><?php esc_html_e( 'No token activity yet.', 'webhoma-subscription' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
