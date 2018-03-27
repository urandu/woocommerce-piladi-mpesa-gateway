<?php
/*
Plugin Name: Piladi - M-pesa WooCommerce Payment Gateway
Plugin URI: http://woocommerce-piladi-mpesa.piladi.com/
Description: WooCommerce m-pesa payment gateway integration.
Version: 1.0.0.1
*/

add_action( 'plugins_loaded', 'cwoa_piladi_mpesa_gateway_init', 0 );
function cwoa_piladi_mpesa_gateway_init() {
    //if condition use to do nothin while WooCommerce is not installed
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
    include_once( 'woocommerce-piladi-mpesa.php' );
    // class add it too WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'cwoa_add_piladi_mpesa_gateway' );
    function cwoa_add_piladi_mpesa_gateway( $methods ) {
        $methods[] = 'cwoa_WCPiladiMpesa';
        return $methods;
    }
}






// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cwoa_piladi_mpesa_action_links' );
function cwoa_piladi_mpesa_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'wc_piladi_mpesa_gateway' ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}
?>