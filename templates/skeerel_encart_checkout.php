<?php

if (!isset($skeerel_options)) {
    $skeerel_options = get_option('woocommerce_skeerel_settings');
}

wp_enqueue_style('skeerel', plugin_dir_url(dirname(__FILE__)) . 'public/css/custom.css', array(), null);
?>

<div id="skeerel_pay">
    <div class="skeerel-sep-container">
        <div class="skeerel-sep"><hr></div>
        <div class="skeerel-sep skeerel-margin-top-minus-11px"><?php echo strtoupper(esc_html__("Achat express", "skeerel")); ?></div>
        <div class="skeerel-sep "><hr></div>
    </div>

    <div class="skeerel-sep-container">
        <div class="skeerel-sep-flex-only skeerel-sep-width-60">
            <?php
            echo esc_html__("Skeerel vous propose une solution d'achat en ligne sécurisée, simple et rapide.", "skeerel") . "<br />";
            echo esc_html__("Téléchargez l'application Skeerel sur votre smartphone, flashez le QR Code et achetez sur tous les sites marchands partenaires du réseau Skeerel !", "skeerel");
            ?>
            &nbsp;<a href="https://skeerel.com/telecharger/" style="color: #1f96cf;" onclick="javascript:window.open('https://skeerel.com/telecharger/','Skeerel','toolbar=no, location=no,directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1200, height=700'); return false;"><?php echo esc_attr__("En savoir plus", 'skeerel'); ?> </a>
        </div>


        <div class="skeerel-shopping-cart-button skeerel-sep-flex-only skeerel-sep-width-40">
            <div class="skeerel-button-self" id="skeerel-pay-button"><?php echo $skeerel_options['button_text_at_checkout']; ?></div>
        </div>
    </div>

    <?php
    $js_addition = "
      document.getElementById('skeerel-pay-button').onclick = function() {
        // Create and show the iframe
        skeerelCheckout.start();
    };";

    wp_add_inline_script('skeerel-api-js', $js_addition);
    ?>

    <br><br>

    <div class="skeerel-sep-container">
        <div class="skeerel-sep"><hr></div>
        <div class="skeerel-sep skeerel-margin-top-minus-11px"><?php echo strtoupper(esc_html__("ou", "skeerel")); ?></div>
        <div class="skeerel-sep "><hr></div>
    </div>
</div>