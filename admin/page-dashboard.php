<?php
/**
 * Admin Dashboard Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wh_sub_admin_dashboard_page() {
    global $wpdb;

    // Get statistics
    $total_users_with_tokens = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}user_tokens WHERE (limited_tokens > 0 OR unlimited_tokens > 0)" );
    $total_active_plans = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}subscription_plans WHERE status = 'active'" );
    $total_token_transactions = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}token_logs" );
    $total_phone_reveals = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}viewed_listings" );

    // Get recent logs
    $recent_logs = $wpdb->get_results( "
        SELECT tl.*, u.display_name
        FROM {$wpdb->prefix}token_logs tl
        LEFT JOIN {$wpdb->users} u ON tl.user_id = u.ID
        ORDER BY tl.created_at DESC
        LIMIT 10
    " );

    // Get top users by token usage
    $top_users = $wpdb->get_results( "
        SELECT u.ID, u.display_name, u.user_email,
               ut.limited_tokens, ut.unlimited_tokens,
               (SELECT COUNT(*) FROM {$wpdb->prefix}viewed_listings WHERE user_id = u.ID) as phones_revealed
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->prefix}user_tokens ut ON u.ID = ut.user_id
        WHERE (ut.limited_tokens > 0 OR ut.unlimited_tokens > 0)
        ORDER BY (ut.limited_tokens + ut.unlimited_tokens) DESC
        LIMIT 5
    " );

    ?>
    <div class="wrap wh-admin-dashboard">
        <h1><?php esc_html_e( 'Classima VIP Dashboard', 'webhoma-subscription' ); ?></h1>

        <div class="wh-stats-grid">
            <div class="wh-stat-card">
                <div class="wh-stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="wh-stat-content">
                    <h3><?php echo esc_html( $total_users_with_tokens ); ?></h3>
                    <p><?php esc_html_e( 'Users with Tokens', 'webhoma-subscription' ); ?></p>
                </div>
            </div>

            <div class="wh-stat-card">
                <div class="wh-stat-icon">
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <div class="wh-stat-content">
                    <h3><?php echo esc_html( $total_active_plans ); ?></h3>
                    <p><?php esc_html_e( 'Active Plans', 'webhoma-subscription' ); ?></p>
                </div>
            </div>

            <div class="wh-stat-card">
                <div class="wh-stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="wh-stat-content">
                    <h3><?php echo esc_html( $total_token_transactions ); ?></h3>
                    <p><?php esc_html_e( 'Total Transactions', 'webhoma-subscription' ); ?></p>
                </div>
            </div>

            <div class="wh-stat-card">
                <div class="wh-stat-icon">
                    <span class="dashicons dashicons-phone"></span>
                </div>
                <div class="wh-stat-content">
                    <h3><?php echo esc_html( $total_phone_reveals ); ?></h3>
                    <p><?php esc_html_e( 'Phone Reveals', 'webhoma-subscription' ); ?></p>
                </div>
            </div>
        </div>

        <div class="wh-dashboard-content">
            <div class="wh-dashboard-section">
                <h2><?php esc_html_e( 'Recent Activity', 'webhoma-subscription' ); ?></h2>
                <?php if ( ! empty( $recent_logs ) ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'User', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Action', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Amount', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Description', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'webhoma-subscription' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $recent_logs as $log ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $log->display_name ?: __( 'Unknown', 'webhoma-subscription' ) ); ?></td>
                                    <td>
                                        <span class="wh-badge wh-badge-<?php echo esc_attr( $log->action_type ); ?>">
                                            <?php echo esc_html( ucfirst( $log->action_type ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ( $log->action_type === 'add' ) : ?>
                                            <span class="wh-positive">+<?php echo esc_html( $log->amount ); ?></span>
                                        <?php else : ?>
                                            <span class="wh-negative">-<?php echo esc_html( $log->amount ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( $log->description ); ?></td>
                                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'No activity yet.', 'webhoma-subscription' ); ?></p>
                <?php endif; ?>
            </div>

            <div class="wh-dashboard-section">
                <h2><?php esc_html_e( 'Top Token Holders', 'webhoma-subscription' ); ?></h2>
                <?php if ( ! empty( $top_users ) ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'User', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Email', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Limited Tokens', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Unlimited Tokens', 'webhoma-subscription' ); ?></th>
                                <th><?php esc_html_e( 'Phones Revealed', 'webhoma-subscription' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_users as $user ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $user->display_name ); ?></td>
                                    <td><?php echo esc_html( $user->user_email ); ?></td>
                                    <td><?php echo esc_html( $user->limited_tokens ); ?></td>
                                    <td><?php echo esc_html( $user->unlimited_tokens ); ?></td>
                                    <td><?php echo esc_html( $user->phones_revealed ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'No users with tokens yet.', 'webhoma-subscription' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="wh-quick-links">
            <h2><?php esc_html_e( 'Quick Links', 'webhoma-subscription' ); ?></h2>
            <div class="wh-quick-links-grid">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-plan-new' ) ); ?>" class="wh-quick-link">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e( 'Add New Plan', 'webhoma-subscription' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-plans' ) ); ?>" class="wh-quick-link">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e( 'View All Plans', 'webhoma-subscription' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-logs' ) ); ?>" class="wh-quick-link">
                    <span class="dashicons dashicons-analytics"></span>
                    <?php esc_html_e( 'View Token Logs', 'webhoma-subscription' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-settings' ) ); ?>" class="wh-quick-link">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Settings', 'webhoma-subscription' ); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}
