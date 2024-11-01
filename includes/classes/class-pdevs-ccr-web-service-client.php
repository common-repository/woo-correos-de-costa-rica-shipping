<?php
/**
 * Class to manage the connection for Correos de Costa Rica Web Service.
 *
 * @file
 * @author   ParallelDevs
 * @category Admin
 * @package  CorreosCostaRica/Includes/Classes/WebServiceClient
 * @version  1.0.0
 */

if ( !defined( 'ABSPATH') ) { exit; }

define( 'METHOD_CCR_GET_GUIDE', 'ccrGenerarGuia' );
define( 'METHOD_CCR_LOG_SEND', 'ccrRegistroEnvio' );

if ( ! class_exists( 'PDEVS_CCR_Web_Service_Client' ) ) :
    /**
     * PDEVS_CCR_Web_Service_Client Class.
     */
    class PDEVS_CCR_Web_Service_Client {

        /**
         * @var array
         */
        private $ccr_ws_settings_options;

        /**
         * PDEVS_CCR_Web_Service_Client constructor.
         */
        public function __construct() {
            // Getting fields settings from config page.
            $this->ccr_ws_settings_options = get_option('ccr_ws_option_name');
        }

        /**
         * Getting guide number id from Correos de Costa Rica Web Services.
         *
         * @return int|mixed
         */
        public function get_guide_number_from_ws() {
            $output_guide_id = null;

            $arguments['Datos'] = [
                'CodCliente' => $this->ccr_ws_settings_options['ccr_client_code'],
                'TipoCliente' => $this->ccr_ws_settings_options['ccr_type_user'],
            ];

            $guide_id = $this->ccr_ws_client_query_call( $arguments, METHOD_CCR_GET_GUIDE );

            if ( '00' === $guide_id->ccrGenerarGuiaResult->Cod_Respuesta ) {
                $output_guide_id = $guide_id->ccrGenerarGuiaResult->ListadoXML;
            } else {
                $this->ccr_ws_client_log_message(
                    sprintf(
                        "Guia: %s: %s",
                        $guide_id->ccrGenerarGuiaResult->Cod_Respuesta,
                        $guide_id->ccrGenerarGuiaResult->Mensaje_Respuesta
                    )
                );
            }

            return $output_guide_id;
        }

        /**
         * Send to Correos de Costa Rica the register or log shipping
         *
         * @param $order_id
         * @param $guide_number
         * @param $details
         * @param $shipping_cost
         * @param $full_name
         * @param $address
         * @param $phone
         * @param $post_code
         * @return string
         */
        public function post_log_shipping_to_ws( $order_id, $guide_number, $details, $shipping_cost, $full_name, $address, $phone, $post_code ) {
            // Create shipping log - Envio
            $arguments = [
                'ccrReqEnvio' => [
                    'Cliente' => $this->ccr_ws_settings_options['ccr_client_code'], // String - Número de cliente
                    'Envio'   => [
                        'ENVIO_ID'            => ( $guide_number ) ? sanitize_text_field( $guide_number ) : '', // String length 25 - Número de guía generada por el proceso ccrGenerarGuia.
                        'DEST_APARTADO'       => $post_code, // String length 20 - Número del código postal del destinatario (campo requerido).
                        'SERVICIO'            => $this->ccr_ws_settings_options['ccr_service_id'], // String length 5 - Número del servicio, Este se le será proporcionado (ver página 4).
                        'DEST_DIRECCION'      => substr( $address, 0, 300 ), // String length 300 - Dirección física del destinatario.
                        'DEST_NOMBRE'         => substr( $full_name, 0, 100 ), // String length 100 - Nombre del destinatario.
                        'DEST_TELEFONO'       => str_replace( '-', '', $phone ), // String length 10 - Número telefónico del destinatario.
                        'DEST_ZIP'            => $post_code, // String length 8 - Código postal del destinatario.
                        'FECHA_RECEPCION'     => strtotime( date( 'Y-m-d H:i:s' ) ), // Datetime - Fecha actual en la que se genera la guía, (Date.now).
                        'ID_DISTRITO_DESTINO' => $post_code, // String length 30 - Código postal del distrito destino.
                        'MONTO_FLETE'         => !empty( $shipping_cost ) ? $shipping_cost : 0, // String - Monto del flete.
                        'OBSERVACIONES'       => !empty( $details ) ? substr( $details, 0, 200 ) : '', // String length 200 - Descripción del contenido del envío (Por ejemplo: accesorios, zapatos, libros, CDs, etc).
                        'PESO'                => $this->ccr_ws_settings_options['ccr_weight'], // Decimal - Peso del envío en gramos
                        'CLIENTE_ID'          => $this->ccr_ws_settings_options['ccr_client_code'], // String length 10 - Identificación del cliente (ver página 4).
                        'SEND_DIRECCION'      => substr( $this->ccr_ws_settings_options['ccr_sender_address'], 0, 300 ), // String length 300 - Dirección física del remitente.
                        'SEND_NOMBRE'         => substr( $this->ccr_ws_settings_options['ccr_sender_username'], 0, 100 ), // String length 100 - Nombre del remitente.
                        'SEND_TELEFONO'       => str_replace( '-', '', $this->ccr_ws_settings_options['ccr_sender_telephone'] ), // String length 50 - Número telefónico del remitente.
                        'SEND_ZIP'            => strval( $this->ccr_ws_settings_options['ccr_sender_zipcode'] ), // String length 8 - Código postal del remitente.
                        'USUARIO_ID'          => intval( $this->ccr_ws_settings_options['ccr_user_id'] ), // Integer - Código postal del remitente.
                    ]
                ]
            ];

            update_post_meta( $order_id, 'pdevs_ccr_shipping_log', $arguments );

            $log = $this->ccr_ws_client_query_call($arguments, METHOD_CCR_LOG_SEND);
            $this->ccr_ws_client_log_message( sprintf("Log: %s: %s", $log->ccrRegistroEnvioResult->Cod_Respuesta, $log->ccrRegistroEnvioResult->Mensaje_Respuesta ) );

            $response = sprintf("%s: %s", $log->ccrRegistroEnvioResult->Cod_Respuesta, $log->ccrRegistroEnvioResult->Mensaje_Respuesta );
            update_post_meta($order_id, 'pdevs_ccr_shipping_log_response', $response);

            return $response;
        }

        /**
         * Call queries to Web Service.
         *
         * @param $args
         * @param $method
         * @return int|mixed
         */
        public function ccr_ws_client_query_call( $args, $method ) {

            $arguments = array_merge(
                $credentials = [
                    'User' => $this->ccr_ws_settings_options['ccr_username'],
                    'Pass' => $this->ccr_ws_settings_options['ccr_password']
                ] ,
                $args
            );

            try {

                $soapClientOptions = [
                    'trace' => 1,
                    'uri' => 'urn:webservices'
                ];

                $url_wsdl = isset( $this->ccr_ws_settings_options['ccr_enable_proxy_connection'] )
                    ? 'http://' . urlencode( trim( $this->ccr_ws_settings_options['ccr_username_proxy_connection'] ) ) . ':' . urlencode( trim( $this->ccr_ws_settings_options['ccr_password_proxy_connection'] ) ) . '@' . trim( $this->ccr_ws_settings_options['ccr_ip_proxy_connection'] ) . ':' . trim( $this->ccr_ws_settings_options['ccr_port_proxy_connection'] )
                    : trim( $this->ccr_ws_settings_options['ccr_url_ws'] );


                if ( isset( $this->ccr_ws_settings_options['ccr_enable_proxy_connection'] ) ) {
                    $client = new SoapClient($url_wsdl, array('trace' => 1, 'location' => 'http://' . trim( $this->ccr_ws_settings_options['ccr_ip_proxy_connection'] ) . ':' . trim( $this->ccr_ws_settings_options['ccr_port_proxy_connection'] ) , 'uri' => 'urn:webservices', 'login' => urlencode( trim( $this->ccr_ws_settings_options['ccr_username_proxy_connection'] ) ), 'password' => urlencode( trim( $this->ccr_ws_settings_options['ccr_password_proxy_connection'] ) ) ) );
                } else {
                    $client = new SoapClient( $url_wsdl, $soapClientOptions );
                }

                $result = $client->__soapCall( $method, ['parameters' => $arguments] );
            } catch( Exception $exception ) {
                $result = -1;
                echo 'Something happened: ' . $exception->getMessage() . "\n";
                $this->ccr_ws_client_log_message(sprintf("Error in service query: %s", $exception->getMessage()));
            }

            return $result;
        }

        /**
         * Log messages for web service query call.
         *
         * @param $message
         */
        public function ccr_ws_client_log_message( $message ) {
            $logger = new WC_Logger();
            $name = 'PDEVS_CCR_Web_Service_Client_Log';
            $logger->add($name, $message);
        }
    }
endif; // Class_exists check.