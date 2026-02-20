<?php
/**
 * Add/Edit Subscription Plan Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wh_sub_admin_plan_edit_page() {
    $plan_id = isset( $_GET['plan_id'] ) ? absint( $_GET['plan_id'] ) : 0;
    $plan = $plan_id ? wh_sub_get_plan( $plan_id ) : null;
    $is_edit = $plan ? true : false;

    // Handle form submission
    if ( isset( $_POST['wh_save_plan'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wh_save_plan' ) ) {
        $plan_data = array(
            'name' => sanitize_text_field( $_POST['plan_name'] ),
            'description' => wp_kses_post( $_POST['plan_description'] ),
            'image_url' => esc_url_raw( $_POST['plan_image_url'] ),
            'token_count' => absint( $_POST['token_count'] ),
            'duration_days' => absint( $_POST['duration_days'] ),
            'duration_label' => sanitize_text_field( $_POST['duration_label'] ),
            'price' => floatval( $_POST['price'] ),
            'token_type' => sanitize_text_field( $_POST['token_type'] ),
            'status' => sanitize_text_field( $_POST['status'] ),
            'sort_order' => absint( $_POST['sort_order'] ),
        );

        if ( $is_edit ) {
            $result = wh_sub_update_plan( $plan_id, $plan_data );
            if ( $result ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Plan updated successfully.', 'webhoma-subscription' ) . '</p></div>';
                $plan = wh_sub_get_plan( $plan_id ); // Refresh data
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to update plan.', 'webhoma-subscription' ) . '</p></div>';
            }
        } else {
            $new_plan_id = wh_sub_create_plan( $plan_data );
            if ( $new_plan_id ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Plan created successfully.', 'webhoma-subscription' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wh-subscription-plan-edit&plan_id=' . $new_plan_id ) ) . '">' . esc_html__( 'Edit plan', 'webhoma-subscription' ) . '</a></p></div>';
                $plan = wh_sub_get_plan( $new_plan_id );
                $is_edit = true;
                $plan_id = $new_plan_id;
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to create plan.', 'webhoma-subscription' ) . '</p></div>';
            }
        }
    }

    // WooCommerce products are now auto-created, no need for dropdown

    ?>
    <div class="wrap wh-admin-plan-edit">
        <h1>
            <?php echo $is_edit ? esc_html__( 'Edit Plan', 'webhoma-subscription' ) : esc_html__( 'Add New Plan', 'webhoma-subscription' ); ?>
        </h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'wh_save_plan' ); ?>

            <div class="wh-form-container">
                <div class="wh-form-main">
                    <div class="wh-form-section">
                        <h2><?php esc_html_e( 'Plan Details', 'webhoma-subscription' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="plan_name"><?php esc_html_e( 'Plan Name', 'webhoma-subscription' ); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" name="plan_name" id="plan_name" class="regular-text"
                                           value="<?php echo esc_attr( $plan->name ?? '' ); ?>" required>
                                    <p class="description"><?php esc_html_e( 'e.g., Gold Package, Silver Package, Pay as you go', 'webhoma-subscription' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="plan_description"><?php esc_html_e( 'Description', 'webhoma-subscription' ); ?></label>
                                </th>
                                <td>
                                    <textarea name="plan_description" id="plan_description" rows="4" class="large-text"><?php echo esc_textarea( $plan->description ?? '' ); ?></textarea>
                                    <p class="description"><?php esc_html_e( 'Short description of the plan', 'webhoma-subscription' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="plan_image_url"><?php esc_html_e( 'Plan Image', 'webhoma-subscription' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="plan_image_url" id="plan_image_url" class="regular-text"
                                           value="<?php echo esc_attr( $plan->image_url ?? '' ); ?>">
                                    <button type="button" class="button wh-upload-image-button"><?php esc_html_e( 'Upload Image', 'webhoma-subscription' ); ?></button>
                                    <div id="plan_image_preview" style="margin-top: 10px;">
                                        <?php if ( ! empty( $plan->image_url ) ) : ?>
                                            <img src="<?php echo esc_url( $plan->image_url ); ?>" style="max-width: 150px; max-height: 150px;">
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="price"><?php esc_html_e( 'Price', 'webhoma-subscription' ); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="number" name="price" id="price" class="small-text" step="0.01"
                                           value="<?php echo esc_attr( $plan->price ?? 0 ); ?>" min="0" required>
                                    <p class="description"><?php echo esc_html( sprintf( __( 'Price in %s', 'webhoma-subscription' ), get_woocommerce_currency() ) ); ?></p>
                                </td>
                            </tr>

                            <?php if ( $is_edit && ! empty( $plan->wc_product_id ) ) : ?>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e( 'WooCommerce Product', 'webhoma-subscription' ); ?></label>
                                </th>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $plan->wc_product_id . '&action=edit' ) ); ?>" target="_blank">
                                        <?php esc_html_e( 'View Product', 'webhoma-subscription' ); ?> #<?php echo esc_html( $plan->wc_product_id ); ?>
                                    </a>
                                    <p class="description"><?php esc_html_e( 'Product auto-created and synced with this plan', 'webhoma-subscription' ); ?></p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="wh-form-section">
                        <h2><?php esc_html_e( 'Token Settings', 'webhoma-subscription' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="token_count"><?php esc_html_e( 'Token Count', 'webhoma-subscription' ); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="number" name="token_count" id="token_count" class="small-text"
                                           value="<?php echo esc_attr( $plan->token_count ?? 0 ); ?>" min="1" required>
                                    <p class="description"><?php esc_html_e( 'Number of tokens in this plan', 'webhoma-subscription' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="token_type"><?php esc_html_e( 'Token Type', 'webhoma-subscription' ); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select name="token_type" id="token_type" required>
                                        <option value="limited" <?php selected( $plan->token_type ?? 'limited', 'limited' ); ?>>
                                            <?php esc_html_e( 'Limited (Expire after duration)', 'webhoma-subscription' ); ?>
                                        </option>
                                        <option value="unlimited" <?php selected( $plan->token_type ?? '', 'unlimited' ); ?>>
                                            <?php esc_html_e( 'Unlimited (Never expire)', 'webhoma-subscription' ); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>

                            <tr class="wh-duration-row">
                                <th scope="row">
                                    <label for="duration_days"><?php esc_html_e( 'Duration (days)', 'webhoma-subscription' ); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="duration_days" id="duration_days" class="small-text"
                                           value="<?php echo esc_attr( $plan->duration_days ?? 30 ); ?>" min="0">
                                    <p class="description"><?php esc_html_e( 'Token validity period in days (0 = unlimited)', 'webhoma-subscription' ); ?></p>
                                </td>
                            </tr>

                            <tr class="wh-duration-row">
                                <th scope="row">
                                    <label for="duration_label"><?php esc_html_e( 'Duration Label', 'webhoma-subscription' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="duration_label" id="duration_label" class="regular-text"
                                           value="<?php echo esc_attr( $plan->duration_label ?? '' ); ?>">
                                    <p class="description"><?php esc_html_e( 'e.g., "Monthly", "30 days", "1 Year"', 'webhoma-subscription' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>

                <div class="wh-form-sidebar">
                    <div class="wh-form-section">
                        <h2><?php esc_html_e( 'Publish', 'webhoma-subscription' ); ?></h2>

                        <div class="wh-publish-box">
                            <div class="wh-publish-field">
                                <label for="status"><?php esc_html_e( 'Status', 'webhoma-subscription' ); ?></label>
                                <select name="status" id="status" class="widefat">
                                    <option value="active" <?php selected( $plan->status ?? 'active', 'active' ); ?>>
                                        <?php esc_html_e( 'Active', 'webhoma-subscription' ); ?>
                                    </option>
                                    <option value="inactive" <?php selected( $plan->status ?? '', 'inactive' ); ?>>
                                        <?php esc_html_e( 'Inactive', 'webhoma-subscription' ); ?>
                                    </option>
                                </select>
                            </div>

                            <div class="wh-publish-field">
                                <label for="sort_order"><?php esc_html_e( 'Sort Order', 'webhoma-subscription' ); ?></label>
                                <input type="number" name="sort_order" id="sort_order" class="widefat"
                                       value="<?php echo esc_attr( $plan->sort_order ?? 0 ); ?>" min="0">
                                <p class="description"><?php esc_html_e( 'Lower numbers appear first', 'webhoma-subscription' ); ?></p>
                            </div>

                            <div class="wh-publish-actions">
                                <button type="submit" name="wh_save_plan" class="button button-primary button-large">
                                    <?php echo $is_edit ? esc_html__( 'Update Plan', 'webhoma-subscription' ) : esc_html__( 'Create Plan', 'webhoma-subscription' ); ?>
                                </button>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wh-subscription-plans' ) ); ?>" class="button button-large">
                                    <?php esc_html_e( 'Cancel', 'webhoma-subscription' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if ( $is_edit ) : ?>
                        <div class="wh-form-section">
                            <h2><?php esc_html_e( 'Plan Info', 'webhoma-subscription' ); ?></h2>
                            <div class="wh-info-box">
                                <p><strong><?php esc_html_e( 'Plan ID:', 'webhoma-subscription' ); ?></strong> <?php echo esc_html( $plan->id ); ?></p>
                                <p><strong><?php esc_html_e( 'Created:', 'webhoma-subscription' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $plan->created_at ) ) ); ?></p>
                                <p><strong><?php esc_html_e( 'Updated:', 'webhoma-subscription' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $plan->updated_at ) ) ); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <?php
}
