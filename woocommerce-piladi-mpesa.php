<?php

/**
 * Created by IntelliJ IDEA.
 * User: urandu
 * Date: 3/24/18
 * Time: 12:26 AM
 */
class cwoa_WCPiladiMpesa extends WC_Payment_Gateway
{

    function __construct()
    {

        // global ID
        $this->id = "cwoa_wc_piladi_mpesa_gateway";

        // Show Title
        $this->method_title = __("Piladi M-pesa", 'wc_piladi_mpesa_gateway');

        // Show Description
        $this->method_description = __("M-pesa Payment Gateway Plug-in for WooCommerce", 'wc_piladi_mpesa_gateway');

        // vertical tab title
        $this->title = __("Piladi M-pesa", 'wc_piladi_mpesa_gateway');


        $this->icon = null;
        $this->transaction = $this->get_option('transaction');

        $this->has_fields = true;

        // support default form with credit card
        //$this->supports = array( 'default_credit_card_form' );

        /**
         * Add the field to the checkout page
         */
        add_action('woocommerce_after_order_notes', 'customise_checkout_field');


        // setting defines
        $this->init_form_fields();

        // load time variable setting
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // further check of SSL if you want
        ///add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );

        // Save settings
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }




    } // Here is the  End __construct()



    // administration fields for specific Gateway
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'wc_piladi_mpesa_gateway'),
                'label' => __('Enable this payment gateway', 'wc_piladi_mpesa_gateway'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'wc_piladi_mpesa_gateway'),
                'type' => 'text',
                'desc_tip' => __('Payment title of checkout process.', 'wc_piladi_mpesa_gateway'),
                'default' => __('M-pesa', 'wc_piladi_mpesa_gateway'),
            ),
            'description' => array(
                'title' => __('Description', 'wc_piladi_mpesa_gateway'),
                'type' => 'textarea',
                'desc_tip' => __('Payment title of checkout process.', 'wc_piladi_mpesa_gateway'),
                'default' => __('Pay via mpesa', 'wc_piladi_mpesa_gateway'),
                'css' => 'max-width:450px;'
            ),
            'consumer_key' => array(
                'title' => __('Consumer key', 'wc_piladi_mpesa_gateway'),
                'type' => 'text',
                'desc_tip' => __('This is the consumer key of the c2b app from daraja dev portal.', 'wc_piladi_mpesa_gateway'),
            ),
            'consumer_secret' => array(
                'title' => __('Consumer secret', 'wc_piladi_mpesa_gateway'),
                'type' => 'password',
                'desc_tip' => __('This is the consumer secret of the c2b app from daraja dev portal.', 'wc_piladi_mpesa_gateway'),
            ),
            'environment' => array(
                'title' => __('Sandbox', 'wc_piladi_mpesa_gateway'),
                'label' => __('Enable Test Mode', 'wc_piladi_mpesa_gateway'),
                'type' => 'checkbox',
                'description' => __('This is the test mode of gateway.', 'wc_piladi_mpesa_gateway'),
                'default' => 'no',
            )
        );
    }


    public function payment_fields(){

        if ( $description = $this->get_description() ) {
            echo wpautop( wptexturize( $description ) );
        }

        ?>
        <div id="custom_input">
            <p class="form-row form-row-wide">
                <label for="transaction" class=""><?php _e('Transaction ID', $this->domain); ?></label>
                <input type="text" class="" name="transaction" id="transaction" placeholder="eg. KMNE4RT5GT" value="">
            </p>
        </div>
        <?php
    }


    public function validate_fields() {
        if ($_POST['transaction']) {
            $transaction_id = $_POST['transaction'];
            if ($transaction_id == "pili")
            {
                global $woocommerce, $post;

                $order = new WC_Order($post->ID);


                $order_id = trim(str_replace('#', '', $order->get_order_number()));
                $success = true;
                update_post_meta( $order_id, 'mpesa_transaction_id', $transaction_id );
            }
            else
            {
                $error_message =  __("T badam quired", 'woothemes');
                wc_add_notice(__('Field error: ', 'woothemes') . $error_message, 'error');
                $success = False;
            }

        } else {
            $error_message =  __("The transaction ID field is required", 'woothemes');
            wc_add_notice(__('Field error: ', 'woothemes') . $error_message, 'error');
            $success = False;
        }
        return $success;
    }




    // Response handled for payment gateway
    public function process_payment($order_id)
    {
        global $woocommerce;

        $customer_order = new WC_Order($order_id);

        // checking for transiction
        $environment = ($this->environment == "yes") ? 'TRUE' : 'FALSE';

        // Decide which URL to post to
        $environment_url = ("FALSE" == $environment)
            ? 'https://secure.authorize.net/gateway/transact.dll'
            : 'https://test.authorize.net/gateway/transact.dll';

        // This is where the fun stuff begins
        $payload = array(
            // Authorize.net Credentials and API Info
            "x_tran_key" => $this->consumer_key,
            "x_login" => $this->consumer_secret,
            "x_version" => "3.1",

            // Order total
            "x_amount" => $customer_order->order_total,

            // Credit Card Information
            "x_card_num" => str_replace(array(' ', '-'), '', $_POST['cwoa_authorizenet_aim-card-number']),
            "x_card_code" => (isset($_POST['cwoa_authorizenet_aim-card-cvc'])) ? $_POST['cwoa_authorizenet_aim-card-cvc'] : '',
            "x_exp_date" => str_replace(array('/', ' '), '', $_POST['cwoa_authorizenet_aim-card-expiry']),

            "x_type" => 'AUTH_CAPTURE',
            "x_invoice_num" => str_replace("#", "", $customer_order->get_order_number()),
            "x_test_request" => $environment,
            "x_delim_char" => '|',
            "x_encap_char" => '',
            "x_delim_data" => "TRUE",
            "x_relay_response" => "FALSE",
            "x_method" => "CC",

            // Billing Information
            "x_first_name" => $customer_order->billing_first_name,
            "x_last_name" => $customer_order->billing_last_name,
            "x_address" => $customer_order->billing_address_1,
            "x_city" => $customer_order->billing_city,
            "x_state" => $customer_order->billing_state,
            "x_zip" => $customer_order->billing_postcode,
            "x_country" => $customer_order->billing_country,
            "x_phone" => $customer_order->billing_phone,
            "x_email" => $customer_order->billing_email,

            // Shipping Information
            "x_ship_to_first_name" => $customer_order->shipping_first_name,
            "x_ship_to_last_name" => $customer_order->shipping_last_name,
            "x_ship_to_company" => $customer_order->shipping_company,
            "x_ship_to_address" => $customer_order->shipping_address_1,
            "x_ship_to_city" => $customer_order->shipping_city,
            "x_ship_to_country" => $customer_order->shipping_country,
            "x_ship_to_state" => $customer_order->shipping_state,
            "x_ship_to_zip" => $customer_order->shipping_postcode,

            // information customer
            "x_cust_id" => $customer_order->user_id,
            "x_customer_ip" => $_SERVER['REMOTE_ADDR'],

        );

        // Send this payload to Authorize.net for processing
        $response = wp_remote_post($environment_url, array(
            'method' => 'POST',
            'body' => http_build_query($payload),
            'timeout' => 90,
            'sslverify' => false,
        ));

        if (is_wp_error($response))
            throw new Exception(__('There is a connection issue to the payment gateway. Sorry for the inconvenience.', 'wc_piladi_mpesa_gateway'));

        if (empty($response['body']))
            throw new Exception(__('There was no response from mpesa.', 'wc_piladi_mpesa_gateway'));

        // get body response while get not error
        $response_body = wp_remote_retrieve_body($response);

        foreach (preg_split("/\r?\n/", $response_body) as $line) {
            $resp = explode("|", $line);
        }

        ///complete payment for now
        // Payment successful
        $customer_order->add_order_note(__('Payment received, please await system confirmation.', 'wc_piladi_mpesa_gateway'));

        // paid order marked
        //$customer_order->payment_complete();
        $customer_order->update_status('pending', __('Waiting to verify MPESA payment.', 'woocommerce'));
        // Reduce stock levels
        $customer_order->reduce_order_stock();
        // this is important part for empty cart
        $woocommerce->cart->empty_cart();

        // Redirect to thank you page
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($customer_order),
        );
//        // values get
//        $r['response_code']             = $resp[0];
//        $r['response_sub_code']         = $resp[1];
//        $r['response_reason_code']      = $resp[2];
//        $r['response_reason_text']      = $resp[3];
//
//        // 1 or 4 means the transaction was a success
//        if ( ( $r['response_code'] == 1 ) || ( $r['response_code'] == 4 ) ) {
//            // Payment successful
//            $customer_order->add_order_note( __( 'Authorize.net complete payment.', 'wc_piladi_mpesa_gateway' ) );
//
//            // paid order marked
//            $customer_order->payment_complete();
//
//            // this is important part for empty cart
//            $woocommerce->cart->empty_cart();
//
//            // Redirect to thank you page
//            return array(
//                'result'   => 'success',
//                'redirect' => $this->get_return_url( $customer_order ),
//            );
//        } else {
//            //transiction fail
//            wc_add_notice( $r['response_reason_text'], 'error' );
//            $customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
//        }

    }

    // Validate fields
//    public function validate_fields()
//    {
//        return true;
//    }

    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

}