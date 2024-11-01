<?php
/**
 * Show dependencies requirements plugins message
 */
function ccr_dependencies_alert_inactive_wc() {
    if ( current_user_can( 'activate_plugins' ) ) :
        if ( !class_exists( 'WooCommerce' ) ) : ?>
            <div id="message" class="error">
                <p>
                    <?php echo __('Correos de Costa Rica Shipping Plugin requires WooCommerce to be active.', 'pdevs-ccr-web-service-woocommerce'); ?>
                    <a href="https://wordpress.org/plugins/woocommerce/" target="_blank" ><strong>WooCommerce</strong></a>
                </p>
            </div>
        <?php
        endif;
        if ( !class_exists( 'WC Provincia-Canton-Distrito' ) ) : ?>
            <div id="message" class="error">
                <p>
                    <?php echo __('Correos de Costa Rica Shipping Plugin requires WC Provincia-Canton-Distrito to be active.', 'pdevs-ccr-web-service-woocommerce'); ?>
                    <a href="https://wordpress.org/plugins/wc-provincia-canton-distrito/" target="_blank" ><strong>WC Provincia-Canton-Distrito</strong></a>
                </p>
            </div>
        <?php
        endif;
    endif;
}

add_action('admin_notices', 'ccr_dependencies_alert_inactive_wc');
