<?php
/**
 * Subscription Plans Page Template
 * Shortcode: [wh_subscription_plans]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get active plans
$plans = wh_sub_get_all_plans( 'active' );

if ( empty( $plans ) ) {
    echo '<p>' . esc_html__( 'No subscription plans available at the moment.', 'webhoma-subscription' ) . '</p>';
    return;
}

// Separate plans by type (for display purposes)
$monthly_plans = array();
$payg_plans = array(); // Pay as you go

foreach ( $plans as $plan ) {
    if ( $plan->duration_days > 0 ) {
        $monthly_plans[] = $plan;
    } else {
        $payg_plans[] = $plan;
    }
}
?>

<section class="wh-subscription-content">
    <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i4.svg' ); ?>" alt="" class="wh-vector1">

    <div class="wh-subscription-container">
        <h1 class="wh-subscription-title"><?php esc_html_e( 'Buy a Special Subscription', 'webhoma-subscription' ); ?></h1>
        <p class="wh-subscription-subtitle">
            <?php esc_html_e( 'Select from best plan, ensuring perfect match. Need more? Customize your subscription for fit', 'webhoma-subscription' ); ?>
        </p>

        <?php if ( ! empty( $payg_plans ) || ! empty( $monthly_plans ) ) : ?>
        <div class="wh-subscription-headers">
            <?php if ( ! empty( $payg_plans ) ) : ?>
            <div class="wh-header-item">
                <h2 class="wh-sub-title-1"><?php esc_html_e( 'Pay as you go', 'webhoma-subscription' ); ?></h2>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $monthly_plans ) ) : ?>
            <div class="wh-header-item wh-header-monthly">
                <h2 class="wh-sub-title-2"><?php esc_html_e( 'Subscription Packages', 'webhoma-subscription' ); ?></h2>
            </div>
            <?php endif; ?>
        </div>

        <div class="wh-subscription-plans">
            <?php
            // Display Pay as you go plans
            foreach ( $payg_plans as $plan ) :
                $price_display = wc_price( $plan->price );
            ?>
            <div class="wh-plan-col">
                <div class="wh-sub-box">
                    <div class="wh-sub-box-items">
                        <div>
                            <?php if ( $plan->image_url ) : ?>
                                <img src="<?php echo esc_url( $plan->image_url ); ?>" alt="<?php echo esc_attr( $plan->name ); ?>" class="wh-plan-icon">
                            <?php else : ?>
                                <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i3.svg' ); ?>" alt="" class="wh-plan-icon">
                            <?php endif; ?>

                            <h3 class="wh-plan-name"><?php echo esc_html( $plan->name ); ?></h3>

                            <?php if ( $plan->description ) : ?>
                                <p class="wh-plan-desc"><?php echo esc_html( $plan->description ); ?></p>
                            <?php endif; ?>

                            <div class="wh-plan-tokens">
                                <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/coin.svg' ); ?>" alt="">
                                <span><?php echo esc_html( number_format( $plan->token_count ) ); ?> <?php esc_html_e( 'Tokens', 'webhoma-subscription' ); ?></span>
                            </div>
                        </div>

                        <button class="wh-btn wh-purchase-btn" data-product-id="<?php echo esc_attr( $plan->wc_product_id ); ?>">
                            <?php echo wp_kses_post( $price_display ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ( ! empty( $payg_plans ) && ! empty( $monthly_plans ) ) : ?>
            <div class="wh-plan-divider">
                <svg width="547" height="2" viewBox="0 0 547 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.599976 0.600098L545.6 0.60005" stroke="url(#paint0_linear_divider)" stroke-width="1.2" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="paint0_linear_divider" x1="0.599976" y1="1.1001" x2="545.6" y2="1.10005" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#F5F5F5"/>
                            <stop offset="0.501047" stop-color="#DBDBDB"/>
                            <stop offset="1" stop-color="#F8F8F8"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <?php endif; ?>

            <?php
            // Display Monthly/Subscription plans
            $plan_index = 0;
            foreach ( $monthly_plans as $plan ) :
                $plan_index++;
                $plan_class = $plan_index === 1 ? 'wh-gold' : 'wh-silver';
                $price_display = wc_price( $plan->price );
            ?>
            <div class="wh-plan-col">
                <div class="wh-sub-box wh-sub-box-premium">
                    <div class="wh-sub-box-items wh-sub-box-items-<?php echo esc_attr( $plan_class ); ?>">
                        <div>
                            <?php if ( $plan->image_url ) : ?>
                                <img src="<?php echo esc_url( $plan->image_url ); ?>" alt="<?php echo esc_attr( $plan->name ); ?>" class="wh-plan-icon">
                            <?php else : ?>
                                <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/' . ( $plan_class === 'wh-gold' ? 'i1.svg' : 'i2.svg' ) ); ?>" alt="" class="wh-plan-icon">
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

                        <button class="wh-btn wh-btn-<?php echo esc_attr( $plan_class ); ?> wh-purchase-btn" data-product-id="<?php echo esc_attr( $plan->wc_product_id ); ?>">
                            <?php esc_html_e( 'Select Plan', 'webhoma-subscription' ); ?> - <?php echo wp_kses_post( $price_display ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <img src="<?php echo esc_url( WH_SUB_URL . 'assets/img/subscription/i6.svg' ); ?>" alt="" class="wh-vector2">
</section>
