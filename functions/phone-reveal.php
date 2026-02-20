<?php
/**
 * Phone Reveal with Token System
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
                    class="wh-reveal-phone-btn"
                    data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
                    <?php echo $available_tokens < 1 ? 'disabled' : ''; ?>
                >
                    <i class="rtcl-icon rtcl-icon-phone"></i>
                    <?php esc_html_e( 'Reveal Phone (1 Token)', 'webhoma-subscription' ); ?>
                </button>
                <span class="wh-phone-revealed" style="display: none;">
                    <a href="tel:" class="phone-number-link">
                        <i class="rtcl-icon rtcl-icon-phone"></i>
                        <span class="wh-phone-number"></span>
                    </a>
                </span>
                <?php if ( $available_tokens < 1 ) : ?>
                    <small class="wh-no-tokens"><?php esc_html_e( 'Insufficient tokens', 'webhoma-subscription' ); ?></small>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
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
    $logs = wh_sub_get_user_logs( $user_id, 20 );

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
                        ?>
                        <div class="wh-balance-item">
                            <span class="wh-label"><?php esc_html_e( 'Limited Tokens:', 'webhoma-subscription' ); ?></span>
                            <span class="wh-value"><?php echo esc_html( $token_data->limited_tokens ); ?></span>
                            <small class="<?php echo $is_expired ? 'wh-expired' : 'wh-expiry'; ?>">
                                <?php
                                if ( $is_expired ) {
                                    esc_html_e( '(Expired)', 'webhoma-subscription' );
                                } else {
                                    echo esc_html( sprintf( __( 'Expires: %s', 'webhoma-subscription' ), date_i18n( get_option( 'date_format' ), $expiry ) ) );
                                }
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="wh-token-logs">
            <h3><?php esc_html_e( 'Token Usage History', 'webhoma-subscription' ); ?></h3>
            <?php if ( ! empty( $logs ) ) : ?>
                <table class="wh-logs-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'webhoma-subscription' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'webhoma-subscription' ); ?></th>
                            <th><?php esc_html_e( 'Amount', 'webhoma-subscription' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'webhoma-subscription' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
                                <td>
                                    <span class="wh-action-badge wh-action-<?php echo esc_attr( $log->action_type ); ?>">
                                        <?php echo esc_html( ucfirst( $log->action_type ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( $log->action_type === 'add' ) : ?>
                                        <span class="wh-amount-positive">+<?php echo esc_html( $log->amount ); ?></span>
                                    <?php else : ?>
                                        <span class="wh-amount-negative">-<?php echo esc_html( $log->amount ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $log->description ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="wh-no-logs"><?php esc_html_e( 'No token activity yet.', 'webhoma-subscription' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
