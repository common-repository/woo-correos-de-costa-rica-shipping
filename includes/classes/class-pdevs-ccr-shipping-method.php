<?php
/**
 * Class to create a custom shipping method for Correos de Costa Rica.
 *
 * @file
 * @author   ParallelDevs
 * @category Admin
 * @package  CorreosCostaRica/Includes/Classes/CCRShippingMethod
 * @version  1.0.0
 */

if ( !defined( 'WPINC' ) ) { die; }

/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) &&
    in_array( 'wc-provincia-canton-distrito/wc-prov-cant-dist.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) :

    function pdevs_shipping_method_ccr_init() {
        /**
         * To ensure the classes you need to extend exist.
         */
        if ( !class_exists( 'PDEVS_CCR_Shipping_Method' ) ) {
            /**
             * PDEVS_CCR_Shipping_Method Class.
             */
            class PDEVS_CCR_Shipping_Method extends WC_Shipping_Method {

                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct( $instance_id = 0 ) {
                    $this->id                 = 'ccr_shipping_id';
                    $this->instance_id        = absint( $instance_id );
                    $this->method_title       = __( 'Correos de Costa Rica Shipping', 'pdevs-ccr-web-service-woocommerce' );
                    $this->method_description = __( 'Custom Shipping Method for Correos de Costa Rica', 'pdevs-ccr-web-service-woocommerce' ) . ' ' .
                        '<img src="'. CCRWS_PLUGIN_URL . 'assets/images/icon-pdevs.png" class="icon-pdevs" alt="Paralleldevs logo">' .  ' ' .
                        __('Plugin developed by ', 'pdevs-ccr-web-service-woocommerce') . '<a href="https://www.paralleldevs.com">ParallelDevs</a> '. ' ' .
                        '<img src="'. CCRWS_PLUGIN_URL . 'assets/images/icon-pdevs.png" class="icon-pdevs" alt="Paralleldevs logo">' .  ' ' .
                        __('This plugin has a Premium add-on, unlocking several powerful features.', 'pdevs-ccr-web-service-woocommerce') . ' ' .
                        '<a href="https://www.paralleldevs.com">' . __('Have a look at its benefits', 'pdevs-ccr-web-service-woocommerce'). '</a>!';
                    $this->supports           = array(
                        'shipping-zones',
                        'instance-settings',
                        'instance-settings-modal',
                    );

                    $this->init();

                    $enable_value = get_option('enabled_settings_ccr_shipping');
                    $title_value = get_option('title_settings_ccr_shipping');
                    $shipping_price_inside_GAM_value = get_option('shipping_price_inside_GAM_settings_ccr_shipping');
                    $shipping_price_outside_GAM_value = get_option('shipping_price_outside_GAM_settings_ccr_shipping');

                    $min_amount = get_option('min_amount_settings_ccr_shipping');
                    $requires = get_option('requires_settings_ccr_shipping');

                    $this->enabled = isset( $enable_value ) ? $enable_value : 'yes';
                    $this->title = isset( $title_value ) ? $title_value : __( 'Correos de Costa Rica Shipping', 'pdevs-ccr-web-service-woocommerce' );
                    $this->shipping_price_inside_GAM = isset( $shipping_price_inside_GAM_value ) ? $shipping_price_inside_GAM_value : 2000;
                    $this->shipping_price_outside_GAM = isset( $shipping_price_outside_GAM_value ) ? $shipping_price_outside_GAM_value : 3000;

                    $title_value_free_shipping = get_option('shipping_title_free_settings_ccr_shipping');

                    $this->shipping_title_free = __( 'Free shipping', 'pdevs-ccr-web-service-woocommerce' );

                    $this->shipping_title_free = isset( $title_value_free_shipping ) ? $title_value_free_shipping : __( 'Free shipping', 'pdevs-ccr-web-service-woocommerce' );
                    $this->shipping_min_amount = $min_amount;
                    $this->shipping_requires   = $requires;
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields();
                    $this->init_settings();

                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                    add_action( 'admin_footer', array( 'PDEVS_CCR_Shipping_Method', 'enqueue_admin_js' ), 10 ); // Priority needs to be higher than wc_print_js (25).
                }

                /**
                 * Save form admin values settings.
                 *
                 * @return bool|void
                 */
                public function process_admin_options() {
                    if ( isset( $_POST['data']['woocommerce_ccr_shipping_id_enabled'] ) ) {
                        update_option( 'enabled_settings_ccr_shipping', 'yes' );
                        $this->settings["enabled"] =  'yes';
                    }

                    if ( isset( $_POST['data']['woocommerce_ccr_shipping_id_title'] ) ) {
                        if ( $this->validate_fields_settings($_POST['data']['woocommerce_ccr_shipping_id_title'], 'string', __( 'Title', 'pdevs-ccr-web-service-woocommerce' )) ) {
                            update_option( "title_settings_ccr_shipping", $_POST['data']['woocommerce_ccr_shipping_id_title'] );
                            $this->settings["title"] = $_POST['data']['woocommerce_ccr_shipping_id_title'];
                        } else {
                            return false;
                        }
                    }

                    if ( isset( $_POST['data']['woocommerce_ccr_shipping_id_shipping_price_inside_GAM'] ) ) {
                        if ( $this->validate_fields_settings($_POST['data']['woocommerce_ccr_shipping_id_shipping_price_inside_GAM'], 'numeric', __( 'Greater Metropolitan Area shipping cost (₡)', 'pdevs-ccr-web-service-woocommerce' )) ) {
                            update_option( "shipping_price_inside_GAM_settings_ccr_shipping", $_POST['data']['woocommerce_ccr_shipping_id_shipping_price_inside_GAM'] );
                            $this->settings["shipping_price_inside_GAM"] = $_POST['data']['woocommerce_ccr_shipping_id_shipping_price_inside_GAM'];
                        } else {
                            return false;
                        }
                    }

                    if ( isset( $_POST['data']['woocommerce_ccr_shipping_id_shipping_price_outside_GAM'] ) ) {
                        if ( $this->validate_fields_settings($_POST['data']['woocommerce_ccr_shipping_id_shipping_price_outside_GAM'], 'numeric', __( 'Rest of the country shipping cost (₡)', 'pdevs-ccr-web-service-woocommerce' )) ) {
                            update_option( "shipping_price_outside_GAM_settings_ccr_shipping", $_POST['data']['woocommerce_ccr_shipping_id_shipping_price_outside_GAM'] );
                            $this->settings["shipping_price_outside_GAM"] = $_POST['data']['woocommerce_ccr_shipping_id_shipping_price_outside_GAM'];
                        } else {
                            return false;
                        }
                    }

                    if ( isset( $_POST['data']['woocommerce_ccr_shipping_id_shipping_title_free'] ) ) {
                        if ( $this->validate_fields_settings($_POST['data']['woocommerce_ccr_shipping_id_shipping_title_free'], 'string', __( 'Title free shipping', 'pdevs-ccr-web-service-woocommerce' )) ) {
                            update_option( "shipping_title_free_settings_ccr_shipping", $_POST['data']['woocommerce_ccr_shipping_id_shipping_title_free'] );
                            $this->settings["shipping_title_free"] = $_POST['data']['woocommerce_ccr_shipping_id_shipping_title_free'];
                        } else {
                            return false;
                        }
                    }

                    if ( isset( $_POST['data']['woocommerce_ccr_shipping_id_shipping_requires'] ) ) {
                        if ( $this->validate_fields_settings($_POST['data']['woocommerce_ccr_shipping_id_shipping_requires'], 'string', __( 'Free shipping requires...', 'pdevs-ccr-web-service-woocommerce' )) ) {
                            update_option( "requires_settings_ccr_shipping", $_POST['data']['woocommerce_ccr_shipping_id_shipping_requires'] );
                            $this->settings["shipping_requires"] = $_POST['data']['woocommerce_ccr_shipping_id_shipping_requires'];
                        } else {
                            return false;
                        }
                    }

                    if ( isset( $_POST['data']['woocommerce_ccr_shipping_id_shipping_min_amount'] ) ) {
                        if ( $this->validate_fields_settings($_POST['data']['woocommerce_ccr_shipping_id_shipping_min_amount'], 'numeric', __( 'Minimum order amount', 'pdevs-ccr-web-service-woocommerce' )) ) {
                            update_option( "min_amount_settings_ccr_shipping", $_POST['data']['woocommerce_ccr_shipping_id_shipping_min_amount'] );
                            $this->settings["shipping_min_amount"] = $_POST['data']['woocommerce_ccr_shipping_id_shipping_min_amount'];
                        } else {
                            return false;
                        }
                    }
                }

                /**
                 * Define settings field for this shipping
                 * @return void
                 */
                function init_form_fields() {

                    $this->form_fields = array(
                        'enabled' => array(
                            'title' => __( 'Enable', 'pdevs-ccr-web-service-woocommerce' ),
                            'type' => 'checkbox',
                            'description' => __( 'Enable this shipping.', 'pdevs-ccr-web-service-woocommerce' ),
                            'default' => get_option('enabled_settings_ccr_shipping'),
                            'required' => false,
                        ),
                        'title' => array(
                            'title' => __( 'Title', 'pdevs-ccr-web-service-woocommerce' ),
                            'type' => 'text',
                            'description' => __( 'Title to be display on site', 'pdevs-ccr-web-service-woocommerce' ),
                            'default' => get_option('title_settings_ccr_shipping'),
                            'placeholder' => __( 'Correos de Costa Rica Shipping', 'pdevs-ccr-web-service-woocommerce' ),
                            'required' => true
                        ),
                        'shipping_price_inside_GAM' => array(
                            'title' => __( 'Greater Metropolitan Area shipping cost (₡)', 'pdevs-ccr-web-service-woocommerce' ),
                            'type' => 'number',
                            'description' => __( 'Shipping cost in colones', 'pdevs-ccr-web-service-woocommerce' ),
                            'default' => get_option('shipping_price_inside_GAM_settings_ccr_shipping'),
                            'placeholder' => '2000',
                            'required' => true
                        ),
                        'shipping_price_outside_GAM' => array(
                            'title' => __( 'Rest of the country shipping cost (₡)', 'pdevs-ccr-web-service-woocommerce' ),
                            'type' => 'number',
                            'description' => __( 'Shipping cost in colones', 'pdevs-ccr-web-service-woocommerce' ),
                            'default' => get_option('shipping_price_outside_GAM_settings_ccr_shipping'),
                            'placeholder' => '3000',
                            'required' => true
                        ),
                        'shipping_title_free' => array(
                            'title' => __( 'Title free shipping', 'pdevs-ccr-web-service-woocommerce' ),
                            'type' => 'text',
                            'description' => __( 'Title to be display on site for free shipping', 'pdevs-ccr-web-service-woocommerce' ),
                            'default' => get_option('shipping_title_free_settings_ccr_shipping'),
                            'placeholder' => __( 'Title free shipping', 'pdevs-ccr-web-service-woocommerce' ),
                            'required' => true
                        ),
                        'shipping_requires'   => array(
                            'title'   => __( 'Free shipping requires...', 'pdevs-ccr-web-service-woocommerce' ),
                            'type'    => 'select',
                            'class'   => 'wc-enhanced-select',
                            'default' => get_option('requires_settings_ccr_shipping'),
                            'options' => array(
                                ''           => __( 'N/A', 'pdevs-ccr-web-service-woocommerce' ),
                                'coupon'     => __( 'A valid free shipping coupon', 'pdevs-ccr-web-service-woocommerce' ),
                                'min_amount' => __( 'A minimum order amount', 'pdevs-ccr-web-service-woocommerce' ),
                                'either'     => __( 'A minimum order amount OR a coupon', 'pdevs-ccr-web-service-woocommerce' ),
                                'both'       => __( 'A minimum order amount AND a coupon', 'pdevs-ccr-web-service-woocommerce' ),
                            ),
                        ),
                        'shipping_min_amount' => array(
                            'title'       => __( 'Minimum order amount', 'pdevs-ccr-web-service-woocommerce' ),
                            'type'        => 'price',
                            'placeholder' => wc_format_localized_price( 0 ),
                            'description' => __( 'Users will need to spend this amount to get free shipping (if enabled above).', 'pdevs-ccr-web-service-woocommerce' ),
                            'default'     => get_option('min_amount_settings_ccr_shipping'),
                            'desc_tip'    => true,
                        ),
                    );
                }

                /**
                 * Get setting form fields for instances of this shipping method within zones.
                 *
                 * @return array
                 */
                public function get_instance_form_fields() {
                    return parent::get_instance_form_fields();
                }


                /**
                 * See if free shipping is available based on the package and cart.
                 *
                 * @param array $package Shipping package.
                 * @return bool
                 */
                public function is_available( $package ) {
                    $has_coupon         = false;
                    $has_met_min_amount = false;

                    if ( in_array( $this->shipping_requires, array( 'coupon', 'either', 'both' ), true ) ) {
                        $coupons = WC()->cart->get_coupons();

                        if ( $coupons ) {
                            foreach ( $coupons as $code => $coupon ) {
                                if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
                                    $has_coupon = true;
                                    break;
                                }
                            }
                        }
                    }

                    if ( in_array( $this->shipping_requires, array( 'min_amount', 'either', 'both' ), true ) ) {
                        $total = WC()->cart->get_displayed_subtotal();

                        if ( WC()->cart->display_prices_including_tax() ) {
                            $total = round( $total - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ), wc_get_price_decimals() );
                        } else {
                            $total = round( $total - WC()->cart->get_discount_total(), wc_get_price_decimals() );
                        }

                        if ( $total >= $this->shipping_min_amount ) {
                            $has_met_min_amount = true;
                        }
                    }

                    switch ( $this->shipping_requires ) {
                        case 'min_amount':
                            $is_available = $has_met_min_amount;
                            break;
                        case 'coupon':
                            $is_available = $has_coupon;
                            break;
                        case 'both':
                            $is_available = $has_met_min_amount && $has_coupon;
                            break;
                        case 'either':
                            $is_available = $has_met_min_amount || $has_coupon;
                            break;
                        default:
                            $is_available = true;
                            break;
                    }


                    update_option( 'is_available_settings_ccr_shipping',  $is_available);

                    return true;
                }


                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param array $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {

                    $country = $package["destination"]["country"];
                    $post_code = $package["destination"]["postcode"];

                    $title = $this->title;

                    if ('gam' === $this->get_shipping_zone_ccr_allowed_by_zipcode( $post_code ) ) {
                        $cost = get_option("shipping_price_inside_GAM_settings_ccr_shipping");
                    } else {
                        $cost = get_option("shipping_price_outside_GAM_settings_ccr_shipping");
                    }

                    if ( get_option("is_available_settings_ccr_shipping") === true || get_option("is_available_settings_ccr_shipping") === "1" ) {
                        $cost = 0;
                        $title = $this->shipping_title_free;
                    }

                    if ('CR' === $country) {
                        $rate = array(
                            'id' => $this->id,
                            'label' => $title,
                            'cost' => $cost,
                            'taxes'   => false,
                            'package' => $package,
                        );

                        $this->add_rate( $rate );
                    }
                }

                /**
                 * Enqueue JS to handle free shipping options.
                 *
                 * Static so that's enqueued only once.
                 */
                public static function enqueue_admin_js() {
                    wc_enqueue_js(
                        "jQuery( function( $ ) {
                        function wcFreeShippingShowHideMinAmountField( el ) {
                            var form = $( el ).closest( 'form' );
                            var minAmountField = $( '#woocommerce_ccr_shipping_id_shipping_min_amount', form ).closest( 'tr' );
                            if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
                                minAmountField.hide();
                            } else {
                                minAmountField.show();
                            }
                        }
        
                        $( document.body ).on( 'change', '#woocommerce_ccr_shipping_id_shipping_requires', function() {
                            wcFreeShippingShowHideMinAmountField( this );
                        });
        
                        // Change while load.
                        $( '#woocommerce_ccr_shipping_id_shipping_requires' ).change();
                        $( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
                            if ( 'wc-modal-shipping-method-settings' === target ) {
                                wcFreeShippingShowHideMinAmountField( $( '#wc-backbone-modal-dialog #woocommerce_ccr_shipping_id_shipping_requires', evt.currentTarget ) );
                            }
                        } );
                        });"
                    );
                }

                /**
                 * Get the allowed shipping zone by zip code.
                 *
                 * @param $postcode
                 * @return string
                 */
                private function get_shipping_zone_ccr_allowed_by_zipcode( $postcode ) {
                    $output = 'rest_country';
                    $postcode = (int) $postcode;

                    if ( in_array( $postcode, $this->get_shipping_zone_gam_allowed(), true ) ) {
                        $output = 'gam';

                        return $output;
                    }

                    if ( in_array( $postcode, $this->get_shipping_zone_rest_country_allowed(), true ) ) {
                        $output = 'rest_country';

                        return $output;
                    }

                    return $output;
                }

                /**
                 * Function to validate form submit.
                 *
                 * @param $value
                 * @param $type
                 * @param $field_name
                 * @return bool
                 */
                private function validate_fields_settings($value, $type, $field_name) {
                    $output = false;
                    switch ($type) {
                        case "string":
                            if (strlen(trim($value)) > 2 && trim($value) !== "") {
                                $output = true;
                            }else {
                                WC_Admin_Settings::add_error(sprintf(__('%s is required and should has minimum 3 letters', 'pdevs-ccr-web-service-woocommerce'), $field_name));
                                WC_Admin_Settings::show_messages();
                                $output = false;
                            }
                            break;
                        case "numeric":
                            if (is_numeric($value)) {
                                $output = true;
                            }else {
                                WC_Admin_Settings::add_error(sprintf(__('%s is required and should be numeric value', 'pdevs-ccr-web-service-woocommerce'), $field_name));
                                WC_Admin_Settings::show_messages();
                                $output = false;
                            }
                            break;
                    }

                    return $output;
                }

                /**
                 * Return all zipcode allowed inside GAM to calc shipping cost.
                 * @return array
                 */
                private function get_shipping_zone_gam_allowed() {
                    return [
                        11205,
                        11001,
                        11004,
                        11003,
                        11005,
                        11002,
                        10601,
                        10607,
                        11801,
                        11802,
                        11803,
                        11804,
                        10310,
                        10312,
                        10313,
                        10307,
                        10305,
                        10303,
                        10302,
                        10311,
                        10304,
                        10201,
                        10202,
                        10203,
                        10803,
                        10801,
                        10805,
                        10804,
                        10807,
                        10802,
                        11503,
                        11502,
                        11501,
                        11504,
                        10701,
                        10702,
                        10705,
                        10704,
                        10703,
                        11403,
                        11401,
                        10101,
                        10104,
                        10110,
                        10103,
                        10108,
                        10102,
                        10109,
                        10106,
                        10111,
                        10107,
                        10105,
                        10906,
                        10905,
                        10903,
                        10902,
                        10901,
                        10904,
                        11303,
                        11302,
                        11305,
                        11304,
                        11301,
                        11104,
                        20101,
                        20110,
                        20113,
                        20109,
                        20104,
                        20102,
                        20108,
                        20112,
                        20501,
                        20701,
                        20801,
                        20201,
                        30601,
                        30105,
                        30103,
                        30106,
                        30102,
                        30101,
                        30104,
                        30801,
                        30305,
                        30308,
                        30302,
                        30303,
                        30304,
                        30307,
                        30301,
                        30701,
                        30201,
                        40201,
                        40701,
                        40801,
                        40101,
                        40102,
                        40103,
                        40104,
                        40601,
                        40901,
                        40501,
                        40401,
                        40301,
                    ];
                }

                /**
                 * Return all zipcode allowed outside of GAM to calc shipping cost
                 *
                 * @return array
                 */
                private function get_shipping_zone_rest_country_allowed() {
                    return [
                        20103,
                        20105,
                        20107,
                        20106,
                        20114,
                        20111,
                        21107,
                        21104,
                        21102,
                        21105,
                        21103,
                        21106,
                        21101,
                        20505,
                        20508,
                        20503,
                        20504,
                        20506,
                        20507,
                        20308,
                        20301,
                        20307,
                        20306,
                        20302,
                        20303,
                        20304,
                        20305,
                        21502,
                        21503,
                        21504,
                        21501,
                        21402,
                        21403,
                        21401,
                        21404,
                        20604,
                        20607,
                        20601,
                        20608,
                        20605,
                        20603,
                        20606,
                        20602,
                        20904,
                        20902,
                        20903,
                        20905,
                        20901,
                        20703,
                        20705,
                        20706,
                        20707,
                        20704,
                        20702,
                        20804,
                        20805,
                        20802,
                        20803,
                        21004,
                        21003,
                        21011,
                        21002,
                        21007,
                        21009,
                        21008,
                        21012,
                        21006,
                        21013,
                        21001,
                        21010,
                        21005,
                        20402,
                        20403,
                        20401,
                        20209,
                        20211,
                        20208,
                        20213,
                        20204,
                        20205,
                        20207,
                        20203,
                        20206,
                        20202,
                        20210,
                        20212,
                        21302,
                        21304,
                        21305,
                        21306,
                        21303,
                        21301,
                        21307,
                        21205,
                        21204,
                        21201,
                        21202,
                        21203,
                        30603,
                        30602,
                        30107,
                        30109,
                        30110,
                        30111,
                        30108,
                        30804,
                        30802,
                        30803,
                        30401,
                        30403,
                        30402,
                        30704,
                        30702,
                        30703,
                        30705,
                        30204,
                        30205,
                        30203,
                        30202,
                        30512,
                        30511,
                        30502,
                        30506,
                        30503,
                        30504,
                        30509,
                        30505,
                        30508,
                        30510,
                        30507,
                        30501,
                        40206,
                        40203,
                        40202,
                        40204,
                        40205,
                        40703,
                        40702,
                        40802,
                        40803,
                        40105,
                        40603,
                        40604,
                        40602,
                        40902,
                        40505,
                        40504,
                        40502,
                        40503,
                        40404,
                        40406,
                        40403,
                        40402,
                        40405,
                        40308,
                        40304,
                        40303,
                        40302,
                        40306,
                        40305,
                        40307,
                        41005,
                        41003,
                        41002,
                        41004,
                        41001,
                        50704,
                        50701,
                        50703,
                        50702,
                        50401,
                        50402,
                        50403,
                        50404,
                        50504,
                        50501,
                        50502,
                        50503,
                        50604,
                        50601,
                        50602,
                        50605,
                        50603,
                        51101,
                        51104,
                        51102,
                        51103,
                        51003,
                        51001,
                        51002,
                        51004,
                        50102,
                        50105,
                        50101,
                        50103,
                        50104,
                        50906,
                        50901,
                        50905,
                        50904,
                        50902,
                        50903,
                        50207,
                        50202,
                        50201,
                        50206,
                        50204,
                        50205,
                        50203,
                        50302,
                        50308,
                        50305,
                        50306,
                        50307,
                        50301,
                        50309,
                        50304,
                        50303,
                        50807,
                        50805,
                        50802,
                        50804,
                        50806,
                        50801,
                        50803,
                        60603,
                        60601,
                        60602,
                        60304,
                        60308,
                        60309,
                        60301,
                        60307,
                        60306,
                        60305,
                        60303,
                        60302,
                        61003,
                        61001,
                        61002,
                        61004,
                        60803,
                        60804,
                        60805,
                        60802,
                        60801,
                        60201,
                        60203,
                        60205,
                        60202,
                        60204,
                        61101,
                        61102,
                        60701,
                        60703,
                        60704,
                        60702,
                        60402,
                        60401,
                        60403,
                        60504,
                        60502,
                        60505,
                        60501,
                        60503,
                        60901,
                        60114,
                        60116,
                        60108,
                        60112,
                        60113,
                        60103,
                        60111,
                        60115,
                        60107,
                        60110,
                        60104,
                        60106,
                        60109,
                        60105,
                        60102,
                        60101,
                        70605,
                        70601,
                        70602,
                        70603,
                        70604,
                        70101,
                        70104,
                        70103,
                        70102,
                        70502,
                        70503,
                        70501,
                        70205,
                        70206,
                        70201,
                        70202,
                        70207,
                        70203,
                        70204,
                        70306,
                        70305,
                        70303,
                        70304,
                        70302,
                        70301,
                        70401,
                        70403,
                        70402,
                        70404
                    ];
                }
            }
        }

    }

    add_action( 'woocommerce_shipping_init', 'pdevs_shipping_method_ccr_init' );

    /**
     * @param $methods
     * @return array
     */
    function add_pdevs_ccr_shipping_method( $methods ) {
        $methods['ccr_shipping_id'] = 'PDEVS_CCR_Shipping_Method';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'add_pdevs_ccr_shipping_method' );

else:
    //Show dependencies message.
    require_once CCRWS_PLUGIN_DIR . '/includes/functions/pdevs-ccr-dependencies-alert-inactive-wc.php';

    return false;
endif;
