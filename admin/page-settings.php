<?php
/**
 * Settings Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wh_sub_admin_settings_page() {
    // Handle form submission
    if ( isset( $_POST['wh_save_settings'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wh_save_settings' ) ) {
        update_option( 'wh_sub_tokens_per_reveal', absint( $_POST['tokens_per_reveal'] ) );
        update_option( 'wh_sub_subscription_page_id', absint( $_POST['subscription_page_id'] ) );

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'webhoma-subscription' ) . '</p></div>';
    }

    // Get current settings
    $tokens_per_reveal = get_option( 'wh_sub_tokens_per_reveal', 1 );
    $subscription_page_id = get_option( 'wh_sub_subscription_page_id', 0 );

    // Get all pages for dropdown
    $pages = get_pages();

    ?>
    <div class="wrap wh-admin-settings">
        <h1><?php esc_html_e( 'Settings', 'webhoma-subscription' ); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'wh_save_settings' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tokens_per_reveal"><?php esc_html_e( 'Tokens per Phone Reveal', 'webhoma-subscription' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="tokens_per_reveal" id="tokens_per_reveal" class="small-text"
                               value="<?php echo esc_attr( $tokens_per_reveal ); ?>" min="1" required>
                        <p class="description"><?php esc_html_e( 'Number of tokens required to reveal a phone number', 'webhoma-subscription' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="subscription_page_id"><?php esc_html_e( 'Subscription Page', 'webhoma-subscription' ); ?></label>
                    </th>
                    <td>
                        <select name="subscription_page_id" id="subscription_page_id">
                            <option value="0"><?php esc_html_e( '-- Select Page --', 'webhoma-subscription' ); ?></option>
                            <?php foreach ( $pages as $page ) : ?>
                                <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $subscription_page_id, $page->ID ); ?>>
                                    <?php echo esc_html( $page->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Page where users can purchase subscription plans', 'webhoma-subscription' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Database Information', 'webhoma-subscription' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Plugin Version', 'webhoma-subscription' ); ?></th>
                    <td><?php echo esc_html( WH_SUB_VERSION ); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Database Tables', 'webhoma-subscription' ); ?></th>
                    <td>
                        <?php
                        global $wpdb;
                        $tables = array(
                            'barter_data',
                            'user_tokens',
                            'token_logs',
                            'viewed_listings',
                            'subscription_plans',
                            'user_subscriptions',
                        );
                        foreach ( $tables as $table ) {
                            $table_name = $wpdb->prefix . $table;
                            $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
                            $status = $exists ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
                            echo $status . ' ' . esc_html( $table_name ) . '<br>';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="wh_save_settings" class="button button-primary">
                    <?php esc_html_e( 'Save Settings', 'webhoma-subscription' ); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}
