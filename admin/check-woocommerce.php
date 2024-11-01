<?php // skeerel - Admin Check woocommerce


// disable direct file access
if (!defined('ABSPATH')) {

    exit;

}


/**
 *  WooCommerce fallback notice.
 */
function skeerel_missing_wc_notice()
{
    echo '<div class="error"><p><strong>' . esc_html__('Skeerel a besoin que WooCommerce soit installé et activé pour fonctionner.', 'skeerel') . '</strong>' . sprintf(esc_html__(' Vous pouvez télécharger %s ici.', 'skeerel'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</p></div>';
}


/**
 * Check if the api key has being revoked
 * The function uses a transient to check the api key every 15s
 * */
function skeerel_check_if_api_key_still_valid()
{

    require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';

    $skeerel_options = get_option('skeerel', skeerel_options_default());

    // onboarding ?
    if ($skeerel_options['step_id'] != "woocommerce_settings")
        return;

    if (get_transient('skeerel_wc_api_key_not_valid') || get_transient('skeerel_wc_notice') || get_transient('skeerel_wc_notice_domain_name')) {
        return;
    }

    $screen = get_current_screen();

    // CONTINUE ONLY IF WE'RE ON WOOCOMMERCE ADMIN PAGE	(SKEEREL)
    if (!isset($screen->base) || $screen->base != 'woocommerce_page_wc-settings' || !isset($_GET['tab']) || $_GET['tab'] != 'checkout' || !isset($_GET['section']) || $_GET['section'] != 'skeerel') {
        return;
    }

    $skeerel_settings = get_option('woocommerce_skeerel_settings');

    try {
        $skeerel = new \Skeerel\Skeerel(sanitize_text_field($skeerel_settings['skeerel_id']), sanitize_text_field($skeerel_settings['skeerel_secret_key']));
        $data = $skeerel->getWebsiteDetails();

        $skeerel_status = $data->getStatus();

        $site_url = $_SERVER['HTTP_HOST'];
        $site_url = preg_replace("/:[0-9]+/", "", $site_url); // fix: remove port

        $skeerel_domains = $data->getDomains();

        // create the associated regex /(mydomain\.com|www.mydomain\.com|...)/
        $regex = '/(' . str_replace('.', '\.', implode('|', $skeerel_domains)) . ')/';
        $skeerel_domain_OK = (bool)preg_match($regex, $site_url);

        // retrieve current info
        $skeerel_options = get_option('woocommerce_skeerel_settings');

        // depending on the website configuration, display a notice to the user
        if ($skeerel_status == "VERIFIED" && $skeerel_domain_OK) {
            // step 3 : nothing to do
        } else if ($skeerel_status == "PENDING" && $skeerel_domain_OK) {
            // step 3_1
            set_transient('skeerel_wc_notice', esc_html__("Votre site est en cours de vérification pour pouvoir effectuer des paiements live.", 'skeerel'), 15);
        } else if ($skeerel_status == "NOT_VERIFIED" && $skeerel_domain_OK) {
            // step 3_2
            set_transient('skeerel_wc_notice', esc_html__("Pour pouvoir accepter des paiements live, vous devez vous rendre sur votre espace e-commerçant Skeerel et demander la vérification de votre site.", 'skeerel'), 15);
        } else if (($skeerel_status == "PENDING" || $skeerel_status == "NOT_VERIFIED") && !$skeerel_domain_OK) {
            // step 3_3
            set_transient('skeerel_wc_notice_domain_name', __("Le nom de domaine de votre site: [SITE], n'est pas enregistré sur votre espace e-commerçant Skeerel. <a href='https://admin.skeerel.com/websites' target='_blank'>Cliquez ici</a> pour l'ajouter", 'skeerel'), 15);

            // disable skeerel on product and checkout page
            $skeerel_options['display_skeerel_at_checkout'] = 'no';
            $skeerel_options['display_skeerel_on_product_page'] = 'no';
            $skeerel_options['display_skeerel_encart_at_checkout'] = 'no';

            if ($skeerel_status == "PENDING")
                set_transient('skeerel_wc_notice', esc_html__("Votre site est en cours de vérification pour pouvoir effectuer des paiements live.", 'skeerel'), 15);
            else
                set_transient('skeerel_wc_notice', esc_html__("Pour pouvoir accepter des paiements live, vous devez vous rendre sur votre espace e-commerçant Skeerel et demander la vérification de votre site.", 'skeerel'), 15);

        } else if ($skeerel_status == "VERIFIED" && !$skeerel_domain_OK) {
            // step 3_4
            set_transient('skeerel_wc_notice_domain_name', __("Le nom de domaine de votre site: [SITE], n'est pas enregistré sur votre espace e-commerçant Skeerel. <a href='https://admin.skeerel.com/websites' target='_blank'>Cliquez ici</a> pour l'ajouter", 'skeerel'), 15);
        }


        update_option('woocommerce_skeerel_settings', $skeerel_options);
    } catch (Exception $e) {

        // disable the payment option
        $skeerel_options = get_option('woocommerce_skeerel_settings');
        $skeerel_options['enabled'] = 'no';

        update_option('woocommerce_skeerel_settings', $skeerel_options);
        set_transient('skeerel_wc_api_key_not_valid', true, 5);
    }
}

/** Display a warning message if the api key has being revoked  */
function skeerel_warning_api_key_not_valid()
{

    $skeerel_options = get_option('skeerel', skeerel_options_default());

    // check permissions
    if (!current_user_can('manage_woocommerce'))
        return;

    if (!get_transient('skeerel_wc_api_key_not_valid'))
        return;

    ?>
    <div class="notice notice-warning is-dismissible">
        <p><?php echo esc_html__("La clé d'API Skeerel a été révoquée. Pour que Skeerel continue à fonctionner correctement, saisissez la nouvelle clé", 'skeerel') ?>
            <a href="./admin.php?page=wc-settings&tab=checkout&section=skeerel"> <?php echo esc_html__('ici.', 'skeerel') ?>  </a>
        </p>
    </div>
    <?php
}

/** Display a message depending on the status of the website  */
function skeerel_warning_wc_notice()
{

    // check permissions
    if (!current_user_can('manage_woocommerce'))
        return;

    if (!get_transient('skeerel_wc_notice'))
        return;

    $screen = get_current_screen();

    if (!isset($screen->base) || $screen->base != 'woocommerce_page_wc-settings' || !isset($_GET['tab']) || $_GET['tab'] != 'checkout' || !isset($_GET['section']) || $_GET['section'] != 'skeerel') {
        return;
    }


    ?>
    <div class="notice notice-warning is-dismissible">
        <p><?php echo get_transient('skeerel_wc_notice') ?> </p>
    </div>
    <?php
}

/** Display a warning message if the domain name doesn't match  */
function skeerel_warning_domain_name()
{

    // check permissions
    if (!current_user_can('manage_woocommerce'))
        return;

    if (!get_transient('skeerel_wc_notice_domain_name'))
        return;

    $site_url = $_SERVER['HTTP_HOST'];
    $site_url = preg_replace("/:[0-9]+/", "", $site_url); // fix: remove port
    ?>
    <div class="notice notice-error is-dismissible">
        <p><b> <?php echo str_replace('[SITE]', $site_url, get_transient('skeerel_wc_notice_domain_name')) ?></b></p>
    </div>
    <?php
}
