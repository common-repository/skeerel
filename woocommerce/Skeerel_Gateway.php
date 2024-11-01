<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skeerel_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class Skeerel_Gateway extends WC_Payment_Gateway_CC
{

    protected $skeerel_id;
    protected $skeerel_secret_key;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 'skeerel';
        $this->method_title = esc_html__('Skeerel - Achat Express', 'skeerel');
        $this->title = esc_html__('Skeerel - Achat Express', 'skeerel');
        $this->method_description = esc_html__("Skeerel permet à vos clients de payer en 1-clic sans avoir à enregistrer leurs informations de livraison et de paiement.", 'skeerel');
        $this->description = esc_html__("Payez en 1 clic avec Skeerel par carte bancaire, Google Pay ou Apple Pay. Cliquez directement sur le bouton de paiement sans remplir le formulaire.", 'skeerel');
        $this->order_button_text = esc_html__('Payer avec Skeerel', 'skeerel');
        $this->has_fields = false;
        $this->custom_url = 'https://skeerel.com/telecharger/';

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $this->supports = array(
            'products',
            'refunds'
        );

        // check if onboarding has been completed
        $options = get_option('skeerel', skeerel_options_default());

        if (isset($options['step_id']) && !empty($options['step_id'])) {
            $step_id = sanitize_text_field($options['step_id']);

            if ($step_id == '4') { // onboarding completed

                // update woocommerce settings
                $this->update_option('skeerel_id', $options['custom_skeerel_id']);
                $this->update_option('skeerel_secret_key', $options['custom_skeerel_secret_key']);

                $this->update_option('button_pay_at_checkout', $options['custom_button_text_at_checkout']);
                $this->update_option('button_text_on_product_page', $options['custom_button_text_on_product_page']);

                $display_skeerel_at_checkout = ($options['custom_display_skeerel_at_checkout']) ? "yes" : "no";
                $display_skeerel_encart_at_checkout = ($options['custom_display_skeerel_encart_at_checkout']) ? "yes" : "no";
                $display_skeerel_on_product_page = ($options['custom_display_skeerel_at_checkout']) ? "yes" : "no";
                $use_test_mode = ($options['custom_use_test_mode']) ? "yes" : "no";
                $admin_only = ($options['custom_admin_only']) ? "yes" : "no";

                $this->update_option('display_skeerel_at_checkout', $display_skeerel_at_checkout);
                $this->update_option('display_skeerel_encart_at_checkout', $display_skeerel_encart_at_checkout);
                $this->update_option('display_skeerel_on_product_page', $display_skeerel_on_product_page);
                $this->update_option('use_test_mode', $use_test_mode);
                $this->update_option('admin_only', $admin_only);

                $this->update_option('enabled', 'yes');

                // update skeerel settings
                $options['step_id'] = "woocommerce_settings";
                $options['custom_activate_skeerel'] = true;

                update_option('skeerel', $options);

                // refresh
                wp_redirect(admin_url('admin.php?page=wc-settings&tab=checkout'));
                exit;
            }
        }

        $this->skeerel_id = $this->get_option('skeerel_id');
        $this->skeerel_secret_key = $this->get_option('skeerel_secret_key');

        // Register css
        add_action('admin_enqueue_scripts', array($this, 'load_css_and_js'));

        // Hooks.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'custom_process_admin_options'));
        add_action('woocommerce_settings_page_init', array($this, 'redirect_to_onboarding'));

        // Filter
        add_filter('woocommerce_available_payment_gateways', array($this, 'filter_payment_gateways'), 1);
    }


    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = require(dirname(__FILE__) . '/skeerel-settings.php');
    }

    /**
     * Load Javascript and CSS
     */
    public function load_css_and_js($hook)
    {

        if ('woocommerce_page_wc-settings' === $hook && isset($_GET['tab']) && isset($_GET['section'])
            && $_GET['tab'] == "checkout" && $_GET['section'] == "skeerel") {

            wp_enqueue_style('skeerel-settings-fontawesome', plugins_url('skeerel/admin/') . 'fontawesome/css/all.css', array());
            wp_enqueue_style('skeerel-settings-page', plugins_url('skeerel/woocommerce/') . 'css/wc_skeerel.css', array('skeerel-settings-fontawesome'));

            wp_enqueue_script('skeerel-admin-showpassword', plugins_url('skeerel/admin/') . 'js/show-password/bootstrap-show-password.min.js', array(), true, false);
            wp_enqueue_script('skeerel-wc-skeerel', plugins_url('skeerel/woocommerce/') . 'js/wc_skeerel.js', array('skeerel-admin-showpassword'), true, false);

            $add_js = "var loader_url = '" . plugin_dir_url(__DIR__) . "woocommerce/images/loader.svg'";
            wp_add_inline_script('skeerel-wc-skeerel', $add_js, 'before');

        }
    }

    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon()
    {
        $icon_html = '&nbsp;<a href="' . $this->custom_url . '" class="about_skeerel" onclick="javascript:window.open(\'' . $this->custom_url . '\',\'Skeerel\',\'toolbar=no, location=no,directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1200, height=700\'); return false;">' . esc_attr__("Qu'est-ce que Skeerel ?", 'skeerel') . '</a>' . '<img src="' . plugin_dir_url(__DIR__) . 'public/images/skeerel-logo.png">';

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    /**
     * Redirects the user to the Skeerel onboarding page if onboarding not completed
     */
    public function redirect_to_onboarding()
    {
        $screen = get_current_screen();
        $options = get_option('skeerel', skeerel_options_default());

        if ('woocommerce_page_wc-settings' === $screen->base && isset($_GET['tab']) && isset($_GET['section'])
            && $_GET['tab'] == "checkout" && $_GET['section'] == "skeerel" && $options['step_id'] != "woocommerce_settings") {

            wp_redirect(admin_url('admin.php?page=skeerel'));
            exit;

        }
    }

    /**
     * Generate admin options
     * wp-admin/admin.php?page=wc-settings&tab=checkout&section=skeerel
     */

    public function admin_options()
    {

        wp_enqueue_script('wp-api');

        // add a nonce to prevent CSRF attacks
        wp_localize_script('wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ));

        // store the relevant translations, they will be loaded in javascript
        ?>
        <input type="hidden" id="lang-api-placeholder"
               value="<?php echo esc_html__('Identifiant du site internet', 'skeerel'); ?>"/>
        <input type="hidden" id="lang-test-apikey" value="<?php echo esc_html__('Tester la connexion', 'skeerel'); ?>"/>

        <h2><?php echo esc_html__('Skeerel', 'skeerel'); ?> <a href="./admin.php?page=wc-settings&#038;tab=checkout">&#x2934;</a></small>
        </h2>
        <p><?php echo sanitize_text_field($this->method_description) ?></p>
        <table class="form-table">

            <?php
            // We can't overwrite the generate_text_html() function from WC_Settings_API to remove the api key value
            // as this might negatively impact other plugins running on woocommerce, so we will use str_replace instead

            echo str_replace($this->skeerel_secret_key, '', $this->generate_settings_html(array(), false)); ?>
        </table> <?php
    }


    /**
     * Processes and saves options.
     * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
     *
     * @return bool was anything saved?
     */
    public function custom_process_admin_options()
    {
        $this->init_settings();

        $post_data = $this->get_post_data();

        $this->settings['skeerel_id'] = $this->get_field_value('skeerel_id', 'input', $post_data);
        $this->settings['skeerel_secret_key'] = $this->get_field_value('skeerel_secret_key', 'password', $post_data);

        if (!array_key_exists('skeerel_secret_key', $this->settings) || empty($this->settings['skeerel_secret_key'])) // the password wans't changed
            $this->settings['skeerel_secret_key'] = $this->skeerel_secret_key;

        // check if credentials are correct
        try {
            $skeerel = new \Skeerel\Skeerel($this->settings['skeerel_id'], $this->settings['skeerel_secret_key']);
            $data = $skeerel->getWebsiteDetails();
        } catch (Exception $e) {
            // reset secret key & id
            $this->settings['skeerel_secret_key'] = $this->skeerel_secret_key;
            $this->settings['skeerel_id'] = $this->skeerel_id;
            set_transient('disp_skeerel_wc_error', esc_html__("Identifiants incorrects. Vérifiez la validité de votre clé d'API puis réessayez.", 'skeerel'), 5);
        }

        try {
            $skeerel = new \Skeerel\Skeerel($this->settings['skeerel_id'], $this->settings['skeerel_secret_key']);
            $data = $skeerel->getWebsiteDetails();

            $skeerel_status = $data->getStatus();

            // default
            $checkbox_enabled = array(
                'use_test_mode' => true,
                'display_skeerel_at_checkout' => true,
                'display_skeerel_encart_at_checkout' => true,
                'display_skeerel_on_product_page' => true
            );

            $checkbox_custom_values = array(
                'use_test_mode' => null,
                'display_skeerel_at_checkout' => null,
                'display_skeerel_encart_at_checkout' => null,
                'display_skeerel_on_product_page' => null
            );


            $site_url = $_SERVER['HTTP_HOST'];
            $site_url = preg_replace("/:[0-9]+/", "", $site_url); // fix: remove port

            $skeerel_domains = $data->getDomains();

            // create the associated regex /(mydomain\.com|www.mydomain\.com|...)/
            $regex = '/(' . str_replace('.', '\.', implode('|', $skeerel_domains)) . ')/';
            $skeerel_domain_OK = (bool)preg_match($regex, $site_url);

            // depending on the website configuration, some settings will be disabled
            if ($skeerel_status == "VERIFIED" && $skeerel_domain_OK) {
                // step 3 : nothing to do
            } else if ($skeerel_status == "PENDING" && $skeerel_domain_OK) {
                // step 3_1
                $checkbox_enabled['use_test_mode'] = false;
                $checkbox_custom_values['use_test_mode'] = true;
            } else if ($skeerel_status == "NOT_VERIFIED" && $skeerel_domain_OK) {
                // step 3_2
                $checkbox_enabled['use_test_mode'] = false;
                $checkbox_custom_values['use_test_mode'] = true;
            } else if (($skeerel_status == "PENDING" || $skeerel_status == "NOT_VERIFIED") && !$skeerel_domain_OK) {
                // step 3_3
                $checkbox_enabled['use_test_mode'] = false;
                $checkbox_enabled['display_skeerel_at_checkout'] = false;
                $checkbox_enabled['display_skeerel_encart_at_checkout'] = false;
                $checkbox_enabled['display_skeerel_on_product_page'] = false;

                $checkbox_custom_values['use_test_mode'] = true;
                $checkbox_custom_values['display_skeerel_at_checkout'] = false;
                $checkbox_custom_values['display_skeerel_encart_at_checkout'] = false;
                $checkbox_custom_values['display_skeerel_on_product_page'] = false;
            } else if ($skeerel_status == "VERIFIED" && !$skeerel_domain_OK) {
                // step 3_4
                $checkbox_enabled['display_skeerel_at_checkout'] = false;
                $checkbox_enabled['display_skeerel_encart_at_checkout'] = false;
                $checkbox_enabled['display_skeerel_on_product_page'] = false;

                $checkbox_custom_values['display_skeerel_at_checkout'] = false;
                $checkbox_custom_values['display_skeerel_encart_at_checkout'] = false;
                $checkbox_custom_values['display_skeerel_on_product_page'] = false;
            }

            foreach ($this->get_form_fields() as $key => $field) {
                if ('title' !== $this->get_field_type($field)) {
                    try {
                        $value = $this->get_field_value($key, $field, $post_data);

                        // skip skeerel secret key & skeerel id verification
                        if ($key == 'skeerel_secret_key' || $key == 'skeerel_id')
                            continue;

                        // checkbox disabled ?
                        if (array_key_exists($key, $checkbox_enabled) && !$checkbox_enabled[$key] &&
                            is_null($checkbox_custom_values[$key])) // the checkbox value doesn't change
                            continue;

                        if (array_key_exists($key, $checkbox_custom_values) && !is_null($checkbox_custom_values[$key]))
                            $this->settings[$key] = ($checkbox_custom_values[$key]) ? "yes" : "no";
                        else
                            $this->settings[$key] = $value;

                    } catch (Exception $e) {
                        $this->add_error($e->getMessage());
                    }
                }
            }

        } catch (Exception $e) {
            $this->settings['skeerel_id'] = $this->get_field_value('skeerel_id', 'input', $post_data);
            $this->settings['skeerel_secret_key'] = $this->get_field_value('skeerel_secret_key', 'password', $post_data);
        }

        // settings have changed, delete transients

        add_action('admin_notices', array($this, 'display_error_message'));
        return update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings), 'yes');
    }

    /**
     * Display error notice
     */
    public function display_error_message()
    {
        if (!empty(get_transient('disp_skeerel_wc_error')))
            echo '<div class="notice notice-error is-dismissible">
                   <p>' . get_transient('disp_skeerel_wc_error') . '</p>
                   </div>';
    }

    /**
     * Refund customer's order
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        $skeerel_payment_id = $order->get_meta('_custom_skeerel_payment_id', true);
        $refunded = false;

        if (($order->get_status() == 'processing' || $order->get_status() == 'completed') && !is_null($amount)) {
            try {
                $skeerel = new \Skeerel\Skeerel($this->skeerel_id, $this->skeerel_secret_key);
                $skeerel->refundPayment($skeerel_payment_id, intval($amount * 100));
                $refunded = true;
            } catch (Exception $e) {
                return new WP_Error("refund-error", __('Une erreur est survenue lors du remboursement.') . '(' . $e->getMessage() . ')');
            }
        } else
            return new WP_Error("refund-error", __('Une commande doit être marquée comme en cours/terminée pour pouvoir procéder au remboursement.', 'skeerel'));

        if ($refunded) {
            $order->add_order_note(sprintf(__("Client remboursé de %d€.", 'skeerel') . $reason, $amount));
        }

        return $refunded;
    }

    /**
     * Disable Skeerel payments when necessary
     */
    public function filter_payment_gateways($gateways)
    {
        if ($this->get_option('display_skeerel_at_checkout') == "no"
            || ($this->get_option('admin_only') == "yes" && !current_user_can('manage_woocommerce')))

            unset($gateways['skeerel']);

        return $gateways;
    }

    /**
     * Process payment ?
     */
    public function process_payment($order_id)
    {
        return false;
    }

    /**
     * Only display description
     */
    public function payment_fields()
    {
        $description = $this->get_description();
        if ($description) {
            echo wpautop(wptexturize($description)); // show description
        }
    }

}
