<?php
/**
 * Plugin Name: Correos de Costa Rica Shipping Plugin
 * Plugin URI: https://example.com/
 * Description: WooCommerce integration for (Correos de Costa Rica) Web Service and Shipping option. It only valid for Costa Rica Country.
 * Version: 1.0.1
 * Author: ParallelDevs
 * Author URI: http://www.paralleldevs.com/
 * Developer: Paralleldevs
 * Developer URI: http://www.paralleldevs.com/
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 *
 * WC requires at least: 2.2
 * WC tested up to: 2.3
 *
 * Copyright: © 2019 ParallelDevs.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( !defined( 'ABSPATH') ) { exit; }

define( 'CCRWS_PLUGIN_DIR', __DIR__ );
define( 'CCRWS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define( 'CCRWS_PLUGIN_URL', plugins_url('/', __FILE__) );

require_once CCRWS_PLUGIN_DIR . '/config/class-pdevs-ccr-settings-page.php';
require_once CCRWS_PLUGIN_DIR . '/includes/classes/class-pdevs-ccr-web-service-client.php';
require_once CCRWS_PLUGIN_DIR . '/includes/classes/class-pdevs-ccr-shipping-method.php';

/**
 * Check if WooCommerce is active.
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) &&
    in_array( 'wc-provincia-canton-distrito/wc-prov-cant-dist.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) :

    /**
     * Check if CCR_web_service_for_woocommerce class exists.
     **/
    if (!class_exists('PDEVS_CCR_Web_Service_Woocommerce')) :

        class PDEVS_CCR_Web_Service_Woocommerce {

            /**
             * @var PDEVS_CCR_Settings_Page
             */
            private $ccr_settings_page;

            /**
             * @var PDEVS_CCR_Web_Service_Client
             */
            private $ccr_web_service_client;

            /**
             * __construct.
             *
             * A dummy constructor to ensure CCR_web_service_for_woocommerce is only initialized once.
             *
             * @date 19/03/19
             * @since 1.0.0
             */
            public function __construct() {
                // Do nothing here.
            }

            /**
             * The real constructor to initialize CCR_web_service_for_woocommerce.
             *
             * @date 19/03/19
             * @since 1.0.0
             *
             */
            public function initialize() {
                if ( is_admin() ) {
                    $this->ccr_settings_page = new PDEVS_CCR_Settings_Page();
                }

                // Init client web service of Correos de Costa Rica.
                $this->ccr_web_service_client = new PDEVS_CCR_Web_Service_Client();

                // Add actions for ajax hooks.
                $this->run_enqueue();

                // Add actions for woocommerce.
                add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'custom_woocommerce_checkout_after_customer_details' ) );
                add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'custom_woocommerce_checkout_update_order_meta_pdevs_ccr_guide_number_save' ) );
                add_action( 'woocommerce_thankyou', array( $this, 'custom_woocommerce_thankyou_log_send') );
                add_action( 'woocommerce_order_details_after_order_table_items', array( $this, 'custom_woocommerce_order_items_table_success_meta' ), 30, 1 );
                add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'custom_woocommerce_admin_order_data_after_billing_address' ), 40, 1);
                add_action( 'woocommerce_email_after_order_table', array( $this, 'custom_woocommerce_email_after_order_table_add_guide_number' ), 12, 2);

                // Set up localization.
		        $this->load_plugin_textdomain();
            }

            /**
             *  Action hooks.
             *
             * @date    19/03/19
             * @since   1.0.0
             *
             * @return  void
             */
            public function run_enqueue() {
                // Enqueue plugin styles and scripts.
                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_pdevs_ccr_web_service_woocommerce_styles' ));
            }

             /**
             *  Enqueues plugin-specific styles.
             *
             * @date    19/03/19
             * @since   1.0.0
             *
             * @return  void
             */
            public function enqueue_pdevs_ccr_web_service_woocommerce_styles() {
                wp_enqueue_style('pdevs-ccr-web-service-woocommerce-style',
                    CCRWS_PLUGIN_URL . 'assets/css/pdevs-ccr-web-service-woocommerce.css',
                    '',
                    '1.0.0'
                );
            }

            /**
             * Add custom WooCommerce field on checkout form
             *
             * @param $checkout
             */
            public function custom_woocommerce_checkout_after_customer_details( $checkout ) {
                woocommerce_form_field('pdevs_ccr_guide_number', array(
                    'type' => 'text',
                    'class' => array('hidden'),
                    'default' => $this->ccr_web_service_client->get_guide_number_from_ws()
                ));
            }

            /**
             * Save guide number result to order meta key
             *
             * @param $order_id
             */
            public function custom_woocommerce_checkout_update_order_meta_pdevs_ccr_guide_number_save( $order_id ) {
                $order = wc_get_order( $order_id );
                if( !empty( $_POST['pdevs_ccr_guide_number'] ) && ( $order->has_shipping_method( 'ccr_shipping_id' ) ) ) {
                    update_post_meta( $order_id, 'pdevs_ccr_guide_number', sanitize_text_field($_POST['pdevs_ccr_guide_number'] ) );
                }
            }

            /**
             * Register the sent order to Correos de Costa Rica
             *
             * @param $order_id
             */
            public function custom_woocommerce_thankyou_log_send( $order_id ) {
                $log_send = get_post_meta( $order_id, 'pdevs_ccr_shipping_log', true );
                $order = wc_get_order( $order_id );

                if ( empty( $log_send ) && $order->has_shipping_method( 'ccr_shipping_id' ) ) {

                    $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

                    foreach ( $order->get_items() as $key => $order_values ) {
                        $product = $order_values->get_product();
                        if ( empty( $details ) ) {
                            $details = $product->get_title();
                        } elseif ( $details != $product->get_title() ) {
                            $details .= ", " . $product->get_title();
                        }
                    }

                    $order->add_order_note("Respond Correos de Costa Rica: " .
                        $this->ccr_web_service_client->post_log_shipping_to_ws(
                            $order_id,
                            get_post_meta( $order_id, 'pdevs_ccr_guide_number', true ),
                            $details,
                            $order->get_shipping_total(),
                            $full_name,
                            $order->get_shipping_address_1(),
                            $order->get_billing_phone(),
                            $order->get_shipping_postcode()
                        )
                    );
                }
            }

            /**
             * Shows guide number in order payment details
             *
             * @param $order
             */
            public function custom_woocommerce_order_items_table_success_meta( $order ) {
                $order_id = $order->get_id();
                $guide_number = get_post_meta( $order_id, 'pdevs_ccr_guide_number', true );

                $ccr_msj_email_label = get_option( 'ccr_ws_option_name' )['ccr_msj_label'];
                $ccr_msj_email_content = get_option( 'ccr_ws_option_name' )['ccr_msj_content'];

                if( !empty( $guide_number ) ) :
                    ?>
                    <tr class="pdevs-crr-ws order_item">
                        <td scope="row">
                            <strong class="guide-number-title"><?= ( !empty( $ccr_msj_email_label ) ) ? $ccr_msj_email_label : '' ?></strong>
                        </td>
                        <td>
                            <strong><span class="guide-number"><?php echo $guide_number; ?></span></strong>
                            <small><?= ( !empty( $ccr_msj_email_content ) ) ? $ccr_msj_email_content : '' ?></small>
                        </td>
                    </tr>
                <?php
                endif;
            }

            /**
             * Shows guide number on order meta
             *
             * @param $order
             */
            public function custom_woocommerce_admin_order_data_after_billing_address( $order ) {
                $order_id = $order->get_id();
                $guide_number = get_post_meta( $order_id, 'pdevs_ccr_guide_number', true );

                if( !empty( $guide_number ) ) :
                    ?>
                    <tr class="pdevs-crr-ws">
                        <td colspan="2">
                            <strong class="title" style="float: left;"><?php echo __( 'Guide number from Correos de Costa Rica: ', 'pdevs-ccr-web-service-woocommerce' ) ?></strong>
                            <span style="float: right;"><?= $guide_number ?></span>
                        </td>
                    </tr>
                <?php
                endif;
            }

            /**
             * Add the guide number to client email
             *
             * @param $order
             * @param $sent_to_admin
             */
            public function custom_woocommerce_email_after_order_table_add_guide_number($order, $sent_to_admin ) {
                $order_id = $order->get_id();
                $guide_number = get_post_meta($order_id, 'pdevs_ccr_guide_number', true);
                $ccr_msj_email_label = get_option( 'ccr_ws_option_name' )['ccr_msj_label'];
                $ccr_msj_email_content = get_option( 'ccr_ws_option_name' )['ccr_msj_content'];

                ob_start();
                if ( !empty( $guide_number ) ) {
                    ?>
                    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
                        <tr class="pdevs-ccr-payment-gateway">
                            <td class="td" scope="row" colspan="2">
                                <strong class="title" style="display: block;"><?= ( !empty( $ccr_msj_email_label ) ) ? $ccr_msj_email_label : '' ?></strong>
                                <span style="font-size: 12px;"><?php echo $guide_number; ?></span>
                                <span style="font-size: 12px; display: block;"><a href="https://www.correos.go.cr/rastreo/consulta_envios/rastreo.aspx"><?= (!empty( $ccr_msj_email_content )) ? $ccr_msj_email_content : '' ?></a></span>
                            </td>
                        </tr>
                    </table>
                    <?php
                }
                $html_email = ob_get_contents();
                ob_end_clean();

                echo $html_email;
            }

            /**
             * Load Localization files.
             *
             * Note: the first-loaded translation file overrides any following ones if the same translation is present.
             *
             * Locales found in:
             *      - WP_LANG_DIR/pdevs-ccr-web-service-woocommerce/woocommerce-LOCALE.mo
             *      - WP_LANG_DIR/plugins/pdevs-ccr-web-service-woocommerce-LOCALE.mo
             */
            public function load_plugin_textdomain() {
                $locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
                $locale = apply_filters( 'plugin_locale', $locale, 'pdevs-ccr-web-service-woocommerce' );

                unload_textdomain( 'pdevs-ccr-web-service-woocommerce' );
                load_textdomain( 'pdevs-ccr-web-service-woocommerce', WP_LANG_DIR . '/pdevs-ccr-web-service-woocommerce/pdevs-ccr-web-service-woocommerce-' . $locale . '.mo' );
                load_plugin_textdomain( 'pdevs-ccr-web-service-woocommerce', false, plugin_basename( dirname( WC_PLUGIN_FILE ) ) . '/languages' );
            }
        }

        /**
         * Ccr_web_service_for_woocommerce.
         *
         * The main function responsible for returning the one true ccr_web_service_for_woocommerce Instance to functions everywhere.
         * Use this function like you would a global variable, except without needing to declare the global.
         *
         * Example: <?php $ccr_web_service_for_woocommerce = ccr_web_service_for_woocommerce(); ?>
         *
         * @date 13/12/18
         * @since 1.0.0
         *
         * @return void
         */
        function pdevs_ccr_web_service_for_woocommerce() {
            global $instance_ccr_web_service_for_woocommerce;
            if ( ! isset( $instance_ccr_web_service_for_woocommerce ) ) {
                $instance_ccr_web_service_for_woocommerce = new PDEVS_CCR_Web_Service_Woocommerce();
                $instance_ccr_web_service_for_woocommerce->initialize();
            }
        }

        /**
         * Init
         */
        function pdevs_ccr_web_service_for_woocommerce_int() {
            // Initialize.
            pdevs_ccr_web_service_for_woocommerce();
        }

        add_action('init', 'pdevs_ccr_web_service_for_woocommerce_int');

    endif; // Class_exists check.

else:
    //Show dependencies message.
    require_once CCRWS_PLUGIN_DIR . '/includes/functions/pdevs-ccr-dependencies-alert-inactive-wc.php';

    return false;
endif;
