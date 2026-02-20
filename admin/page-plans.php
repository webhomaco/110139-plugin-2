<?php
/**
 * Subscription Plans List Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wh_sub_admin_plans_page() {
    // Handle delete action
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['plan_id'] ) && isset( $_GET['_wpnonce'] ) ) {
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'delete_plan_' . $_GET['plan_id'] ) ) {
            $result = wh_sub_delete_plan( absint( $_GET['plan_id'] ) );

            if ( is_wp_error( $result ) ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Plan deleted successfully.', 'webhoma-subscription' ) . '</p></div>';
            }
        }
    }

    // Get all plans
    $plans = wh_sub_get_all_plans();

    ?>
    <div class="wrap wh-admin-plans">
        <h1 class="wp-heading-inline">
            <?php esc_html_e( 'Subscription Plans', 'webhoma-subscription' ); ?>
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-plan-new' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Add New', 'webhoma-subscription' ); ?>
        </a>
        <hr class="wp-header-end">

        <?php if ( ! empty( $plans ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e( 'ID', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Tokens', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Duration', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Price', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'WC Product', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'webhoma-subscription' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'webhoma-subscription' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $plans as $plan ) : ?>
                        <tr>
                            <td><?php echo esc_html( $plan->id ); ?></td>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-plan-edit&plan_id=' . $plan->id ) ); ?>">
                                        <?php echo esc_html( $plan->name ); ?>
                                    </a>
                                </strong>
                                <?php if ( $plan->image_url ) : ?>
                                    <br>
                                    <img src="<?php echo esc_url( $plan->image_url ); ?>" alt="<?php echo esc_attr( $plan->name ); ?>" style="max-width: 50px; max-height: 50px; margin-top: 5px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( number_format( $plan->token_count ) ); ?></td>
                            <td>
                                <span class="wh-badge wh-badge-<?php echo esc_attr( $plan->token_type ); ?>">
                                    <?php echo esc_html( ucfirst( $plan->token_type ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ( $plan->duration_days == 0 ) {
                                    esc_html_e( 'Unlimited', 'webhoma-subscription' );
                                } else {
                                    echo esc_html( $plan->duration_label ?: $plan->duration_days . ' days' );
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html( wc_price( $plan->price ) ); ?></td>
                            <td>
                                <?php if ( $plan->wc_product_id ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $plan->wc_product_id . '&action=edit' ) ); ?>" target="_blank">
                                        #<?php echo esc_html( $plan->wc_product_id ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="wh-text-muted"><?php esc_html_e( 'Not linked', 'webhoma-subscription' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="wh-status wh-status-<?php echo esc_attr( $plan->status ); ?>">
                                    <?php echo esc_html( ucfirst( $plan->status ) ); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-plan-edit&plan_id=' . $plan->id ) ); ?>" class="button button-small">
                                    <?php esc_html_e( 'Edit', 'webhoma-subscription' ); ?>
                                </a>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wh-subscription-plans&action=delete&plan_id=' . $plan->id ), 'delete_plan_' . $plan->id ) ); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this plan?', 'webhoma-subscription' ); ?>');">
                                    <?php esc_html_e( 'Delete', 'webhoma-subscription' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="wh-empty-state">
                <span class="dashicons dashicons-star-empty" style="font-size: 80px; opacity: 0.3;"></span>
                <h2><?php esc_html_e( 'No subscription plans yet', 'webhoma-subscription' ); ?></h2>
                <p><?php esc_html_e( 'Create your first subscription plan to start selling tokens.', 'webhoma-subscription' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-plan-new' ) ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Add New Plan', 'webhoma-subscription' ); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
