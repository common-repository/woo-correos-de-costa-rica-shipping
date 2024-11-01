<?php
/**
 * Class to manage page config settings for Correos de Costa Rica Web Service Credentials.
 *
 * @file
 * @author   ParallelDevs
 * @category Admin
 * @package  CorreosCostaRica/Config/Settings
 * @version  3.1.0
 */

if ( !defined( 'ABSPATH') ) { exit; }

define( 'CCRWS_PAGE_SETTINGS_SLUG', 'ccr-ws-setting-admin');

if ( ! class_exists( 'PDEVS_CCR_Settings_Page' ) ) :
    /**
     * PDEVS_CCR_Settings_Page Class.
     */
    class PDEVS_CCR_Settings_Page {
        /**
         * Holds the values to be used in the fields callbacks
         */
        private $options;

        /**
         * Start up
         */
        public function __construct() {
            add_action('admin_menu', array($this, 'add_plugin_page'));
            add_action('admin_init', array($this, 'page_init'));
            add_action('admin_head', 'add_css_style_form_settings');
            add_filter('plugin_action_links_' . CCRWS_PLUGIN_BASENAME, 'add_settings_plugin_links');
        }

        /**
         * Add options page
         */
        public function add_plugin_page() {
            // This page will be in admin sidebar "Correos de Costa Rica WS"
            add_menu_page(
                __('Correos de Costa Rica Web Service Settings', 'pdevs-ccr-web-service-woocommerce'),
                __('Correos de Costa Rica WS', 'pdevs-ccr-web-service-woocommerce'),
                'manage_options',
                CCRWS_PAGE_SETTINGS_SLUG,
                array($this, 'create_admin_page'),
                CCRWS_PLUGIN_URL . '/assets/images/icon-ccr.png'
            );
        }

        /**
         * Options page callback
         */
        public function create_admin_page() {
            // Set class property
            $this->options = get_option('ccr_ws_option_name');
            ?>
            <div class="wrap ccr-ws-settings">
            <div class="row">
                    <div class="main-content col col-4">
                        <h1 class="ccr-main-title"><?php echo __('General Settings', 'pdevs-ccr-web-service-woocommerce') ?></h1>

                        <!-- wordpress provides the styling for tabs. -->
                        <h2 class="nav-tab-wrapper">
                            <!-- when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active -->
                            <a href="#" class="nav-tab nav-tab-active"><?php echo __('Configuration Web Service Client', 'pdevs-ccr-web-service-woocommerce'); ?></a>
                            <a href="#ccr-sender-info-section" class="nav-tab"><?php echo __('Sender Settings', 'pdevs-ccr-web-service-woocommerce'); ?></a>
                            <a href="#ccr-msj-info-section" class="nav-tab"><?php echo __('Message Mail Settings', 'pdevs-ccr-web-service-woocommerce'); ?></a>
                        </h2>

                        <form method="post" action="options.php">
                            <?php
                            // This prints out all hidden setting fields
                            settings_fields('ccr_ws_option_group');
                            do_settings_sections(CCRWS_PAGE_SETTINGS_SLUG);
                            submit_button();
                            ?>
                        </form>
                        <div class="big-margin">
                            <p class="help"><?php echo __('Are you enjoying this plugin? The Premium add-on unlocks several powerful features.', 'pdevs-ccr-web-service-woocommerce'); ?>
                                <a href="https://www.paralleldevs.com"><?php echo __('Find out about all benefits now', 'pdevs-ccr-web-service-woocommerce'); ?></a>.
                            </p>
                        </div>
                    </div>
                    <div class="sidebar col col-2">
                        <div class="pdevs-box">
                            <div style="border: 5px dotted #67b418; padding: 0 20px; background: white;">
                                <h3><?php echo __('Correos de Costa Rica WS for WordPress Premium', 'pdevs-ccr-web-service-woocommerce'); ?></h3>
                                <p><?php echo __('This plugin has a Premium add-on, unlocking several powerful features.', 'pdevs-ccr-web-service-woocommerce'); ?>
                                    <a href="https://www.paralleldevs.com"><?php echo __('Have a look at its benefits', 'pdevs-ccr-web-service-woocommerce'); ?></a>!
                                </p>
                            </div>
                        </div>
                        <div class="pdevs-box">
                            <h4 class="pdevs-box-title"><?php echo __('Plugin developed by', 'pdevs-ccr-web-service-woocommerce'); ?> <a href="https://www.paralleldevs.com">ParallelDevs</a></h4>
                            <img src="<?php echo CCRWS_PLUGIN_URL . 'assets/images/logo-pdevs.png' ?>" alt="Paralleldevs logo">
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Register and add settings
         */
        public function page_init() {
            $settings_section_ws_data_id = 'setting_section_id';

            add_settings_section(
                $settings_section_ws_data_id, // ID
                __('Web Service Settings', 'pdevs-ccr-web-service-woocommerce'), // Title
                array($this, 'print_section_ws_info'), // Callback
                CCRWS_PAGE_SETTINGS_SLUG // Page
            );

            register_setting(
                'ccr_ws_option_group', // Option group
                'ccr_ws_option_name', // Option name
                array($this, 'sanitize') // Sanitize
            );

            $this->add_settings_field_ws_ccr( $settings_section_ws_data_id );
            $this->add_settings_field_sender_ccr( $settings_section_ws_data_id );
            $this->add_settings_field_msj_ccr( $settings_section_ws_data_id );
            $this->add_settings_field_proxy_connection( $settings_section_ws_data_id );
        }

        /**
         * Build settings fields for Web Service.
         */
        public function add_settings_field_ws_ccr( $settings_section_ws_data_id ) {
            add_settings_field(
                'ccr_url_ws',
                __('URL Web Service :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_url_ws_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_username',
                __('User :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_username_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_password',
                __('Pass :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_password_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_user_id',
                __('ID User :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_user_id_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_type_user',
                __('Type of user :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_type_user_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_service_id',
                __('ID Service :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_service_id_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_client_code', // ID
                __('Client code :', 'pdevs-ccr-web-service-woocommerce'), // Title
                array($this, 'ccr_client_code_callback'),  // Callback
                CCRWS_PAGE_SETTINGS_SLUG, // Page
                $settings_section_ws_data_id // Section
            );

            add_settings_field(
                'ccr_weight', // ID
                __('Package weight :', 'pdevs-ccr-web-service-woocommerce'), // Title
                array($this, 'ccr_weight_callback'),  // Callback
                CCRWS_PAGE_SETTINGS_SLUG, // Page
                $settings_section_ws_data_id // Section
            );
        }

        /**
         * Build settings fields for Sender.
         */
        public function add_settings_field_sender_ccr( $settings_section_ws_data_id ) {
            add_settings_field(
                'ccr_sender_info',
                '<h2 id="ccr-sender-info-section">' . __("Sender Settings", "pdevs-ccr-web-service-woocommerce"). '</h2>',
                array($this, 'print_section_sender_info'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_sender_username',
                __('Name :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_sender_username_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_sender_address',
                __('Address :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_sender_address_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_sender_zipcode',
                __('Zip Code :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_sender_zipcode_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_sender_telephone',
                __('Telephone :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_sender_telephone_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
        }

        /**
         * Build settings fields for Message on mail order.
         */
        public function add_settings_field_msj_ccr( $settings_section_ws_data_id ) {
            add_settings_field(
                'ccr_msj_info',
                '<h2 id="ccr-msj-info-section">' . __("Message In Mail Orders Settings", "pdevs-ccr-web-service-woocommerce"). '</h2>',
                array($this, 'print_section_msj_info'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_msj_label',
                __('Label :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_msj_label_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_msj_content',
                __('Message :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_msj_content_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
        }

        /**
         * Build settings fields for Message on mail order.
         *
         * @param $settings_section_ws_data_id
         */
        public function add_settings_field_proxy_connection($settings_section_ws_data_id ) {
            add_settings_field(
                'ccr_info_proxy_connection',
                '<h2 id="ccr-info-proxy-connection-section">' . __("Proxy Connection Settings", "pdevs-ccr-web-service-woocommerce"). '</h2>',
                array($this, 'print_section_proxy_connection_info'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_enable_proxy_connection',
                __('Enable Proxy Connection :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_enable_proxy_connection_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_username_proxy_connection',
                __('Username :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_username_proxy_connection_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_password_proxy_connection',
                __('Password :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_password_proxy_connection_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_ip_proxy_connection',
                __('Proxy IP :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_ip_proxy_connection_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
            add_settings_field(
                'ccr_port_proxy_connection',
                __('Port :', 'pdevs-ccr-web-service-woocommerce'),
                array($this, 'ccr_port_proxy_connection_callback'),
                CCRWS_PAGE_SETTINGS_SLUG,
                $settings_section_ws_data_id
            );
        }

        /**
         * Sanitize each setting field as needed.
         *
         * @param $input
         * @return array
         */
        public function sanitize($input) {
            $new_input = array();

            if ( isset($input['ccr_url_ws'] ) )
                $new_input['ccr_url_ws'] = esc_url_raw( $input['ccr_url_ws'] );

            if ( isset($input['ccr_username'] ) )
                $new_input['ccr_username'] = sanitize_text_field( $input['ccr_username'] );

            if ( isset($input['ccr_password'] ) )
                $new_input['ccr_password'] = $input['ccr_password'];

            if ( isset($input['ccr_user_id'] ) )
                $new_input['ccr_user_id'] = sanitize_text_field( $input['ccr_user_id'] );

            if ( isset($input['ccr_type_user'] ) )
                $new_input['ccr_type_user'] = sanitize_text_field( $input['ccr_type_user'] );

            if ( isset($input['ccr_service_id'] ) )
                $new_input['ccr_service_id'] = sanitize_text_field( $input['ccr_service_id'] );

            if ( isset($input['ccr_client_code'] ) )
                $new_input['ccr_client_code'] = sanitize_text_field( $input['ccr_client_code'] );

            if ( isset($input['ccr_weight'] ) )
                $new_input['ccr_weight'] = absint( $input['ccr_weight'] );

            if ( isset($input['ccr_sender_username'] ) )
                $new_input['ccr_sender_username'] = sanitize_text_field( $input['ccr_sender_username'] );

            if ( isset($input['ccr_sender_address'] ) )
                $new_input['ccr_sender_address'] = sanitize_text_field( $input['ccr_sender_address'] );

            if ( isset($input['ccr_sender_zipcode'] ) )
                $new_input['ccr_sender_zipcode'] = absint( $input['ccr_sender_zipcode'] );

            if ( isset($input['ccr_sender_telephone'] ) )
                $new_input['ccr_sender_telephone'] = absint( $input['ccr_sender_telephone'] );

            if ( isset($input['ccr_msj_label'] ) )
                $new_input['ccr_msj_label'] = sanitize_text_field( $input['ccr_msj_label'] );

            if ( isset($input['ccr_msj_content'] ) )
                $new_input['ccr_msj_content'] = sanitize_text_field( $input['ccr_msj_content'] );

            if ( isset($input['ccr_username_proxy_connection'] ) )
                $new_input['ccr_username_proxy_connection'] = sanitize_text_field( $input['ccr_username_proxy_connection'] );

            if ( isset($input['ccr_password_proxy_connection'] ) )
                $new_input['ccr_password_proxy_connection'] = $input['ccr_password_proxy_connection'];

            if ( isset($input['ccr_ip_proxy_connection'] ) )
                $new_input['ccr_ip_proxy_connection'] = sanitize_text_field( $input['ccr_ip_proxy_connection'] );

            if ( isset($input['ccr_port_proxy_connection'] ) )
                $new_input['ccr_port_proxy_connection'] = absint( $input['ccr_port_proxy_connection'] );

            if ( isset( $input['ccr_enable_proxy_connection'] ) )
                $new_input['ccr_enable_proxy_connection'] = sanitize_key( $input['ccr_enable_proxy_connection'] );

            return $new_input;
        }

        /**
         * Print the Section Web Service Settings text
         */
        public function print_section_ws_info() {
            echo '<p>' . __('Configuration to consume the Correos de Costa Rica web service. Note: available only for local shipments.', 'pdevs-ccr-web-service-woocommerce') . '</p>';
        }

        /**
         * Print the Section sender settings text
         */
        public function print_section_sender_info() {
            echo '<hr><p>' . __('Your company or store information.', 'pdevs-ccr-web-service-woocommerce') . '</p>';
        }

        /**
         * Print the Section sender settings text
         */
        public function print_section_msj_info() {
            echo '<hr><p>' . __('Custom message to show it on orders mail.', 'pdevs-ccr-web-service-woocommerce') . '</p>';
        }

        /**
         * Print the Section sender settings text
         */
        public function print_section_proxy_connection_info() {
            echo '<hr><p>' . __('Connection Settings.', 'pdevs-ccr-web-service-woocommerce') . '</p>';
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_url_ws_callback() {
            printf(
                '<input type="text" id="ccr_url_ws" name="ccr_ws_option_name[ccr_url_ws]" value="%s" />',
                isset( $this->options['ccr_url_ws'] ) ? esc_attr($this->options['ccr_url_ws']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_username_callback() {
            printf(
                '<input type="text" id="ccr_username" name="ccr_ws_option_name[ccr_username]" value="%s" />',
                isset( $this->options['ccr_username'] ) ? esc_attr($this->options['ccr_username']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_password_callback() {
            printf(
                '<input type="password" placeholder="******" id="ccr_password" name="ccr_ws_option_name[ccr_password]" value="%s" />',
                isset( $this->options['ccr_password'] ) ? esc_attr($this->options['ccr_password']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_user_id_callback() {
            printf(
                '<input type="text" id="ccr_user_id" name="ccr_ws_option_name[ccr_user_id]" value="%s" />',
                isset( $this->options['ccr_user_id'] ) ? esc_attr($this->options['ccr_user_id']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_type_user_callback() {
            printf(
                '<input type="text" id="ccr_type_user" name="ccr_ws_option_name[ccr_type_user]" value="%s" />',
                isset( $this->options['ccr_type_user'] ) ? esc_attr($this->options['ccr_type_user']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_service_id_callback() {
            printf(
                '<input type="text" id="ccr_service_id" name="ccr_ws_option_name[ccr_service_id]" value="%s" />',
                isset( $this->options['ccr_service_id'] ) ? esc_attr($this->options['ccr_service_id']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public  function ccr_client_code_callback() {
            printf(
                '<input type="text" id="ccr_client_code" name="ccr_ws_option_name[ccr_client_code]" value="%s" />',
                isset( $this->options['ccr_client_code'] ) ? esc_attr($this->options['ccr_client_code']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public  function ccr_weight_callback() {
            printf(
                '<input type="number" id="ccr_weight" name="ccr_ws_option_name[ccr_weight]" value="%s" />',
                isset( $this->options['ccr_weight'] ) ? esc_attr($this->options['ccr_weight']) : ''
            );
            echo '<p class="description">'. __( 'Weight (in grams) 1 kilogram (kg) equals 1000 grams', 'pdevs-ccr-web-service-woocommerce' ) . '</p>';
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_sender_username_callback() {
            printf(
                '<input type="text" id="ccr_sender_username" name="ccr_ws_option_name[ccr_sender_username]" value="%s" />',
                isset( $this->options['ccr_sender_username'] ) ? esc_attr($this->options['ccr_sender_username']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_sender_address_callback() {
            printf(
                '<input type="text" id="ccr_sender_address" name="ccr_ws_option_name[ccr_sender_address]" value="%s" />',
                isset( $this->options['ccr_sender_address'] ) ? esc_attr($this->options['ccr_sender_address']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_sender_zipcode_callback() {
            printf(
                '<input type="number" id="ccr_sender_zipcode" name="ccr_ws_option_name[ccr_sender_zipcode]" value="%s" />',
                isset( $this->options['ccr_sender_zipcode'] ) ? esc_attr($this->options['ccr_sender_zipcode']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_sender_telephone_callback() {
            printf(
                '<input type="number" id="ccr_sender_telephone" name="ccr_ws_option_name[ccr_sender_telephone]" value="%s" />',
                isset( $this->options['ccr_sender_telephone'] ) ? esc_attr($this->options['ccr_sender_telephone']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_msj_label_callback() {
            printf(
                '<input type="text" id="ccr_msj_label" name="ccr_ws_option_name[ccr_msj_label]" value="%s" />',
                isset( $this->options['ccr_msj_label'] ) ? esc_attr($this->options['ccr_msj_label']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_msj_content_callback() {
            printf(
                '<input type="text" id="ccr_msj_content" name="ccr_ws_option_name[ccr_msj_content]" value="%s" />',
                isset( $this->options['ccr_msj_content'] ) ? esc_attr($this->options['ccr_msj_content']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_enable_proxy_connection_callback() {
            ?>
            <!-- Here we are comparing stored value with 1. Stored value is 1 if user checks the checkbox otherwise empty string. -->
            <input type="checkbox" id="ccr_enable_proxy_connection" name="ccr_ws_option_name[ccr_enable_proxy_connection]" value="yes" <?php array_key_exists('ccr_enable_proxy_connection', $this->options) ? checked($this->options['ccr_enable_proxy_connection'], 'yes') : ''; ?> />
            <?php
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_username_proxy_connection_callback() {
            printf(
                '<input type="text" id="ccr_username_proxy_connection" name="ccr_ws_option_name[ccr_username_proxy_connection]" value="%s" />',
                isset( $this->options['ccr_username_proxy_connection'] ) ? esc_attr($this->options['ccr_username_proxy_connection']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_password_proxy_connection_callback() {
            printf(
                '<input type="password" placeholder="******" id="ccr_password" name="ccr_ws_option_name[ccr_password_proxy_connection]" value="%s" />',
                isset( $this->options['ccr_password_proxy_connection'] ) ? esc_attr($this->options['ccr_password_proxy_connection']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ccr_ip_proxy_connection_callback() {
            printf(
                '<input type="text" id="ccr_ip_proxy_connection" name="ccr_ws_option_name[ccr_ip_proxy_connection]" value="%s" />',
                isset( $this->options['ccr_ip_proxy_connection'] ) ? esc_attr($this->options['ccr_ip_proxy_connection']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public  function ccr_port_proxy_connection_callback() {
            printf(
                '<input type="number" id="ccr_port_proxy_connection" name="ccr_ws_option_name[ccr_port_proxy_connection]" value="%s" />',
                isset( $this->options['ccr_port_proxy_connection'] ) ? esc_attr($this->options['ccr_port_proxy_connection']) : ''
            );
        }
    }
endif; // Class_exists check.

/**
 * Add Settings action links
 *
 * @param $links
 * @return array
 */
function add_settings_plugin_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=' . CCRWS_PAGE_SETTINGS_SLUG ) . '">' . __( 'Settings', 'pdevs-ccr-web-service-woocommerce' ) . '</a>',
    );

    // Merge our new link with the default ones
    return array_merge( $plugin_links, $links );
}

/**
 * Settings form styles
 */
function add_css_style_form_settings() {
    ?>
    <style type="text/css">
        #ccr_msj_label {
            min-width: 400px;
        }
        #ccr_url_ws, #ccr_sender_address, #ccr_msj_content {
            width: 100%;
        }
    </style>
    <?php
}

