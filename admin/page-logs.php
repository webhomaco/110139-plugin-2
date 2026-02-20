<?php
/**
 * Token Logs Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wh_sub_admin_logs_page() {
    global $wpdb;

    // Pagination
    $per_page = 50;
    $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $offset = ( $current_page - 1 ) * $per_page;

    // Filters
    $user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
    $action_filter = isset( $_GET['action_type'] ) ? sanitize_text_field( $_GET['action_type'] ) : '';

    // Build query
    $where = array( '1=1' );
    if ( $user_filter > 0 ) {
        $where[] = $wpdb->prepare( 'tl.user_id = %d', $user_filter );
    }
    if ( ! empty( $action_filter ) ) {
        $where[] = $wpdb->prepare( 'tl.action_type = %s', $action_filter );
    }
    $where_sql = implode( ' AND ', $where );

    // Get total count
    $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}token_logs tl WHERE $where_sql" );
    $total_pages = ceil( $total_items / $per_page );

    // Get logs
    $logs = $wpdb->get_results( "
        SELECT tl.*, u.display_name, u.user_email
        FROM {$wpdb->prefix}token_logs tl
        LEFT JOIN {$wpdb->users} u ON tl.user_id = u.ID
        WHERE $where_sql
        ORDER BY tl.created_at DESC
        LIMIT $per_page OFFSET $offset
    " );

    ?>
    <div class="wrap wh-admin-logs">
        <h1><?php esc_html_e( 'Token Logs', 'webhoma-subscription' ); ?></h1>

        <div class="wh-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="wh-subscription-logs">

                <label for="action_type"><?php esc_html_e( 'Action Type:', 'webhoma-subscription' ); ?></label>
                <select name="action_type" id="action_type">
                    <option value=""><?php esc_html_e( 'All Actions', 'webhoma-subscription' ); ?></option>
                    <option value="add" <?php selected( $action_filter, 'add' ); ?>><?php esc_html_e( 'Add', 'webhoma-subscription' ); ?></option>
                    <option value="deduct" <?php selected( $action_filter, 'deduct' ); ?>><?php esc_html_e( 'Deduct', 'webhoma-subscription' ); ?></option>
                    <option value="purchase" <?php selected( $action_filter, 'purchase' ); ?>><?php esc_html_e( 'Purchase', 'webhoma-subscription' ); ?></option>
                    <option value="expire" <?php selected( $action_filter, 'expire' ); ?>><?php esc_html_e( 'Expire', 'webhoma-subscription' ); ?></option>
                </select>

                <button type="submit" class="button"><?php esc_html_e( 'Filter', 'webhoma-subscription' ); ?></button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-logs' ) ); ?>" class="button">
                    <?php esc_html_e( 'Clear Filters', 'webhoma-subscription' ); ?>
                </a>
            </form>
        </div>

        <?php if ( ! empty( $logs ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e( 'ID', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'User', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Listing', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'webhoma-subscription' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log->id ); ?></td>
                            <td>
                                <strong><?php echo esc_html( $log->display_name ?: __( 'Unknown', 'webhoma-subscription' ) ); ?></strong>
                                <br>
                                <small><?php echo esc_html( $log->user_email ); ?></small>
                            </td>
                            <td>
                                <span class="wh-badge wh-badge-<?php echo esc_attr( $log->action_type ); ?>">
                                    <?php echo esc_html( ucfirst( $log->action_type ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( in_array( $log->action_type, array( 'add', 'purchase' ) ) ) : ?>
                                    <span class="wh-positive">+<?php echo esc_html( $log->amount ); ?></span>
                                <?php else : ?>
                                    <span class="wh-negative">-<?php echo esc_html( $log->amount ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $log->listing_id ) : ?>
                                    <a href="<?php echo esc_url( get_permalink( $log->listing_id ) ); ?>" target="_blank">
                                        #<?php echo esc_html( $log->listing_id ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="wh-text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $log->description ); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base' => add_query_arg( 'paged', '%#%' ),
                            'format' => '',
                            'prev_text' => __( '&laquo;' ),
                            'next_text' => __( '&raquo;' ),
                            'total' => $total_pages,
                            'current' => $current_page,
                        ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="wh-empty-state">
                <span class="dashicons dashicons-list-view" style="font-size: 80px; opacity: 0.3;"></span>
                <h2><?php esc_html_e( 'No token logs found', 'webhoma-subscription' ); ?></h2>
                <p><?php esc_html_e( 'Token transactions will appear here.', 'webhoma-subscription' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
