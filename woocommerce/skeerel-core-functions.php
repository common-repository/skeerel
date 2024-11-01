<?php // skeerel - Skeerel core functions


// disable direct file access
if (!defined('ABSPATH')) {

    exit;

}

/**
 * Display Skeerel button on the product page
 *
 */
function skeerel_display_button_product_page()
{

    require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';

    $skeerel_options = get_option('woocommerce_skeerel_settings');

    if ($skeerel_options["display_skeerel_on_product_page"] != "yes")
        return;

    // check if skeerel is enabled
    if ($skeerel_options['enabled'] == "no")
        return;

    // check if skeerel payments are only available for administrators
    if ($skeerel_options['admin_only'] == "yes" && !current_user_can('manage_woocommerce'))
        return;

    global $product;

    if ($product->is_type('external'))    // skeerel does not support variable/grouped products yet (product page only)
        return;

    if (is_null(\Skeerel\Util\Session::get(\Skeerel\Skeerel::DEFAULT_COOKIE_NAME)))
        \Skeerel\Skeerel::generateSessionStateParameter();

    require_once plugin_dir_path(dirname(__DIR__)) . 'skeerel/templates/skeerel_button_product_page.php';
}

add_action('woocommerce_after_add_to_cart_form', 'skeerel_display_button_product_page');

/**
 * Process payment on product page
 */
function skeerel_process_product_payment()
{

    require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';

    global $wp;

    if (is_checkout() && isset($_GET['product_checkout']) && isset($_GET['token']) && !empty($_GET['token']) && isset($_GET['state']) && !empty($_GET['state'])) {

        // Verify that the state parameter is the same to avoid XSRF attacks
        if (\Skeerel\Skeerel::verifyAndRemoveSessionStateParameter($_GET['state'])) {

            $skeerel_options = get_option('woocommerce_skeerel_settings');

            $skeerel = new \Skeerel\Skeerel($skeerel_options['skeerel_id'], $skeerel_options['skeerel_secret_key']);

            $data = $skeerel->getData($_GET['token']);
            $custom = $data->getCustom();
            $custom = explode(',', $custom);

            $delivery = $data->getDelivery();

            // save cart
            $current_cart = WC()->cart->get_cart_contents();

            WC()->cart->empty_cart();

            foreach ($custom as $product) {
                $product = explode(':', $product);
                WC()->cart->add_to_cart($product[0], $product[1], $product[2]); // $product_id , $quantity, $variation_id
            }

            if ($delivery != null) {
                WC()->customer->set_shipping_location($delivery->getShippingAddress()->getCountry()->getAlpha2(), '', $delivery->getShippingAddress()->getZipCode());
                WC()->session->set('chosen_shipping_methods', array($data->getDelivery()->getMethodId()));

                set_transient('skeerel_wc_shipping_method_' . session_id(), $data->getDelivery()->getMethodId(), 10);
                add_filter('woocommerce_shipping_chosen_method', function ($method) {
                    $shippingMethod = get_transient('skeerel_wc_shipping_method_' . session_id());
                    delete_transient('skeerel_wc_shipping_method_' . session_id());
                    return $shippingMethod;
                }, 10);
            }

            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();

            $total = WC()->cart->get_totals()['total'];

            // check if the total are equal.
            // check if the total are equal
            // (int)(string) => https://stackoverflow.com/questions/5651026/surprising-float-int-conversion-in-php
            if ((int)(string)($total * 100) == $data->getPayment()->getAmount() &&
                (
                    ($skeerel_options['use_test_mode'] == "no" && $data->getPayment()->isLive()) ||    // check if payment status matches 'use test mode'
                    ($skeerel_options['use_test_mode'] == "yes" && !$data->getPayment()->isLive())
                )
            ) {

                $checkout = WC()->checkout();

                $order_id = $checkout->create_order(array('payment_method' => 'skeerel'));
                $order = wc_get_order($order_id);

                $user = $data->getUser();
                $billing = $data->getPayment()->getBillingAddress();

                if ($delivery != null) {
                    $order->set_address(array(
                        'first_name' => $user->getFirstName(),
                        'last_name' => $user->getLastName(),
                        'company' => ($delivery->getShippingAddress()->isCompany()) ? $delivery->getShippingAddress()->getCompanyName() : '',
                        'email' => $user->getMail(),
                        'phone' => $delivery->getShippingAddress()->getPhone(),
                        'address_1' => $delivery->getShippingAddress()->getAddress(),
                        'address_2' => $delivery->getShippingAddress()->getAddressLine2() . ' ' . $delivery->getShippingAddress()->getAddressLine3(),
                        'city' => $delivery->getShippingAddress()->getCity(),
                        'postcode' => $delivery->getShippingAddress()->getZipCode(),
                        'country' => $delivery->getShippingAddress()->getCountry()->getAlpha2()
                    ), 'shipping');
                }


                $order->set_address(array(
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'company' => ($billing->isCompany()) ? $billing->getCompanyName() : '',
                    'email' => $user->getMail(),
                    'phone' => $billing->getPhone(),
                    'address_1' => $billing->getAddress(),
                    'address_2' => $billing->getAddressLine2() . ' ' . $billing->getAddressLine3(),
                    'city' => $billing->getCity(),
                    'postcode' => $billing->getZipCode(),
                    'country' => $billing->getCountry()->getAlpha2()
                ), 'billing');

                update_post_meta($order_id, '_customer_user', get_current_user_id());
                $order->add_meta_data('_custom_skeerel_payment_id', $data->getPayment()->getId());

                $order->calculate_totals();
                $order->payment_complete();

                // if test mode is active, append [TEST] to every note
                $test_append = ($skeerel_options['use_test_mode'] == 'yes') ? '[TEST] ' : '';

                // if company, add note
                if ($billing->isCompany() && !empty($billing->getVatNumber())) {
                    $order->add_order_note(__("L'entreprise a pour numéro de TVA : ", 'skeerel') . $billing->getVatNumber());
                }

                // check if the payment has been captured or not
                if ($data->getPayment()->isCaptured()) {
                    $order->add_order_note($test_append . __("Commande validée. L'argent a été transféré sur votre compte Skeerel.", 'skeerel'));
                } else {
                    $order->set_status('on-hold', $test_append . __("Ce paiement semble suspect. Si vous jugez qu'il s'agit d'un paiement légitime, cliquez sur Action > Capturer le paiement. ", 'skeerel'));
                }

                $order->save();

                set_transient('skeerel_wc_cart_saved_' . session_id(), $current_cart, 60);
                set_transient('skeerel_wc_order_id_' . session_id(), $order->get_id(), 60);

                // redirect to order-received page
                wp_redirect($order->get_checkout_order_received_url());
                exit;

            } else {
                wp_die(esc_html__('Une erreur est survenue. Merci de bien vouloir réessayer.', 'skeerel'));
            }
        }
    }
}

add_action('wp', 'skeerel_process_product_payment');

/**
 * Process payment at checkout
 */
function skeerel_process_checkout_payment()
{

    require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';

    if (is_checkout() && isset($_GET['checkout']) && isset($_GET['token']) && !empty($_GET['token']) && isset($_GET['state']) && !empty($_GET['state'])) {

        // Verify that the state parameter is the same to avoid XSRF attacks
        if (\Skeerel\Skeerel::verifyAndRemoveSessionStateParameter($_GET['state'])) {

            $skeerel_options = get_option('woocommerce_skeerel_settings');

            $skeerel = new \Skeerel\Skeerel($skeerel_options['skeerel_id'], $skeerel_options['skeerel_secret_key']);

            $data = $skeerel->getData($_GET['token']);
            $custom = $data->getCustom();

            // empty cart
            WC()->cart->empty_cart();
            $custom_parts = explode('|', $custom);

            // promo codes detected ?
            if (count($custom_parts) == 2) {
                $custom_products = $custom_parts[1];

                // apply promo codes
                $promo_codes = explode(',', $custom_parts[0]);
                foreach ($promo_codes as $key => $value) {
                    WC()->cart->apply_coupon($value);

                }

            } else {
                $custom_products = $custom_parts[0];
            }

            $custom_products = explode(',', $custom_products);

            // load the cart
            foreach ($custom_products as $custom) {
                $custom = explode(':', $custom);
                WC()->cart->add_to_cart($custom[0], $custom[1], $custom[2]); // $product_id , $quantity, $variation_id
            }

            if ($data->getDelivery() != null) {
                WC()->customer->set_shipping_location($data->getDelivery()->getShippingAddress()->getCountry()->getAlpha2(), '', $data->getDelivery()->getShippingAddress()->getZipCode());
                WC()->session->set('chosen_shipping_methods', array($data->getDelivery()->getMethodId()));

                set_transient('skeerel_wc_shipping_method_' . session_id(), $data->getDelivery()->getMethodId(), 10);
                add_filter('woocommerce_shipping_chosen_method', function ($method) {
                    $shippingMethod = get_transient('skeerel_wc_shipping_method_' . session_id());
                    delete_transient('skeerel_wc_shipping_method_' . session_id());
                    return $shippingMethod;
                }, 10);
            }

            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();

            $total = WC()->cart->get_totals()['total'];

            // check if the total are equal
            // (int)(string) => https://stackoverflow.com/questions/5651026/surprising-float-int-conversion-in-php
            if ((int)(string)($total * 100) == $data->getPayment()->getAmount() &&
                (
                    ($skeerel_options['use_test_mode'] == "no" && $data->getPayment()->isLive()) ||    // check if payment status matches 'use test mode'
                    ($skeerel_options['use_test_mode'] == "yes" && !$data->getPayment()->isLive())
                )
            ) {
                $checkout = WC()->checkout();

                $order_id = $checkout->create_order(array('payment_method' => 'skeerel'));
                $order = wc_get_order($order_id);

                $user = $data->getUser();
                $billing = $data->getPayment()->getBillingAddress();

                if ($data->getDelivery() != null) {
                    $order->set_address(array(
                        'first_name' => $user->getFirstName(),
                        'last_name' => $user->getLastName(),
                        'email' => $user->getMail(),
                        'phone' => $data->getDelivery()->getShippingAddress()->getPhone(),
                        'address_1' => $data->getDelivery()->getShippingAddress()->getAddress(),
                        'address_2' => $data->getDelivery()->getShippingAddress()->getAddressLine2() . ' ' . $data->getDelivery()->getShippingAddress()->getAddressLine3(),
                        'city' => $data->getDelivery()->getShippingAddress()->getCity(),
                        'postcode' => $data->getDelivery()->getShippingAddress()->getZipCode(),
                        'country' => $data->getDelivery()->getShippingAddress()->getCountry()->getAlpha2()
                    ), 'shipping');
                }

                $order->set_address(array(
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'email' => $user->getMail(),
                    'phone' => $billing->getPhone(),
                    'address_1' => $billing->getAddress(),
                    'address_2' => $billing->getAddressLine2() . ' ' . $billing->getAddressLine3(),
                    'city' => $billing->getCity(),
                    'postcode' => $billing->getZipCode(),
                    'country' => $billing->getCountry()->getAlpha2()
                ), 'billing');

                update_post_meta($order_id, '_customer_user', get_current_user_id());
                $order->add_meta_data('_custom_skeerel_payment_id', $data->getPayment()->getId());

                $order->calculate_totals();
                $order->payment_complete();

                // if test mode is active, append [TEST] to every note
                $test_append = ($skeerel_options['use_test_mode'] == 'yes') ? '[TEST] ' : '';

                // check if the payment has been captured or not
                if ($data->getPayment()->isCaptured()) {
                    $order->add_order_note($test_append . __("Commande validée. L'argent a été transféré sur votre compte Skeerel.", 'skeerel'));
                } else {
                    $order->set_status('on-hold', $test_append . __("Ce paiement semble suspect. Si vous jugez qu'il s'agit d'un paiement légitime, cliquez sur Action > Capturer le paiement. ", 'skeerel'));
                }

                $order->save();

                // redirect to order-received page
                wp_redirect($order->get_checkout_order_received_url());
                exit;

            } else {
                wp_die(esc_html__('Une erreur est survenue. Merci de bien vouloir réessayer.', 'skeerel'));
            }
        }
    }
}

add_action('wp', 'skeerel_process_checkout_payment');

// woocommerce automatically empties the cart when redirecting to the order-received page
// the function restores the woocommerce cart to its previous state; transients expire after 5 minutes
function skeerel_restore_wc_cart()
{
    global $wp;

    if (get_transient('skeerel_wc_cart_saved_' . session_id()) && isset($wp->query_vars['order-received']) && $wp->query_vars['order-received'] == get_transient('skeerel_wc_order_id_' . session_id())) { // only apply to current order

        $current_cart = get_transient('skeerel_wc_cart_saved_' . session_id());

        foreach ($current_cart as $key => $values) {
            WC()->cart->add_to_cart($values['product_id'], $values['quantity'], $values['variation_id'], $values['variation']);
        }

        delete_transient('skeerel_wc_order_id_' . session_id());
        delete_transient('skeerel_wc_cart_saved_' . session_id());
    }
}

add_action('woocommerce_thankyou_skeerel', 'skeerel_restore_wc_cart');

// If the payment is on-hold, add capture payment option
function skeerel_add_order_capture_payment_to_command_list($actions)
{
    global $theorder;

    if ($theorder->get_payment_method() == 'skeerel' && $theorder->get_status() == 'on-hold') {
        $actions['wc_skeerel_capture_payment'] = __('Capturer le paiement', 'skeerel'); // add capture payment option
    }

    return $actions;
}

add_action('woocommerce_order_actions', 'skeerel_add_order_capture_payment_to_command_list');

// If the payment is on-hold, add reject payment option
function skeerel_add_order_reject_payment_to_command_list($actions)
{
    global $theorder;

    if ($theorder->get_payment_method() == 'skeerel' && $theorder->get_status() == 'on-hold') {
        $actions['wc_skeerel_reject_payment'] = __('Rejeter le paiement', 'skeerel'); // add capture payment option
    }

    return $actions;
}

add_action('woocommerce_order_actions', 'skeerel_add_order_reject_payment_to_command_list');


/**
 * Capture user payment
 *
 * @param \WC_Order $order
 */
function skeerel_on_hold_capture_payment($order)
{
    $skeerel_options = get_option('woocommerce_skeerel_settings');
    $skeerel_payment_id = $order->get_meta('_custom_skeerel_payment_id', true);

    try {
        $skeerel = new \Skeerel\Skeerel($skeerel_options['skeerel_id'], $skeerel_options['skeerel_secret_key']);
        $skeerel->capturePayment($skeerel_payment_id);

        $order->set_status('processing', __("Le paiement a été capturé. L'argent est disponible sur votre compte Skeerel. ", 'skeerel'));
    } catch (Exception $e) {
        $order->add_order_note(__("La paiement n'a pas pu être capturé. Si le problème persiste, contactez Skeerel.", 'skeerel'));
    }

}

add_action('woocommerce_order_action_wc_skeerel_capture_payment', 'skeerel_on_hold_capture_payment');

/**
 * Reject user payment
 *
 * @param \WC_Order $order
 */
function skeerel_on_hold_reject_payment($order)
{
    $skeerel_options = get_option('woocommerce_skeerel_settings');
    $skeerel_payment_id = $order->get_meta('_custom_skeerel_payment_id', true);

    try {
        $skeerel = new \Skeerel\Skeerel($skeerel_options['skeerel_id'], $skeerel_options['skeerel_secret_key']);
        $skeerel->rejectPayment($skeerel_payment_id);

        $order->set_status('processing', __("Le paiement a été rejeté. Les fonds ne seront pas transférés vers votre compte Skeerel.", 'skeerel'));
    } catch (Exception $e) {
        $order->add_order_note(__("La paiement n'a pas pu être rejeté. Si le problème persiste, contactez Skeerel.", 'skeerel'));
    }

}

add_action('woocommerce_order_action_wc_skeerel_reject_payment', 'skeerel_on_hold_reject_payment');

function skeerel_display_button_at_checkout()
{

    require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';
    $skeerel_options = get_option('woocommerce_skeerel_settings');

    if ($skeerel_options["display_skeerel_at_checkout"] != "yes" || $skeerel_options["enabled"] == "no")
        return;

    // check if skeerel payments are only available for administrators
    if ($skeerel_options['admin_only'] == "yes" && !current_user_can('manage_woocommerce'))
        return;

    // select skeerel by default

    $total = (WC()->cart->get_subtotal() - WC()->cart->get_discount_total()) * 100;
    $product_ids = "";

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_ids = $product_ids . $cart_item['data']->get_id() . ":" . $cart_item['quantity'] . ":" . $cart_item['variation_id'] . ",";
    }

    if ($product_ids != "")
        $product_ids = substr($product_ids, 0, -1);

    $custom_field = $product_ids;

    // check if a coupon has been applied to this order
    $coupons = WC()->cart->get_coupons();

    if (!empty($coupons)) {
        $list_coupons = "";

        foreach ($coupons as $key => $value) {
            $list_coupons = $list_coupons . $value->get_code() . ",";
        }

        if ($list_coupons != "")
            $list_coupons = substr($list_coupons, 0, -1);

        $custom_field = $list_coupons . "|" . $custom_field;
    }

    $previous_price = get_transient('skeerel_checkout_price');

    if (is_null(\Skeerel\Util\Session::get(\Skeerel\Skeerel::DEFAULT_COOKIE_NAME)))
        \Skeerel\Skeerel::generateSessionStateParameter();

    // load external script
    wp_enqueue_script('skeerel-api-js', "https://api.skeerel.com/assets/v2/javascript/api.min.js", array(), true, false);

    $bool = array('0' => "false", '1' => "true");
    $js_addition = "
        var skeerelCheckout = new SkeerelCheckout(
                \"" . $skeerel_options['skeerel_id'] . "\", // Website id
                \"" . \Skeerel\Util\Session::get(\Skeerel\Skeerel::DEFAULT_COOKIE_NAME) . "\", // state parameter
                \"" . wc_get_checkout_url() . "?checkout\", // redirect url
                " . $bool[WC()->cart->needs_shipping_address()] . ", // needs shipping address?
                '" . esc_url_raw(rest_url()) . "' + 'skeerel/v1/get-delivery-methods?product_id={$product_ids}&zip_code=__ZIP_CODE__&country=__COUNTRY__', // delivery methods url
				{$total}, // amount to pay
                'eur', // currency
                null, // profile id
                \"{$custom_field}\", // custom data
                " . $bool[($skeerel_options['use_test_mode'] == "yes")] . " // is test mode?
        );";
    wp_add_inline_script('skeerel-api-js', $js_addition);
    ?>

    <?php
    if (array_key_exists("display_skeerel_encart_at_checkout", $skeerel_options) && $skeerel_options["display_skeerel_encart_at_checkout"] == "yes") {
        require_once plugin_dir_path(dirname(__DIR__)) . 'skeerel/templates/skeerel_encart_checkout.php';
    }
}

add_action('woocommerce_checkout_before_customer_details', 'skeerel_display_button_at_checkout');

function skeerel_custom_jquery_add_to_checkout()
{
    $skeerel_options = get_option('woocommerce_skeerel_settings');

    if (!is_checkout() || $skeerel_options['enabled'] == 'no' || $skeerel_options["display_skeerel_at_checkout"] != "yes")
        return;

    $total = (WC()->cart->get_subtotal() - WC()->cart->get_discount_total());

    $add_to_js = "
        (function($){
            var defaultTotalText = $('.order-total td').html();

            function bindOnClick() {
                if (!$('#place_order').length) {
                    setTimeout(function() {
                        bindOnClick();
                    }, 200);
                    return;
                }
                
                $('#place_order').unbind('click');
                $('#place_order').unbind('DOMNodeRemoved');
                
                $('#place_order').click(function() {
                    if( $(\"input[name='payment_method']:checked\").val() == 'skeerel' ) {
                        skeerelCheckout.start();
                    }
                    return false;
                });
                
                $('#place_order').bind('DOMNodeRemoved', function(e) {
                    setTimeout(function() {
                        bindOnClick();
                    }, 200);
                });
            }

            function onPaymentMethodSelected() {
                if( $(\"input[name='payment_method']:checked\").val() == 'skeerel') {
                    $('.shipping').hide();
                    $('.order-total td').html( '" . wc_price($total) . "' + \"<span style='font-weight:400'>" . esc_html__(" + frais de ports (calculés dans l'application Skeerel)", 'skeerel') . "</span>\");
                    $('#customer_details input,textarea').prop('disabled', true);
                    $('select').prop('disabled', true);
                } else {
                    $('.shipping').show(); // unmask shipping options
                    $('.order-total td').html(defaultTotalText);
                    $('#customer_details input,textarea').prop('disabled', false);
                    $('select').prop('disabled', false);
                }
                
                bindOnClick();
            }
            
            $(document.body).on('payment_method_selected', function(){
                onPaymentMethodSelected();
            });

            $(document.body).on( 'update_checkout', function(event, custom) {
                if (typeof custom !== 'undefined' && custom.update_shipping_method === false) { // add/delete coupon
                    setTimeout(function() {  
                        location.reload(true); // refresh the page
                    }, 500);
                }
            });
            
            // Initialize
            onPaymentMethodSelected();
        })(jQuery);";

    if (wp_script_is('skeerel-api-js', 'enqueued')) {
        wp_add_inline_script('skeerel-api-js', $add_to_js);
    }
}

add_action('wp_footer', 'skeerel_custom_jquery_add_to_checkout');


