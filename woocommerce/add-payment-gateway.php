<?php // skeerel - Add payment gateway


// disable direct file access
if (!defined('ABSPATH')) {

    exit;

}

/**
 * Add the payment gateway to WooCommerce.
 *
 */
function skeerel_add_gateways($methods)
{

    $methods[] = 'Skeerel_Gateway';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'skeerel_add_gateways');

