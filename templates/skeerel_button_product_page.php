<?php

if (!isset($skeerel_options)) {
    $skeerel_options = get_option('woocommerce_skeerel_settings');
}

global $product;

wp_enqueue_style('skeerel', plugin_dir_url(dirname(__FILE__)) . 'public/css/custom.css', array(), null);
?>

<div id="skeerel_pay">
    <div class="skeerel-sep-container">
        <div class="skeerel-sep"><hr></div>
        <div class="skeerel-sep skeerel-margin-top-minus-11px"><?php echo strtoupper(esc_html__("Achetez en un clic !", "skeerel")); ?></div>
        <div class="skeerel-sep "><hr></div>
    </div>

    <div class="skeerel-shopping-cart-button">
        <div class="skeerel-button-self" id="skeerel_payment_button"><?php echo $skeerel_options['button_text_on_product_page']; ?></div>
    </div>

    <div>
        <?php
        echo esc_html__("Skeerel vous propose une solution d'achat en ligne sécurisée, simple et rapide.", "skeerel") . "<br />";
        echo esc_html__("Téléchargez l'application Skeerel sur votre smartphone, flashez le QR Code et achetez sur tous les sites marchands partenaires du réseau Skeerel !", "skeerel");
        ?>
        &nbsp;<a href="https://skeerel.com/telecharger/" style="color: #1f96cf;" onclick="javascript:window.open('https://skeerel.com/telecharger/','Skeerel','toolbar=no, location=no,directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1200, height=700'); return false;"><?php echo esc_attr__("En savoir plus", 'skeerel'); ?> </a>
    </div>
    <hr />
</div>

<?php

// load external script
wp_enqueue_script('skeerel-api-js', "https://api.skeerel.com/assets/v2/javascript/api.min.js", array(), true, false);

$bool = array('0' => "false", '1' => "true");
$js_addition = "

    (function ($) {
        var skeerelCheckout;
        var previous_custom = '';
    
        $('#skeerel_payment_button').on('click', function() {
    
            var custom = '';
            var price = 0;
            var product_price;
    
            // variable product
            if( $('.product:first').hasClass('product-type-variable') && !$('.product:first').hasClass('product-type-simple') ) {
                var product_qty = $('.qty').val() || 1;
                var product_id = $('input[name=\"product_id\"]').val();
                var variation_id = $('input[name=\"variation_id\"]').val() || 0;
    
                try {
                    if( $('.woocommerce-variation-price .amount:visible').length == 1 )
                        product_price = $('.woocommerce-variation-price .amount:visible').html().match(/\d+/);
                    else
                        product_price = $('ins .amount:visible').html().match(/\d+/);
                } catch( e ) {
                    product_price = 0;
                }
    
                price += product_price * product_qty;
                custom = product_id+':'+product_qty+':'+variation_id;
            } else if ( $('.product').hasClass('woocommerce-grouped-product-list-item') ) {
                $('form.cart tr').each(function() {
                    var product_qty = $(this).find('.qty').val() || 1;
                    var product_id = $(this).attr('id').match(/\d+/);
                    var variation_id = 0;
                            
                    if( $(this).find('.amount').length == 1 )
                        product_price = $(this).find('.amount').html().match(/\d+/);
                    else
                        product_price = $(this).find('ins .amount').html().match(/\d+/);
    
                    price += product_price * product_qty;			
                    custom += product_id+':'+product_qty+':'+variation_id+',';
                });
    
                custom = custom.slice(0,-1);
            } else {
                var product_qty = $('.qty').val() || 1;
                var product_id = $('.product').attr('id').match(/\d+/);
                var variation_id = 0;
                var product_price = " . $product->get_price() . "
                price += product_price * product_qty;
   
                custom = product_id+':'+product_qty+':'+variation_id;
            }
    
            if( price <= 0 ) {
                alert( \"" . esc_html__("Veuillez sélectionner certaines options du produit avant de l’ajouter à votre panier.", 'skeerel') . "\");
                return;
            }
    
            if(previous_custom != custom ) {
    
                if( typeof skeerelCheckout !== 'undefined' ) {
                    skeerelCheckout.cancel();
                }
                    
    
                skeerelCheckout = new SkeerelCheckout(
                    \"" . $skeerel_options['skeerel_id'] . "\", // Website id
                    \"" . \Skeerel\Util\Session::get(\Skeerel\Skeerel::DEFAULT_COOKIE_NAME) . "\", // state parameter
                    \"" . wc_get_checkout_url() . "?product_checkout\", // redirect url
                    " . $bool [$product->needs_shipping()] . ", // needs shipping address?
                    '" . esc_url_raw(rest_url()) . "' + 'skeerel/v1/get-delivery-methods?product_id=' + custom + '&zip_code=__ZIP_CODE__&country=__COUNTRY__', // delivery methods url
                    price*100, // amount to pay
                    'eur', // currency
                    null, // profile id
                    custom, // custom data
                    " . $bool [($skeerel_options['use_test_mode'] == "yes")] . " // is test mode?
                );
    
                // Create and show the iframe
                skeerelCheckout.start();
                previous_custom = custom;
            } else {
                $('#skeerel-api-iframe-div').css('display', 'block');
            }
        });
    })(jQuery);";

wp_add_inline_script('skeerel-api-js', $js_addition);